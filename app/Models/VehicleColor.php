<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['vehicle_id', 'name', 'slug', 'hex_code', 'surcharge', 'sort_order', 'is_active'])]
class VehicleColor extends Model
{
    protected function casts(): array
    {
        return [
            'surcharge' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(Variant::class, 'vehicle_variant_color')
            ->withPivot(['surcharge_override', 'image_path'])
            ->withTimestamps();
    }
}
