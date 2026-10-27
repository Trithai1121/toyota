<?php

namespace App\Filament\Resources\TrackingSettingAudits;

use App\Filament\Resources\TrackingSettingAudits\Pages\ManageTrackingSettingAudits;
use App\Models\TrackingSettingAudit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TrackingSettingAuditResource extends Resource
{
    protected static ?string $model = TrackingSettingAudit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $modelLabel = 'thay đổi mã đo lường';

    protected static ?string $pluralModelLabel = 'Lịch sử mã đo lường';

    protected static string|UnitEnum|null $navigationGroup = 'Cài đặt hệ thống';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Thời điểm')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Người chỉnh sửa')->placeholder('Tác vụ hệ thống'),
                TextColumn::make('before')
                    ->label('Giá trị trước')
                    ->formatStateUsing(fn (?array $state): string => json_encode($state ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '')
                    ->wrap(),
                TextColumn::make('after')
                    ->label('Giá trị sau')
                    ->formatStateUsing(fn (?array $state): string => json_encode($state ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '')
                    ->wrap(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTrackingSettingAudits::route('/')];
    }
}
