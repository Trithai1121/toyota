<?php

namespace App\Filament\Resources\CampaignLandingPages;

use App\Filament\Resources\CampaignLandingPages\Pages\ManageCampaignLandingPages;
use App\Models\CampaignLandingPage;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

class CampaignLandingPageResource extends Resource
{
    protected static ?string $model = CampaignLandingPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $modelLabel = 'landing campaign';

    protected static ?string $pluralModelLabel = 'Landing chiến dịch';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('campaign_name')->label('Tên chiến dịch')->required()->maxLength(255),
            TextInput::make('slug')->label('Đường dẫn')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(255),
            Select::make('vehicle_id')->label('Mẫu xe liên quan')->relationship('vehicle', 'name')->searchable()->preload(),
            Select::make('content_page_id')
                ->label('Nội dung và khối trang (tùy chọn)')
                ->relationship('contentPage', 'title')
                ->searchable()
                ->preload()
                ->helperText('Chọn trang trong Trình dựng trang để thay thế bố cục mặc định của landing.'),
            TextInput::make('headline')->label('Tiêu đề chính')->maxLength(255),
            Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->columnSpanFull(),
            Textarea::make('body')->label('Nội dung')->rows(10)->columnSpanFull(),
            TextInput::make('seo_title')->label('SEO title')->maxLength(255),
            Textarea::make('seo_description')->label('SEO description')->rows(2)->maxLength(320),
            Toggle::make('is_published')->label('Hiển thị công khai')->default(false),
            DateTimePicker::make('starts_at')->label('Bắt đầu'),
            DateTimePicker::make('ends_at')->label('Kết thúc')->afterOrEqual('starts_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campaign_name')->label('Chiến dịch')->searchable()->sortable(),
                TextColumn::make('vehicle.name')->label('Mẫu xe'),
                TextColumn::make('slug')->label('Đường dẫn'),
                IconColumn::make('is_published')->label('Công khai')->boolean(),
                TextColumn::make('starts_at')->label('Bắt đầu')->dateTime('d/m/Y H:i'),
                TextColumn::make('ends_at')->label('Kết thúc')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCampaignLandingPages::route('/')];
    }
}
