<?php

namespace App\Models;

use App\Services\ResponsiveImageProcessor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'vehicle_id', 'path', 'alt_text', 'source_note', 'source_url', 'rights_verified_at',
    'color_name', 'color_hex', 'category', 'sort_order',
])]
class VehicleImage extends Model
{
    protected function casts(): array
    {
        return ['rights_verified_at' => 'date'];
    }

    public function isReadyForDisplay(): bool
    {
        return filled($this->path)
            && filled($this->source_url)
            && $this->rights_verified_at !== null
            && Storage::disk('public')->exists($this->path);
    }

    protected static function booted(): void
    {
        static::saved(function (VehicleImage $image): void {
            $processor = app(ResponsiveImageProcessor::class);

            if (filled($image->path) && ! $processor->isProcessed($image->path)) {
                $image->path = $processor->process($image->path);
                $image->saveQuietly();
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
