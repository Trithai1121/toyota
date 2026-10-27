<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Exports\LeadExporter;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $modelLabel = 'lead';

    protected static ?string $pluralModelLabel = 'Khách hàng tiềm năng';

    protected static string|UnitEnum|null $navigationGroup = 'Kinh doanh';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'new' => 'Mới',
                        'called' => 'Đã gọi',
                        'test_drive_scheduled' => 'Hẹn lái thử',
                        'sold' => 'Đã bán',
                        'cancelled' => 'Đã hủy',
                    ])
                    ->required(),
                Select::make('assigned_to')
                    ->label('Nhân viên phụ trách')
                    ->options(fn (): array => User::role('sale')
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label('Họ tên')->searchable()
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['admin', 'sale']) ?? false),
                TextColumn::make('phone')->label('Số điện thoại')->searchable()
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['admin', 'sale']) ?? false),
                TextColumn::make('request_type')
                    ->label('Nhu cầu')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'test_drive' => 'Lái thử',
                        'quote' => 'Báo giá',
                        default => 'Khác',
                    }),
                TextColumn::make('vehicle.name')->label('Xe quan tâm')->placeholder('Chưa chọn'),
                TextColumn::make('variant.name')->label('Phiên bản')->placeholder('Chưa chọn'),
                TextColumn::make('vehicleColor.name')->label('Màu xe')->placeholder('Chưa chọn'),
                TextColumn::make('selected_price')->label('Giá đã chọn')->formatStateUsing(fn (?int $state): string => $state === null ? '—' : number_format($state, 0, ',', '.').' VNĐ'),
                TextColumn::make('region')->label('Khu vực'),
                TextColumn::make('desired_date')->label('Ngày dự kiến')->date('d/m/Y')->placeholder('Chưa chọn'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => 'Mới',
                        'called' => 'Đã gọi',
                        'test_drive_scheduled' => 'Hẹn lái thử',
                        'sold' => 'Đã bán',
                        'cancelled' => 'Đã hủy',
                        default => $state,
                    }),
                TextColumn::make('assignee.name')->label('Phụ trách')
                    ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
                TextColumn::make('created_at')->label('Ngày nhận')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options([
                    'new' => 'Mới',
                    'called' => 'Đã gọi',
                    'test_drive_scheduled' => 'Hẹn lái thử',
                    'sold' => 'Đã bán',
                    'cancelled' => 'Đã hủy',
                ]),
                SelectFilter::make('request_type')->label('Nhu cầu')->options([
                    'test_drive' => 'Lái thử',
                    'quote' => 'Báo giá',
                ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Chi tiết')
                    ->schema([
                        TextEntry::make('full_name')->label('Họ tên'),
                        TextEntry::make('phone')
                            ->label('Số điện thoại')
                            ->url(fn (string $state): string => 'tel:'.$state),
                        TextEntry::make('request_type')
                            ->label('Nhu cầu')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'test_drive' => 'Lái thử',
                                'quote' => 'Báo giá',
                                default => 'Khác',
                            }),
                        TextEntry::make('vehicle.name')->label('Xe quan tâm')->placeholder('Chưa chọn'),
                        TextEntry::make('variant.name')->label('Phiên bản')->placeholder('Chưa chọn'),
                        TextEntry::make('vehicleColor.name')->label('Màu xe')->placeholder('Chưa chọn'),
                        TextEntry::make('selected_price')->label('Giá đã chọn')->formatStateUsing(fn (?int $state): string => $state === null ? '—' : number_format($state, 0, ',', '.').' VNĐ'),
                        TextEntry::make('desired_date')->label('Ngày dự kiến')->date('d/m/Y')->placeholder('Chưa chọn'),
                        TextEntry::make('region')->label('Khu vực')->placeholder('Chưa cung cấp'),
                        TextEntry::make('customer_note')->label('Ghi chú của khách')->placeholder('Không có ghi chú')->columnSpanFull(),
                        TextEntry::make('status')
                            ->label('Trạng thái')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'new' => 'Mới',
                                'called' => 'Đã gọi',
                                'test_drive_scheduled' => 'Hẹn lái thử',
                                'sold' => 'Đã bán',
                                default => $state,
                            }),
                        TextEntry::make('assignee.name')->label('Nhân viên phụ trách')->placeholder('Chưa phân công'),
                        TextEntry::make('source')->label('Nguồn')->placeholder('Không xác định'),
                        TextEntry::make('landingPage.campaign_name')->label('Chiến dịch')->placeholder('Không áp dụng'),
                        TextEntry::make('utm_source')->label('UTM source')->placeholder('—'),
                        TextEntry::make('utm_medium')->label('UTM medium')->placeholder('—'),
                        TextEntry::make('utm_campaign')->label('UTM campaign')->placeholder('—'),
                        TextEntry::make('created_at')->label('Thời điểm nhận')->dateTime('d/m/Y H:i'),
                    ]),
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Xuất CSV')
                    ->exporter(LeadExporter::class)
                    ->authorize('export', Lead::class)
                    ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['vehicle', 'variant', 'vehicleColor', 'assignee', 'landingPage']);
        $user = auth()->user();

        if ($user?->hasRole('sale')) {
            $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLeads::route('/'),
        ];
    }
}
