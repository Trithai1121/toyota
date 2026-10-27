<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Variant;
use App\Services\OnRoadFeeSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalculatorController extends Controller
{
    private const REGIONS = [
        'kon-tum' => 'Kon Tum',
        'gia-lai' => 'Gia Lai',
        'quang-ngai' => 'Quảng Ngãi',
    ];

    public function installment(Request $request): View
    {
        $input = $request->validate([
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'down_payment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'term_months' => ['nullable', 'integer', 'min:1', 'max:360'],
        ]);

        $variants = $this->publicVariants();
        $variant = isset($input['variant_id'])
            ? (clone $variants)->firstWhere('id', $input['variant_id'])
            : null;

        if (isset($input['variant_id']) && ! $variant) {
            abort(404);
        }

        $rates = $this->loanRates();
        $price = $variant?->list_price;
        $downPaymentPercent = isset($input['down_payment_percent']) ? (float) $input['down_payment_percent'] : null;
        $termMonths = isset($input['term_months']) ? (int) $input['term_months'] : null;
        $calculation = null;

        if ($termMonths !== null && ! array_key_exists($termMonths, $rates)) {
            $request->session()->flash('calculator_error', 'Kỳ hạn này chưa được đại lý cấu hình lãi suất.');
        } elseif ($price !== null && $downPaymentPercent !== null && $termMonths !== null && isset($rates[$termMonths])) {
            $principal = (float) $price * (1 - $downPaymentPercent / 100);
            $monthlyRate = $rates[$termMonths] / 1200;
            $monthlyPayment = $monthlyRate === 0.0
                ? $principal / $termMonths
                : $principal * $monthlyRate / (1 - (1 + $monthlyRate) ** (-$termMonths));

            $calculation = [
                'price' => (float) $price,
                'down_payment' => (float) $price * $downPaymentPercent / 100,
                'principal' => $principal,
                'annual_rate' => $rates[$termMonths],
                'term_months' => $termMonths,
                'monthly_payment' => $monthlyPayment,
                'total_payment' => $monthlyPayment * $termMonths,
                'total_interest' => ($monthlyPayment * $termMonths) - $principal,
            ];
        }

        return view('calculators.installment', [
            'variants' => $variants,
            'selectedVariant' => $variant,
            'input' => $input,
            'rates' => $rates,
            'calculation' => $calculation,
        ]);
    }

    public function onRoad(Request $request, OnRoadFeeSettings $feeSettings): View
    {
        $input = $request->validate([
            'region' => ['nullable', 'in:'.implode(',', array_keys(self::REGIONS))],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:1', 'max:10000000000'],
        ]);

        $variants = $this->publicVariants();
        $variant = isset($input['variant_id'])
            ? (clone $variants)->firstWhere('id', $input['variant_id'])
            : null;

        if (isset($input['variant_id']) && ! $variant) {
            abort(404);
        }

        $region = $input['region'] ?? null;
        $price = $input['price'] ?? $variant?->list_price;
        $fees = $region ? $feeSettings->forRegion($region, $variant?->id) : null;
        $calculation = null;

        if ($region && $price !== null && $fees !== null) {
            $registration = round((float) $price * $fees['registration_rate_percent'] / 100)
                + $fees['registration_fee'];
            $bodyInsurance = round((float) $price * $fees['body_insurance_rate_percent'] / 100);
            $totalFees = $registration + $bodyInsurance + $fees['license_plate'] + $fees['inspection']
                + $fees['road_use'] + $fees['compulsory_insurance'] + $fees['service_fee'] + $fees['other'];

            $calculation = [
                'price' => (float) $price,
                'registration' => $registration,
                'registration_rate_percent' => $fees['registration_rate_percent'],
                'body_insurance' => $bodyInsurance,
                'body_insurance_configured' => $fees['body_insurance_configured'] ?? true,
                'license_plate' => $fees['license_plate'],
                'inspection' => $fees['inspection'],
                'road_use' => $fees['road_use'],
                'compulsory_insurance' => $fees['compulsory_insurance'],
                'service_fee' => $fees['service_fee'],
                'other' => $fees['other'],
                'total_fees' => $totalFees,
                'total_on_road' => (float) $price + $totalFees,
                'source_note' => $fees['source_note'] ?? null,
                'source_url' => $fees['source_url'] ?? null,
                'verified_at' => $fees['verified_at'] ?? null,
            ];
        }

        return view('calculators.on-road', [
            'regions' => self::REGIONS,
            'variants' => $variants,
            'selectedVariant' => $variant,
            'input' => $input,
            'feesConfigured' => $region ? $fees !== null : false,
            'calculation' => $calculation,
        ]);
    }

    private function publicVariants()
    {
        return Variant::query()
            ->where('is_published', true)
            ->whereHas('vehicle', fn ($query) => $query
                ->where('is_published', true)
                ->where(fn ($vehicleQuery) => $vehicleQuery->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->whereHas('line', fn ($lineQuery) => $lineQuery->where('is_published', true)))
            ->with('vehicle:id,name,slug')
            ->orderBy('vehicle_id')
            ->orderBy('sort_order')
            ->get(['id', 'vehicle_id', 'name', 'list_price', 'source_url', 'verified_at'])
            ->filter(fn (Variant $variant): bool => $variant->list_price !== null && $variant->hasVerifiedSource())
            ->values();
    }

    private function loanRates(): array
    {
        $configured = Setting::query()->where('key', 'loan_rates')->first()?->value ?? [];
        $rates = [];

        foreach ($configured as $term => $rate) {
            if (
                ! ctype_digit((string) $term)
                || (int) $term < 1
                || (int) $term > 360
                || ! is_numeric($rate)
                || (float) $rate < 0
                || (float) $rate > 100
            ) {
                Log::warning('Ignoring invalid loan rate setting.', ['setting' => 'loan_rates']);

                continue;
            }

            $rates[(int) $term] = (float) $rate;
        }

        ksort($rates);

        return $rates;
    }
}
