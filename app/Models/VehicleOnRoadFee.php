<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'variant_id', 'region', 'registration_fee', 'registration_rate_percent', 'body_insurance_rate_percent',
    'license_plate_fee', 'inspection_fee', 'road_use_fee', 'compulsory_insurance_fee',
    'service_fee', 'other_fee', 'source_note', 'is_enabled',
])]
class VehicleOnRoadFee extends Model
{
    protected function casts(): array
    {
        return [
            'registration_fee' => 'integer',
            'registration_rate_percent' => 'decimal:2',
            'body_insurance_rate_percent' => 'decimal:2',
            'license_plate_fee' => 'integer',
            'inspection_fee' => 'integer',
            'road_use_fee' => 'integer',
            'compulsory_insurance_fee' => 'integer',
            'service_fee' => 'integer',
            'other_fee' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function hasCompleteFees(): bool
    {
        foreach ([
            'registration_fee', 'registration_rate_percent', 'license_plate_fee',
            'inspection_fee', 'road_use_fee', 'compulsory_insurance_fee',
        ] as $field) {
            if ($this->{$field} === null) {
                return false;
            }
        }

        return $this->is_enabled;
    }
}
