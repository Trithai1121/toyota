<?php

namespace App\Models;

use App\Services\ResponsiveImageProcessor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'description'])]
class ThemeSetting extends Model
{
    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('website.theme-settings.v1'));
        static::deleted(fn () => Cache::forget('website.theme-settings.v1'));
        static::saved(function (ThemeSetting $setting): void {
            if ($setting->key !== 'brand') {
                return;
            }

            $processor = app(ResponsiveImageProcessor::class);
            $value = $setting->value ?? [];
            $changed = false;

            foreach (['logo_path', 'favicon_path'] as $field) {
                if (filled($value[$field] ?? null) && ! $processor->isProcessed($value[$field])) {
                    $value[$field] = $processor->process($value[$field]);
                    $changed = true;
                }
            }

            if ($changed) {
                $setting->value = $value;
                $setting->saveQuietly();
            }
        });
        static::updated(function (ThemeSetting $setting): void {
            if ($setting->key !== 'tracking' || ! $setting->wasChanged('value')) {
                return;
            }

            TrackingSettingAudit::query()->create([
                'user_id' => auth()->id(),
                'before' => $setting->getOriginal('value'),
                'after' => $setting->value,
            ]);
        });
    }
}
