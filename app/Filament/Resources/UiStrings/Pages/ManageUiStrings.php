<?php

namespace App\Filament\Resources\UiStrings\Pages;

use App\Filament\Resources\UiStrings\UiStringResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUiStrings extends ManageRecords
{
    protected static string $resource = UiStringResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
