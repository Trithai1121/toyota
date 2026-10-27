<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vehicle_id', 'variant_id', 'vehicle_color_id', 'selected_price', 'campaign_landing_page_id', 'assigned_to', 'request_type',
    'full_name', 'phone', 'desired_date', 'region', 'customer_note', 'status',
    'consent_given', 'consent_version', 'consented_at', 'source', 'utm_source',
    'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'event_id',
    'marketing_consent', 'marketing_consent_at', 'submission_token',
])]
class Lead extends Model
{
    protected function casts(): array
    {
        return [
            'desired_date' => 'date',
            'consent_given' => 'boolean',
            'consented_at' => 'datetime',
            'marketing_consent' => 'boolean',
            'marketing_consent_at' => 'datetime',
            'selected_price' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function vehicleColor(): BelongsTo
    {
        return $this->belongsTo(VehicleColor::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(CampaignLandingPage::class, 'campaign_landing_page_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }
}
