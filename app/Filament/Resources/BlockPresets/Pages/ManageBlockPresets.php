<?php

namespace App\Filament\Resources\BlockPresets\Pages;

use App\Filament\Resources\BlockPresets\BlockPresetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBlockPresets extends ManageRecords
{
    protected static string $resource = BlockPresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => [...$data, 'created_by' => auth()->id()]),
        ];
    }
}
