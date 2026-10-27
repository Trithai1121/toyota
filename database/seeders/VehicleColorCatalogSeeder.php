<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleVariantColor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VehicleColorCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Names/codes follow Toyota Vietnam's current product pages and catalogues.
        // Surcharges are taken from Toyota Vietnam's published 2026 price tables.
        $catalog = [
            'Wigo' => [
                ['Đỏ', '#B51F32', 0], ['Cam', '#E66A24', 0], ['Trắng', '#F5F5F2', 0], ['Bạc', '#B8BDC2', 0],
            ],
            'vios' => [
                ['Trắng', '#F5F5F2', 0], ['Trắng ngọc trai', '#F8F7F2', 8000000],
                ['Bạc', '#B8BDC2', 0], ['Đen', '#17191B', 0], ['Đỏ', '#A91E2C', 0], ['Nâu ánh vàng', '#856548', 0],
            ],
            'yaris-cross' => [
                ['Đen', '#17191B', 0], ['Trắng ngọc trai', '#F8F7F2', 8000000],
                ['Cam/Đen', '#E66A24', 12000000], ['Ngọc lam/Đen', '#007E83', 12000000],
                ['Trắng ngọc trai/Đen', '#F8F7F2', 12000000],
            ],
            'CorollaCross' => [
                ['Xám ánh bạc', '#858B90', 0], ['Đỏ', '#A91E2C', 8000000],
                ['Đen', '#17191B', 0], ['Trắng ngọc trai', '#F8F7F2', 8000000], ['Xám', '#666B70', 0],
            ],
            'Raize' => [
                ['Đen', '#17191B', 0], ['Đỏ', '#B51F32', 0], ['Trắng ngọc trai', '#F8F7F2', 8000000],
                ['Trắng/Đen', '#F5F5F2', 12000000], ['Đỏ/Đen', '#B51F32', 12000000],
                ['Ngọc lam/Đen', '#007E83', 12000000], ['Trắng ngọc trai/Đen', '#F8F7F2', 12000000],
            ],
            'VelozCross' => [
                ['Bạc tím', '#8D8993', 0], ['Bạc', '#B8BDC2', 0], ['Đen', '#17191B', 0],
                ['Đỏ', '#A91E2C', 0], ['Trắng ngọc trai', '#F8F7F2', 8000000],
            ],
            'AvanzaPremio' => [
                ['Bạc tím', '#8D8993', 0], ['Bạc', '#B8BDC2', 0], ['Trắng', '#F5F5F2', 0], ['Đen', '#17191B', 0],
            ],
            'InnovaCross' => [
                ['Bạc', '#B8BDC2', 0], ['Đen', '#17191B', 0], ['Trắng ngọc trai', '#F8F7F2', 8000000],
            ],
            'Camry' => [
                ['Đen', '#17191B', 0], ['Trắng ngọc trai', '#F8F7F2', 12000000],
                ['Xám ánh kim', '#858B90', 12000000], ['Đỏ', '#A91E2C', 12000000],
            ],
            'Fortuner' => [
                ['Đen', '#17191B', 0], ['Bạc', '#B8BDC2', 0], ['Trắng', '#F5F5F2', 0],
                ['Nâu đồng', '#806044', 0], ['Trắng ngọc trai', '#F8F7F2', 8000000],
                ['Trắng ngọc trai nóc đen', '#F8F7F2', 12000000, 'LEGENDER'],
                ['Nâu đồng nóc đen', '#806044', 12000000, 'LEGENDER'],
            ],
            'Hilux' => [
                ['Trắng ngọc trai', '#F8F7F2', 8000000], ['Đen', '#17191B', 0],
                ['Bạc', '#B8BDC2', 0], ['Vàng', '#C99A27', 0], ['Xám', '#777C81', 0],
            ],
            'LandCruiser' => [
                ['Đen', '#17191B', 0], ['Trắng ngọc trai', '#F8F7F2', 0],
                ['Nâu', '#806044', 0, 'FJ'],
                ['Nâu đồng', '#806044', 0, 'PRADO'],
            ],
            'alphard' => [
                ['Đen', '#17191B', 0], ['Trắng ngọc trai', '#F8F7F2', 20000000],
                ['Nâu ánh vàng', '#9B7954', 20000000],
            ],
        ];

        foreach ($catalog as $vehicleSlug => $colorRows) {
            $vehicle = Vehicle::query()->with('images')->where('slug', $vehicleSlug)->first();

            if ($vehicle === null) {
                continue;
            }

            $variants = $vehicle->variants()->where('is_published', true)->orderBy('sort_order')->get();
            $activeColorSlugs = [];

            foreach ($colorRows as $index => $row) {
                [$name, $hex, $surcharge] = $row;
                $variantNameFilter = $row[3] ?? null;

                $color = VehicleColor::query()->firstOrNew([
                    'vehicle_id' => $vehicle->id,
                    'slug' => Str::slug($name),
                ]);
                $color->fill([
                    'name' => $name,
                    'hex_code' => $hex,
                    'surcharge' => $surcharge,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ])->save();
                $activeColorSlugs[] = $color->slug;
                $plainColorName = $name;
                $verifiedImage = $vehicle->images->first(fn ($image): bool => $image->isReadyForDisplay()
                    && mb_strtolower(trim((string) $image->color_name)) === mb_strtolower($plainColorName));

                foreach ($variants as $variant) {
                    if ($variantNameFilter !== null && ! str_contains(Str::upper($variant->name), $variantNameFilter)) {
                        continue;
                    }

                    $variantSurcharge = null;
                    if ($vehicleSlug === 'LandCruiser' && $name === 'Trắng ngọc trai') {
                        $variantSurcharge = str_contains(Str::upper($variant->name), 'FJ') ? 12000000 : 20000000;
                    }

                    $link = VehicleVariantColor::query()->firstOrNew([
                        'variant_id' => $variant->id,
                        'vehicle_color_id' => $color->id,
                    ]);
                    $link->surcharge_override = $variantSurcharge;
                    $link->image_path ??= $verifiedImage?->path;
                    $link->save();
                }
            }

            $inactiveColors = $vehicle->colors()->whereNotIn('slug', $activeColorSlugs)->get();
            foreach ($inactiveColors as $inactiveColor) {
                $inactiveColor->update(['is_active' => false]);
                $inactiveColor->variants()->detach($variants->modelKeys());
            }

            $this->command?->info("Toyota colors refreshed: {$vehicle->name} (".count($activeColorSlugs).' colors).');
        }
    }
}
