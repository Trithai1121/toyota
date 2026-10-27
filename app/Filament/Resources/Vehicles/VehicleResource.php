<?php

namespace App\Filament\Resources\Vehicles;

use App\Filament\Resources\Vehicles\Pages\ManageVehicles;
use App\Models\Vehicle;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'mẫu xe';

    protected static ?string $pluralModelLabel = 'Mẫu xe';

    protected static string|UnitEnum|null $navigationGroup = 'Danh mục xe';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('vehicle_line_id')
                    ->label('Dòng xe')
                    ->relationship('line', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('content_page_id')
                    ->label('Nội dung và khối trang (tùy chọn)')
                    ->relationship('contentPage', 'title')
                    ->searchable()
                    ->preload()
                    ->helperText('Chọn trang trong Trình dựng trang để thay thế bố cục mặc định của chi tiết xe.'),
                TextInput::make('name')
                    ->label('Tên mẫu xe')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Đường dẫn')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('subtitle')->label('Tiêu đề phụ')->maxLength(255),
                Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->columnSpanFull(),
                Textarea::make('description')->label('Nội dung')->rows(8)->columnSpanFull(),
                TextInput::make('official_source_url')
                    ->label('Nguồn nội dung Toyota Việt Nam')
                    ->url()
                    ->regex('/^https:\/\/(?:[a-z0-9-]+\.)*toyota\.com\.vn(?:\/|$)/i')
                    ->maxLength(255),
                DatePicker::make('content_verified_at')->label('Ngày kiểm chứng nội dung'),
                TextInput::make('catalogue_url')
                    ->label('URL catalogue chính thức')
                    ->url()
                    ->regex('/^https:\/\/(?:[a-z0-9-]+\.)*toyota\.com\.vn(?:\/|$)/i')
                    ->maxLength(255),
                TextInput::make('manual_url')
                    ->label('URL hướng dẫn sử dụng chính thức')
                    ->url()
                    ->regex('/^https:\/\/(?:[a-z0-9-]+\.)*toyota\.com\.vn(?:\/|$)/i')
                    ->maxLength(255),
                TextInput::make('video_url')
                    ->label('Đường dẫn video đã được cấp quyền sử dụng')
                    ->url()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->rows(2)->maxLength(320),
                Toggle::make('is_published')->label('Hiển thị công khai')->default(false),
                DateTimePicker::make('published_at')->label('Thời điểm đăng'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Mẫu xe')->searchable()->sortable(),
                TextColumn::make('line.name')->label('Dòng xe')->sortable(),
                TextColumn::make('variants_count')->counts('variants')->label('Phiên bản'),
                IconColumn::make('is_published')->label('Công khai')->boolean(),
                TextColumn::make('published_at')->label('Ngày đăng')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('vehicle_line_id')
                    ->label('Dòng xe')
                    ->relationship('line', 'name'),
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
            'index' => ManageVehicles::route('/'),
        ];
    }
}
