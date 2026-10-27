<?php

namespace App\Services;

use App\Models\CampaignLandingPage;
use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\ThemeSetting;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PagePublicationChecklist
{
    public function validate(array $data): void
    {
        if (! in_array($data['status'] ?? null, ['published', 'scheduled'], true)) {
            return;
        }

        $errors = [];

        if (blank($data['seo_title'] ?? null)) {
            $errors['seo_title'] = 'Nhập tiêu đề SEO trước khi xuất bản.';
        }

        if (blank($data['seo_description'] ?? null)) {
            $errors['seo_description'] = 'Nhập mô tả SEO trước khi xuất bản.';
        }

        $dealerSettings = ThemeSetting::query()->where('key', 'dealer')->first()?->value;
        $hotline = is_array($dealerSettings) ? ($dealerSettings['hotline'] ?? null) : null;
        if (is_string($hotline) && filled($hotline) && ! str_contains($hotline, '[') && ! $this->isVietnamesePhone($hotline)) {
            $errors['dealer.hotline'] = 'Hotline trong cài đặt đại lý không đúng định dạng số điện thoại Việt Nam.';
        }

        if (($data['status'] ?? null) === 'scheduled') {
            $scheduleValidation = Validator::make(
                ['published_at' => $data['published_at'] ?? null],
                ['published_at' => ['required', 'date', 'after:now']],
            );

            if ($scheduleValidation->fails()) {
                $errors['published_at'] = 'Thời điểm xuất bản theo lịch phải là một ngày giờ trong tương lai.';
            }
        }

        $hasPromotionBlock = collect((array) ($data['blocks'] ?? []))
            ->contains(fn (mixed $block): bool => is_array($block) && ($block['type'] ?? null) === 'promotion_strip');

        foreach ((array) ($data['blocks'] ?? []) as $index => $block) {
            if (! is_array($block)) {
                continue;
            }

            $prefix = "blocks.{$index}";

            if (filled($block['image_path'] ?? null) && blank($block['image_alt'] ?? null)) {
                $errors["{$prefix}.image_alt"] = 'Ảnh cần có văn bản thay thế trước khi xuất bản.';
            }

            foreach ((array) data_get($block, 'settings.images', []) as $imageIndex => $image) {
                if (is_array($image) && filled($image['path'] ?? null) && blank($image['alt'] ?? null)) {
                    $errors["{$prefix}.settings.images.{$imageIndex}.alt"] = 'Ảnh thư viện cần có văn bản thay thế.';
                }
            }

            if (is_string($block['cta_url'] ?? null) && filled($block['cta_url']) && ! $this->isValidLink($block['cta_url'])) {
                $errors["{$prefix}.cta_url"] = 'Liên kết không hợp lệ hoặc không tìm thấy trang đích nội bộ.';
            }

            if (($block['type'] ?? null) === 'promotion_strip') {
                if (blank(data_get($block, 'settings.conditions'))) {
                    $errors["{$prefix}.settings.conditions"] = 'Ưu đãi cần có điều kiện đã xác nhận.';
                }

                if (blank(data_get($block, 'settings.ends_at'))) {
                    $errors["{$prefix}.settings.ends_at"] = 'Ưu đãi cần có thời hạn kết thúc.';
                } elseif (! Validator::make(
                    ['ends_at' => data_get($block, 'settings.ends_at')],
                    ['ends_at' => ['date', 'after:now']],
                )->passes()) {
                    $errors["{$prefix}.settings.ends_at"] = 'Thời hạn kết thúc ưu đãi phải là ngày giờ trong tương lai.';
                }
            }
        }

        $publishedPromotionWithoutConditions = $hasPromotionBlock
            && Promotion::query()
                ->where('is_published', true)
                ->where(fn ($query) => $query
                    ->whereNull('ends_at')
                    ->orWhereNull('conditions')
                    ->orWhere('conditions', '')
                    ->orWhereDate('ends_at', '<', today()))
                ->exists();

        if ($publishedPromotionWithoutConditions) {
            $errors['blocks'] = 'Cập nhật điều kiện và thời hạn cho mọi ưu đãi đang công khai trước khi xuất bản.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function isValidLink(string $url): bool
    {
        if (preg_match('/^tel:(.+)$/i', $url, $matches)) {
            return $this->isVietnamesePhone($matches[1]);
        }

        if (preg_match('/^mailto:(.+)$/i', $url, $matches)) {
            return Validator::make(['email' => $matches[1]], ['email' => ['required', 'email']])->passes();
        }

        if (str_starts_with($url, 'https://')) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        }

        if (! str_starts_with($url, '/')) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        if (str_starts_with($path, '/admin')) {
            return false;
        }

        if (str_starts_with($path, '/storage/')) {
            return Storage::disk('public')->exists(substr($path, strlen('/storage/')));
        }

        try {
            $request = Request::create($path, 'GET');
            $route = app('router')->getRoutes()->match($request);
        } catch (MethodNotAllowedHttpException|NotFoundHttpException) {
            return false;
        }

        return $this->hasPublicDestination($route);
    }

    private function hasPublicDestination(Route $route): bool
    {
        $parameters = $route->parameters();
        $slug = $parameters['slug'] ?? null;

        return match ($route->getName()) {
            'home' => Page::query()->publiclyAvailable()->where('slug', 'home')->exists(),
            'vehicles.show' => Vehicle::query()
                ->where('slug', $slug)
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->whereHas('line', fn ($query) => $query->where('is_published', true))
                ->exists(),
            'posts.show' => Post::query()->readyToPublish()
                ->where('slug', $slug)
                ->where('status', 'published')
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->exists(),
            'campaigns.show' => CampaignLandingPage::query()
                ->where('slug', $slug)
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->exists(),
            'pages.maintenance', 'pages.parts', 'pages.about', 'pages.contact', 'pages.privacy', 'pages.dynamic' => Page::query()
                ->publiclyAvailable()
                ->where('slug', $slug)
                ->exists(),
            default => $route->getName() !== null && ! str_starts_with($route->getName(), 'admin.'),
        };
    }

    private function isVietnamesePhone(string $phone): bool
    {
        $normalized = preg_replace('/[\s().-]/', '', $phone) ?? '';

        return (bool) preg_match('/^(?:\+84|84|0)(?:[0-9]{9,10})$/', $normalized);
    }
}
