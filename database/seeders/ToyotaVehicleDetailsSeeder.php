<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ToyotaVehicleDetailsSeeder extends Seeder
{
    private const VERIFIED_AT = '2026-10-24';

    private const SECTIONS = [
        'exterior' => 'exterior',
        'interior' => 'funiture',
        'performance' => 'operation',
        'safety' => 'safety',
    ];

    public function run(): void
    {
        Vehicle::query()
            ->where('is_published', true)
            ->whereNotNull('official_source_url')
            ->with('specs')
            ->get()
            ->each(fn (Vehicle $vehicle) => $this->syncVehicle($vehicle));
    }

    private function syncVehicle(Vehicle $vehicle): void
    {
        $vehicle->images()
            ->where('source_note', 'Ảnh sản phẩm trên trang Toyota Việt Nam; dùng làm ảnh đại diện ngoại thất.')
            ->update(['category' => 'overview']);

        $page = Http::timeout(15)->get($vehicle->official_source_url);
        if (! $page->successful()
            || ! preg_match('/id=["\']get-data-car["\'][^>]*data-modelid=["\'](\d+)["\'][^>]*data-gradeid=["\'](\d+)["\']/i', $page->body(), $ids)) {
            return;
        }

        [$modelId, $gradeId] = [(int) $ids[1], (int) $ids[2]];
        $vehicle->specs()
            ->whereIn('name', ['Thông tin ngoại thất', 'Thông tin nội thất', 'Động cơ và vận hành', 'Trang bị an toàn'])
            ->delete();

        foreach (self::SECTIONS as $category => $endpoint) {
            $response = Http::timeout(15)->get('https://www.toyota.com.vn/api/car/detail/'.$endpoint, [
                'modelId' => $modelId,
                'gradeId' => $gradeId,
            ]);
            if (! $response->successful()) {
                continue;
            }

            $items = data_get($response->json(), 'Data_Ext.result.items', []);
            foreach (array_values(array_filter($items, fn ($item): bool => is_array($item) && filled($item['name'] ?? null))) as $index => $item) {
                $name = trim(strip_tags((string) $item['name']));
                $detail = trim(html_entity_decode(strip_tags((string) ($item['detail'] ?? $item['ShortDescription'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($detail !== '') {
                    $vehicle->specs()->updateOrCreate(
                        ['name' => $name, 'category' => $category],
                        [
                            'value' => $detail,
                            'sort_order' => $index + 1,
                            'source_url' => $vehicle->official_source_url,
                            'verified_at' => self::VERIFIED_AT,
                        ],
                    );
                }

                $assetUrl = $this->officialAssetUrl((string) ($item['url'] ?? ''));
                if ($assetUrl !== null) {
                    $this->storeImage($vehicle, $category, $name, $assetUrl, $index);
                }
            }
        }

        $vehicle->forceFill([
            'seo_title' => 'Toyota '.$vehicle->name.' | Ngoại thất, nội thất, vận hành và an toàn',
            'seo_description' => 'Khám phá '.$vehicle->name.': hình ảnh và thông tin ngoại thất, nội thất, vận hành, an toàn cùng các phiên bản. Tham khảo nguồn Toyota Việt Nam; trang bị có thể khác theo phiên bản.',
            'content_verified_at' => self::VERIFIED_AT,
        ])->save();
    }

    private function officialAssetUrl(string $path): ?string
    {
        $path = html_entity_decode(trim($path), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (Str::startsWith($path, 'https://www.toyota.com.vn/')) {
            $parts = parse_url($path);
            if (($parts['host'] ?? null) !== 'www.toyota.com.vn') {
                return null;
            }

            $path = '/'.ltrim($parts['path'] ?? '', '/');
            if (filled($parts['query'] ?? null)) {
                $path .= '?'.$parts['query'];
            }
        }

        if (Str::startsWith($path, '//Resources/')) {
            $path = '/'.ltrim($path, '/');
        }

        return (Str::startsWith($path, '/Resources/') || Str::startsWith($path, '/media/'))
            ? 'https://www.toyota.com.vn'.$path
            : null;
    }

    private function storeImage(Vehicle $vehicle, string $category, string $name, string $url, int $sortOrder): void
    {
        $existing = $vehicle->images()->where('source_url', $url)->first();
        if ($existing?->isReadyForDisplay()
            && strtolower(pathinfo($existing->path, PATHINFO_EXTENSION)) === 'webp') {
            return;
        }

        $relativeUrl = substr($url, strlen('https://www.toyota.com.vn'));
        $optimizedUrl = 'https://www.toyota.com.vn/compression/webp?u='.rawurlencode($relativeUrl).'&q=78';
        $response = Http::timeout(15)->get($optimizedUrl);
        $extension = 'webp';

        if (! $response->successful() || ! str_starts_with(strtolower((string) $response->header('Content-Type')), 'image/webp')) {
            $response = Http::timeout(15)->get($url);
            $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            $extension = match ($mime) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/jpeg' => 'jpg',
                default => null,
            };
        }

        if (! $response->successful() || $extension === null || strlen($response->body()) > 12 * 1024 * 1024) {
            return;
        }

        $dimensions = @getimagesizefromstring($response->body());
        if (! $dimensions
            || ($dimensions[0] ?? 0) < 1
            || ($dimensions[1] ?? 0) < 1
            || ($dimensions[0] * $dimensions[1]) > 40_000_000) {
            return;
        }

        $oldPath = $existing?->path;
        $relativePath = 'vehicles/toyota/'.$vehicle->slug.'/'.$category.'/'.substr(hash('sha256', $url), 0, 24).'.'.$extension;
        if (! Storage::disk('public')->put($relativePath, $response->body())) {
            return;
        }

        $attributes = [
            'path' => $relativePath,
            'alt_text' => $name.' — '.$vehicle->name,
            'source_note' => 'Ảnh minh họa trang bị lấy từ trang sản phẩm Toyota Việt Nam.',
            'category' => $category,
            'sort_order' => $sortOrder,
            'rights_verified_at' => self::VERIFIED_AT,
        ];

        if ($existing) {
            $existing->fill($attributes)->save();
        } else {
            $vehicle->images()->create(['source_url' => $url, ...$attributes]);
        }

        if ($oldPath && $oldPath !== $relativePath) {
            $this->deleteProcessedImageFiles($oldPath);
        }
    }

    private function deleteProcessedImageFiles(string $path): void
    {
        $disk = Storage::disk('public');
        $directory = dirname($path);
        $stem = pathinfo($path, PATHINFO_FILENAME);
        $disk->delete($path);
        $disk->delete($directory.'/'.$stem.'.json');
        foreach ([480, 960, 1600] as $width) {
            $disk->delete($directory.'/'.$stem.'-'.$width.'.webp');
        }
    }
}
