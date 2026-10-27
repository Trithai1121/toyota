<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\OnRoadFeeSettings;
use App\Services\PageContentPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    private const CATEGORIES = ['sedan', 'suv-crossover', 'mpv', 'pickup', 'hatchback', 'hybrid'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'category' => ['nullable', 'in:'.implode(',', self::CATEGORIES)],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:10000000000'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:10000000000', 'gte:min_price'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:99'],
            'fuel' => ['nullable', 'string', 'max:80'],
        ]);

        $query = Vehicle::query()
            ->where('is_published', true)
            ->where(fn ($builder) => $builder->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->whereHas('line', fn ($builder) => $builder->where('is_published', true))
            ->with([
                'line',
                'images' => fn ($builder) => $builder->orderBy('sort_order'),
                'variants' => fn ($builder) => $builder->where('is_published', true)->orderBy('sort_order'),
            ]);

        if (isset($filters['category'])) {
            $query->whereHas('line', fn ($builder) => $builder->where('category', $filters['category']));
        }

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->whereHas('variants', function ($builder) use ($filters): void {
                $builder->where('is_published', true)->whereNotNull('list_price');

                if (isset($filters['min_price'])) {
                    $builder->where('list_price', '>=', $filters['min_price']);
                }

                if (isset($filters['max_price'])) {
                    $builder->where('list_price', '<=', $filters['max_price']);
                }
            });
        }

        if (isset($filters['seats'])) {
            $query->whereHas('variants', fn ($builder) => $builder
                ->where('is_published', true)
                ->where('seats', $filters['seats']));
        }

        if (isset($filters['fuel'])) {
            $query->whereHas('variants', fn ($builder) => $builder
                ->where('is_published', true)
                ->where('fuel_type', $filters['fuel']));
        }

        $vehicles = $query->orderBy('name')->paginate(9)->withQueryString();
        $fuelTypes = Vehicle::query()
            ->where('vehicles.is_published', true)
            ->whereHas('variants', fn ($builder) => $builder->where('is_published', true)->whereNotNull('fuel_type'))
            ->join('variants', 'vehicles.id', '=', 'variants.vehicle_id')
            ->where('variants.is_published', true)
            ->whereNotNull('variants.fuel_type')
            ->distinct()
            ->orderBy('variants.fuel_type')
            ->pluck('variants.fuel_type');
        $seatCounts = Vehicle::query()
            ->join('variants', 'vehicles.id', '=', 'variants.vehicle_id')
            ->where('vehicles.is_published', true)
            ->where('variants.is_published', true)
            ->whereNotNull('variants.seats')
            ->distinct()
            ->orderBy('variants.seats')
            ->pluck('variants.seats');

        return view('vehicles.index', compact('vehicles', 'filters', 'fuelTypes', 'seatCounts'));
    }

    public function show(string $slug, Request $request, PageContentPresenter $presenter, OnRoadFeeSettings $feeSettings): View
    {
        $vehicle = Vehicle::query()
            ->where(fn ($query) => $query
                ->where('slug', $slug)
                ->orWhereRaw("LOWER(REPLACE(name, ' ', '-')) = ?", [mb_strtolower($slug)]))
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->whereHas('line', fn ($query) => $query->where('is_published', true))
            ->with([
                'line',
                'contentPage',
                'images',
                'variants' => fn ($query) => $query->where('is_published', true)->with(['colors' => fn ($colorQuery) => $colorQuery->where('is_active', true)->orderBy('sort_order')])->orderBy('sort_order'),
                'specs',
                'promotions' => fn ($query) => $query
                    ->where('is_published', true)
                    ->where(fn ($builder) => $builder->whereNull('starts_at')->orWhereDate('starts_at', '<=', today()))
                    ->where(fn ($builder) => $builder->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())),
            ])
            ->firstOrFail();

        $verifiedImages = $vehicle->images
            ->filter(fn ($image): bool => $image->isReadyForDisplay())
            ->values();

        $variantOptions = $vehicle->variants
            ->filter(fn ($variant): bool => $variant->hasVerifiedSource() && $variant->list_price !== null)
            ->map(function ($variant) use ($vehicle, $verifiedImages): array {
                $colors = $variant->colors
                    ->filter(fn ($color): bool => $color->is_active)
                    ->map(function ($color) use ($vehicle, $verifiedImages): array {
                        $imagePath = filled($color->pivot->image_path)
                            && Storage::disk('public')->exists($color->pivot->image_path)
                                ? $color->pivot->image_path
                                : null;
                        $galleryImage = $verifiedImages->first(fn ($image): bool => mb_strtolower((string) $image->color_name) === mb_strtolower($color->name));
                        $imagePath ??= $galleryImage?->path;

                        return [
                            'id' => $color->id,
                            'name' => $color->name,
                            'slug' => $color->slug,
                            'hex' => $color->hex_code,
                            'surcharge' => (int) ($color->pivot->surcharge_override ?? $color->surcharge),
                            'image' => $imagePath ? Storage::disk('public')->url($imagePath) : null,
                            'imageAlt' => $galleryImage?->alt_text ?: $vehicle->name.' màu '.$color->name,
                        ];
                    })
                    ->values();

                return [
                    'id' => $variant->id,
                    'slug' => $variant->slug,
                    'name' => $variant->name,
                    'listPrice' => (int) $variant->list_price,
                    'seats' => $variant->seats,
                    'bodyType' => $variant->body_type ?: match ($vehicle->line->category) {
                        'sedan' => 'Sedan',
                        'suv-crossover' => 'SUV / Crossover',
                        'mpv' => 'MPV',
                        'pickup' => 'Bán tải',
                        'hatchback' => 'Hatchback',
                        default => null,
                    },
                    'fuel' => $variant->fuel_type,
                    'transmission' => $variant->transmission,
                    'engine' => $variant->engine ?: ($variant->engine_displacement_cc ? number_format($variant->engine_displacement_cc, 0, ',', '.').' cc' : null),
                    'origin' => $variant->origin,
                    'verifiedAt' => $variant->verified_at?->format('d/m/Y'),
                    'specs' => $variant->specs ?: ['Thông tin phiên bản' => array_filter([
                        'Số chỗ' => $variant->seats ? $variant->seats.' chỗ' : null,
                        'Kiểu dáng' => $variant->body_type ?: null,
                        'Động cơ' => $variant->engine ?: ($variant->engine_displacement_cc ? number_format($variant->engine_displacement_cc, 0, ',', '.').' cc' : null),
                        'Nhiên liệu' => $variant->fuel_type,
                        'Hộp số' => $variant->transmission,
                        'Xuất xứ' => $variant->origin,
                    ])],
                    'colors' => $colors,
                ];
            })
            ->values();

        $requestedVariant = (string) $request->query('variant', '');
        $selectedVariant = $variantOptions->firstWhere('slug', $requestedVariant) ?? $variantOptions->first();
        $requestedColor = (string) $request->query('color', '');
        $selectedColor = $selectedVariant === null
            ? null
            : ($selectedVariant['colors']->firstWhere('slug', $requestedColor) ?? $selectedVariant['colors']->first());
        $selectedPrice = $selectedVariant === null
            ? null
            : $selectedVariant['listPrice'] + (int) ($selectedColor['surcharge'] ?? 0);
        $defaultVariant = $variantOptions->first();
        $defaultColor = $defaultVariant === null ? null : $defaultVariant['colors']->first();
        $defaultVariantPrice = $defaultVariant === null
            ? null
            : $defaultVariant['listPrice'] + (int) ($defaultColor['surcharge'] ?? 0);

        $content = $vehicle->contentPage
            ? $presenter->present($vehicle->contentPage)
            : ['blocks' => collect(), 'vehicles' => collect(), 'posts' => collect(), 'promotions' => collect()];

        $relatedVehicles = $content['blocks']->isNotEmpty()
            ? collect()
            : Vehicle::query()
                ->where('id', '!=', $vehicle->id)
                ->where('is_published', true)
                ->whereHas('line', function ($query) use ($vehicle): void {
                    $query->where('is_published', true);

                    if ($vehicle->line->category !== null) {
                        $query->where('category', $vehicle->line->category);
                    } else {
                        $query->whereKey($vehicle->vehicle_line_id);
                    }
                })
                ->with(['line', 'images' => fn ($query) => $query->orderBy('sort_order'), 'variants' => fn ($query) => $query->where('is_published', true)])
                ->limit(3)
                ->get();

        return view('vehicles.show', [
            ...$content,
            'vehicle' => $vehicle,
            'relatedVehicles' => $relatedVehicles,
            'onRoadFeeRegions' => $feeSettings->availableRegions(),
            'variantOptions' => $variantOptions,
            'selectedVariant' => $selectedVariant,
            'selectedColor' => $selectedColor,
            'selectedPrice' => $selectedPrice,
            'defaultVariantPrice' => $defaultVariantPrice,
        ]);
    }

    public function specifications(string $slug, Request $request): View
    {
        $vehicle = Vehicle::query()
            ->where(fn ($query) => $query
                ->where('slug', $slug)
                ->orWhereRaw("LOWER(REPLACE(name, ' ', '-')) = ?", [mb_strtolower($slug)]))
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->whereHas('line', fn ($query) => $query->where('is_published', true))
            ->with([
                'line',
                'images',
                'variants' => fn ($query) => $query->where('is_published', true)->whereNotNull('list_price')->orderBy('sort_order'),
                'specs',
            ])
            ->firstOrFail();

        $variants = $vehicle->variants
            ->filter(fn ($variant): bool => $variant->hasVerifiedSource())
            ->values();
        $selectedVariant = $variants->firstWhere('slug', (string) $request->query('variant')) ?? $variants->first();
        $specGroups = $vehicle->specs
            ->filter(fn ($spec): bool => $spec->hasVerifiedSource())
            ->groupBy(fn ($spec): string => in_array($spec->category, ['dimensions', 'exterior', 'interior', 'performance', 'safety'], true) ? $spec->category : 'general');
        $sectionImages = $vehicle->images
            ->filter(fn ($image): bool => $image->isReadyForDisplay())
            ->groupBy('category');

        return view('vehicles.specifications', compact('vehicle', 'variants', 'selectedVariant', 'specGroups', 'sectionImages'));
    }
}
