<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vehicle_id', 'content_page_id', 'campaign_name', 'slug', 'headline', 'summary', 'body',
    'seo_title', 'seo_description', 'is_published', 'starts_at', 'ends_at',
])]
class CampaignLandingPage extends Model
{
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function contentPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'content_page_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
