<?php

namespace App\Services;

use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Cache;

class SiteTheme
{
    public function all(): array
    {
        return Cache::remember('website.theme-settings.v1', now()->addMinutes(10), fn (): array => ThemeSetting::query()
            ->get()
            ->mapWithKeys(fn (ThemeSetting $setting): array => [$setting->key => $setting->value])
            ->all());
    }
}
