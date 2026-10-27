<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\ManagePosts;
use App\Models\Post;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $modelLabel = 'bài viết';

    protected static ?string $pluralModelLabel = 'Tin tức';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255),
            TextInput::make('slug')->label('Đường dẫn')->required()->alphaDash()->unique(ignoreRecord: true)->maxLength(255),
            Textarea::make('excerpt')->label('Mô tả ngắn')->rows(3)->columnSpanFull(),
            TextInput::make('source_url')->label('Nguồn bài viết')->url()->maxLength(2048)->rules(['regex:/^https:\/\/([a-z0-9-]+\.)*toyota\.com\.vn\//i'])->required(fn (Get $get): bool => $get('status') === 'published')->helperText('Bài lấy dữ kiện từ Toyota Việt Nam cần dẫn tới bài gốc chính thức.'),
            FileUpload::make('cover_image_path')->label('Ảnh bìa')->image()->disk('public')->directory('posts/covers')->visibility('public')->maxSize(3072)->imageEditor()->imageResizeMode('cover')->imageResizeTargetWidth('1200')->imageResizeTargetHeight('675')->required(fn (Get $get): bool => $get('status') === 'published')->helperText('Bắt buộc trước khi xuất bản. Tối đa 3 MB, tỉ lệ 16:9; chỉ dùng ảnh đại lý sở hữu hoặc được cấp quyền.'),
            TextInput::make('cover_image_alt')->label('Mô tả ảnh (alt)')->maxLength(255)->required(fn (Get $get): bool => $get('status') === 'published')->helperText('Mô tả ngắn, đúng nội dung ảnh để hỗ trợ người dùng đọc màn hình và SEO.'),
            TextInput::make('image_credit')->label('Nguồn / quyền sử dụng ảnh')->maxLength(255)->required(fn (Get $get): bool => $get('status') === 'published')->helperText('Ghi tác giả/đơn vị và căn cứ được phép sử dụng ảnh.'),
            RichEditor::make('body')->label('Nội dung')->fileAttachmentsDisk('public')->fileAttachmentsDirectory('posts/content')->fileAttachmentsVisibility('public')->fileAttachmentsMaxSize(3072)->columnSpanFull(),
            TextInput::make('seo_title')->label('SEO title')->maxLength(255),
            Textarea::make('seo_description')->label('SEO description')->rows(2)->maxLength(320),
            Select::make('status')->label('Trạng thái')->options([
                'draft' => 'Bản nháp',
                'published' => 'Đã xuất bản',
            ])->required()->default('draft'),
            DateTimePicker::make('published_at')->label('Thời điểm đăng'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Tiêu đề')->searchable()->sortable(),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('published_at')->label('Ngày đăng')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('cover_image_path')->label('Ảnh bìa')->state(fn (Post $record): string => filled($record->cover_image_path) ? 'Đã có' : 'Thiếu')->badge()->color(fn (string $state): string => $state === 'Đã có' ? 'success' : 'warning'),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options([
                    'draft' => 'Bản nháp',
                    'published' => 'Đã xuất bản',
                ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePosts::route('/')];
    }
}
