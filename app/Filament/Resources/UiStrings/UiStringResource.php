<?php

namespace App\Filament\Resources\UiStrings;

use App\Filament\Resources\UiStrings\Pages\ManageUiStrings;
use App\Models\UiString;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UiStringResource extends Resource
{
    protected static ?string $model = UiString::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?string $modelLabel = 'chuỗi giao diện';

    protected static ?string $pluralModelLabel = 'Văn bản website';

    protected static string|UnitEnum|null $navigationGroup = 'Giao diện';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->label('Mã chuỗi')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(190),
            Textarea::make('value')->label('Nội dung hiển thị')->required()->maxLength(10000)->rows(5)->columnSpanFull(),
            TextInput::make('description')->label('Vị trí sử dụng')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label('Mã chuỗi')->searchable()->sortable(),
                TextColumn::make('value')->label('Văn bản')->limit(100)->searchable(),
                TextColumn::make('description')->label('Vị trí'),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUiStrings::route('/')];
    }
}
