<?php

namespace App\Filament\Resources\Specs\Pages;

use App\Filament\Resources\Specs\SpecResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSpecs extends ManageRecords
{
    protected static string $resource = SpecResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
