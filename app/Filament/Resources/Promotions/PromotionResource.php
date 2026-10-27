<?php

namespace App\Filament\Resources\Promotions;

use App\Filament\Resources\Promotions\Pages\ManagePromotions;
use App\Models\Promotion;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $modelLabel = 'ưu đãi';

    protected static ?string $pluralModelLabel = 'Ưu đãi';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255),
            TextInput::make('slug')->label('Đường dẫn')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(255),
            Textarea::make('description')->label('Nội dung')->rows(4),
            Textarea::make('conditions')->label('Điều kiện áp dụng')->rows(3),
            TextInput::make('source_url')->label('URL nguồn chương trình')->url()->regex('/^https:\/\//i')->maxLength(255),
            DatePicker::make('verified_at')->label('Ngày xác minh chương trình'),
            DatePicker::make('starts_at')->label('Ngày bắt đầu'),
            DatePicker::make('ends_at')->label('Ngày kết thúc')->afterOrEqual('starts_at'),
            Select::make('vehicles')->label('Áp dụng cho mẫu xe')->relationship('vehicles', 'name')->multiple()->searchable()->preload(),
            Toggle::make('is_published')->label('Hiển thị công khai')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Ưu đãi')->searchable()->sortable(),
                TextColumn::make('starts_at')->label('Từ ngày')->date('d/m/Y')->sortable(),
                TextColumn::make('ends_at')->label('Đến ngày')->date('d/m/Y')->sortable(),
                IconColumn::make('is_published')->label('Công khai')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePromotions::route('/')];
    }
}
