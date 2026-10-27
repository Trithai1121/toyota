<?php

namespace App\Filament\Resources\VehicleImages\Pages;

use App\Filament\Resources\VehicleImages\VehicleImageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleImages extends ManageRecords
{
    protected static string $resource = VehicleImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
