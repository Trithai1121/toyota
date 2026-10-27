<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ToyotaCatalogSeeder extends Seeder
{
    private const PRICE_SOURCE = 'https://www.toyota.com.vn/danh-sach-xe';

    private const VERIFIED_AT = '2026-10-24';

    public function run(): void
    {
        $catalog = [
            [
                'line' => 'Wigo', 'line_slug' => 'Wigo', 'category' => 'hatchback', 'sort' => 1,
                'vehicle' => 'Wigo', 'vehicle_slug' => 'Wigo', 'source' => 'https://www.toyota.com.vn/wigo?gradeId=1650',
                'summary' => 'Hatchback 5 chỗ, động cơ xăng 1.2L và hộp số D-CVT, phù hợp di chuyển trong đô thị.',
                'variants' => [
                    ['name' => 'WIGO G', 'price' => 405000000, 'seats' => 5, 'fuel' => 'Xăng', 'transmission' => 'D-CVT', 'cc' => 1196, 'origin' => 'Indonesia'],
                ],
            ],
            [
                'line' => 'Vios', 'line_slug' => 'vios', 'category' => 'sedan', 'sort' => 2,
                'vehicle' => 'Vios', 'vehicle_slug' => 'vios', 'source' => 'https://www.toyota.com.vn/vios?gradeId=1514',
                'summary' => 'Sedan 5 chỗ, động cơ xăng 1.5L, hộp số sàn hoặc CVT; lắp ráp tại Việt Nam.',
                'variants' => [
                    ['name' => 'VIOS 1.5E-MT', 'price' => 458000000, 'seats' => 5, 'fuel' => 'Xăng', 'transmission' => '5MT', 'cc' => 1496, 'origin' => 'Việt Nam'],
                    ['name' => 'VIOS 1.5E-CVT', 'price' => 488000000, 'seats' => 5, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 1496, 'origin' => 'Việt Nam'],
                    ['name' => 'VIOS 1.5G-CVT', 'price' => 545000000, 'seats' => 5, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 1496, 'origin' => 'Việt Nam'],
                ],
            ],
            [
                'line' => 'Yaris Cross', 'line_slug' => 'yaris-cross', 'category' => 'suv-crossover', 'sort' => 3,
                'vehicle' => 'Yaris Cross', 'vehicle_slug' => 'yaris-cross', 'source' => 'https://www.toyota.com.vn/yaris-cross?gradeId=1943',
                'summary' => 'SUV 5 chỗ, có phiên bản xăng và Hybrid; nhập khẩu từ Indonesia.',
                'variants' => [
                    ['name' => 'YARIS CROSS', 'price' => 650000000, 'seats' => 5, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 1496, 'origin' => 'Indonesia'],
                    ['name' => 'YARIS CROSS HEV', 'price' => 728000000, 'seats' => 5, 'fuel' => 'Xăng + Điện', 'transmission' => 'CVT', 'cc' => 1496, 'origin' => 'Indonesia'],
                ],
            ],
            [
                'line' => 'Corolla Cross', 'line_slug' => 'corolla-cross', 'category' => 'suv-crossover', 'sort' => 4,
                'vehicle' => 'Corolla Cross', 'vehicle_slug' => 'CorollaCross', 'source' => 'https://www.toyota.com.vn/corolla-cross?gradeId=1923',
                'summary' => 'SUV 5 chỗ, có phiên bản xăng và Hybrid, động cơ 1.8L; nhập khẩu từ Thái Lan.',
                'variants' => [
                    ['name' => 'COROLLA CROSS 1.8V', 'price' => 820000000, 'seats' => 5, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 1798, 'origin' => 'Thái Lan'],
                    ['name' => 'COROLLA CROSS 1.8HEV', 'price' => 865000000, 'seats' => 5, 'fuel' => 'Xăng + Điện', 'transmission' => 'CVT', 'cc' => 1798, 'origin' => 'Thái Lan'],
                ],
            ],
            [
                'line' => 'Raize', 'line_slug' => 'Raize', 'category' => 'suv-crossover', 'sort' => 5,
                'vehicle' => 'Raize', 'vehicle_slug' => 'Raize', 'source' => 'https://www.toyota.com.vn/raize?gradeId=1901',
                'summary' => 'SUV đô thị 5 chỗ, động cơ xăng Turbo 1.0L và hộp số CVT; nhập khẩu từ Indonesia.',
                'variants' => [
                    ['name' => 'RAIZE 1.0 TURBO', 'price' => 510000000, 'seats' => 5, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 998, 'origin' => 'Indonesia'],
                ],
            ],
            [
                'line' => 'Veloz Cross', 'line_slug' => 'VelozCross', 'category' => 'mpv', 'sort' => 6,
                'vehicle' => 'Veloz Cross', 'vehicle_slug' => 'VelozCross', 'source' => 'https://www.toyota.com.vn/veloz-cross?gradeId=1933',
                'summary' => 'MPV 7 chỗ, động cơ xăng 1.5L và hộp số CVT; lắp ráp tại Việt Nam.',
                'variants' => [
                    ['name' => 'VELOZ CROSS CVT', 'price' => 638000000, 'seats' => 7, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 1496, 'origin' => 'Việt Nam'],
                    ['name' => 'VELOZ CROSS CVT TOP', 'price' => 660000000, 'seats' => 7, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 1496, 'origin' => 'Việt Nam'],
                ],
            ],
            [
                'line' => 'Avanza Premio', 'line_slug' => 'AvanzaPremio', 'category' => 'mpv', 'sort' => 7,
                'vehicle' => 'Avanza Premio', 'vehicle_slug' => 'AvanzaPremio', 'source' => 'https://www.toyota.com.vn/avanza-premio?gradeId=1935',
                'summary' => 'MPV 7 chỗ với lựa chọn hộp số sàn hoặc CVT; động cơ xăng 1.5L.',
                'variants' => [
                    ['name' => 'AVANZA PREMIO MT', 'price' => 558000000, 'seats' => 7, 'fuel' => 'Xăng', 'transmission' => 'MT', 'cc' => 1496, 'origin' => 'Việt Nam'],
                    ['name' => 'AVANZA PREMIO CVT', 'price' => 598000000, 'seats' => 7, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 1496, 'origin' => 'Việt Nam'],
                ],
            ],
            [
                'line' => 'Innova Cross', 'line_slug' => 'InnovaCross', 'category' => 'mpv', 'sort' => 8,
                'vehicle' => 'Innova Cross', 'vehicle_slug' => 'InnovaCross', 'source' => 'https://www.toyota.com.vn/innova-cross?gradeId=1940',
                'summary' => 'MPV 7 hoặc 8 chỗ, động cơ xăng 2.0L hoặc Hybrid; nhập khẩu từ Indonesia.',
                'variants' => [
                    ['name' => 'INNOVA CROSS 2.0G', 'price' => 730000000, 'seats' => 8, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 1987, 'origin' => 'Indonesia'],
                    ['name' => 'INNOVA CROSS 2.0V', 'price' => 825000000, 'seats' => 8, 'fuel' => 'Xăng', 'transmission' => 'CVT', 'cc' => 1987, 'origin' => 'Indonesia'],
                    ['name' => 'INNOVA CROSS 2.0HEV', 'price' => 960000000, 'seats' => 7, 'fuel' => 'Xăng + Điện', 'transmission' => 'CVT', 'cc' => 1987, 'origin' => 'Indonesia'],
                ],
            ],
            [
                'line' => 'Camry', 'line_slug' => 'Camry', 'category' => 'sedan', 'sort' => 9,
                'vehicle' => 'Camry', 'vehicle_slug' => 'Camry', 'source' => 'https://www.toyota.com.vn/camry?gradeId=11939',
                'summary' => 'Sedan Hybrid 5 chỗ, động cơ 2.5L và hộp số E-CVT; nhập khẩu từ Thái Lan.',
                'variants' => [
                    ['name' => 'CAMRY HEV PREMIUM', 'price' => 1320000000, 'seats' => 5, 'fuel' => 'Xăng + Điện', 'transmission' => 'E-CVT', 'cc' => 2487, 'origin' => 'Thái Lan'],
                    ['name' => 'CAMRY HEV LUXURY', 'price' => 1460000000, 'seats' => 5, 'fuel' => 'Xăng + Điện', 'transmission' => 'E-CVT', 'cc' => 2487, 'origin' => 'Thái Lan'],
                ],
            ],
            [
                'line' => 'Fortuner', 'line_slug' => 'Fortuner', 'category' => 'suv-crossover', 'sort' => 10,
                'vehicle' => 'Fortuner', 'vehicle_slug' => 'Fortuner', 'source' => 'https://www.toyota.com.vn/fortuner?gradeId=11937',
                'summary' => 'SUV 7 chỗ, động cơ xăng 2.7L và hộp số tự động 6 cấp.',
                'variants' => [
                    ['name' => 'FORTUNER 2.7AT 4x2', 'price' => 1155000000, 'seats' => 7, 'fuel' => 'Xăng', 'transmission' => '6AT', 'cc' => 2694, 'origin' => 'Indonesia'],
                    ['name' => 'FORTUNER LEGENDER 2.7AT 4x2', 'price' => 1290000000, 'seats' => 7, 'fuel' => 'Xăng', 'transmission' => '6AT', 'cc' => 2694, 'origin' => 'Indonesia'],
                    ['name' => 'FORTUNER LEGENDER 2.7AT 4x4', 'price' => 1395000000, 'seats' => 7, 'fuel' => 'Xăng', 'transmission' => '6AT', 'cc' => 2694, 'origin' => 'Indonesia'],
                ],
            ],
            [
                'line' => 'Hilux', 'line_slug' => 'Hilux', 'category' => 'pickup', 'sort' => 11,
                'vehicle' => 'Hilux', 'vehicle_slug' => 'Hilux', 'source' => 'https://www.toyota.com.vn/hilux?gradeId=1595',
                'summary' => 'Bán tải 5 chỗ, động cơ dầu 2.8L; có hộp số sàn hoặc tự động và nhiều cấu hình dẫn động.',
                'variants' => [
                    ['name' => 'HILUX STANDARD 2.8 4x2 MT', 'price' => 632000000, 'seats' => 5, 'fuel' => 'Dầu', 'transmission' => '6MT', 'cc' => 2755, 'origin' => 'Thái Lan'],
                    ['name' => 'HILUX PRO 2.8 4x2 AT', 'price' => 706000000, 'seats' => 5, 'fuel' => 'Dầu', 'transmission' => '6AT', 'cc' => 2755, 'origin' => 'Thái Lan'],
                    ['name' => 'HILUX TRAILHUNTER 2.8 4x4 AT', 'price' => 903000000, 'seats' => 5, 'fuel' => 'Dầu', 'transmission' => '6AT', 'cc' => 2755, 'origin' => 'Thái Lan'],
                ],
            ],
            [
                'line' => 'Land Cruiser', 'line_slug' => 'LandCruiser', 'category' => 'suv-crossover', 'sort' => 12,
                'vehicle' => 'Land Cruiser', 'vehicle_slug' => 'LandCruiser', 'source' => 'https://www.toyota.com.vn/land-cruiser-fj?gradeId=11947',
                'summary' => 'Danh mục SUV Land Cruiser gồm FJ, Prado, Land Cruiser 300 và phiên bản Hybrid.',
                'variants' => [
                    ['name' => 'LAND CRUISER FJ', 'price' => 1198000000, 'seats' => 5, 'fuel' => 'Xăng', 'transmission' => '6AT', 'cc' => 2694, 'origin' => 'Thái Lan'],
                    ['name' => 'LAND CRUISER PRADO', 'price' => 3480000000, 'seats' => 7, 'fuel' => 'Xăng', 'transmission' => '6AT', 'cc' => 2393, 'origin' => 'Nhật Bản'],
                    ['name' => 'LAND CRUISER 300', 'price' => 4580000000, 'seats' => 7, 'fuel' => 'Xăng', 'transmission' => '10AT', 'cc' => 3445, 'origin' => 'Nhật Bản'],
                    ['name' => 'LAND CRUISER HEV', 'price' => 4710000000, 'seats' => 7, 'fuel' => 'Xăng + Điện', 'transmission' => '10AT', 'cc' => 3445, 'origin' => 'Nhật Bản'],
                ],
            ],
            [
                'line' => 'Alphard', 'line_slug' => 'alphard', 'category' => 'mpv', 'sort' => 13,
                'vehicle' => 'Alphard HEV', 'vehicle_slug' => 'alphard', 'source' => 'https://www.toyota.com.vn/alphard?gradeId=1944',
                'summary' => 'MPV Hybrid 7 chỗ, động cơ 2.5L và hộp số Hybrid; nhập khẩu từ Nhật Bản.',
                'variants' => [
                    ['name' => 'ALPHARD HEV', 'price' => 4415000000, 'seats' => 7, 'fuel' => 'Xăng + Điện', 'transmission' => 'Hybrid', 'cc' => 2487, 'origin' => 'Nhật Bản'],
                ],
            ],
        ];

        foreach ($catalog as $item) {
            $line = VehicleLine::query()->firstOrNew(['slug' => $item['line_slug']]);
            $line->fill([
                'name' => $item['line'],
                'category' => $item['category'],
                'sort_order' => $item['sort'],
                'is_published' => true,
            ])->save();

            $vehicle = Vehicle::query()->firstOrNew(['slug' => $item['vehicle_slug']]);
            $vehicle->fill([
                'vehicle_line_id' => $line->id,
                'name' => $item['vehicle'],
                'summary' => $item['summary'],
                'description' => $item['summary'],
                'seo_title' => $item['vehicle'].' | Phiên bản, giá và màu xe Toyota',
                'seo_description' => $item['summary'].' Giá tham khảo từ Toyota Việt Nam, vui lòng liên hệ đại lý để xác nhận tại thời điểm mua.',
                'official_source_url' => $item['source'],
                'content_verified_at' => self::VERIFIED_AT,
                'catalogue_url' => 'https://www.toyota.com.vn/catalogue',
                'is_published' => true,
            ])->save();

            $this->syncSharedOfficialSpecs($vehicle, $item);
            $this->syncOfficialFeatureSpecs($vehicle, $item['vehicle_slug'], $item['source']);

            $activeNames = [];
            foreach ($item['variants'] as $index => $variantData) {
                $activeNames[] = $variantData['name'];
                $variant = $vehicle->variants()->firstOrNew(['name' => $variantData['name']]);
                $trimName = trim(preg_replace('/^'.preg_quote($vehicle->name, '/').'\s*/iu', '', $variantData['name']) ?? $variantData['name']);
                $variant->fill([
                    'slug' => Str::slug(preg_replace('/(?<=\d)\.(?=\d)/', '-', $trimName !== '' ? $trimName : $variantData['name']) ?? $variantData['name']),
                    'code' => null,
                    'list_price' => $variantData['price'],
                    'seats' => $variantData['seats'],
                    'fuel_type' => $variantData['fuel'],
                    'transmission' => $variantData['transmission'],
                    'engine_displacement_cc' => $variantData['cc'],
                    'origin' => $variantData['origin'],
                    'engine' => $variantData['cc'].' cc',
                    'body_type' => match ($item['category']) {
                        'sedan' => 'Sedan',
                        'suv-crossover' => 'SUV / Crossover',
                        'mpv' => 'MPV',
                        'pickup' => 'Bán tải',
                        'hatchback' => 'Hatchback',
                        default => null,
                    },
                    'sort_order' => $index + 1,
                    'is_published' => true,
                    'source_url' => self::PRICE_SOURCE,
                    'verified_at' => self::VERIFIED_AT,
                ])->save();
            }

            $vehicle->variants()->whereNotIn('name', $activeNames)->update(['is_published' => false]);
        }

        $this->splitLandCruiserModels();

        Vehicle::query()->where('slug', 'corolla-altis')->update(['is_published' => false]);
        VehicleLine::query()->where('slug', 'corolla-altis')->update(['is_published' => false]);
    }

    /** Give the four separately listed Land Cruiser models their own detail pages. */
    private function splitLandCruiserModels(): void
    {
        $legacy = Vehicle::query()->with('variants')->where('slug', 'LandCruiser')->first();

        if ($legacy === null) {
            return;
        }

        $models = [
            [
                'name' => 'Land Cruiser 300', 'slug' => 'land-cruiser-300', 'variant' => 'LAND CRUISER 300',
                'source' => 'https://www.toyota.com.vn/land-cruiser?gradeId=1896', 'sort' => 12,
            ],
            [
                'name' => 'Land Cruiser HEV', 'slug' => 'land-cruiser-hev', 'variant' => 'LAND CRUISER HEV',
                'source' => 'https://www.toyota.com.vn/land-cruiser?gradeId=11948', 'sort' => 13,
            ],
            [
                'name' => 'Land Cruiser Prado', 'slug' => 'land-cruiser-prado', 'variant' => 'LAND CRUISER PRADO',
                'source' => 'https://www.toyota.com.vn/land-prado?gradeId=1009', 'sort' => 14,
            ],
            [
                'name' => 'Land Cruiser FJ', 'slug' => 'land-cruiser-fj', 'variant' => 'LAND CRUISER FJ',
                'source' => 'https://www.toyota.com.vn/land-cruiser-fj?gradeId=11947', 'sort' => 15,
            ],
        ];

        foreach ($models as $model) {
            $legacyVariant = $legacy->variants->firstWhere('name', $model['variant']);

            if ($legacyVariant === null) {
                continue;
            }

            $line = VehicleLine::query()->updateOrCreate(
                ['slug' => $model['slug']],
                [
                    'name' => $model['name'],
                    'category' => 'suv-crossover',
                    'sort_order' => $model['sort'],
                    'is_published' => true,
                ],
            );

            $vehicle = Vehicle::query()->firstOrNew(['slug' => $model['slug']]);
            $summary = $model['name'].' tại Toyota Việt Nam: phiên bản, thông số và giá niêm yết tham khảo.';
            $vehicle->fill([
                'vehicle_line_id' => $line->id,
                'name' => $model['name'],
                'summary' => $summary,
                'description' => $summary,
                'seo_title' => $model['name'].' | Phiên bản, giá và thông số Toyota',
                'seo_description' => $summary.' Vui lòng liên hệ đại lý để xác nhận thông tin tại thời điểm mua.',
                'official_source_url' => $model['source'],
                'content_verified_at' => self::VERIFIED_AT,
                'catalogue_url' => 'https://www.toyota.com.vn/catalogue',
                'is_published' => true,
            ])->save();

            $variant = $vehicle->variants()->firstOrNew(['name' => $legacyVariant->name]);
            $variant->fill([
                'slug' => Str::slug($legacyVariant->name),
                'code' => $legacyVariant->code,
                'engine' => $legacyVariant->engine,
                'body_type' => 'SUV / Crossover',
                'specs' => $legacyVariant->specs,
                'list_price' => $legacyVariant->list_price,
                'seats' => $legacyVariant->seats,
                'fuel_type' => $legacyVariant->fuel_type,
                'transmission' => $legacyVariant->transmission,
                'engine_displacement_cc' => $legacyVariant->engine_displacement_cc,
                'origin' => $legacyVariant->origin,
                'sort_order' => 1,
                'is_published' => true,
                'source_url' => self::PRICE_SOURCE,
                'verified_at' => self::VERIFIED_AT,
            ])->save();

            $this->syncSharedOfficialSpecs($vehicle, [
                'category' => 'suv-crossover',
                'variants' => [[
                    'seats' => $legacyVariant->seats,
                    'fuel' => $legacyVariant->fuel_type,
                    'transmission' => $legacyVariant->transmission,
                    'cc' => $legacyVariant->engine_displacement_cc,
                    'origin' => $legacyVariant->origin,
                ]],
            ]);
            $this->syncOfficialFeatureSpecs($vehicle, $model['slug'], $model['source']);
        }

        $legacy->variants()->update(['is_published' => false]);
        $legacy->update(['is_published' => false]);
        $legacy->line()->update(['is_published' => false]);
    }

    /**
     * Store only facts that are shared by every published trim in the official
     * Toyota Vietnam listing. Trim-specific values remain on Variant records.
     */
    private function syncSharedOfficialSpecs(Vehicle $vehicle, array $item): void
    {
        $variants = collect($item['variants']);
        $first = $variants->first();
        $shared = [
            'Kiểu dáng' => match ($item['category']) {
                'sedan' => 'Sedan',
                'suv-crossover' => 'SUV',
                'mpv' => 'Đa dụng',
                'pickup' => 'Bán tải',
                'hatchback' => 'Hatchback',
                default => null,
            },
            'Số chỗ ngồi' => $variants->pluck('seats')->unique()->count() === 1 ? $first['seats'].' chỗ' : null,
            'Nhiên liệu' => $variants->pluck('fuel')->unique()->count() === 1 ? $first['fuel'] : null,
            'Hộp số' => $variants->pluck('transmission')->unique()->count() === 1 ? $first['transmission'] : null,
            'Dung tích động cơ' => $variants->pluck('cc')->filter()->unique()->count() === 1 ? number_format((int) $first['cc'], 0, ',', '.').' cc' : null,
            'Xuất xứ' => $variants->pluck('origin')->unique()->count() === 1 ? $first['origin'] : null,
        ];

        foreach ($shared as $name => $value) {
            if ($value === null) {
                continue;
            }

            $vehicle->specs()->updateOrCreate(
                ['name' => $name, 'category' => 'general'],
                [
                    'value' => $value,
                    'sort_order' => array_search($name, array_keys($shared), true) + 1,
                    'source_url' => self::PRICE_SOURCE,
                    'verified_at' => self::VERIFIED_AT,
                ],
            );
        }
    }

    /**
     * Curated highlights taken from each model's Toyota Vietnam product page.
     * Keep this model-level copy limited to facts that the source page presents
     * for the model; equipment that varies by grade is explicitly qualified.
     */
    private function syncOfficialFeatureSpecs(Vehicle $vehicle, string $slug, string $source): void
    {
        $features = [
            'Wigo' => [
                'exterior' => ['Thiết kế hatchback đô thị với cụm đèn trước và sau LED.'],
                'interior' => ['Khoang cabin bố trí 5 chỗ ngồi; trang bị cụ thể theo phiên bản.'],
                'performance' => ['Động cơ xăng 1.2L kết hợp hộp số D-CVT.'],
                'safety' => ['Toyota công bố các tính năng VSC, HAC, BSM và RCTA cho phiên bản tương ứng.'],
            ],
            'vios' => [
                'exterior' => ['Kiểu dáng sedan; thiết kế và trang bị ngoại thất có khác biệt theo phiên bản.'],
                'interior' => ['Khoang cabin 5 chỗ; trang bị tiện nghi thay đổi theo phiên bản.'],
                'performance' => ['Động cơ xăng 1.5L; có lựa chọn hộp số sàn hoặc CVT.'],
                'safety' => ['Trang bị túi khí và Toyota Safety Sense khác nhau theo phiên bản; cần đối chiếu đúng grade.'],
            ],
            'yaris-cross' => [
                'exterior' => ['SUV đô thị 5 chỗ; trang bị đèn, mâm và cửa sổ trời thay đổi theo phiên bản.'],
                'interior' => ['Màn hình đa thông tin và tiện nghi cabin được trang bị theo grade; bản HEV có cấu hình riêng.'],
                'performance' => ['Có bản xăng CVT và bản Hybrid tự sạc; không cần cắm sạc ngoài.'],
                'safety' => ['Toyota Safety Sense và hỗ trợ điểm mù/cắt ngang phía sau được công bố theo grade.'],
            ],
            'CorollaCross' => [
                'exterior' => ['SUV 5 chỗ; trang bị ngoại thất khác nhau theo phiên bản xăng và HEV.'],
                'interior' => ['Trang bị khoang lái và tiện nghi được phân theo grade; vui lòng chọn đúng phiên bản.'],
                'performance' => ['Có lựa chọn động cơ xăng 1.8L và Hybrid 1.8L; thông số vận hành phụ thuộc phiên bản.'],
                'safety' => ['Toyota Safety Sense và hỗ trợ đỗ xe được trang bị theo grade; không mặc định áp dụng cho mọi bản.'],
            ],
            'Raize' => [
                'exterior' => ['SUV đô thị 5 chỗ, động cơ Turbo 1.0L; thiết kế gầm cao và thân xe nhỏ gọn.'],
                'interior' => ['Khoang cabin 5 chỗ với màn hình đa thông tin; tiện nghi theo cấu hình Toyota Việt Nam công bố.'],
                'performance' => ['Động cơ xăng Turbo 1.0L kết hợp hộp số CVT; bán kính quay vòng tối thiểu 5,1 m.'],
                'safety' => ['Toyota công bố VSC, HAC, TRC, ABS/EBD/BA và camera/cảm biến theo cấu hình xe.'],
            ],
            'VelozCross' => [
                'exterior' => ['MPV 7 chỗ; thiết kế và trang bị ngoại thất thay đổi theo bản CVT/CVT Top.'],
                'interior' => ['Bố trí 3 hàng ghế 7 chỗ; trang bị tiện nghi khác nhau theo phiên bản.'],
                'performance' => ['Động cơ xăng 1.5L, hộp số CVT và dẫn động cầu trước.'],
                'safety' => ['Các tính năng như BSM, RCTA, PCS và camera 360° phụ thuộc phiên bản.'],
            ],
            'AvanzaPremio' => [
                'exterior' => ['MPV 7 chỗ; cấu hình ngoại thất được Toyota phân theo phiên bản MT và CVT.'],
                'interior' => ['Ba hàng ghế, sức chứa 7 người; tiện nghi cabin theo phiên bản.'],
                'performance' => ['Động cơ xăng 1.5L; có lựa chọn hộp số sàn hoặc CVT, dẫn động cầu trước.'],
                'safety' => ['Số túi khí và hỗ trợ an toàn khác nhau theo MT/CVT; không áp dụng đồng nhất cho mọi bản.'],
            ],
            'InnovaCross' => [
                'exterior' => ['MPV có cấu hình 7 hoặc 8 chỗ; kích thước bản HEV và bản xăng có khác biệt nhỏ.'],
                'interior' => ['Bản HEV 7 chỗ có ghế thương gia hàng hai; bản xăng bố trí 8 chỗ.'],
                'performance' => ['Có động cơ xăng 2.0L và hệ Hybrid 2.0L; bản HEV dùng hộp số E-CVT.'],
                'safety' => ['Toyota Safety Sense, PVM và một số hỗ trợ lái được trang bị theo grade; bản HEV có cấu hình an toàn riêng.'],
            ],
            'Camry' => [
                'exterior' => ['Sedan 5 chỗ; thiết kế và các chi tiết ngoại thất được phân theo phiên bản.'],
                'interior' => ['Khoang cabin sedan 5 chỗ; tiện nghi và vật liệu nội thất thay đổi theo grade.'],
                'performance' => ['Dòng Camry hiện có cấu hình Hybrid; thông số động cơ và truyền động theo từng grade.'],
                'safety' => ['Toyota Safety Sense và hệ thống hỗ trợ đỗ xe được công bố theo phiên bản; đối chiếu bảng grade trước khi tư vấn.'],
            ],
            'Fortuner' => [
                'exterior' => ['SUV 7 chỗ; thiết kế và trang bị khác nhau giữa bản tiêu chuẩn và Legender.'],
                'interior' => ['Ba hàng ghế 7 chỗ; tiện nghi khoang lái thay đổi theo phiên bản.'],
                'performance' => ['Các phiên bản có lựa chọn động cơ xăng/dầu, hộp số và dẫn động khác nhau.'],
                'safety' => ['BSM, RCTA, camera và các hỗ trợ an toàn phụ thuộc cấu hình phiên bản.'],
            ],
            'Hilux' => [
                'exterior' => ['Bán tải 5 chỗ; trang bị ngoại thất được phân theo Standard, Pro và Trailhunter.'],
                'interior' => ['Cabin 5 chỗ; trang bị tiện nghi khác nhau theo từng phiên bản.'],
                'performance' => ['Động cơ diesel 2.8L; hộp số sàn/tự động và hệ dẫn động phụ thuộc phiên bản.'],
                'safety' => ['Toyota Safety Sense, camera toàn cảnh và hỗ trợ địa hình không có đồng nhất trên mọi grade.'],
            ],
            'land-cruiser-300' => [
                'exterior' => ['SUV Land Cruiser 300; thông tin chi tiết ngoại thất cần đối chiếu catalogue theo grade đang bán.'],
                'interior' => ['Cấu hình nội thất theo phiên bản; nguồn trang mẫu không mô tả đủ để xác nhận trang bị chung.'],
                'performance' => ['Trang Toyota Việt Nam công bố động cơ V6 tăng áp kép 3.5L và hộp số tự động 10 cấp.'],
                'safety' => ['Trang chính thức nêu ABS, EBD, BA, HAC và VSC; các trang bị khác cần đối chiếu đúng grade.'],
            ],
            'land-cruiser-hev' => [
                'exterior' => ['SUV Land Cruiser HEV; trang bị ngoại thất chi tiết cần đối chiếu catalogue đúng phiên bản.'],
                'interior' => ['Cấu hình nội thất theo grade; trang mẫu chưa cung cấp bảng phân biệt đủ rõ để xác nhận trang bị chung.'],
                'performance' => ['Thông số truyền động HEV cần xác nhận bằng catalogue/thông số kỹ thuật đúng grade trước khi công bố.'],
                'safety' => ['Trang bị an toàn có thể theo grade; cần đối chiếu bảng thông số chính thức của đúng phiên bản.'],
            ],
            'land-cruiser-prado' => [
                'exterior' => ['SUV địa hình Land Cruiser Prado; trang bị chi tiết ngoại thất theo grade.'],
                'interior' => ['Cấu hình khoang cabin theo grade; xem thông số Toyota Việt Nam để xác nhận trang bị.'],
                'performance' => ['Động cơ tăng áp 2.4L, hộp số tự động 8 cấp; hệ thống treo thích ứng AVS và chế độ địa hình được giới thiệu.'],
                'safety' => ['Toyota công bố các tính năng hỗ trợ như SEA và DRCC; trang bị cụ thể theo grade.'],
            ],
            'land-cruiser-fj' => [
                'exterior' => ['SUV địa hình Land Cruiser FJ; cấu hình ngoại thất theo phiên bản Toyota phân phối.'],
                'interior' => ['Khoang cabin 5 chỗ; tiện nghi và vật liệu được thể hiện theo cấu hình xe.'],
                'performance' => ['Động cơ xăng 2.7L 2TR-FE; có chế độ khởi hành số 2 hỗ trợ bề mặt trơn trượt.'],
                'safety' => ['Trang Toyota Việt Nam giới thiệu RCTA, BSM, HAC, LDA, DRCC, PCS và AHS.'],
            ],
            'alphard' => [
                'exterior' => ['MPV hạng sang 7 chỗ; trang bị chi tiết theo phiên bản HEV/xăng.'],
                'interior' => ['Bố trí 7 chỗ; tiện nghi hàng ghế sau được cấu hình theo phiên bản.'],
                'performance' => ['Phiên bản HEV sử dụng động cơ xăng 2.5L kết hợp hệ thống Hybrid.'],
                'safety' => ['Toyota Safety Sense và các hỗ trợ giữ làn/điều khiển hành trình được công bố theo grade.'],
            ],
        ];

        foreach ($features[$slug] ?? [] as $category => $items) {
            foreach ($items as $index => $value) {
                $name = match ($category) {
                    'exterior' => 'Thông tin ngoại thất',
                    'interior' => 'Thông tin nội thất',
                    'performance' => 'Động cơ và vận hành',
                    'safety' => 'Trang bị an toàn',
                };

                $vehicle->specs()->updateOrCreate(
                    ['name' => $name, 'category' => $category],
                    [
                        'value' => $value,
                        'sort_order' => 1 + $index,
                        'source_url' => $source,
                        'verified_at' => self::VERIFIED_AT,
                    ],
                );
            }
        }
    }
}
