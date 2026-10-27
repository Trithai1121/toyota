<?php

namespace App\Filament\Resources\VehicleLines;

use App\Filament\Resources\VehicleLines\Pages\ManageVehicleLines;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class VehicleLineResource extends Resource
{
    protected static ?string $model = VehicleLine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'dòng xe';

    protected static ?string $pluralModelLabel = 'Dòng xe';

    protected static string|UnitEnum|null $navigationGroup = 'Danh mục xe';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tên dòng xe')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Đường dẫn')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('category')
                    ->label('Phân khúc')
                    ->options([
                        'sedan' => 'Sedan',
                        'hatchback' => 'Hatchback',
                        'suv-crossover' => 'SUV / Crossover',
                        'mpv' => 'MPV',
                        'pickup' => 'Bán tải',
                        'hybrid' => 'Hybrid',
                    ])
                    ->placeholder('Chưa phân loại'),
                TextInput::make('sort_order')
                    ->label('Thứ tự')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Toggle::make('is_published')
                    ->label('Hiển thị công khai')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Dòng xe')->searchable()->sortable(),
                TextColumn::make('category')->label('Phân khúc')->badge(),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
                IconColumn::make('is_published')->label('Công khai')->boolean(),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_published')
                    ->label('Trạng thái')
                    ->options([1 => 'Đang công khai', 0 => 'Bản nháp']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVehicleLines::route('/'),
        ];
    }
}
