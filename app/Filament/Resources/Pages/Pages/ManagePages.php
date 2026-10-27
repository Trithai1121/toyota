<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Services\PagePublicationChecklist;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePages extends ManageRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->before(fn (array $data) => app(PagePublicationChecklist::class)->validate($data))
                ->after(fn (Page $record) => $record->createRevision(auth()->id(), 'Tạo trang')),
        ];
    }
}
