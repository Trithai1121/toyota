<?php

namespace App\Filament\Resources\LeadNotes\Pages;

use App\Filament\Resources\LeadNotes\LeadNoteResource;
use App\Models\Lead;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Gate;

class ManageLeadNotes extends ManageRecords
{
    protected static string $resource = LeadNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Thêm ghi chú')
                ->mutateDataUsing(function (array $data): array {
                    $lead = Lead::query()->findOrFail($data['lead_id']);
                    Gate::authorize('view', $lead);

                    return [
                        ...$data,
                        'user_id' => auth()->id(),
                    ];
                }),
        ];
    }
}
