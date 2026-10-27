<?php

namespace App\Filament\Resources\VehicleColors;

use App\Filament\Resources\VehicleColors\Pages\ManageVehicleColors;
use App\Models\VehicleColor;
use App\Models\VehicleLine;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Str;
use UnitEnum;

class VehicleColorResource extends Resource
{
    protected static ?string $model = VehicleColor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $modelLabel = 'màu xe';

    protected static ?string $pluralModelLabel = 'Màu xe';

    protected static string|UnitEnum|null $navigationGroup = 'Danh mục xe';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mẫu xe')
                ->schema([
                    Select::make('vehicle_line_id')
                        ->label('Dòng xe')
                        ->options(fn (): array => VehicleLine::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->live()
                        ->afterStateHydrated(function (Select $component, ?VehicleColor $record): void {
                            if ($record !== null) {
                                $component->state($record->vehicle?->vehicle_line_id);
                            }
                        })
                        ->afterStateUpdated(fn (Set $set): mixed => $set('vehicle_id', null))
                        ->dehydrated(false)
                        ->required(),
                    Select::make('vehicle_id')
                        ->label('Mẫu xe')
                        ->relationship('vehicle', 'name', modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                            ->where('vehicle_line_id', $get('vehicle_line_id') ?? 0))
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Get $get): bool => blank($get('vehicle_line_id')))
                        ->required(),
                ])
                ->columns(2),
            Section::make('Thông tin màu')
                ->schema([
                    TextInput::make('name')->label('Tên màu')->required()->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('slug', Str::slug((string) $state))),
                    TextInput::make('slug')->label('Slug')->required()->alphaDash()->maxLength(120),
                    TextInput::make('hex_code')->label('Mã màu HEX')->regex('/^#[0-9a-fA-F]{6}$/')->maxLength(7)->default('#D1D5DB')->required(),
                    TextInput::make('surcharge')->label('Phụ phí màu (VNĐ)')->numeric()->minValue(0)->default(0)->required(),
                    TextInput::make('sort_order')->label('Thứ tự')->numeric()->minValue(0)->default(0)->required(),
                    Toggle::make('is_active')->label('Đang sử dụng')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle.name')->label('Mẫu xe')->searchable(),
                TextColumn::make('name')->label('Màu')->searchable()->sortable(),
                TextColumn::make('hex_code')->label('HEX'),
                TextColumn::make('surcharge')->label('Phụ phí')->formatStateUsing(fn (int $state): string => number_format($state, 0, ',', '.').' VNĐ'),
                IconColumn::make('is_active')->label('Sử dụng')->boolean(),
            ])
            ->filters([
                SelectFilter::make('vehicle_id')->label('Mẫu xe')->relationship('vehicle', 'name'),
                SelectFilter::make('is_active')->label('Trạng thái')->options([1 => 'Đang dùng', 0 => 'Đã ẩn']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVehicleColors::route('/')];
    }
}
