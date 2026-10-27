<?php

namespace App\Filament\Resources\VehicleOnRoadFees\Pages;

use App\Filament\Resources\VehicleOnRoadFees\VehicleOnRoadFeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVehicleOnRoadFees extends ManageRecords
{
    protected static string $resource = VehicleOnRoadFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
