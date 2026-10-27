<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AppearanceSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'Giao diện';

    protected static ?string $navigationLabel = 'Giao diện & Màu sắc';

    protected static ?string $title = 'Giao diện & Màu sắc';

    protected static ?string $slug = 'giao-dien';

    protected string $view = 'filament.pages.appearance-settings';

    public array $settings = [];

    private const DEFAULTS = [
        'color_page_bg' => '#FFFFFF',
        'page_bg_gradient_enabled' => true,
        'page_bg_gradient_end' => '#F3E8EA',
        'page_bg_gradient_direction' => '135deg',
        'color_text' => '#1F2328',
        'color_footer_bg' => '#2B2F33',
        'navbar_follow_footer' => true,
        'color_navbar_bg' => '#2B2F33',
        'color_nav_button_border' => '#8A9096',
        'color_nav_button_hover' => '#EB0A1E',
    ];

    public function mount(): void
    {
        $this->settings = self::DEFAULTS;

        foreach (SiteSetting::query()->pluck('value', 'key') as $key => $value) {
            if (array_key_exists($key, $this->settings)) {
                $this->settings[$key] = in_array($key, ['navbar_follow_footer', 'page_bg_gradient_enabled'], true)
                    ? $value === '1'
                    : $value;
            }
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'settings.color_page_bg' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'settings.page_bg_gradient_enabled' => ['required', 'boolean'],
            'settings.page_bg_gradient_end' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'settings.page_bg_gradient_direction' => ['required', 'in:90deg,135deg,180deg'],
            'settings.color_text' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'settings.color_footer_bg' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'settings.navbar_follow_footer' => ['required', 'boolean'],
            'settings.color_navbar_bg' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'settings.color_nav_button_border' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'settings.color_nav_button_hover' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
        ], [
            'settings.*.required' => 'Vui lòng nhập mã màu theo định dạng #RRGGBB.',
            'settings.*.regex' => 'Mã màu không hợp lệ. Hãy dùng đúng định dạng #RRGGBB, ví dụ #2B2F33.',
            'settings.navbar_follow_footer.boolean' => 'Tùy chọn dùng chung màu phải được bật hoặc tắt.',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => in_array($key, ['navbar_follow_footer', 'page_bg_gradient_enabled'], true)
                    ? ($value ? '1' : '0')
                    : (str_ends_with($key, '_direction') ? $value : strtoupper($value))],
            );
        }

        Notification::make()->success()->title('Đã cập nhật giao diện')->send();
    }

    public function resetDefaults(): void
    {
        $this->settings = self::DEFAULTS;
        $this->save();
    }
}
