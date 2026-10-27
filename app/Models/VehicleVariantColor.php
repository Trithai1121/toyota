<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['variant_id', 'vehicle_color_id', 'surcharge_override', 'image_path'])]
class VehicleVariantColor extends Model
{
    protected $table = 'vehicle_variant_color';

    protected function casts(): array
    {
        return ['surcharge_override' => 'integer'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(VehicleColor::class, 'vehicle_color_id');
    }
}
