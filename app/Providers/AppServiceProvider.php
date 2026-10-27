<?php

namespace App\Providers;

use App\Models\BlockPreset;
use App\Models\CampaignLandingPage;
use App\Models\LeadNote;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageRevision;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Spec;
use App\Models\ThemeSetting;
use App\Models\UiString;
use App\Models\Variant;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Policies\CatalogContentPolicy;
use App\Policies\LeadNotePolicy;
use App\Policies\PageRevisionPolicy;
use App\Policies\SettingsPolicy;
use App\Services\SiteTheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('ui', fn (string $expression): string => "<?php echo e(app(\\App\\Services\\UiText::class)->get({$expression})); ?>");
        View::composer('layouts.site', function ($view): void {
            $vehicleRelations = [
                'line:id,name,slug,category',
                'images' => fn ($query) => $query->select(['id', 'vehicle_id', 'path', 'alt_text', 'color_name', 'sort_order'])->orderBy('sort_order'),
                'variants' => fn ($query) => $query->select(['id', 'vehicle_id', 'name', 'slug', 'list_price', 'fuel_type', 'is_published', 'verified_at', 'source_url'])->where('is_published', true)->orderBy('sort_order'),
                'promotions' => fn ($query) => $query
                    ->where('is_published', true)
                    ->where(fn ($builder) => $builder->whereNull('starts_at')->orWhereDate('starts_at', '<=', today()))
                    ->where(fn ($builder) => $builder->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())),
            ];
            if (Schema::hasTable('vehicle_categories')) {
                $vehicleRelations['categories'] = fn ($query) => $query->select(['vehicle_categories.id', 'name', 'slug']);
            }

            $navVehicles = Vehicle::query()
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->whereHas('line', fn ($query) => $query->where('is_published', true))
                ->with($vehicleRelations)
                ->orderBy('name')
                ->get();

            $view->with('siteTheme', app(SiteTheme::class)->all())
                ->with('navVehicles', $navVehicles);
        });

        RateLimiter::for('lead-submissions', fn (Request $request): Limit => Limit::perMinutes(10, 5)->by((string) $request->ip()));

        foreach ([
            CampaignLandingPage::class,
            BlockPreset::class,
            Page::class,
            PageBlock::class,
            Post::class,
            Promotion::class,
            Spec::class,
            Variant::class,
            VehicleImage::class,
        ] as $model) {
            Gate::policy($model, CatalogContentPolicy::class);
        }

        Gate::policy(Setting::class, SettingsPolicy::class);
        Gate::policy(ThemeSetting::class, CatalogContentPolicy::class);
        Gate::policy(UiString::class, CatalogContentPolicy::class);
        Gate::policy(LeadNote::class, LeadNotePolicy::class);
        Gate::policy(PageRevision::class, PageRevisionPolicy::class);
    }
}
