<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SendMetaLeadConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $leadId) {}

    public function handle(): void
    {
        $lead = Lead::query()->findOrFail($this->leadId);

        if (! $lead->marketing_consent) {
            return;
        }

        $pixelId = config('tracking.meta.pixel_id');
        $accessToken = config('tracking.meta.capi_access_token');
        $apiVersion = config('tracking.meta.graph_api_version');

        if (! filled($pixelId) || ! filled($accessToken) || ! preg_match('/^v[0-9]+\.[0-9]+$/', (string) $apiVersion)) {
            throw new RuntimeException('Meta Conversions API configuration is incomplete.');
        }

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->post("https://graph.facebook.com/{$apiVersion}/{$pixelId}/events", [
                    'access_token' => $accessToken,
                    'data' => [[
                        'event_name' => 'Lead',
                        'event_time' => $lead->created_at->timestamp,
                        'event_id' => $lead->event_id,
                        'action_source' => 'website',
                        'event_source_url' => route('leads.thank-you'),
                        'user_data' => [
                            'ph' => [hash('sha256', $lead->phone)],
                        ],
                    ]],
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Meta Conversions API could not connect.');
        }

        if (! $response->successful() || data_get($response->json(), 'events_received') !== 1) {
            throw new RuntimeException('Meta Conversions API rejected the lead event with HTTP '.$response->status().'.');
        }
    }
}
