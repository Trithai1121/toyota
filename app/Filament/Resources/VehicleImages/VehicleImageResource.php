<?php

namespace App\Filament\Resources\VehicleImages;

use App\Filament\Resources\VehicleImages\Pages\ManageVehicleImages;
use App\Models\VehicleImage;
use App\Services\ImageUploadGuidance;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class VehicleImageResource extends Resource
{
    protected static ?string $model = VehicleImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $modelLabel = 'ảnh xe';

    protected static ?string $pluralModelLabel = 'Thư viện ảnh xe';

    protected static string|UnitEnum|null $navigationGroup = 'Danh mục xe';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vehicle_id')->label('Mẫu xe')->relationship('vehicle', 'name')->searchable()->preload()->required(),
            FileUpload::make('path')
                ->label('Ảnh xe')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(5120)
                ->disk('public')
                ->directory('vehicles')
                ->visibility('public')
                ->afterStateUpdated(function (mixed $state): void {
                    if (! $state instanceof TemporaryUploadedFile) {
                        return;
                    }

                    $path = $state->getRealPath();
                    $warnings = is_string($path)
                        ? app(ImageUploadGuidance::class)->warnings($path, 16 / 10)
                        : ['Không thể kiểm tra ảnh đã tải lên. Vui lòng chọn lại ảnh.'];

                    if ($warnings !== []) {
                        Notification::make()
                            ->warning()
                            ->title('Kiểm tra ảnh trước khi xuất bản')
                            ->body(implode("\n", $warnings))
                            ->persistent()
                            ->send();
                    }
                })
                ->helperText('Tự chuyển WebP và tạo ảnh responsive; ưu tiên ảnh ngang 16:10, tối đa 5 MB.')
                ->required(),
            TextInput::make('alt_text')->label('Mô tả ảnh (alt)')->maxLength(255),
            TextInput::make('color_name')
                ->label('Tên màu xe')
                ->maxLength(80)
                ->required(fn (Get $get): bool => filled($get('color_hex')))
                ->helperText('Tùy chọn. Nhập tên màu để ảnh được chọn theo màu xe.'),
            TextInput::make('color_hex')
                ->label('Mã màu')
                ->regex('/^#[0-9a-fA-F]{6}$/')
                ->maxLength(7)
                ->required(fn (Get $get): bool => filled($get('color_name')))
                ->helperText('Nhập mã HEX dạng #RRGGBB; cần điền cùng tên màu.')
                ->placeholder('#D1D5DB'),
            Select::make('category')
                ->label('Khu vực ảnh')
                ->options([
                    'overview' => 'Ảnh tổng quan / màu xe',
                    'exterior' => 'Ngoại thất',
                    'interior' => 'Nội thất',
                    'performance' => 'Vận hành',
                    'safety' => 'An toàn',
                    'gallery' => 'Hình ảnh thực tế',
                ])
                ->default('gallery')
                ->helperText('Ảnh sẽ hiển thị ngay trong khu vực tương ứng của trang xe.'),
            TextInput::make('sort_order')->label('Thứ tự')->numeric()->minValue(0)->default(0)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')->label('Ảnh')->disk('public'),
                TextColumn::make('vehicle.name')->label('Mẫu xe')->searchable(),
                TextColumn::make('alt_text')->label('Mô tả ảnh'),
                TextColumn::make('color_name')->label('Màu xe'),
                TextColumn::make('category')->label('Khu vực')->formatStateUsing(fn (?string $state): string => match ($state) {
                    'overview' => 'Tổng quan',
                    'exterior' => 'Ngoại thất',
                    'interior' => 'Nội thất',
                    'performance' => 'Vận hành',
                    'safety' => 'An toàn',
                    'gallery' => 'Thư viện',
                    default => 'Chưa phân loại',
                }),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->filters([SelectFilter::make('vehicle_id')->label('Mẫu xe')->relationship('vehicle', 'name')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVehicleImages::route('/')];
    }
}
