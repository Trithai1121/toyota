<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\ThemeSettings\Pages\ManageThemeSettings;
use App\Filament\Resources\ThemeSettings\ThemeSettingResource;
use App\Filament\Resources\TrackingSettingAudits\TrackingSettingAuditResource;
use App\Models\Page;
use App\Models\PageDraft;
use App\Models\ThemeSetting;
use App\Models\TrackingSettingAudit;
use App\Models\UiString;
use App\Models\User;
use App\Services\ImageUploadGuidance;
use App\Services\PagePublicationChecklist;
use App\Services\PageRevisionDiff;
use App\Services\ResponsiveImageProcessor;
use App\Services\SiteTheme;
use Database\Seeders\ContentPagesSeeder;
use Database\Seeders\ThemeSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PageBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_can_manage_pages_but_sales_cannot(): void
    {
        $this->createRoles();
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');
        $sale = User::factory()->create();
        $sale->assignRole('sale');

        $this->assertTrue(Gate::forUser($marketing)->allows('create', Page::class));
        $this->assertFalse(Gate::forUser($sale)->allows('create', Page::class));
    }

    public function test_only_content_editors_can_preview_a_draft_page(): void
    {
        $this->createRoles();
        $page = Page::create([
            'title' => 'Trang nháp',
            'slug' => 'trang-nhap',
            'status' => 'draft',
        ]);
        $block = $page->blocks()->create(['type' => 'hero', 'title' => 'Nội dung cần sửa']);
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');
        $sale = User::factory()->create();
        $sale->assignRole('sale');
        UiString::create([
            'key' => 'preview.banner',
            'value' => 'Bản xem trước — nội dung chưa được xuất bản',
        ]);
        UiString::create(['key' => 'page.edit_block', 'value' => 'Sửa khối này']);

        $this->actingAs($marketing)
            ->get(route('pages.preview', $page))
            ->assertOk()
            ->assertSee('Bản xem trước')
            ->assertSee('Sửa khối này')
            ->assertSee('/admin/pages/'.$page->id.'/edit?block='.$block->id, false);

        $this->actingAs($sale)
            ->get(route('pages.preview', $page))
            ->assertForbidden();
    }

    public function test_page_block_removes_active_html_and_unsafe_links(): void
    {
        $page = Page::create([
            'title' => 'Trang',
            'slug' => 'trang',
            'status' => 'draft',
        ]);

        $block = $page->blocks()->create([
            'type' => 'text_image',
            'body' => '<p>Hợp lệ</p><script>alert(1)</script><a href="javascript:alert(1)" onclick="alert(2)">liên kết</a><a href="//evil.example">liên kết ngoài</a>',
        ]);

        $this->assertStringNotContainsString('<script', $block->body);
        $this->assertStringNotContainsString('javascript:', $block->body);
        $this->assertStringNotContainsString('onclick', $block->body);
        $this->assertStringNotContainsString('href="//evil.example"', $block->body);
        $this->assertStringContainsString('<p>Hợp lệ</p>', $block->body);
    }

    public function test_uploaded_page_images_are_optimized_to_responsive_webp(): void
    {
        Storage::fake('public');
        $source = imagecreatetruecolor(1000, 500);
        ob_start();
        imagejpeg($source, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($source);
        Storage::disk('public')->put('page-blocks/upload.jpg', $jpeg);

        $page = Page::create([
            'title' => 'Trang có ảnh',
            'slug' => 'trang-co-anh',
            'status' => 'draft',
        ]);
        $block = $page->blocks()->create([
            'type' => 'hero',
            'image_path' => 'page-blocks/upload.jpg',
            'image_alt' => 'Ảnh minh họa',
        ])->fresh();

        $this->assertStringEndsWith('.webp', $block->image_path);
        Storage::disk('public')->assertExists($block->image_path);
        Storage::disk('public')->assertMissing('page-blocks/upload.jpg');
        $this->assertStringContainsString('480w', app(ResponsiveImageProcessor::class)->srcset($block->image_path));
        $this->assertStringContainsString('960w', app(ResponsiveImageProcessor::class)->srcset($block->image_path));
    }

    public function test_hero_gallery_uploads_are_optimized_to_responsive_webp(): void
    {
        Storage::fake('public');
        $heroImages = [];

        foreach (['banner-mot.png', 'banner-hai.png'] as $index => $path) {
            $image = imagecreatetruecolor(600, 300);
            imagefill($image, 0, 0, imagecolorallocate($image, 100 + 50 * $index, 50, 80));
            ob_start();
            imagepng($image);
            $png = ob_get_clean();
            imagedestroy($image);
            Storage::disk('public')->put("page-blocks/{$path}", $png);
            $heroImages[] = [
                'path' => "page-blocks/{$path}",
                'alt' => 'Banner '.($index + 1),
            ];
        }

        $page = Page::create([
            'title' => 'Trang chủ',
            'slug' => 'home',
            'status' => 'published',
            'is_published' => true,
        ]);
        $block = $page->blocks()->create([
            'type' => 'hero',
            'settings' => ['hero_images' => $heroImages],
        ])->fresh();

        foreach ($block->settings['hero_images'] as $image) {
            $this->assertStringEndsWith('.webp', $image['path']);
            Storage::disk('public')->assertExists($image['path']);
            $this->assertStringContainsString('480w', app(ResponsiveImageProcessor::class)->srcset($image['path']));
        }
    }

    public function test_saved_brand_logo_is_optimized_and_rendered_from_public_storage(): void
    {
        Storage::fake('public');
        $image = imagecreatetruecolor(600, 300);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        Storage::disk('public')->put('theme/dealer-logo.png', $png);
        $logoPath = app(ResponsiveImageProcessor::class)->process('theme/dealer-logo.png');

        ThemeSetting::create([
            'key' => 'brand',
            'value' => [
                'site_name' => 'Toyota Kon Tum',
                'logo_path' => $logoPath,
                'logo_alt' => 'Logo đại lý thử nghiệm',
            ],
        ]);

        $brandSettings = ThemeSetting::query()->where('key', 'brand')->firstOrFail();
        $this->assertSame($logoPath, $brandSettings->value['logo_path']);
        Storage::disk('public')->assertExists($logoPath);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('src="/storage/'.$logoPath.'"', false)
            ->assertSee('alt="Logo đại lý thử nghiệm"', false);
    }

    public function test_homepage_renders_multiple_hero_images_above_the_hero_copy(): void
    {
        Storage::fake('public');
        foreach (['banner-mau-mot', 'banner-mau-hai'] as $name) {
            Storage::disk('public')->put("page-blocks/{$name}.webp", 'webp');
            Storage::disk('public')->put("page-blocks/{$name}.json", json_encode(['widths' => []]));
        }

        $page = Page::create([
            'title' => 'Trang chủ',
            'slug' => 'home',
            'status' => 'published',
            'is_published' => true,
        ]);
        $page->blocks()->create([
            'type' => 'hero',
            'title' => 'Tiêu đề trang chủ',
            'settings' => [
                'hero_images' => [
                    ['path' => 'page-blocks/banner-mau-mot.webp', 'alt' => 'Banner mẫu một'],
                    ['path' => 'page-blocks/banner-mau-hai.webp', 'alt' => 'Banner mẫu hai'],
                ],
            ],
            'sort_order' => 1,
        ]);
        $page->blocks()->create([
            'type' => 'hero',
            'title' => 'Slide trang chủ thứ hai',
            'sort_order' => 2,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Danh sách ảnh nổi bật', false)
            ->assertSee('page-block--hero-home', false)
            ->assertSee('hero-banner-content', false)
            ->assertSee('alt="Banner mẫu một"', false)
            ->assertSee('alt="Banner mẫu hai"', false)
            ->assertSee('x-data="heroImageCarousel(2, 6000)"', false)
            ->assertSee('aria-label="Ảnh tiếp theo"', false);
        $response->assertSee('aria-label="Chọn ảnh banner"', false);
        $response->assertDontSee('Tạm dừng tự động', false)
            ->assertDontSee('Phát tự động', false);
        $this->assertMatchesRegularExpression(
            '/class="hero-image-carousel[^"]*".*class="hero-copy"/s',
            $response->getContent(),
        );
    }

    public function test_marketing_can_view_page_revisions_but_sales_cannot(): void
    {
        $this->createRoles();
        $page = Page::create([
            'title' => 'Trang lưu lịch sử',
            'slug' => 'trang-luu-lich-su',
            'status' => 'draft',
        ]);
        $revision = $page->createRevision();
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');
        $sale = User::factory()->create();
        $sale->assignRole('sale');

        $this->assertTrue(Gate::forUser($marketing)->allows('view', $revision));
        $this->assertFalse(Gate::forUser($sale)->allows('view', $revision));
    }

    public function test_only_admin_can_change_tracking_ids_and_changes_are_audited(): void
    {
        $this->createRoles();
        $setting = ThemeSetting::create([
            'key' => 'tracking',
            'value' => ['ga4_id' => 'G-OLD123'],
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');

        $this->assertTrue(Gate::forUser($admin)->allows('update', $setting));
        $this->assertFalse(Gate::forUser($marketing)->allows('update', $setting));

        $this->actingAs($marketing);
        $this->assertFalse(ThemeSettingResource::canCreate());
        $this->assertFalse(TrackingSettingAuditResource::canViewAny());

        $this->actingAs($admin);
        $this->assertTrue(ThemeSettingResource::canCreate());
        $this->assertTrue(TrackingSettingAuditResource::canViewAny());
        $setting->update(['value' => ['ga4_id' => 'G-NEW456']]);

        $audit = TrackingSettingAudit::query()->firstOrFail();
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame(['ga4_id' => 'G-OLD123'], $audit->before);
        $this->assertSame(['ga4_id' => 'G-NEW456'], $audit->after);
        $this->assertSame('G-NEW456', app(SiteTheme::class)->all()['tracking']['ga4_id']);
    }

    public function test_admin_can_save_dealer_settings_and_see_them_on_the_homepage(): void
    {
        $this->createRoles();
        config()->set('auth.admin_mfa_required', false);
        $this->seed([ContentPagesSeeder::class, ThemeSettingsSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $dealer = ThemeSetting::query()->where('key', 'dealer')->firstOrFail();
        $updatedValue = array_replace($dealer->value, [
            'name' => 'Đại lý kiểm tra lưu',
            'address' => '99 Đường Kiểm Tra, Kon Tum',
            'hotline' => '02601234567',
            'zalo' => 'https://zalo.me/02601234567',
            'email' => 'contact@example.test',
            'hours' => 'Thứ Hai - Thứ Bảy, 08:00 - 17:00',
            'maps_url' => 'https://maps.google.com/?q=Kon+Tum+Test',
            'maps_embed_url' => 'https://www.google.com/maps/embed?pb=kon-tum-test',
            'service_area' => 'Kon Tum, Gia Lai',
            'service_regions' => ['Kon Tum', 'Gia Lai'],
        ]);
        app(SiteTheme::class)->all();

        $editForm = Livewire::actingAs($admin)
            ->test(ManageThemeSettings::class)
            ->mountTableAction('edit', $dealer);
        $editForm->assertTableActionDataSet([
            'value.name' => $dealer->value['name'],
            'value.address' => $dealer->value['address'],
        ]);

        Livewire::actingAs($admin)
            ->test(ManageThemeSettings::class)
            ->callTableAction('edit', $dealer, [
                'key' => 'dealer',
                'description' => $dealer->description,
                'value' => $updatedValue,
            ])
            ->assertNotified();

        $this->assertSame($updatedValue, $dealer->fresh()->value);
        $this->assertSame($updatedValue, app(SiteTheme::class)->all()['dealer']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Đại lý kiểm tra lưu')
            ->assertSee('99 Đường Kiểm Tra, Kon Tum')
            ->assertSee('class="showroom-map-embed"', false)
            ->assertSee('src="https://www.google.com/maps/embed?pb=kon-tum-test"', false)
            ->assertSee('href="https://maps.google.com/?q=Kon+Tum+Test"', false);
    }

    public function test_admin_can_create_custom_theme_settings_from_json(): void
    {
        $this->createRoles();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ManageThemeSettings::class)
            ->callAction('create', [
                'key' => 'custom_banner',
                'description' => 'Thiết lập banner tùy chỉnh',
                'settings_json' => '{"enabled":true,"label":"Thông báo"}',
            ])
            ->assertNotified();

        $setting = ThemeSetting::query()->where('key', 'custom_banner')->firstOrFail();
        $this->assertSame([
            'enabled' => true,
            'label' => 'Thông báo',
        ], $setting->value);
    }

    public function test_page_revision_diff_lists_changed_fields(): void
    {
        $differences = app(PageRevisionDiff::class)->compare(
            ['title' => 'Tiêu đề cũ', 'blocks' => [['title' => 'Khối cũ']]],
            ['title' => 'Tiêu đề mới', 'blocks' => [['title' => 'Khối mới']]],
        );

        $this->assertSame([
            [
                'path' => 'title',
                'before' => 'Tiêu đề cũ',
                'after' => 'Tiêu đề mới',
            ],
            [
                'path' => 'blocks.0.title',
                'before' => 'Khối cũ',
                'after' => 'Khối mới',
            ],
        ], $differences);
    }

    public function test_image_upload_guidance_warns_about_incorrect_aspect_ratio(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'image-guidance-');
        $image = imagecreatetruecolor(1000, 500);

        try {
            imagejpeg($image, $path);

            $warnings = app(ImageUploadGuidance::class)->warnings($path, 16 / 9);

            $this->assertCount(1, $warnings);
            $this->assertStringContainsString('Tỉ lệ ảnh hiện tại', $warnings[0]);
        } finally {
            imagedestroy($image);
            unlink($path);
        }
    }

    public function test_page_revision_restores_previous_page_and_block_data(): void
    {
        $page = Page::create([
            'title' => 'Tiêu đề ban đầu',
            'slug' => 'trang-phien-ban',
            'status' => 'draft',
        ]);
        $page->blocks()->create(['type' => 'hero', 'title' => 'Banner ban đầu', 'sort_order' => 1]);
        $revision = $page->createRevision(summary: 'Phiên bản ban đầu');
        $page->update(['title' => 'Tiêu đề mới']);
        $page->blocks()->first()->update(['title' => 'Banner mới']);

        $page->restoreRevision($revision);
        $page->refresh();

        $this->assertSame('Tiêu đề ban đầu', $page->title);
        $this->assertFalse($page->is_published);
        $this->assertSame('Banner ban đầu', $page->blocks()->firstOrFail()->title);
        $this->assertDatabaseHas('page_revisions', [
            'page_id' => $page->id,
            'summary' => 'Khôi phục phiên bản',
        ]);
    }

    public function test_page_editor_auto_saves_and_restores_a_draft_without_changing_live_content(): void
    {
        $this->createRoles();
        $page = Page::create([
            'title' => 'Tiêu đề đang xuất bản',
            'slug' => 'trang-tu-dong-luu',
            'seo_title' => 'Tiêu đề SEO',
            'seo_description' => 'Mô tả SEO',
            'status' => 'published',
        ]);
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');

        Livewire::actingAs($marketing)
            ->test(EditPage::class, ['record' => $page->id])
            ->set('data.title', 'Tiêu đề bản nháp')
            ->set('data.seo_title', 'SEO bản nháp');

        $this->assertSame('Tiêu đề đang xuất bản', $page->fresh()->title);
        $this->assertDatabaseHas('page_drafts', [
            'page_id' => $page->id,
            'user_id' => $marketing->id,
        ]);
        $this->actingAs($marketing)
            ->get(route('pages.preview', $page))
            ->assertOk()
            ->assertSee('SEO bản nháp');

        Livewire::actingAs($marketing)
            ->test(EditPage::class, ['record' => $page->id])
            ->assertSet('data.title', 'Tiêu đề bản nháp')
            ->call('save', shouldRedirect: false, shouldSendSavedNotification: false);

        $this->assertSame('Tiêu đề bản nháp', $page->fresh()->title);
        $this->assertDatabaseMissing('page_drafts', ['page_id' => $page->id]);
    }

    public function test_page_editor_cannot_publish_without_seo_metadata(): void
    {
        $this->createRoles();
        $page = Page::create([
            'title' => 'Trang nháp',
            'slug' => 'trang-thieu-seo',
            'status' => 'draft',
        ]);
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');

        Livewire::actingAs($marketing)
            ->test(EditPage::class, ['record' => $page->id])
            ->set('data.status', 'published')
            ->assertSet('data.status', 'published')
            ->call('save', shouldRedirect: false, shouldSendSavedNotification: false)
            ->assertHasErrors(['data.seo_title', 'data.seo_description']);

        $this->assertSame('draft', $page->fresh()->status);
    }

    public function test_preview_uses_private_draft_blocks_and_sanitizes_draft_html(): void
    {
        $this->createRoles();
        $page = Page::create([
            'title' => 'Trang đã xuất bản',
            'slug' => 'trang-preview-ban-nhap',
            'seo_title' => 'Trang đã xuất bản',
            'seo_description' => 'Trang đã xuất bản',
            'status' => 'published',
        ]);
        $block = $page->blocks()->create([
            'type' => 'text_image',
            'title' => 'Tiêu đề đã lưu',
            'body' => '<p>Nội dung đã lưu</p>',
        ]);
        $marketing = User::factory()->create();
        $marketing->assignRole('marketing');

        PageDraft::create([
            'page_id' => $page->id,
            'user_id' => $marketing->id,
            'payload' => [
                'title' => 'Tiêu đề nháp',
                'slug' => $page->slug,
                'seo_title' => 'SEO nháp',
                'seo_description' => 'Mô tả nháp',
                'status' => 'published',
                'blocks' => [
                    [
                        'id' => $block->id,
                        'type' => 'text_image',
                        'title' => 'Tiêu đề khối nháp',
                        'body' => '<p>Nội dung nháp</p><script>alert(1)</script>',
                        'is_enabled' => true,
                        'sort_order' => 0,
                    ],
                ],
            ],
        ]);

        $this->actingAs($marketing)
            ->get(route('pages.preview', $page))
            ->assertOk()
            ->assertSee('SEO nháp')
            ->assertSee('Tiêu đề khối nháp')
            ->assertSee('Nội dung nháp')
            ->assertDontSee('alert(1)', false);

        $this->assertSame('Trang đã xuất bản', $page->fresh()->title);
        $this->assertSame('Tiêu đề đã lưu', $page->blocks()->firstOrFail()->title);
    }

    public function test_publication_checklist_rejects_broken_links_and_incomplete_promotions(): void
    {
        try {
            app(PagePublicationChecklist::class)->validate([
                'status' => 'published',
                'seo_title' => 'SEO title',
                'seo_description' => 'SEO description',
                'blocks' => [
                    ['type' => 'cta', 'cta_url' => '/not-a-real-page'],
                    ['type' => 'promotion_strip', 'settings' => []],
                ],
            ]);
            $this->fail('The publication checklist accepted invalid content.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $this->assertArrayHasKey('blocks.0.cta_url', $errors);
            $this->assertArrayHasKey('blocks.1.settings.conditions', $errors);
            $this->assertArrayHasKey('blocks.1.settings.ends_at', $errors);
        }
    }

    public function test_publication_checklist_rejects_an_invalid_configured_dealer_phone(): void
    {
        ThemeSetting::create([
            'key' => 'dealer',
            'value' => ['hotline' => '12345'],
        ]);

        try {
            app(PagePublicationChecklist::class)->validate([
                'status' => 'published',
                'seo_title' => 'SEO title',
                'seo_description' => 'SEO description',
                'blocks' => [],
            ]);
            $this->fail('The publication checklist accepted an invalid dealer phone.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('dealer.hotline', $exception->errors());
        }
    }

    public function test_scheduled_page_is_published_after_its_publish_time(): void
    {
        $page = Page::create([
            'title' => 'Trang đã lên lịch',
            'slug' => 'trang-len-lich',
            'status' => 'scheduled',
            'published_at' => now()->subMinute(),
        ]);

        Artisan::call('pages:publish-scheduled');
        $page->refresh();

        $this->assertSame('published', $page->status);
        $this->assertTrue($page->is_published);
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'status' => 'published']);
    }

    private function createRoles(): void
    {
        foreach (['admin', 'marketing', 'sale'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }
}
