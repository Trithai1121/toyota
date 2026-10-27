<?php

namespace App\Filament\Resources\Specs;

use App\Filament\Resources\Specs\Pages\ManageSpecs;
use App\Models\Spec;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class SpecResource extends Resource
{
    protected static ?string $model = Spec::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'thông số';

    protected static ?string $pluralModelLabel = 'Thông số xe';

    protected static string|UnitEnum|null $navigationGroup = 'Danh mục xe';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vehicle_id')->label('Mẫu xe')->relationship('vehicle', 'name')->searchable()->preload()->required(),
            TextInput::make('name')->label('Tên thông số')->required()->maxLength(255),
            Textarea::make('value')->label('Giá trị')->rows(3),
            TextInput::make('source_url')
                ->label('Nguồn thông số Toyota Việt Nam')
                ->url()
                ->regex('/^https:\/\/(?:[a-z0-9-]+\.)*toyota\.com\.vn(?:\/|$)/i')
                ->maxLength(255),
            DatePicker::make('verified_at')->label('Ngày kiểm chứng'),
            Select::make('category')
                ->label('Nhóm thông tin')
                ->options([
                    'general' => 'Thông số chung',
                    'dimensions' => 'Kích thước',
                    'performance' => 'Vận hành',
                    'safety' => 'An toàn',
                ])
                ->default('general')
                ->required(),
            TextInput::make('sort_order')->label('Thứ tự')->numeric()->minValue(0)->default(0)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle.name')->label('Mẫu xe')->searchable(),
                TextColumn::make('name')->label('Thông số')->searchable()->sortable(),
                TextColumn::make('value')->label('Giá trị')->limit(80),
                TextColumn::make('category')->label('Nhóm')->formatStateUsing(fn (?string $state): string => match ($state) {
                    'dimensions' => 'Kích thước',
                    'performance' => 'Vận hành',
                    'safety' => 'An toàn',
                    default => 'Thông số chung',
                }),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->filters([SelectFilter::make('vehicle_id')->label('Mẫu xe')->relationship('vehicle', 'name')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSpecs::route('/')];
    }
}
