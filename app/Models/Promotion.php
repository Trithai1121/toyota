<?php

namespace App\Models;

use App\Services\ToyotaSourceVerifier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'title', 'slug', 'description', 'conditions', 'starts_at',
    'ends_at', 'is_published', 'source_url', 'verified_at',
])]
class Promotion extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_published' => 'boolean',
            'verified_at' => 'date',
        ];
    }

    public function hasVerifiedSource(): bool
    {
        return $this->verified_at !== null
            && $this->verified_at->lessThanOrEqualTo(today())
            && app(ToyotaSourceVerifier::class)->isSecureUrl($this->source_url);
    }

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class)->withTimestamps();
    }
}
