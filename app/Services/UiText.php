<?php

namespace App\Services;

use App\Models\UiString;
use Illuminate\Support\Facades\Cache;

class UiText
{
    public function get(string $key, string $fallback = ''): string
    {
        $strings = Cache::remember('website.ui-strings.v1', now()->addMinutes(10), fn (): array => UiString::query()
            ->pluck('value', 'key')
            ->all());

        return $strings[$key] ?? $fallback;
    }
}
