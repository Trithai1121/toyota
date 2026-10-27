<?php

namespace App\Filament\Resources\VariantColors;

use App\Filament\Resources\VariantColors\Pages\ManageVariantColors;
use App\Models\Variant;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleImage;
use App\Models\VehicleLine;
use App\Models\VehicleVariantColor;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VariantColorResource extends Resource
{
    protected static ?string $model = VehicleVariantColor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $modelLabel = 'màu theo phiên bản';

    protected static ?string $pluralModelLabel = 'Ảnh và phụ phí theo phiên bản';

    protected static string|UnitEnum|null $navigationGroup = 'Danh mục xe';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Phiên bản và màu')
                ->schema([
                    Select::make('vehicle_line_id')
                        ->label('Dòng xe')
                        ->options(fn (): array => VehicleLine::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->live()
                        ->afterStateHydrated(function (Select $component, ?VehicleVariantColor $record): void {
                            if ($record !== null) {
                                $component->state($record->variant?->vehicle?->vehicle_line_id);
                            }
                        })
                        ->afterStateUpdated(fn (Set $set): mixed => [$set('vehicle_id', null), $set('variant_id', null), $set('vehicle_color_id', null)])
                        ->dehydrated(false)
                        ->required(),
                    Select::make('vehicle_id')
                        ->label('Mẫu xe')
                        ->options(fn (Get $get): array => Vehicle::query()
                            ->where('vehicle_line_id', $get('vehicle_line_id') ?? 0)
                            ->orderBy('name')->pluck('name', 'id')->all())
                        ->live()
                        ->afterStateHydrated(function (Select $component, ?VehicleVariantColor $record): void {
                            if ($record !== null) {
                                $component->state($record->variant?->vehicle_id);
                            }
                        })
                        ->afterStateUpdated(fn (Set $set): mixed => [$set('variant_id', null), $set('vehicle_color_id', null)])
                        ->dehydrated(false)
                        ->required(),
                    Select::make('variant_id')
                        ->label('Phiên bản')
                        ->options(fn (Get $get): array => Variant::query()
                            ->where('vehicle_id', $get('vehicle_id') ?? 0)
                            ->orderBy('sort_order')->pluck('name', 'id')->all())
                        ->searchable()->live()
                        ->required(),
                    Select::make('vehicle_color_id')
                        ->label('Màu')
                        ->options(fn (Get $get): array => VehicleColor::query()
                            ->where('vehicle_id', $get('vehicle_id') ?? 0)
                            ->orderBy('sort_order')->pluck('name', 'id')->all())
                        ->searchable()->live()->required(),
                    TextInput::make('surcharge_override')
                        ->label('Phụ phí riêng (VNĐ)')
                        ->numeric()->minValue(0)->nullable()
                        ->helperText('Để trống để lấy phụ phí mặc định của màu.'),
                    Select::make('image_path')
                        ->label('Ảnh đã tải lên cho màu này')
                        ->options(function (Get $get): array {
                            $color = VehicleColor::query()->find($get('vehicle_color_id'));
                            if ($color === null) {
                                return [];
                            }

                            return VehicleImage::query()
                                ->where('vehicle_id', $color->vehicle_id)
                                ->whereRaw('LOWER(color_name) = ?', [mb_strtolower($color->name)])
                                ->orderBy('sort_order')
                                ->get()
                                ->filter(fn (VehicleImage $image): bool => $image->isReadyForDisplay())
                                ->mapWithKeys(fn (VehicleImage $image): array => [$image->path => $image->alt_text ?: $image->path])
                                ->all();
                        })
                        ->searchable()
                        ->nullable()
                        ->helperText('Chọn ảnh đã tải lên trong Thư viện ảnh xe; màu chưa có ảnh sẽ dùng ảnh đại diện của xe.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('variant.vehicle.name')->label('Mẫu xe')->searchable(),
            TextColumn::make('variant.name')->label('Phiên bản')->searchable(),
            TextColumn::make('color.name')->label('Màu')->searchable(),
            TextColumn::make('surcharge_override')->label('Phụ phí riêng')->formatStateUsing(fn (?int $state): string => $state === null ? 'Theo màu' : number_format($state, 0, ',', '.').' VNĐ'),
            ImageColumn::make('image_path')->label('Ảnh')->disk('public'),
        ])->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVariantColors::route('/')];
    }
}
