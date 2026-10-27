<?php

namespace App\Models;

use App\Services\HtmlContentSanitizer;
use App\Services\ResponsiveImageProcessor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'page_id', 'type', 'title', 'description', 'body', 'image_path', 'image_alt',
    'vehicle_line_id', 'vehicle_id',
    'background_color', 'alignment', 'spacing_top', 'spacing_bottom', 'cta_label',
    'cta_url', 'cta_style', 'show_desktop', 'show_mobile', 'is_enabled', 'settings',
    'sort_order',
])]
class PageBlock extends Model
{
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'show_desktop' => 'boolean',
            'show_mobile' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PageBlock $block): void {
            $block->body = app(HtmlContentSanitizer::class)->sanitize($block->body);

            if ($block->background_color && ! preg_match('/^#[0-9a-fA-F]{6}$/', $block->background_color)) {
                $block->background_color = null;
            }
        });

        static::saved(function (PageBlock $block): void {
            $processor = app(ResponsiveImageProcessor::class);
            $settings = is_array($block->settings) ? $block->settings : [];
            $changed = false;

            if (filled($block->image_path) && ! $processor->isProcessed($block->image_path)) {
                $block->image_path = $processor->process($block->image_path);
                $changed = true;
            }

            foreach (['images', 'hero_images'] as $imageCollection) {
                if (! is_array($settings[$imageCollection] ?? null)) {
                    continue;
                }

                foreach ($settings[$imageCollection] as $index => $image) {
                    if (! is_array($image) || ! filled($image['path'] ?? null) || $processor->isProcessed($image['path'])) {
                        continue;
                    }

                    $settings[$imageCollection][$index]['path'] = $processor->process($image['path']);
                    $changed = true;
                }
            }

            if ($changed) {
                $block->settings = $settings;
                $block->saveQuietly();
            }
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function vehicleLine(): BelongsTo
    {
        return $this->belongsTo(VehicleLine::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
