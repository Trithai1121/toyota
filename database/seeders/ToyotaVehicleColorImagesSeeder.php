<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ToyotaVehicleColorImagesSeeder extends Seeder
{
    private const VERIFIED_AT = '2026-10-24';

    public function run(): void
    {
        $unmatched = [];

        Vehicle::query()
            ->where('is_published', true)
            ->whereNotNull('official_source_url')
            ->with(['colors', 'variants'])
            ->get()
            ->each(function (Vehicle $vehicle) use (&$unmatched): void {
                $page = Http::timeout(25)->retry(2, 500)->get($vehicle->official_source_url);
                if (! $page->successful() || ! preg_match('/id=["\']get-data-car["\'][^>]*data-modelid=["\'](\d+)["\'][^>]*data-gradeid=["\'](\d+)["\']/i', $page->body(), $ids)) {
                    $unmatched[$vehicle->name] = ['Lỗi tải trang sản phẩm/không đọc được mã xe'];

                    return;
                }

                $response = Http::timeout(25)->retry(2, 500)->get('https://www.toyota.com.vn/api/products/color', [
                    'modelId' => (int) $ids[1],
                    'gradeId' => (int) $ids[2],
                ]);
                $officialColors = data_get($response->json(), 'Data_Ext.result.vehicleImages', []);
                if (! $response->successful() || ! is_array($officialColors)) {
                    $unmatched[$vehicle->name] = ['Toyota API không trả danh sách ảnh màu'];

                    return;
                }

                $matchedSlugs = [];
                foreach ($officialColors as $index => $officialColor) {
                    $imageUrl = $this->officialImageUrl((string) ($officialColor['imageUrl'] ?? ''));
                    $code = trim((string) ($officialColor['colorCode'] ?? ''));
                    $name = trim((string) ($officialColor['colorName'] ?? ''));
                    if ($imageUrl === null || $name === '') {
                        continue;
                    }

                    $color = $this->findColor($vehicle, $name, $code, $matchedSlugs);
                    if ($color === null) {
                        $color = VehicleColor::query()->firstOrCreate(
                            ['vehicle_id' => $vehicle->id, 'slug' => Str::slug($name.' '.$code)],
                            [
                                'name' => $name,
                                'hex_code' => $this->validHex((string) ($officialColor['hexcode'] ?? ''), (string) ($officialColor['hexcode2'] ?? '')),
                                'surcharge' => 0,
                                'sort_order' => ($vehicle->colors->max('sort_order') ?? 0) + 1,
                                'is_active' => true,
                            ],
                        );
                        $vehicle->setRelation('colors', $vehicle->colors->push($color));
                    }
                    if (! $color->is_active) {
                        $color->update(['is_active' => true]);
                    }

                    $image = $this->storeImage($vehicle, $color, $imageUrl, $index);
                    if ($image === null) {
                        continue;
                    }

                    $matchedSlugs[] = $color->slug;
                    $vehicle->variants->each(function ($variant) use ($color, $image): void {
                        $variant->colors()->syncWithoutDetaching([$color->id => ['image_path' => $image->path]]);
                    });
                }

                $missing = $vehicle->colors
                    ->where('is_active', true)
                    ->reject(fn (VehicleColor $color): bool => in_array($color->slug, $matchedSlugs, true))
                    ->values();
                if ($missing->isNotEmpty()) {
                    // Do not leave selectable colors with no image or no match in
                    // Toyota's current paint list for this model/grade.
                    $missing->each(fn (VehicleColor $color) => $color->update(['is_active' => false]));
                    $unmatched[$vehicle->name] = $missing->pluck('name')->all();
                }

                $this->command?->info("Ảnh màu Toyota đã đồng bộ: {$vehicle->name} (".count($matchedSlugs).' màu).');
            });

        if ($unmatched !== []) {
            $this->command?->warn('Các màu chưa có ảnh khớp chính xác từ Toyota API:');
            foreach ($unmatched as $vehicle => $colors) {
                $this->command?->line($vehicle.': '.implode(', ', $colors));
            }
        }
    }

    private function findColor(Vehicle $vehicle, string $officialName, string $code, array $alreadyMatched): ?VehicleColor
    {
        $official = $this->normalize($officialName);
        $officialCode = Str::slug($code);
        $available = $vehicle->colors->reject(fn (VehicleColor $color): bool => in_array($color->slug, $alreadyMatched, true));

        if ($officialCode !== '') {
            $coded = $available->first(fn (VehicleColor $color): bool => Str::endsWith($color->slug, '-'.$officialCode));
            if ($coded !== null) {
                return $coded;
            }
        }

        $matches = $available->filter(function (VehicleColor $color) use ($official, $officialCode): bool {
            $configured = $this->normalize($color->name);
            if ($configured === $official || ($officialCode !== '' && Str::endsWith($color->slug, $officialCode))) {
                return true;
            }

            // Accept only a meaningful color-name match (for example "Đỏ" and
            // "Đỏ (3U5)"); do not guess between unrelated paint colors.
            $configuredWords = array_filter(explode(' ', $configured), fn (string $word): bool => strlen($word) > 2);
            $officialWords = array_filter(explode(' ', $official), fn (string $word): bool => strlen($word) > 2);

            return $configuredWords !== [] && array_diff($configuredWords, $officialWords) === [];
        });

        return $matches->sortByDesc(fn (VehicleColor $color): int => $color->is_active ? 1 : 0)->first();
    }

    private function validHex(string $first, string $second): string
    {
        $hex = preg_match('/^#[0-9a-f]{6}$/i', $first) === 1 ? $first : $second;

        return preg_match('/^#[0-9a-f]{6}$/i', $hex) === 1 ? $hex : '#D1D5DB';
    }

    private function storeImage(Vehicle $vehicle, VehicleColor $color, string $url, int $sortOrder): ?VehicleImage
    {
        $response = Http::timeout(30)->retry(2, 500)->get($url);
        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        if (! $response->successful() || $mime !== 'image/png' || strlen($response->body()) > 15 * 1024 * 1024) {
            return null;
        }

        $dimensions = @getimagesizefromstring($response->body());
        if (! $dimensions || ($dimensions[0] ?? 0) < 1 || ($dimensions[1] ?? 0) < 1 || $dimensions[0] * $dimensions[1] > 40_000_000) {
            return null;
        }

        // Include a stable URL hash so the same named color with changed source art is refreshed safely.
        $path = 'vehicles/toyota/'.$vehicle->slug.'/colors/'.Str::slug($color->name).'-'.substr(hash('sha256', $url), 0, 12).'.png';
        if (! Storage::disk('public')->put($path, $response->body())) {
            return null;
        }

        $image = VehicleImage::query()->updateOrCreate(
            ['vehicle_id' => $vehicle->id, 'source_url' => $url],
            [
                'path' => $path,
                'alt_text' => $vehicle->name.' màu '.$color->name,
                'source_note' => 'Ảnh màu ngoại thất theo mã màu, lấy từ bộ chọn màu Toyota Việt Nam.',
                'rights_verified_at' => self::VERIFIED_AT,
                'color_name' => $color->name,
                'color_hex' => $color->hex_code,
                'category' => 'overview',
                'sort_order' => $sortOrder + 1,
            ],
        );

        return $image->fresh();
    }

    private function officialImageUrl(string $path): ?string
    {
        $path = html_entity_decode(trim($path), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (Str::startsWith($path, '/Resources/')) {
            return 'https://www.toyota.com.vn'.$path;
        }

        $host = parse_url($path, PHP_URL_HOST);

        return in_array($host, ['www.toyota.com.vn', 'ssa-api.toyotavn.com.vn', 'tmv-api.toyotavn.com.vn'], true)
            && Str::contains((string) parse_url($path, PHP_URL_PATH), '/Resources/')
                ? $path
                : null;
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/\([^)]*\)/u', '', $value) ?? $value;

        return Str::of(Str::ascii(mb_strtolower($value)))
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }
}
