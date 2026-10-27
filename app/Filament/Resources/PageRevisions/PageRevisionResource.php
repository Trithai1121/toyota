<?php

namespace App\Filament\Resources\PageRevisions;

use App\Filament\Resources\PageRevisions\Pages\ManagePageRevisions;
use App\Models\PageRevision;
use App\Services\PageRevisionDiff;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class PageRevisionResource extends Resource
{
    protected static ?string $model = PageRevision::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $modelLabel = 'phiên bản trang';

    protected static ?string $pluralModelLabel = 'Lịch sử phiên bản';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('page.title')->label('Trang'),
            TextEntry::make('summary')->label('Ghi chú phiên bản'),
            TextEntry::make('user.name')->label('Người chỉnh sửa')->placeholder('Tác vụ hệ thống'),
            TextEntry::make('created_at')->label('Thời điểm')->dateTime('d/m/Y H:i:s'),
            TextEntry::make('snapshot')
                ->label('Nội dung lưu')
                ->formatStateUsing(fn (array $state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('page.title')->label('Trang')->searchable()->sortable(),
                TextColumn::make('summary')->label('Ghi chú'),
                TextColumn::make('user.name')->label('Người chỉnh sửa')->placeholder('Tác vụ hệ thống'),
                TextColumn::make('created_at')->label('Thời điểm')->dateTime('d/m/Y H:i:s')->sortable(),
            ])
            ->filters([
                SelectFilter::make('page_id')->label('Trang')->relationship('page', 'title')->searchable(),
            ])
            ->recordActions([
                ViewAction::make()->label('Xem phiên bản'),
                Action::make('compare')
                    ->label('So sánh với bản hiện tại')
                    ->modalContent(fn (PageRevision $record) => view('filament.resources.page-revisions.compare', [
                        'differences' => app(PageRevisionDiff::class)->compare(
                            $record->snapshot,
                            $record->page->revisionSnapshot(),
                        ),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
                Action::make('restore')
                    ->label('Khôi phục')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->requiresConfirmation()
                    ->action(function (PageRevision $record): void {
                        Gate::authorize('update', $record);
                        $record->page->restoreRevision($record);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePageRevisions::route('/')];
    }
}
