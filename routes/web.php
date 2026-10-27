<?php

use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\CampaignLandingPageController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\PagePreviewController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response(
    "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /dang-ky\nDisallow: /cam-on\nSitemap: ".route('sitemap')."\n",
    200,
    ['Content-Type' => 'text/plain; charset=UTF-8'],
))->name('robots');

Route::get('/xe-toyota', [VehicleController::class, 'index'])->name('vehicles.index');
Route::get('/xe-toyota/{slug}/thong-so-ky-thuat', [VehicleController::class, 'specifications'])->name('vehicles.specifications');
Route::get('/xe-toyota/{slug}', [VehicleController::class, 'show'])->name('vehicles.show');

Route::get('/tin-tuc', [PostController::class, 'index'])->name('posts.index');
Route::get('/tin-tuc/{slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/cong-cu/tra-gop', [CalculatorController::class, 'installment'])->name('calculators.installment');
Route::get('/cong-cu/chi-phi-lan-banh', [CalculatorController::class, 'onRoad'])->name('calculators.on-road');

Route::get('/lp/{slug}', [CampaignLandingPageController::class, 'show'])->name('campaigns.show');

Route::get('/dang-ky', [LeadController::class, 'create'])->name('leads.create');
Route::post('/dang-ky', [LeadController::class, 'store'])->middleware('throttle:lead-submissions')->name('leads.store');
Route::get('/cam-on', [LeadController::class, 'thankYou'])->name('leads.thank-you');
Route::post('/cookie-consent', [CookieConsentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('cookie-consent.store');
Route::get('/admin/preview/pages/{page}', PagePreviewController::class)
    ->middleware(['auth', 'can:view,page'])
    ->name('pages.preview');

Route::get('/bao-duong', [StaticPageController::class, 'show'])->defaults('slug', 'bao-duong')->name('pages.maintenance');
Route::get('/phu-tung-phu-kien', [StaticPageController::class, 'show'])->defaults('slug', 'phu-tung-phu-kien')->name('pages.parts');
Route::get('/gioi-thieu', [StaticPageController::class, 'show'])->defaults('slug', 'gioi-thieu')->name('pages.about');
Route::get('/lien-he', [StaticPageController::class, 'show'])->defaults('slug', 'lien-he')->name('pages.contact');
Route::get('/chinh-sach-bao-mat', [StaticPageController::class, 'show'])->defaults('slug', 'chinh-sach-bao-mat')->name('pages.privacy');
Route::get('/trang/{slug}', [StaticPageController::class, 'show'])->name('pages.dynamic');
