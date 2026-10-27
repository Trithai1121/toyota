<?php

namespace App\Filament\Resources\CampaignLandingPages\Pages;

use App\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCampaignLandingPages extends ManageRecords
{
    protected static string $resource = CampaignLandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
