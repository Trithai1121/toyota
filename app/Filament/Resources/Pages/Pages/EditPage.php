<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\PageDraft;
use App\Services\PagePublicationChecklist;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function afterFill(): void
    {
        $draft = $this->record->draft;

        if ($draft) {
            $this->form->fill($draft->payload);
            Notification::make()
                ->title('Đã khôi phục bản nháp tự động')
                ->body('Bản nháp chưa được xuất bản. Lưu trang để áp dụng nội dung.')
                ->info()
                ->send();
        }

        $blockId = request()->integer('block');

        if ($blockId && $this->record->blocks()->whereKey($blockId)->exists()) {
            Notification::make()
                ->title('Đang chỉnh sửa trang có khối được chọn')
                ->body('Bạn có thể chỉnh sửa nội dung các khối trong biểu mẫu trang này.')
                ->info()
                ->send();
        }
    }

    protected function afterSave(): void
    {
        $this->record->draft()->delete();
        $this->record->createRevision(auth()->id(), 'Cập nhật nội dung');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        app(PagePublicationChecklist::class)->validate($data);

        return $data;
    }

    public function updatedData(): void
    {
        if (! $this->record || ! PageResource::canEdit($this->record)) {
            return;
        }

        $payload = Arr::only($this->data ?? [], [
            'title', 'slug', 'seo_title', 'seo_description', 'status', 'published_at', 'blocks',
        ]);

        if ($payload === []) {
            return;
        }

        PageDraft::updateOrCreate(
            ['page_id' => $this->record->getKey()],
            ['user_id' => auth()->id(), 'payload' => $payload],
        );
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
