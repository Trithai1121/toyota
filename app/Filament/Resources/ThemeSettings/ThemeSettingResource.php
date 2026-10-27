<?php

namespace App\Filament\Resources\ThemeSettings;

use App\Filament\Resources\ThemeSettings\Pages\ManageThemeSettings;
use App\Models\ThemeSetting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ThemeSettingResource extends Resource
{
    protected static ?string $model = ThemeSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $modelLabel = 'thiết lập giao diện';

    protected static ?string $pluralModelLabel = 'Cài đặt website';

    protected static string|UnitEnum|null $navigationGroup = 'Giao diện';

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->label('Khóa (ví dụ: brand, header, footer)')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(100)->live()->disabledOn('edit'),
            TextInput::make('description')->label('Mô tả')->maxLength(255),
            Textarea::make('settings_json')
                ->label('Thiết lập dạng JSON')
                ->helperText('Sửa các tùy chọn giao diện: màu, logo, font đã duyệt, menu, footer, thanh thông báo và nút liên hệ.')
                ->required(fn (Get $get): bool => ! in_array($get('key'), ['brand', 'dealer', 'tracking', 'floating_contact'], true))
                ->rules(['json'])
                ->default('[]')
                ->dehydrateStateUsing(fn (string $state): array => json_decode($state, true, 512, JSON_THROW_ON_ERROR))
                ->rows(16)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => ! in_array($get('key'), ['brand', 'dealer', 'tracking', 'floating_contact'], true)),
            Toggle::make('value.contact_widget_enabled')
                ->label('Bật nút Liên hệ nổi')
                ->default(true)
                ->visible(fn (Get $get): bool => $get('key') === 'floating_contact'),
            Toggle::make('value.contact_call_enabled')
                ->label('Hiện Gọi hotline')
                ->default(true)
                ->visible(fn (Get $get): bool => $get('key') === 'floating_contact'),
            Toggle::make('value.contact_zalo_enabled')
                ->label('Hiện Liên hệ Zalo')
                ->default(true)
                ->visible(fn (Get $get): bool => $get('key') === 'floating_contact'),
            Toggle::make('value.contact_quote_enabled')
                ->label('Hiện Nhận tư vấn / báo giá')
                ->default(true)
                ->visible(fn (Get $get): bool => $get('key') === 'floating_contact'),
            TextInput::make('value.ga4_id')
                ->label('GA4 Measurement ID')
                ->regex('/^(G-[A-Z0-9]+)?$/')
                ->maxLength(32)
                ->helperText('Chỉ nhập ID. Website chỉ tải công cụ sau khi khách đồng ý cookie phân tích.')
                ->visible(fn (Get $get): bool => $get('key') === 'tracking'),
            TextInput::make('value.gtm_id')
                ->label('Google Tag Manager Container ID')
                ->regex('/^(GTM-[A-Z0-9]+)?$/')
                ->maxLength(32)
                ->helperText('Chỉ nhập ID; không cho phép dán mã HTML hoặc JavaScript tùy ý.')
                ->visible(fn (Get $get): bool => $get('key') === 'tracking'),
            TextInput::make('value.meta_pixel_id')
                ->label('Meta Pixel ID')
                ->regex('/^([0-9]{5,20})?$/')
                ->maxLength(20)
                ->helperText('Chỉ nhập ID. Pixel chạy sau khi khách đồng ý cookie quảng cáo.')
                ->visible(fn (Get $get): bool => $get('key') === 'tracking'),
            TextInput::make('value.site_name')
                ->label('Tên website')
                ->maxLength(120)
                ->helperText('Tên hiển thị ở đầu trang và tiêu đề mặc định của website.')
                ->visible(fn (Get $get): bool => $get('key') === 'brand'),
            FileUpload::make('value.logo_path')
                ->label('Logo')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(5120)
                ->disk('public')
                ->directory('theme')
                ->visibility('public')
                ->helperText('Ảnh sẽ được chuyển WebP; chỉ tải tài sản có quyền sử dụng, tối đa 5 MB.')
                ->visible(fn (Get $get): bool => $get('key') === 'brand'),
            TextInput::make('value.logo_alt')
                ->label('Văn bản thay thế logo')
                ->required(fn (Get $get): bool => filled($get('value.logo_path')))
                ->maxLength(255)
                ->visible(fn (Get $get): bool => $get('key') === 'brand'),
            FileUpload::make('value.favicon_path')
                ->label('Favicon')
                ->image()
                ->acceptedFileTypes(['image/png', 'image/webp'])
                ->maxSize(1024)
                ->disk('public')
                ->directory('theme')
                ->visibility('public')
                ->visible(fn (Get $get): bool => $get('key') === 'brand'),
            ColorPicker::make('value.primary_color')->label('Màu chủ đạo')->visible(fn (Get $get): bool => $get('key') === 'brand'),
            ColorPicker::make('value.secondary_color')->label('Màu phụ')->visible(fn (Get $get): bool => $get('key') === 'brand'),
            Select::make('value.font')->label('Phông chữ đã duyệt')->options([
                'system' => 'System',
                'arial' => 'Arial',
                'georgia' => 'Georgia',
            ])->visible(fn (Get $get): bool => $get('key') === 'brand'),
            Select::make('value.button_style')->label('Kiểu nút')->options([
                'rounded' => 'Bo tròn',
                'pill' => 'Hình viên thuốc',
                'square' => 'Vuông',
            ])->visible(fn (Get $get): bool => $get('key') === 'brand'),
            TextInput::make('value.border_radius')->label('Bo góc (px)')->numeric()->minValue(0)->maxValue(48)->visible(fn (Get $get): bool => $get('key') === 'brand'),
            TextInput::make('value.content_width')->label('Độ rộng nội dung (px)')->numeric()->minValue(960)->maxValue(1920)->visible(fn (Get $get): bool => $get('key') === 'brand'),
            TextInput::make('value.name')
                ->label('Tên đại lý')
                ->maxLength(255)
                ->helperText('Hiển thị trên thanh đầu trang, chân trang và khối showroom.')
                ->visible(fn (Get $get): bool => $get('key') === 'dealer'),
            TextInput::make('value.address')
                ->label('Địa chỉ showroom')
                ->maxLength(500)
                ->helperText('Địa chỉ này được dùng ở thanh đầu trang, chân trang và khối showroom.')
                ->visible(fn (Get $get): bool => $get('key') === 'dealer'),
            TextInput::make('value.hotline')
                ->label('Hotline')
                ->tel()
                ->maxLength(24)
                ->helperText('Hiển thị trong thông tin liên hệ; dùng số có thể gọi trực tiếp.')
                ->visible(fn (Get $get): bool => $get('key') === 'dealer'),
            TextInput::make('value.zalo')
                ->label('URL Zalo')
                ->url()
                ->maxLength(2048)
                ->helperText('Nhập liên kết Zalo HTTPS; được dùng cho nút liên hệ.')
                ->visible(fn (Get $get): bool => $get('key') === 'dealer'),
            TextInput::make('value.email')->label('Email')->email()->maxLength(255)->visible(fn (Get $get): bool => $get('key') === 'dealer'),
            TextInput::make('value.hours')
                ->label('Giờ làm việc')
                ->maxLength(255)
                ->helperText('Hiển thị trong phần thông tin showroom và chân trang.')
                ->visible(fn (Get $get): bool => $get('key') === 'dealer'),
            TextInput::make('value.maps_url')
                ->label('URL Google Maps')
                ->url()
                ->maxLength(2048)
                ->helperText('Liên kết mở chỉ đường ở thẻ showroom.')
                ->visible(fn (Get $get): bool => $get('key') === 'dealer'),
            TextInput::make('value.maps_embed_url')
                ->label('URL nhúng Google Maps')
                ->url()
                ->regex('/^https:\/\/www\.google\.com\/maps\/embed\?.+$/')
                ->maxLength(4096)
                ->helperText('Dán riêng giá trị src từ Google Maps → Chia sẻ → Nhúng bản đồ. Ví dụ bắt đầu bằng https://www.google.com/maps/embed?pb=...')
                ->visible(fn (Get $get): bool => $get('key') === 'dealer'),
            TextInput::make('value.service_area')->label('Khu vực phục vụ (mô tả)')->maxLength(500)->visible(fn (Get $get): bool => $get('key') === 'dealer'),
            TagsInput::make('value.service_regions')
                ->label('Khu vực phục vụ (schema SEO)')
                ->rules(['array', 'max:20'])
                ->visible(fn (Get $get): bool => $get('key') === 'dealer'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->label('Nhóm thiết lập')->searchable()->sortable(),
                TextColumn::make('description')->label('Mô tả'),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => ($data['key'] ?? null) === 'floating_contact' ? $data : [
                        ...$data,
                        'settings_json' => json_encode($data['value'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ])
                    ->using(function (array $data, ThemeSetting $record): ThemeSetting {
                        if (array_key_exists('settings_json', $data)) {
                            $data['value'] = $data['settings_json'];
                            unset($data['settings_json']);
                        } elseif ($record->key === 'floating_contact' && is_array($data['value'] ?? null)) {
                            $data['value'] = array_replace($record->value ?? [], $data['value']);
                        }

                        $record->update($data);

                        return $record;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageThemeSettings::route('/')];
    }
}
