<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\PageRevisions\PageRevisionResource;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ManagePages;
use App\Models\BlockPreset;
use App\Models\Page;
use App\Models\PageDraft;
use App\Models\PageRevision;
use App\Models\Vehicle;
use App\Models\VehicleLine;
use App\Services\ImageUploadGuidance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'trang';

    protected static ?string $pluralModelLabel = 'Trình dựng trang';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    private const BLOCK_TYPES = [
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
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin và xuất bản')->schema([
                TextInput::make('title')->label('Tên trang')->required()->maxLength(255)->live(debounce: 1500),
                TextInput::make('slug')->label('Đường dẫn')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(255)->live(debounce: 1500),
                TextInput::make('seo_title')
                    ->label('SEO title')
                    ->required(fn (Get $get): bool => in_array($get('status'), ['published', 'scheduled'], true))
                    ->maxLength(255)
                    ->live(debounce: 1500),
                Textarea::make('seo_description')
                    ->label('SEO description')
                    ->required(fn (Get $get): bool => in_array($get('status'), ['published', 'scheduled'], true))
                    ->rows(2)
                    ->maxLength(320)
                    ->live(debounce: 1500),
                Select::make('status')->label('Trạng thái')->options([
                    'draft' => 'Bản nháp',
                    'scheduled' => 'Lên lịch xuất bản',
                    'published' => 'Đã xuất bản',
                ])->required()->default('draft')->live(debounce: 1000),
                DateTimePicker::make('published_at')
                    ->label('Thời điểm xuất bản')
                    ->visible(fn (Get $get): bool => in_array($get('status'), ['scheduled', 'published'], true))
                    ->live(debounce: 1000),
            ])->columns(2),
            Repeater::make('blocks')
                ->label('Các khối nội dung')
                ->relationship()
                ->orderColumn('sort_order')
                ->reorderableWithDragAndDrop()
                ->collapsible()
                ->cloneable()
                ->live(debounce: 1500)
                ->itemLabel(fn (array $state): ?string => self::BLOCK_TYPES[$state['type'] ?? ''] ?? 'Khối nội dung')
                ->schema([
                    Select::make('type')
                        ->label('Loại khối')
                        ->options(self::BLOCK_TYPES)
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('title')->label('Tiêu đề')->maxLength(255)->live(debounce: 1500),
                    TextInput::make('description')->label('Mô tả ngắn')->maxLength(500)->live(debounce: 1500),
                    RichEditor::make('body')
                        ->label('Nội dung')
                        ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'link', 'h2', 'h3', 'blockquote'])
                        ->columnSpanFull()
                        ->live(debounce: 1500),
                    FileUpload::make('image_path')
                        ->label(fn (Get $get): string => $get('type') === 'hero' ? 'Ảnh banner dự phòng' : 'Ảnh nền / ảnh khối')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('page-blocks')
                        ->visibility('public')
                        ->live(debounce: 1500)
                        ->helperText(fn (Get $get): string => $get('type') === 'hero'
                            ? 'Ảnh này chỉ dùng khi chưa có ảnh trong mục Ảnh trình chiếu banner bên dưới.'
                            : 'Tự chuyển WebP và tạo ảnh responsive; ưu tiên ảnh ngang 16:9, tối đa 5 MB.')
                        ->afterStateUpdated(function (mixed $state, Get $get): void {
                            if (! $state instanceof TemporaryUploadedFile) {
                                return;
                            }

                            $expectedRatio = match ($get('type')) {
                                'hero', 'promotion_strip', 'cta' => 16 / 9,
                                'text_image', 'gallery' => 4 / 3,
                                default => null,
                            };
                            $path = $state->getRealPath();
                            $warnings = is_string($path)
                                ? app(ImageUploadGuidance::class)->warnings($path, $expectedRatio)
                                : ['Không thể kiểm tra ảnh đã tải lên. Vui lòng chọn lại ảnh.'];

                            if ($warnings !== []) {
                                Notification::make()
                                    ->warning()
                                    ->title('Kiểm tra ảnh trước khi xuất bản')
                                    ->body(implode("\n", $warnings))
                                    ->persistent()
                                    ->send();
                            }
                        }),
                    TextInput::make('image_alt')
                        ->label('Văn bản thay thế ảnh (alt)')
                        ->required(fn (Get $get): bool => filled($get('image_path')))
                        ->maxLength(255)
                        ->live(debounce: 1500),
                    ColorPicker::make('background_color')->label('Màu nền')->nullable()->live(debounce: 1000),
                    Select::make('alignment')->label('Căn lề')->options([
                        'left' => 'Trái',
                        'center' => 'Giữa',
                        'right' => 'Phải',
                    ])->required()->default('left')->live(),
                    TextInput::make('spacing_top')->label('Khoảng cách trên (bậc 0-24)')->numeric()->minValue(0)->maxValue(24)->default(8)->required()->live(),
                    TextInput::make('spacing_bottom')->label('Khoảng cách dưới (bậc 0-24)')->numeric()->minValue(0)->maxValue(24)->default(8)->required()->live(),
                    TextInput::make('cta_label')->label('Nhãn nút')->maxLength(80)->live(debounce: 1500),
                    TextInput::make('cta_url')
                        ->label('Liên kết nút (URL https://, tel:, hoặc đường dẫn /...)')
                        ->maxLength(2048)
                        ->rules(['nullable', 'regex:/^(https:\/\/|tel:|mailto:|\/)[^\s]*$/i'])
                        ->live(debounce: 1500),
                    Select::make('cta_style')->label('Kiểu nút')->options([
                        'primary' => 'Chính',
                        'secondary' => 'Phụ',
                        'link' => 'Liên kết chữ',
                    ])->default('primary')->required()->live(),
                    Toggle::make('show_desktop')->label('Hiện trên máy tính')->default(true)->live(),
                    Toggle::make('show_mobile')->label('Hiện trên điện thoại')->default(true)->live(),
                    Toggle::make('is_enabled')->label('Đang bật')->default(true)->live(),
                    Select::make('vehicle_line_id')
                        ->label('Lọc theo dòng xe')
                        ->options(fn (): array => VehicleLine::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live()
                        ->visible(fn (Get $get): bool => $get('type') === 'vehicle_grid'),
                    Select::make('settings.vehicle_ids')
                        ->label('Chọn mẫu xe thủ công')
                        ->multiple()
                        ->options(fn (): array => Vehicle::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live()
                        ->visible(fn (Get $get): bool => $get('type') === 'vehicle_grid'),
                    Select::make('vehicle_id')
                        ->label('Mẫu xe lấy thông số')
                        ->options(fn (): array => Vehicle::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live()
                        ->visible(fn (Get $get): bool => $get('type') === 'specifications'),
                    TextInput::make('settings.video_url')
                        ->label('Đường dẫn video (YouTube / Vimeo)')
                        ->url()
                        ->maxLength(2048)
                        ->live(debounce: 1500)
                        ->visible(fn (Get $get): bool => $get('type') === 'gallery'),
                    Repeater::make('settings.images')
                        ->label('Ảnh thư viện')
                        ->schema([
                            FileUpload::make('path')->label('Ảnh')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->disk('public')->directory('page-blocks')->helperText('Tự chuyển WebP và tạo ảnh responsive; ưu tiên ảnh ngang 16:9, tối đa 5 MB.')->required(),
                            TextInput::make('alt')->label('Văn bản thay thế (alt)')->required()->maxLength(255),
                        ])
                        ->live(debounce: 1500)
                        ->visible(fn (Get $get): bool => $get('type') === 'gallery')
                        ->columnSpanFull(),
                    Repeater::make('settings.hero_images')
                        ->label('Ảnh trình chiếu banner')
                        ->schema([
                            FileUpload::make('path')
                                ->label('Ảnh')
                                ->image()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(5120)
                                ->disk('public')
                                ->directory('page-blocks')
                                ->helperText('Tự chuyển WebP, tạo ảnh responsive. Thêm nhiều ảnh để khách có thể vuốt trên điện thoại hoặc dùng nút điều hướng; ưu tiên ảnh ngang 16:9, tối đa 5 MB.')
                                ->required(),
                            TextInput::make('alt')
                                ->label('Văn bản thay thế (alt)')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->live(debounce: 1500)
                        ->visible(fn (Get $get): bool => $get('type') === 'hero')
                        ->columnSpanFull(),
                    Repeater::make('settings.items')
                        ->label('Các câu hỏi')
                        ->schema([
                            TextInput::make('question')->label('Câu hỏi')->required()->maxLength(500),
                            Textarea::make('answer')->label('Câu trả lời')->required()->maxLength(5000),
                        ])
                        ->live(debounce: 1500)
                        ->visible(fn (Get $get): bool => $get('type') === 'faq')
                        ->columnSpanFull(),
                    Repeater::make('settings.reviews')
                        ->label('Đánh giá thực tế do khách hàng cung cấp')
                        ->schema([
                            TextInput::make('customer_name')->label('Tên khách hàng')->required()->maxLength(255),
                            DateTimePicker::make('review_date')->label('Ngày đánh giá')->required(),
                            Textarea::make('review')->label('Nội dung đánh giá nguyên văn')->required()->maxLength(3000),
                            Toggle::make('confirmed_authentic')->label('Xác nhận đây là đánh giá thực, có thể kiểm chứng và được phép công bố')->accepted()->required(),
                        ])
                        ->live(debounce: 1500)
                        ->visible(fn (Get $get): bool => $get('type') === 'customer_reviews')
                        ->columnSpanFull(),
                    TextInput::make('settings.conditions')
                        ->label('Điều kiện ưu đãi đã xác nhận')
                        ->maxLength(1000)
                        ->live(debounce: 1500)
                        ->visible(fn (Get $get): bool => $get('type') === 'promotion_strip'),
                    DateTimePicker::make('settings.ends_at')
                        ->label('Thời điểm kết thúc ưu đãi')
                        ->visible(fn (Get $get): bool => $get('type') === 'promotion_strip')
                        ->live(debounce: 1000),
                    TextInput::make('settings.maps_url')
                        ->label('Đường dẫn Google Maps')
                        ->url()
                        ->maxLength(2048)
                        ->live(debounce: 1500)
                        ->visible(fn (Get $get): bool => $get('type') === 'showroom_map'),
                ])
                ->columnSpanFull()
                ->addActionLabel('Thêm khối nội dung')
                ->deleteAction(fn (Action $action): Action => $action->requiresConfirmation()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Trang')->searchable()->sortable(),
                TextColumn::make('slug')->label('Đường dẫn'),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('published_at')->label('Thời điểm xuất bản')->dateTime('d/m/Y H:i')->placeholder('Chưa lên lịch'),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options([
                    'draft' => 'Bản nháp',
                    'scheduled' => 'Lên lịch xuất bản',
                    'published' => 'Đã xuất bản',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('preview')
                    ->label('Xem trước')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Page $record): string => route('pages.preview', $record))
                    ->openUrlInNewTab(),
                Action::make('history')
                    ->label('Lịch sử phiên bản')
                    ->icon(Heroicon::OutlinedClock)
                    ->url(fn (Page $record): string => PageRevisionResource::getUrl('index', [
                        'tableFilters' => ['page_id' => ['value' => $record->id]],
                    ])),
                Action::make('load_preset')
                    ->label('Dùng mẫu')
                    ->icon(Heroicon::OutlinedRectangleStack)
                    ->schema([
                        Select::make('preset_id')
                            ->label('Mẫu nội dung')
                            ->options(fn (): array => BlockPreset::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->successNotificationTitle('Mẫu đã được thêm vào bản nháp riêng. Xem trước rồi lưu để áp dụng.')
                    ->action(function (Page $record, array $data): void {
                        Gate::authorize('update', $record);
                        $preset = BlockPreset::query()->findOrFail($data['preset_id']);
                        $snapshot = $record->revisionSnapshot();
                        $blocks = $snapshot['blocks'];
                        $presetBlocks = $preset->type === 'page'
                            ? ($preset->data['blocks'] ?? [])
                            : [[...$preset->data, 'type' => $preset->type]];
                        $sortOrder = $blocks === [] ? 0 : max(array_column($blocks, 'sort_order')) + 1;

                        foreach ($presetBlocks as $block) {
                            $blocks[] = [...$block, 'sort_order' => $sortOrder++];
                        }

                        PageDraft::updateOrCreate(
                            ['page_id' => $record->id],
                            [
                                'user_id' => auth()->id(),
                                'payload' => [...$snapshot['page'], 'blocks' => $blocks],
                            ],
                        );
                    }),
                Action::make('restore_revision')
                    ->label('Khôi phục phiên bản')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Select::make('revision_id')
                            ->label('Phiên bản')
                            ->options(fn (Page $record): array => $record->revisions()
                                ->latest()
                                ->limit(30)
                                ->get()
                                ->mapWithKeys(fn (PageRevision $revision): array => [
                                    $revision->id => sprintf(
                                        '%s — %s — %s',
                                        $revision->created_at->format('d/m/Y H:i'),
                                        $revision->user?->name ?? 'Tài khoản đã xóa',
                                        $revision->summary ?? 'Bản lưu',
                                    ),
                                ])->all())
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Page $record, array $data): void {
                        Gate::authorize('update', $record);
                        $revision = $record->revisions()->findOrFail($data['revision_id']);
                        $record->restoreRevision($revision);
                    }),
                Action::make('edit')
                    ->label('Chỉnh sửa')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Page $record): string => static::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePages::route('/'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
