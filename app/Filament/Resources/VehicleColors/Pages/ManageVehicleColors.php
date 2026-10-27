<?php

namespace App\Filament\Resources\VehicleColors\Pages;

use App\Filament\Resources\VehicleColors\VehicleColorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleColors extends ManageRecords
{
    protected static string $resource = VehicleColorResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
