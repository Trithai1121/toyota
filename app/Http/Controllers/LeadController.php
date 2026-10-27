<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Jobs\SendLeadNotifications;
use App\Jobs\SendMetaLeadConversion;
use App\Models\CampaignLandingPage;
use App\Models\Lead;
use App\Models\Variant;
use App\Models\Vehicle;
use App\Services\SiteTheme;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class LeadController extends Controller
{
    public function create(Request $request): View
    {
        $request->validate([
            'type' => ['nullable', Rule::in(['quote', 'test_drive'])],
            'vehicle' => ['nullable', 'string', 'max:255'],
            'variant' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'campaign' => ['nullable', 'integer', 'min:1'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'submission_token' => ['nullable', 'uuid'],
        ]);

        $vehicles = Vehicle::query()
            ->where('is_published', true)
            ->whereHas('line', fn ($query) => $query->where('is_published', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedVehicle = null;
        if ($request->filled('vehicle')) {
            $selectedVehicle = Vehicle::query()
                ->where('slug', (string) $request->string('vehicle'))
                ->where('is_published', true)
                ->whereHas('line', fn ($query) => $query->where('is_published', true))
                ->firstOrFail(['id', 'name']);
        }

        $selectedVariant = null;
        $selectedColor = null;
        $selectedPrice = null;

        if ($selectedVehicle && $request->filled('variant')) {
            $selectedVariant = Variant::query()
                ->where('vehicle_id', $selectedVehicle->id)
                ->where('slug', (string) $request->query('variant'))
                ->where('is_published', true)
                ->with(['colors' => fn ($query) => $query->where('is_active', true)])
                ->first();

            if ($selectedVariant?->hasVerifiedSource()) {
                $selectedColor = $selectedVariant->colors->firstWhere('slug', (string) $request->query('color'))
                    ?? $selectedVariant->colors->first();
                $selectedPrice = $selectedVariant->totalPriceWithColor($selectedColor);
            }
        }

        $campaign = null;
        if ($request->filled('campaign')) {
            $campaign = CampaignLandingPage::query()
                ->whereKey($request->integer('campaign'))
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->firstOrFail(['id', 'campaign_name']);
        }

        return view('leads.create', [
            'vehicles' => $vehicles,
            'selectedVehicle' => $selectedVehicle,
            'selectedVariant' => $selectedVariant,
            'selectedColor' => $selectedColor,
            'selectedPrice' => $selectedPrice,
            'campaign' => $campaign,
            'requestType' => $request->query('type', 'quote'),
            'regions' => config('lead.regions'),
            'utm' => $request->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term']),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (isset($validated['vehicle_id'])) {
            $vehicleIsPublic = Vehicle::query()
                ->whereKey($validated['vehicle_id'])
                ->whereHas('line', fn ($query) => $query->where('is_published', true))
                ->exists();

            if (! $vehicleIsPublic) {
                return back()
                    ->withErrors(['vehicle_id' => 'Vui lòng chọn mẫu xe đang được hiển thị.'])
                    ->withInput($request->except('website'));
            }
        }

        $selectedVariant = null;
        $selectedColor = null;
        $selectedPrice = null;
        if (isset($validated['variant_id']) || isset($validated['vehicle_color_id'])) {
            $selectedVariant = isset($validated['variant_id'])
                ? Variant::query()->whereKey($validated['variant_id'])->where('is_published', true)->with('colors')->first()
                : null;
            $selectedColor = $selectedVariant?->colors->firstWhere('id', $validated['vehicle_color_id'] ?? null);
            $validSelection = $selectedVariant !== null
                && $selectedColor !== null
                && $selectedColor->is_active
                && (int) $selectedVariant->vehicle_id === (int) ($validated['vehicle_id'] ?? 0)
                && $selectedVariant->hasVerifiedSource();

            if (! $validSelection) {
                return back()
                    ->withErrors(['variant_id' => 'Phiên bản hoặc màu xe đã chọn không hợp lệ. Vui lòng chọn lại từ trang chi tiết xe.'])
                    ->withInput($request->except('website'));
            }

            $selectedPrice = $selectedVariant->totalPriceWithColor($selectedColor);
        }

        $campaign = null;
        if (isset($validated['campaign_landing_page_id'])) {
            $campaign = CampaignLandingPage::query()
                ->whereKey($validated['campaign_landing_page_id'])
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->first();

            if (! $campaign) {
                return back()
                    ->withErrors(['form' => 'Chiến dịch này không còn nhận yêu cầu.'])
                    ->withInput($request->except('website'));
            }
        }

        $cookieConsent = $request->session()->get('cookie_consent');
        $marketingConsent = is_array($cookieConsent)
            && ($cookieConsent['version'] ?? null) === 1
            && ($cookieConsent['marketing'] ?? false) === true
            && (bool) ($validated['marketing_consent'] ?? false);

        $token = $validated['submission_token'] ?? null;
        if ($token && ($existingLead = Lead::query()->where('submission_token', $token)->first())) {
            return $this->successfulLeadRedirect($request, $existingLead);
        }

        try {
            $lead = DB::transaction(fn (): Lead => Lead::create([
                'request_type' => $validated['request_type'],
                'full_name' => trim($validated['full_name']),
                'phone' => $validated['phone'],
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'variant_id' => $selectedVariant?->id,
                'vehicle_color_id' => $selectedColor?->id,
                'selected_price' => $selectedPrice,
                'desired_date' => $validated['desired_date'] ?? null,
                'region' => config('lead.regions')[$validated['region']],
                'customer_note' => $validated['customer_note'] ?? null,
                'status' => 'new',
                'consent_given' => true,
                'consent_version' => '1.0',
                'consented_at' => now(),
                'marketing_consent' => $marketingConsent,
                'marketing_consent_at' => $marketingConsent ? ($cookieConsent['consented_at'] ?? now()) : null,
                'source' => $campaign ? 'campaign' : ($validated['source'] ?? 'website'),
                'campaign_landing_page_id' => $campaign?->id,
                'utm_source' => $validated['utm_source'] ?? null,
                'utm_medium' => $validated['utm_medium'] ?? null,
                'utm_campaign' => $validated['utm_campaign'] ?? null,
                'utm_content' => $validated['utm_content'] ?? null,
                'utm_term' => $validated['utm_term'] ?? null,
                'event_id' => (string) str()->uuid(),
                'submission_token' => $token,
            ]));
        } catch (QueryException $exception) {
            $duplicate = $token ? Lead::query()->where('submission_token', $token)->first() : null;
            if ($duplicate) {
                return $this->successfulLeadRedirect($request, $duplicate);
            }
            throw $exception;
        }

        try {
            SendLeadNotifications::dispatch($lead->id);
        } catch (Throwable $exception) {
            report($exception);
        }

        if (
            $lead->marketing_consent
            && filled(config('tracking.meta.pixel_id'))
            && filled(config('tracking.meta.capi_access_token'))
            && filled(config('tracking.meta.graph_api_version'))
        ) {
            try {
                SendMetaLeadConversion::dispatch($lead->id);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $this->successfulLeadRedirect($request, $lead);
    }

    private function successfulLeadRedirect(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->source === 'popup-zalo') {
            $theme = app(SiteTheme::class)->all();
            $zaloUrl = data_get($theme, 'dealer.zalo', '');
            $message = "Tôi muốn nhận báo giá xe {$lead->vehicle?->name}";
            if ($lead->variant) {
                $message .= " phiên bản {$lead->variant->name}";
            }
            if ($lead->vehicleColor) {
                $message .= " màu {$lead->vehicleColor->name}";
            }
            $request->session()->flash('popup_quote_success', [
                'zalo_url' => filter_var($zaloUrl, FILTER_VALIDATE_URL) && str_starts_with($zaloUrl, 'https://') ? $zaloUrl.'?text='.rawurlencode($message) : null,
            ]);

            return redirect()->back()->with('lead_event_id', $lead->event_id);
        }

        return redirect()->route('leads.thank-you')->with('lead_event_id', $lead->event_id);
    }

    public function thankYou(): View
    {
        return view('leads.thank-you');
    }
}
