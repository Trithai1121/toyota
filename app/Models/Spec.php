<?php

namespace App\Models;

use App\Services\ToyotaSourceVerifier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'name', 'value', 'category', 'sort_order', 'source_url', 'verified_at'])]
class Spec extends Model
{
    protected function casts(): array
    {
        return ['verified_at' => 'date'];
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
}
