<?php

namespace App\Filament\Resources\VehicleLines\Pages;

use App\Filament\Resources\VehicleLines\VehicleLineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleLines extends ManageRecords
{
    protected static string $resource = VehicleLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
