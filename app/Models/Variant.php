<?php

namespace App\Models;

use App\Services\ToyotaSourceVerifier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vehicle_id', 'name', 'slug', 'engine', 'body_type', 'specs', 'code', 'list_price', 'seats', 'fuel_type',
    'transmission', 'engine_displacement_cc', 'origin',
    'sort_order', 'is_published', 'source_url', 'verified_at',
])]
class Variant extends Model
{
    protected function casts(): array
    {
        return [
            'list_price' => 'integer',
            'is_published' => 'boolean',
            'verified_at' => 'date',
            'specs' => 'array',
        ];
    }

    public function hasVerifiedSource(): bool
    {
        return $this->verified_at !== null
            && $this->verified_at->lessThanOrEqualTo(today())
            && app(ToyotaSourceVerifier::class)->isToyotaOfficialUrl($this->source_url);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(VehicleColor::class, 'vehicle_variant_color')
            ->withPivot(['surcharge_override', 'image_path'])
            ->withTimestamps();
    }

    public function onRoadFees(): HasMany
    {
        return $this->hasMany(VehicleOnRoadFee::class);
    }

    public function totalPriceWithColor(?VehicleColor $color = null): ?int
    {
        if ($this->list_price === null) {
            return null;
        }

        if ($color === null) {
            return (int) $this->list_price;
        }

        $linkedColor = $this->relationLoaded('colors')
            ? $this->colors->firstWhere('id', $color->id)
            : $this->colors()->whereKey($color->id)->first();

        if ($linkedColor === null || (int) $linkedColor->vehicle_id !== (int) $this->vehicle_id) {
            return null;
        }

        $surcharge = $linkedColor->pivot->surcharge_override ?? $linkedColor->surcharge;

        return (int) $this->list_price + (int) $surcharge;
    }
}
