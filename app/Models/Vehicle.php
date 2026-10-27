<?php

namespace App\Models;

use App\Services\ToyotaSourceVerifier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vehicle_line_id', 'content_page_id', 'name', 'slug', 'subtitle', 'summary', 'description',
    'video_url', 'seo_title', 'seo_description', 'official_source_url', 'content_verified_at',
    'catalogue_url', 'manual_url', 'is_new', 'is_published', 'published_at',
])]
class Vehicle extends Model
{
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_new' => 'boolean',
            'published_at' => 'datetime',
            'content_verified_at' => 'date',
        ];
    }

    public function hasVerifiedOfficialContent(): bool
    {
        return $this->content_verified_at !== null
            && $this->content_verified_at->lessThanOrEqualTo(today())
            && app(ToyotaSourceVerifier::class)->isToyotaOfficialUrl($this->official_source_url);
    }

    public function hasVerifiedDocumentUrl(?string $url): bool
    {
        return $this->hasVerifiedOfficialContent()
            && app(ToyotaSourceVerifier::class)->isToyotaOfficialUrl($url);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(VehicleLine::class, 'vehicle_line_id');
    }

    public function contentPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'content_page_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class)->orderBy('sort_order');
    }

    public function colors(): HasMany
    {
        return $this->hasMany(VehicleColor::class)->orderBy('sort_order');
    }

    public function specs(): HasMany
    {
        return $this->hasMany(Spec::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order');
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class)->withTimestamps();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(VehicleCategory::class, 'vehicle_category')->withTimestamps();
    }
}
