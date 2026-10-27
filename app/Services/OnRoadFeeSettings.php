<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\VehicleOnRoadFee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OnRoadFeeSettings
{
    private const REGIONS = ['kon-tum', 'gia-lai', 'quang-ngai'];

    private const FEE_KEYS = [
        'registration_rate_percent',
        'registration_fixed',
        'license_plate',
        'road_use',
        'compulsory_insurance',
        'other',
    ];

    public function forRegion(string $region, ?int $variantId = null): ?array
    {
        if (! in_array($region, self::REGIONS, true)) {
            return null;
        }

        if ($variantId !== null && Schema::hasTable('vehicle_on_road_fees')) {
            $vehicleFees = VehicleOnRoadFee::query()
                ->where('variant_id', $variantId)
                ->where('region', $region)
                ->first();

            if ($vehicleFees !== null) {
                if (! $vehicleFees->hasCompleteFees()) {
                    return null;
                }

                return [
                    'registration_fee' => (float) $vehicleFees->registration_fee,
                    'registration_rate_percent' => (float) $vehicleFees->registration_rate_percent,
                    'body_insurance_rate_percent' => $vehicleFees->body_insurance_rate_percent === null
                        ? 0
                        : (float) $vehicleFees->body_insurance_rate_percent,
                    'body_insurance_configured' => $vehicleFees->body_insurance_rate_percent !== null,
                    'license_plate' => (float) $vehicleFees->license_plate_fee,
                    'inspection' => (float) $vehicleFees->inspection_fee,
                    'road_use' => (float) $vehicleFees->road_use_fee,
                    'compulsory_insurance' => (float) $vehicleFees->compulsory_insurance_fee,
                    'service_fee' => (float) $vehicleFees->service_fee,
                    'other' => (float) $vehicleFees->other_fee,
                    'source_note' => $vehicleFees->source_note,
                ];
            }

            return null;
        }

        $settingKey = $this->settingKey($region);
        $configured = Setting::query()->where('key', $settingKey)->first()?->value;

        return $this->validatedFees($configured, $region, $settingKey);
    }

    /** @return list<string> */
    public function availableRegions(): array
    {
        return Setting::query()
            ->whereIn('key', array_map($this->settingKey(...), self::REGIONS))
            ->get(['key', 'value'])
            ->filter(function (Setting $setting): bool {
                $region = str_replace('_', '-', substr($setting->key, strlen('on_road_')));

                return $this->validatedFees($setting->value, $region, $setting->key, false) !== null;
            })
            ->map(fn (Setting $setting): string => str_replace('_', '-', substr($setting->key, strlen('on_road_'))))
            ->values()
            ->all();
    }

    private function validatedFees(mixed $configured, string $region, string $settingKey, bool $log = true): ?array
    {
        if (! is_array($configured)) {
            return null;
        }

        $verifiedAt = $configured['verified_at'] ?? null;
        if (
            ! app(ToyotaSourceVerifier::class)->isOfficialGovernmentUrl($configured['source_url'] ?? null)
            || ! is_string($verifiedAt)
            || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $verifiedAt, $dateParts) !== 1
            || ! checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])
            || $verifiedAt > today()->toDateString()
        ) {
            if ($log) {
                Log::warning('On-road fee setting has no current authoritative source.', ['region' => $region]);
            }

            return null;
        }

        $fees = [];
        foreach (self::FEE_KEYS as $key) {
            if (! isset($configured[$key]) || ! is_numeric($configured[$key]) || (float) $configured[$key] < 0) {
                if ($log) {
                    Log::warning('On-road fee setting is missing or invalid.', ['setting' => $settingKey, 'field' => $key]);
                }

                return null;
            }

            $fees[$key] = (float) $configured[$key];
        }

        if ($fees['registration_rate_percent'] > 100) {
            if ($log) {
                Log::warning('On-road registration fee rate is invalid.', ['region' => $region]);
            }

            return null;
        }

        return [
            ...$fees,
            'registration_fee' => $fees['registration_fixed'],
            'registration_rate_percent' => $fees['registration_rate_percent'],
            'body_insurance_rate_percent' => 0,
            'body_insurance_configured' => false,
            'inspection' => 0,
            'service_fee' => 0,
            'source_note' => null,
            'source_url' => $configured['source_url'],
            'verified_at' => $verifiedAt,
        ];
    }

    private function settingKey(string $region): string
    {
        return 'on_road_'.str_replace('-', '_', $region);
    }
}
