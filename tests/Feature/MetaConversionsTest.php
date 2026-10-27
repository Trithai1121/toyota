<?php

namespace Tests\Feature;

use App\Jobs\SendMetaLeadConversion;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaConversionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_consented_lead_is_sent_with_hashed_phone_and_event_id(): void
    {
        config([
            'tracking.meta.pixel_id' => '123456789',
            'tracking.meta.capi_access_token' => 'test-secret-token',
            'tracking.meta.graph_api_version' => 'v99.0',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);
        $lead = Lead::create([
            'request_type' => 'quote',
            'full_name' => 'Test Customer',
            'phone' => '0901234567',
            'marketing_consent' => true,
            'event_id' => (string) str()->uuid(),
        ]);

        (new SendMetaLeadConversion($lead->id))->handle();

        Http::assertSent(function (Request $request) use ($lead): bool {
            $event = data_get($request->data(), 'data.0');

            return $request->url() === 'https://graph.facebook.com/v99.0/123456789/events'
                && data_get($event, 'event_name') === 'Lead'
                && data_get($event, 'event_id') === $lead->event_id
                && data_get($event, 'event_source_url') === route('leads.thank-you')
                && data_get($event, 'user_data.ph.0') === hash('sha256', $lead->phone)
                && ! str_contains($request->url(), 'test-secret-token');
        });
    }

    public function test_lead_without_marketing_consent_is_not_sent(): void
    {
        Http::fake();
        $lead = Lead::create([
            'request_type' => 'quote',
            'full_name' => 'Test Customer',
            'phone' => '0901234567',
            'marketing_consent' => false,
            'event_id' => (string) str()->uuid(),
        ]);

        (new SendMetaLeadConversion($lead->id))->handle();

        Http::assertNothingSent();
    }
}
