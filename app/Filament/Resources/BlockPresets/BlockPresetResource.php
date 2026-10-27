<?php

namespace App\Filament\Resources\BlockPresets;

use App\Filament\Resources\BlockPresets\Pages\ManageBlockPresets;
use App\Models\BlockPreset;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BlockPresetResource extends Resource
{
    protected static ?string $model = BlockPreset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'mẫu khối';

    protected static ?string $pluralModelLabel = 'Thư viện khối mẫu';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Tên mẫu')->required()->maxLength(255),
            Select::make('type')->label('Loại mẫu')->options([
                'page' => 'Cả trang',
                'hero' => 'Banner / slider ưu đãi',
                'vehicle_grid' => 'Lưới xe',
                'promotion_strip' => 'Dải ưu đãi',
                'text_image' => 'Văn bản + ảnh',
                'specifications' => 'Bảng thông số',
                'gallery' => 'Thư viện ảnh / video',
                'lead_form' => 'Form lái thử / báo giá',
                'calculator' => 'Công cụ tính',
                'faq' => 'Câu hỏi thường gặp',
                'recent_posts' => 'Bài viết mới',
                'showroom_map' => 'Bản đồ showroom',
                'cta' => 'Kêu gọi hành động',
                'customer_reviews' => 'Đánh giá khách hàng thật',
            ])->required()->live(),
            Textarea::make('data')
                ->label('Dữ liệu mẫu dạng JSON')
                ->helperText('Với mẫu khối, nhập các trường PageBlock. Với mẫu trang, nhập {"blocks":[...]} sử dụng các trường khối.')
                ->required()
                ->rules(['json'])
                ->formatStateUsing(fn (?array $state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                ->dehydrateStateUsing(fn (string $state): array => json_decode($state, true, 512, JSON_THROW_ON_ERROR))
                ->rows(14)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tên mẫu')->searchable()->sortable(),
                TextColumn::make('type')->label('Loại')->badge(),
                TextColumn::make('author.name')->label('Người tạo'),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBlockPresets::route('/')];
    }
}
