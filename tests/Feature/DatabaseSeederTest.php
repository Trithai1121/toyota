<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\ThemeSetting;
use Database\Seeders\ContentPagesSeeder;
use Database\Seeders\ThemeSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_publishes_the_verified_vehicle_catalog_and_admin_roles(): void
    {
        $this->seed();

        $this->assertDatabaseHas('vehicle_lines', [
            'slug' => 'vios',
            'is_published' => true,
        ]);
        $this->assertDatabaseHas('vehicles', [
            'slug' => 'vios',
            'is_published' => true,
        ]);
        $this->assertDatabaseHas('variants', [
            'name' => 'VIOS 1.5E-MT',
            'list_price' => 458_000_000,
            'is_published' => true,
        ]);
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'marketing']);
        $this->assertDatabaseHas('roles', ['name' => 'sale']);
    }

    public function test_seeding_adds_new_theme_defaults_without_overwriting_editor_content(): void
    {
        $this->seed();

        $page = Page::query()->where('slug', 'home')->firstOrFail();
        $page->update(['title' => 'Tên trang do marketing sửa']);
        $dealer = ThemeSetting::query()->where('key', 'dealer')->firstOrFail();
        $value = $dealer->value;
        $value['name'] = 'Tên đại lý do marketing sửa';
        unset($value['service_regions']);
        $dealer->update(['value' => $value]);

        $this->seed(ThemeSettingsSeeder::class);
        $this->seed(ContentPagesSeeder::class);

        $this->assertSame('Tên trang do marketing sửa', $page->fresh()->title);
        $this->assertSame(1, $page->blocks()->where('type', 'lead_form')->count());
        $this->assertSame('Tên đại lý do marketing sửa', $dealer->fresh()->value['name']);
        $this->assertSame(['Kon Tum', 'Gia Lai', 'Quảng Ngãi'], $dealer->fresh()->value['service_regions']);

        $tracking = ThemeSetting::query()->where('key', 'tracking')->firstOrFail();
        $tracking->update(['value' => ['ga4_id' => 'G-APPROVED123']]);
        $this->seed(ThemeSettingsSeeder::class);

        $this->assertSame('G-APPROVED123', $tracking->fresh()->value['ga4_id']);
        $this->assertArrayHasKey('gtm_id', $tracking->fresh()->value);
        $this->assertArrayHasKey('meta_pixel_id', $tracking->fresh()->value);
    }
}
