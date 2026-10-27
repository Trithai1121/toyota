<?php

namespace Tests\Feature;

use App\Filament\Resources\Variants\Pages\ManageVariants;
use App\Filament\Resources\VehicleImages\Pages\ManageVehicleImages;
use App\Models\Spec;
use App\Models\User;
use App\Models\Variant;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleImage;
use App\Models\VehicleLine;
use App\Services\ResponsiveImageProcessor;
use Database\Seeders\ToyotaVehicleDetailsSeeder;
use Database\Seeders\UiStringsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_displays_filter_options_for_published_vehicles_and_variants(): void
    {
        $this->seed(UiStringsSeeder::class);

        $line = VehicleLine::create([
            'name' => 'SUV',
            'slug' => 'suv',
            'category' => 'suv-crossover',
            'is_published' => true,
        ]);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Mẫu xe thử nghiệm',
            'slug' => 'mau-xe-thu-nghiem',
            'is_published' => true,
        ]);
        Variant::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Bản tiêu chuẩn',
            'seats' => 5,
            'fuel_type' => 'Hybrid',
            'is_published' => true,
        ]);

        $this->get(route('vehicles.index'))
            ->assertOk()
            ->assertSee('value="Hybrid"', false)
            ->assertSee('value="5"', false)
            ->assertSee('Mẫu xe thử nghiệm');
    }

    public function test_admin_can_upload_a_vehicle_image_and_it_is_saved_as_responsive_webp(): void
    {
        Storage::fake('public');
        $line = VehicleLine::create(['name' => 'SUV', 'slug' => 'suv', 'is_published' => true]);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Mẫu xe thử nghiệm',
            'slug' => 'mau-xe-thu-nghiem',
        ]);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ManageVehicleImages::class)
            ->callAction('create', [
                'vehicle_id' => $vehicle->id,
                'path' => TemporaryUploadedFile::fake()->image('mau-xe.png', 1000, 600),
                'alt_text' => 'Ảnh mẫu xe thử nghiệm',
                'source_note' => 'Ảnh được đại lý cấp phép',
                'color_name' => 'Bạc',
                'color_hex' => '#D1D5DB',
                'sort_order' => 0,
            ])
            ->assertNotified();

        $image = VehicleImage::query()->firstOrFail();
        $this->assertSame('Bạc', $image->color_name);
        $this->assertSame('#D1D5DB', $image->color_hex);
        $this->assertStringEndsWith('.webp', $image->path);
        Storage::disk('public')->assertExists($image->path);
        Storage::disk('public')->assertExists(
            str_replace('.webp', '.json', $image->path),
        );
        $this->assertStringContainsString('960w', app(ResponsiveImageProcessor::class)->srcset($image->path));
    }

    public function test_published_vehicle_detail_displays_color_selector_and_list_price(): void
    {
        $this->seed(UiStringsSeeder::class);
        Storage::fake('public');
        $line = VehicleLine::create(['name' => 'Sedan', 'slug' => 'sedan', 'is_published' => true]);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Mẫu sedan thử nghiệm',
            'slug' => 'mau-sedan-thu-nghiem',
            'is_published' => true,
        ]);
        $variant = Variant::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Bản cao cấp',
            'list_price' => 650_000_000,
            'source_url' => 'https://www.toyota.com.vn/vios-1-5g-cvt',
            'verified_at' => today(),
            'is_published' => true,
        ]);
        $imagePath = UploadedFile::fake()->image('sedan-bac.png', 1200, 750)->store('vehicles', 'public');
        $image = VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'path' => $imagePath,
            'alt_text' => 'Mẫu sedan màu bạc',
            'source_note' => 'Ảnh được đại lý cấp phép',
            'source_url' => 'https://example.org/image-license',
            'rights_verified_at' => today(),
            'color_name' => 'Bạc ánh kim',
            'color_hex' => '#D1D5DB',
            'category' => 'overview',
            'sort_order' => 0,
        ]);
        $exteriorImage = VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'path' => UploadedFile::fake()->image('sedan-ngoai-that.png', 1200, 750)->store('vehicles', 'public'),
            'alt_text' => 'Ảnh ngoại thất mẫu sedan',
            'source_note' => 'Ảnh được đại lý cấp phép',
            'source_url' => 'https://example.org/image-license',
            'rights_verified_at' => today(),
            'category' => 'exterior',
            'sort_order' => 1,
        ]);
        $color = VehicleColor::create([
            'vehicle_id' => $vehicle->id,
            'name' => $image->color_name,
            'slug' => 'bac-anh-kim',
            'hex_code' => $image->color_hex,
            'surcharge' => 0,
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $variant->colors()->attach($color->id);

        Spec::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Chiều dài cơ sở',
            'value' => 'Thông số do đại lý xác nhận',
            'category' => 'exterior',
            'source_url' => 'https://www.toyota.com.vn/vios',
            'verified_at' => today(),
            'sort_order' => 0,
        ]);
        Spec::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Hộp số',
            'value' => 'Thông tin vận hành do đại lý xác nhận',
            'category' => 'performance',
            'source_url' => 'https://www.toyota.com.vn/vios',
            'verified_at' => today(),
            'sort_order' => 1,
        ]);

        $response = $this->get(route('vehicles.show', $vehicle->slug));

        $response->assertOk()
            ->assertSee('Bạc ánh kim')
            ->assertSee('#D1D5DB')
            ->assertSee('vehicleConfigurator', false)
            ->assertSee('Giá niêm yết (VNĐ)')
            ->assertSee('650.000.000 VNĐ')
            ->assertSee('href="#vehicle-versions"', false)
            ->assertSee('href="#vehicle-exterior"', false)
            ->assertSee(e(route('leads.create', ['type' => 'quote', 'vehicle' => $vehicle->slug, 'variant' => $variant->slug])), false)
            ->assertSee('id="vehicle-exterior"', false)
            ->assertSee('Chiều dài cơ sở')
            ->assertSee('Thông tin vận hành do đại lý xác nhận')
            ->assertSee($image->path)
            ->assertSee($exteriorImage->path);
    }

    public function test_vehicle_detail_hides_unverified_price_and_images_but_keeps_quote_actions(): void
    {
        $this->seed(UiStringsSeeder::class);
        Storage::fake('public');
        $line = VehicleLine::create(['name' => 'Sedan', 'slug' => 'sedan', 'is_published' => true]);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Toyota Vios',
            'slug' => 'vios',
            'is_published' => true,
        ]);
        Variant::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Vios 1.5E-CVT',
            'list_price' => 488_000_000,
            'seats' => 5,
            'fuel_type' => 'Xăng',
            'source_url' => 'https://www.toyota.com.vn/vios-1-5e-cvt',
            'verified_at' => today()->addDay(),
            'is_published' => true,
        ]);
        $image = VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'path' => UploadedFile::fake()->image('unverified.png')->store('vehicles', 'public'),
            'alt_text' => 'Ảnh chưa xác minh của Vios',
            'source_note' => 'Nguồn chưa được kiểm tra',
            'sort_order' => 0,
        ]);

        $this->get(route('vehicles.show', $vehicle->slug))
            ->assertOk()
            ->assertSee('Ảnh Toyota Vios đang chờ xác minh quyền sử dụng')
            ->assertSee('id="vehicle-gallery"', false)
            ->assertSee(route('vehicles.specifications', $vehicle->slug), false)
            ->assertSee('id="vehicle-accessories"', false)
            ->assertSee('id="vehicle-purchase-tools"', false)
            ->assertSee('id="vehicle-faq"', false)
            ->assertSee('type=quote', false)
            ->assertSee('type=test_drive', false)
            ->assertSee('vehicle=vios', false)
            ->assertSee('aria-label="Li&#234;n h&#7879;"', false)
            ->assertDontSee('488.000.000 ₫')
            ->assertDontSee($image->path)
            ->assertDontSee('5 chỗ')
            ->assertDontSee('"@type":"Product"', false);
    }

    public function test_toyota_vehicle_detail_seeder_marks_official_section_images_ready_for_display(): void
    {
        Storage::fake('public');
        $line = VehicleLine::create(['name' => 'Sedan', 'slug' => 'sedan', 'is_published' => true]);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Toyota Vios',
            'slug' => 'vios',
            'official_source_url' => 'https://www.toyota.com.vn/vios',
            'is_published' => true,
        ]);

        $canvas = imagecreatetruecolor(2, 2);
        ob_start();
        imagewebp($canvas);
        $webp = ob_get_clean();
        imagedestroy($canvas);

        Http::fake(function ($request) use ($webp) {
            $url = $request->url();

            if (str_contains($url, '/api/car/detail/')) {
                $section = basename((string) parse_url($url, PHP_URL_PATH));

                return Http::response([
                    'Data_Ext' => ['result' => ['items' => [[
                        'name' => ucfirst($section),
                        'detail' => 'Thông tin đã xác minh.',
                        'url' => '/Resources/'.$section.'.png',
                    ]]]],
                ]);
            }

            if (str_contains($url, '/compression/webp?')) {
                return Http::response($webp, 200, ['Content-Type' => 'image/webp']);
            }

            return Http::response('<div id="get-data-car" data-modelid="10" data-gradeid="20"></div>');
        });

        app(ToyotaVehicleDetailsSeeder::class)->run();

        $images = $vehicle->images()->get();
        $this->assertCount(4, $images);
        $this->assertEqualsCanonicalizing(
            ['exterior', 'interior', 'performance', 'safety'],
            $images->pluck('category')->all(),
        );
        $images->each(function (VehicleImage $image): void {
            $this->assertSame('2026-10-24', $image->rights_verified_at?->toDateString());
            $this->assertTrue($image->isReadyForDisplay());
        });
    }

    public function test_vehicle_image_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $line = VehicleLine::create(['name' => 'SUV', 'slug' => 'suv']);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Mẫu xe thử nghiệm',
            'slug' => 'mau-xe-thu-nghiem',
        ]);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ManageVehicleImages::class)
            ->callAction('create', [
                'vehicle_id' => $vehicle->id,
                'path' => TemporaryUploadedFile::fake()->create('khong-phai-anh.jpg', 10, 'text/plain'),
                'alt_text' => 'Tệp không hợp lệ',
                'source_note' => 'Tệp thử nghiệm',
                'sort_order' => 0,
            ])
            ->assertHasActionErrors(['path']);

        $this->assertDatabaseCount('vehicle_images', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('vehicles'));
    }

    public function test_admin_rejects_invalid_vehicle_image_color_codes(): void
    {
        Storage::fake('public');
        $line = VehicleLine::create(['name' => 'SUV', 'slug' => 'suv']);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Mẫu xe thử nghiệm',
            'slug' => 'mau-xe-thu-nghiem',
        ]);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ManageVehicleImages::class)
            ->callAction('create', [
                'vehicle_id' => $vehicle->id,
                'path' => TemporaryUploadedFile::fake()->image('mau-xe.png', 1000, 600),
                'alt_text' => 'Ảnh mẫu xe thử nghiệm',
                'source_note' => 'Ảnh được đại lý cấp phép',
                'color_name' => 'Màu thử nghiệm',
                'color_hex' => 'red; background-image:url(x)',
                'sort_order' => 0,
            ])
            ->assertHasActionErrors(['color_hex']);

        $this->assertDatabaseCount('vehicle_images', 0);
    }

    public function test_admin_can_manage_vehicle_variants_from_their_vehicle_line(): void
    {
        $line = VehicleLine::create(['name' => 'SUV', 'slug' => 'suv']);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Mẫu xe thử nghiệm',
            'slug' => 'mau-xe-thu-nghiem',
        ]);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ManageVariants::class)
            ->mountAction('create')
            ->assertActionDataSet(['vehicle_line_id' => null])
            ->setActionData([
                'vehicle_line_id' => $line->id,
                'vehicle_id' => $vehicle->id,
                'name' => 'Bản tiêu chuẩn',
                'slug' => 'ban-tieu-chuan',
                'code' => 'STD',
                'list_price' => 500_000_000,
                'seats' => 5,
                'fuel_type' => 'Xăng',
                'sort_order' => 1,
                'is_published' => false,
            ])
            ->callMountedAction()
            ->assertNotified();

        $variant = Variant::query()->firstOrFail();
        $this->assertSame($vehicle->id, $variant->vehicle_id);
        $this->assertSame('Bản tiêu chuẩn', $variant->name);

        $otherLine = VehicleLine::create(['name' => 'Sedan', 'slug' => 'sedan']);
        $otherVehicle = Vehicle::create([
            'vehicle_line_id' => $otherLine->id,
            'name' => 'Mẫu sedan thử nghiệm',
            'slug' => 'mau-sedan-thu-nghiem',
        ]);
        $otherVariant = Variant::create([
            'vehicle_id' => $otherVehicle->id,
            'name' => 'Bản sedan',
        ]);

        Livewire::actingAs($admin)
            ->test(ManageVariants::class)
            ->mountTableAction('edit', $variant)
            ->assertTableActionDataSet([
                'vehicle_line_id' => $line->id,
                'vehicle_id' => $vehicle->id,
            ]);

        Livewire::actingAs($admin)
            ->test(ManageVariants::class)
            ->filterTable('vehicle_line_id', $line->id)
            ->assertTableColumnExists('vehicle.line.name')
            ->assertCanSeeTableRecords([$variant])
            ->assertCanNotSeeTableRecords([$otherVariant]);
    }

    public function test_admin_cannot_assign_a_vehicle_variant_to_a_different_line(): void
    {
        $selectedLine = VehicleLine::create(['name' => 'Sedan', 'slug' => 'sedan']);
        $otherLine = VehicleLine::create(['name' => 'SUV', 'slug' => 'suv']);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $otherLine->id,
            'name' => 'Mẫu xe thử nghiệm',
            'slug' => 'mau-xe-thu-nghiem',
        ]);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ManageVariants::class)
            ->callAction('create', [
                'vehicle_line_id' => $selectedLine->id,
                'vehicle_id' => $vehicle->id,
                'name' => 'Bản không hợp lệ',
                'sort_order' => 0,
                'is_published' => false,
            ])
            ->assertHasActionErrors(['vehicle_id']);

        $this->assertDatabaseCount('variants', 0);
    }
}
