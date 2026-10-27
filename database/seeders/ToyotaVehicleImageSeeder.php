<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ToyotaVehicleImageSeeder extends Seeder
{
    public function run(): void
    {
        Vehicle::query()
            ->where('is_published', true)
            ->whereNotNull('official_source_url')
            ->get()
            ->each(function (Vehicle $vehicle): void {
                $existing = $vehicle->images()->where('category', 'exterior')->get()
                    ->first(fn (VehicleImage $image): bool => $image->isReadyForDisplay());

                if ($existing) {
                    return;
                }

                $page = Http::timeout(25)->get($vehicle->official_source_url);
                if (! $page->successful()) {
                    return;
                }

                if (! preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)/i', $page->body(), $matches)
                    && ! preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $page->body(), $matches)) {
                    return;
                }

                $imageUrl = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (! Str::startsWith($imageUrl, 'https://www.toyota.com.vn/')) {
                    return;
                }

                $image = Http::timeout(30)->get($imageUrl);
                $mime = strtolower(trim(explode(';', (string) $image->header('Content-Type'))[0]));
                $extension = match ($mime) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/jpeg' => 'jpg',
                    default => null,
                };

                if (! $image->successful() || $extension === null || strlen($image->body()) > 8 * 1024 * 1024) {
                    return;
                }

                $path = 'vehicles/toyota/'.$vehicle->slug.'-exterior.'.$extension;
                if (! Storage::disk('public')->put($path, $image->body())) {
                    return;
                }

                $vehicle->images()->create([
                    'path' => $path,
                    'alt_text' => 'Ảnh ngoại thất '.$vehicle->name.' từ Toyota Việt Nam',
                    'source_note' => 'Ảnh sản phẩm trên trang Toyota Việt Nam; dùng làm ảnh đại diện ngoại thất.',
                    'source_url' => $imageUrl,
                    'rights_verified_at' => '2026-10-24',
                    'category' => 'exterior',
                    'sort_order' => 0,
                ]);
            });
    }
}
