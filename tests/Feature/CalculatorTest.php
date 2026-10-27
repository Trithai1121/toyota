<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Variant;
use App\Models\Vehicle;
use App\Models\VehicleLine;
use Database\Seeders\UiStringsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UiStringsSeeder::class);
    }

    public function test_installment_calculator_uses_only_configured_rates(): void
    {
        $variant = $this->createPublishedVariant(120_000_000);
        Setting::create([
            'key' => 'loan_rates',
            'value' => ['12' => 0],
            'description' => 'Test configuration',
        ]);

        $this->get(route('calculators.installment', [
            'variant_id' => $variant->id,
            'down_payment_percent' => 25,
            'term_months' => 12,
        ]))
            ->assertOk()
            ->assertSee('90.000.000 ₫')
            ->assertSee('7.500.000 ₫')
            ->assertSee('0,00%/năm');
    }

    public function test_installment_calculator_does_not_show_unconfigured_interest_rate(): void
    {
        $this->get(route('calculators.installment'))
            ->assertOk()
            ->assertSee('Lãi suất chưa được đại lý cấu hình')
            ->assertSee('Liên hệ tư vấn');
    }

    public function test_installment_calculator_returns_zero_monthly_payment_when_down_payment_covers_price(): void
    {
        $variant = $this->createPublishedVariant(120_000_000);
        Setting::create([
            'key' => 'loan_rates',
            'value' => ['12' => 0],
            'description' => 'Test configuration',
        ]);

        $this->get(route('calculators.installment', [
            'variant_id' => $variant->id,
            'down_payment_percent' => 100,
            'term_months' => 12,
        ]))
            ->assertOk()
            ->assertSee('120.000.000 ₫')
            ->assertSee('0 ₫');
    }

    public function test_installment_calculator_uses_amortized_interest_and_rounds_for_display(): void
    {
        $variant = $this->createPublishedVariant(120_000_000);
        Setting::create([
            'key' => 'loan_rates',
            'value' => ['12' => 12],
            'description' => 'Test configuration',
        ]);

        $this->get(route('calculators.installment', [
            'variant_id' => $variant->id,
            'down_payment_percent' => 0,
            'term_months' => 12,
        ]))
            ->assertOk()
            ->assertSee('10.661.855 ₫')
            ->assertSee('7.942.256 ₫');
    }

    public function test_installment_calculator_rejects_negative_price(): void
    {
        $this->from(route('calculators.on-road'))
            ->get(route('calculators.on-road', ['price' => -1]))
            ->assertRedirect(route('calculators.on-road'))
            ->assertSessionHasErrors('price');
    }

    public function test_on_road_calculator_uses_region_specific_configured_fees(): void
    {
        Setting::create([
            'key' => 'on_road_kon_tum',
            'value' => [
                'registration_rate_percent' => 10,
                'registration_fixed' => 1_000_000,
                'license_plate' => 500_000,
                'road_use' => 600_000,
                'compulsory_insurance' => 100_000,
                'other' => 0,
                'source_url' => 'https://vanban.chinhphu.vn/?pageid=27160&docid=200000',
                'verified_at' => today()->toDateString(),
            ],
            'description' => 'Test configuration',
        ]);

        $this->get(route('calculators.on-road', [
            'region' => 'kon-tum',
            'price' => 100_000_000,
        ]))
            ->assertOk()
            ->assertSee('12.200.000 ₫')
            ->assertSee('112.200.000 ₫')
            ->assertSee('Cơ quan nhà nước')
            ->assertSee('Chi phí mang tính tham khảo');
    }

    public function test_on_road_calculator_does_not_use_fees_without_a_verified_source(): void
    {
        $variant = $this->createPublishedVariant(100_000_000);
        Setting::create([
            'key' => 'on_road_kon_tum',
            'value' => [
                'registration_rate_percent' => 10,
                'registration_fixed' => 1_000_000,
                'license_plate' => 500_000,
                'road_use' => 600_000,
                'compulsory_insurance' => 100_000,
                'other' => 0,
            ],
            'description' => 'Test configuration',
        ]);

        $this->get(route('calculators.on-road', [
            'region' => 'kon-tum',
            'variant_id' => $variant->id,
        ]))
            ->assertOk()
            ->assertSee('Các khoản phí khu vực này chưa được đại lý cấu hình.')
            ->assertDontSee('112.200.000 ₫');
    }

    private function createPublishedVariant(int $price): Variant
    {
        $line = VehicleLine::create([
            'name' => 'Sedan',
            'slug' => 'sedan-test',
            'category' => 'sedan',
            'is_published' => true,
        ]);
        $vehicle = Vehicle::create([
            'vehicle_line_id' => $line->id,
            'name' => 'Mẫu thử',
            'slug' => 'mau-thu',
            'is_published' => true,
        ]);

        return Variant::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Bản thử',
            'list_price' => $price,
            'source_url' => 'https://www.toyota.com.vn/vios',
            'verified_at' => today(),
            'is_published' => true,
        ]);
    }
}
