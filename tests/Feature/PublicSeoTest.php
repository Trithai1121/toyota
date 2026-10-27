<?php

namespace Tests\Feature;

use App\Models\CampaignLandingPage;
use App\Models\Page;
use App\Models\Promotion;
use App\Models\ThemeSetting;
use App\Models\UiString;
use App\Models\Vehicle;
use App\Models\VehicleLine;
use Database\Seeders\ContentPagesSeeder;
use Database\Seeders\ThemeSettingsSeeder;
use Database\Seeders\UiStringsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_public_site_pages_and_omits_unpublished_vehicles(): void
    {
        $line = VehicleLine::create([
            'name' => 'Sedan',
            'slug' => 'sedan',
            'is_published' => true,
        ]);
        Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Published model',
            'slug' => 'published-model',
            'is_published' => true,
        ]);
        Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Draft model',
            'slug' => 'draft-model',
            'is_published' => false,
        ]);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('vehicles.show', 'published-model'), false)
            ->assertDontSee('draft-model');
    }

    public function test_robots_file_excludes_private_forms_and_points_to_sitemap(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /dang-ky')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_homepage_omits_expired_promotions_and_displays_current_promotions(): void
    {
        $this->seed([ContentPagesSeeder::class, UiStringsSeeder::class]);

        Promotion::create([
            'title' => 'Ưu đãi đã hết hạn',
            'slug' => 'uu-dai-het-han',
            'ends_at' => today()->subDay(),
            'is_published' => true,
        ]);
        Promotion::create([
            'title' => 'Ưu đãi đang áp dụng',
            'slug' => 'uu-dai-dang-ap-dung',
            'starts_at' => today(),
            'ends_at' => today()->addDay(),
            'is_published' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ưu đãi đang áp dụng')
            ->assertDontSee('Ưu đãi đã hết hạn');
    }

    public function test_homepage_cookie_settings_control_uses_the_minimum_touch_target(): void
    {
        $this->seed([ContentPagesSeeder::class, UiStringsSeeder::class]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<button type="button" class="touch-target hover:text-white"', false);
    }

    public function test_homepage_title_does_not_append_laravels_default_app_name(): void
    {
        config(['app.name' => 'Laravel']);
        $this->seed(ContentPagesSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(' | Laravel</title>', false);
    }

    public function test_homepage_showroom_uses_dealer_details_configured_in_admin(): void
    {
        $this->seed([ContentPagesSeeder::class, ThemeSettingsSeeder::class]);
        $dealerSettings = ThemeSetting::query()->where('key', 'dealer')->firstOrFail();
        $dealerSettings->update([
            'value' => [
                'name' => 'Đại lý thử nghiệm Kon Tum',
                'address' => '12 Đường Thử Nghiệm, Kon Tum',
                'maps_url' => 'https://maps.google.com/?q=Kon+Tum+Test',
                'maps_embed_url' => 'https://www.google.com/maps/embed?pb=kon-tum-test',
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Đại lý thử nghiệm Kon Tum')
            ->assertSee('12 Đường Thử Nghiệm, Kon Tum')
            ->assertSee('class="showroom-map-embed"', false)
            ->assertSee('src="https://www.google.com/maps/embed?pb=kon-tum-test"', false)
            ->assertSee('href="https://maps.google.com/?q=Kon+Tum+Test"', false);
    }

    public function test_homepage_does_not_embed_a_non_google_maps_url(): void
    {
        $this->seed([ContentPagesSeeder::class, ThemeSettingsSeeder::class]);
        $dealerSettings = ThemeSetting::query()->where('key', 'dealer')->firstOrFail();
        $dealerSettings->update([
            'value' => [
                'name' => 'Đại lý thử nghiệm Kon Tum',
                'address' => '12 Đường Thử Nghiệm, Kon Tum',
                'maps_embed_url' => 'https://maps.attacker.example/maps/embed?pb=test',
            ],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('<iframe class="showroom-map-embed"', false)
            ->assertSee('Khung bản đồ showroom đang chờ liên kết được xác nhận');
    }

    public function test_unverified_vehicle_does_not_publish_product_schema_or_offer(): void
    {
        UiString::create([
            'key' => 'vehicle.contact_price_note',
            'value' => 'Phiên bản và giá sẽ được cập nhật sau khi đại lý xác nhận. Liên hệ để được tư vấn.',
        ]);

        $line = VehicleLine::create([
            'name' => 'Sedan',
            'slug' => 'sedan',
            'is_published' => true,
        ]);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Published model',
            'slug' => 'published-model',
            'is_published' => true,
        ]);

        $this->get(route('vehicles.show', $vehicle->slug))
            ->assertOk()
            ->assertDontSee('"offers"', false)
            ->assertDontSee('"@type":"Product"', false)
            ->assertSee('Liên hệ');
    }

    public function test_vehicle_content_can_be_composed_from_page_builder_blocks(): void
    {
        $line = VehicleLine::create([
            'name' => 'Sedan',
            'slug' => 'sedan',
            'is_published' => true,
        ]);
        $contentPage = Page::create([
            'title' => 'Nội dung xe',
            'slug' => 'noi-dung-xe',
            'status' => 'draft',
        ]);
        $contentPage->blocks()->create([
            'type' => 'cta',
            'title' => 'Nội dung chi tiết do trình dựng quản lý',
        ]);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'content_page_id' => $contentPage->id,
            'name' => 'Xe trình dựng',
            'slug' => 'xe-trinh-dung',
            'is_published' => true,
        ]);

        $this->get(route('vehicles.show', $vehicle->slug))
            ->assertOk()
            ->assertSee('Nội dung chi tiết do trình dựng quản lý')
            ->assertSee('data-page-block', false);
    }

    public function test_campaign_landing_content_can_be_composed_from_page_builder_blocks(): void
    {
        $contentPage = Page::create([
            'title' => 'Nội dung chiến dịch',
            'slug' => 'noi-dung-chien-dich',
            'status' => 'draft',
        ]);
        $contentPage->blocks()->create([
            'type' => 'cta',
            'title' => 'Nội dung chiến dịch do trình dựng quản lý',
        ]);
        $landingPage = CampaignLandingPage::create([
            'campaign_name' => 'Chiến dịch thử',
            'content_page_id' => $contentPage->id,
            'slug' => 'chien-dich-thu',
            'is_published' => true,
        ]);

        $this->get(route('campaigns.show', $landingPage->slug))
            ->assertOk()
            ->assertSee('Nội dung chiến dịch do trình dựng quản lý')
            ->assertSee('data-page-block', false);
    }
}
