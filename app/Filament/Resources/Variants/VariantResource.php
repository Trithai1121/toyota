<?php

namespace App\Filament\Resources\Variants;

use App\Filament\Resources\Variants\Pages\ManageVariants;
use App\Models\Variant;
use App\Models\VehicleLine;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VariantResource extends Resource
{
    protected static ?string $model = Variant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'phiên bản';

    protected static ?string $pluralModelLabel = 'Phiên bản xe';

    protected static string|UnitEnum|null $navigationGroup = 'Danh mục xe';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Phiên bản theo dòng xe')
                ->description('Chọn dòng xe trước, sau đó chọn mẫu xe thuộc dòng đó.')
                ->schema([
                    Select::make('vehicle_line_id')
                        ->label('Dòng xe')
                        ->options(fn (): array => VehicleLine::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live()
                        ->afterStateHydrated(function (Select $component, ?Variant $record): void {
                            if ($record !== null) {
                                $component->state($record->vehicle?->vehicle_line_id);
                            }
                        })
                        ->afterStateUpdated(fn (Set $set): mixed => $set('vehicle_id', null))
                        ->dehydrated(false)
                        ->required(),
                    Select::make('vehicle_id')
                        ->label('Mẫu xe')
                        ->relationship(
                            'vehicle',
                            'name',
                            modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                                ->where('vehicle_line_id', $get('vehicle_line_id') ?? 0),
                        )
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Get $get): bool => blank($get('vehicle_line_id')))
                        ->required(),
                ])
                ->columns(2),
            Section::make('Thông tin phiên bản')
                ->schema([
                    TextInput::make('name')->label('Tên phiên bản')->required()->maxLength(255),
                    TextInput::make('code')->label('Mã phiên bản')->maxLength(255),
                    TextInput::make('list_price')->label('Giá niêm yết (VND)')->numeric()->nullable()->minValue(0),
                    TextInput::make('seats')->label('Số chỗ')->numeric()->nullable()->minValue(1),
                    TextInput::make('fuel_type')->label('Nhiên liệu')->maxLength(100),
                    TextInput::make('transmission')->label('Hộp số')->maxLength(100),
                    TextInput::make('engine_displacement_cc')->label('Dung tích động cơ (cc)')->numeric()->nullable()->minValue(1),
                    TextInput::make('origin')->label('Xuất xứ')->maxLength(100),
                    TextInput::make('slug')->label('Slug phiên bản')->alphaDash()->maxLength(255)->required(),
                    TextInput::make('engine')->label('Động cơ')->maxLength(120),
                    TextInput::make('body_type')->label('Kiểu dáng')->maxLength(120),
                    Textarea::make('specs')
                        ->label('Thông số theo phiên bản (JSON)')
                        ->rows(8)
                        ->rules(['nullable', 'json'])
                        ->formatStateUsing(fn (mixed $state): string => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) ($state ?? ''))
                        ->dehydrateStateUsing(fn (?string $state): ?array => filled($state) ? json_decode($state, true) : null)
                        ->helperText('JSON theo nhóm dimensions, engine, chassis, exterior, interior, safety và comfort.')
                        ->columnSpanFull(),
                    TextInput::make('source_url')
                        ->label('Nguồn phiên bản Toyota Việt Nam')
                        ->url()
                        ->regex('/^https:\/\/(?:[a-z0-9-]+\.)*toyota\.com\.vn(?:\/|$)/i')
                        ->maxLength(255)
                        ->helperText('Giá và thông số chỉ hiển thị công khai khi có nguồn chính thức và ngày kiểm chứng.'),
                    DatePicker::make('verified_at')->label('Ngày kiểm chứng'),
                    TextInput::make('sort_order')->label('Thứ tự')->numeric()->minValue(0)->default(0)->required(),
                    Toggle::make('is_published')->label('Hiển thị công khai')->default(false),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle.line.name')->label('Dòng xe')->searchable()->sortable(),
                TextColumn::make('vehicle.name')->label('Mẫu xe')->searchable(),
                TextColumn::make('name')->label('Phiên bản')->searchable()->sortable(),
                TextColumn::make('list_price')->label('Giá niêm yết')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? 'Liên hệ' : number_format($state, 0, ',', '.').' ₫'),
                TextColumn::make('seats')->label('Số chỗ'),
                TextColumn::make('fuel_type')->label('Nhiên liệu'),
                TextColumn::make('transmission')->label('Hộp số'),
                IconColumn::make('is_published')->label('Công khai')->boolean(),
            ])
            ->filters([
                SelectFilter::make('vehicle_line_id')
                    ->label('Dòng xe')
                    ->options(fn (): array => VehicleLine::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, int|string $lineId): Builder => $query->whereHas(
                            'vehicle',
                            fn (Builder $vehicleQuery): Builder => $vehicleQuery->where('vehicle_line_id', $lineId),
                        ),
                    )),
                SelectFilter::make('vehicle_id')->label('Mẫu xe')->relationship('vehicle', 'name'),
                SelectFilter::make('is_published')->label('Trạng thái')->options([1 => 'Đang công khai', 0 => 'Bản nháp']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVariants::route('/')];
    }
}
