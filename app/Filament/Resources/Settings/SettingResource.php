<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\Pages\ManageSettings;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $modelLabel = 'cấu hình';

    protected static ?string $pluralModelLabel = 'Cấu hình nghiệp vụ';

    protected static string|UnitEnum|null $navigationGroup = 'Quản trị';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->label('Khóa cấu hình')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(255),
            TextInput::make('description')->label('Mô tả')->maxLength(255),
            KeyValue::make('value')
                ->label('Giá trị')
                ->keyLabel('Thuộc tính')
                ->valueLabel('Giá trị')
                ->addable()
                ->deletable()
                ->helperText('Cấu hình on_road_* cần đủ các khoản phí, source_url thuộc tên miền .gov.vn hoặc chinhphu.vn và verified_at dạng YYYY-MM-DD. Thiếu nguồn hoặc ngày kiểm chứng thì công cụ không tính.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label('Khóa')->searchable()->sortable(),
                TextColumn::make('description')->label('Mô tả'),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSettings::route('/')];
    }
}
