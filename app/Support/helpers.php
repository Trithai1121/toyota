<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('site_settings', fn (): array => SiteSetting::query()
            ->pluck('value', 'key')
            ->all());

        return $settings[$key] ?? $default;
    }
}

if (! function_exists('contrastColor')) {
    function contrastColor(?string $hex): string
    {
        if (! is_string($hex) || preg_match('/^#([A-Fa-f0-9]{6})$/', $hex, $matches) !== 1) {
            return '#111111';
        }

        $channels = array_map('hexdec', str_split($matches[1], 2));
        $linear = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $channels);
        $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];

        return $luminance > 0.2 ? '#111111' : '#FFFFFF';
    }
}
