<?php

namespace Database\Seeders;

use App\Models\ThemeSetting;
use Illuminate\Database\Seeder;

class ThemeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'brand' => [
                'description' => 'Logo, màu sắc và kiểu chữ',
                'value' => [
                    'site_name' => 'Toyota Kon Tum',
                    'logo_path' => '',
                    'logo_alt' => 'Logo đại lý',
                    'favicon_path' => '',
                    'primary_color' => '#B71C1C',
                    'secondary_color' => '#1F2937',
                    'font' => 'system',
                    'button_style' => 'rounded',
                    'border_radius' => 12,
                    'content_width' => 1280,
                ],
            ],
            'header' => [
                'description' => 'Menu, nút liên hệ và thanh thông báo',
                'value' => [
                    'announcement_enabled' => false,
                    'announcement_text' => '',
                    'announcement_starts_at' => '',
                    'announcement_ends_at' => '',
                    'menu' => [
                        ['label' => 'Xe Toyota', 'url' => '/xe-toyota'],
                        ['label' => 'Bảo dưỡng', 'url' => '/bao-duong'],
                        ['label' => 'Phụ tùng - phụ kiện', 'url' => '/phu-tung-phu-kien'],
                        ['label' => 'Tính trả góp', 'url' => '/cong-cu/tra-gop'],
                        ['label' => 'Chi phí lăn bánh', 'url' => '/cong-cu/chi-phi-lan-banh'],
                        ['label' => 'Tin tức', 'url' => '/tin-tuc'],
                        ['label' => 'Liên hệ', 'url' => '/lien-he'],
                    ],
                    'quote_label' => 'Nhận tư vấn',
                    'quote_url' => '/dang-ky?type=quote',
                    'phone_label' => 'Gọi điện',
                    'zalo_label' => 'Zalo',
                ],
            ],
            'footer' => [
                'description' => 'Cột thông tin và liên kết chân trang',
                'value' => [
                    'columns' => [
                        ['title' => 'Hỗ trợ', 'links' => [
                            ['label' => 'Liên hệ', 'url' => '/lien-he'],
                            ['label' => 'Chính sách bảo mật', 'url' => '/chinh-sach-bao-mat'],
                            ['label' => 'Giới thiệu đại lý', 'url' => '/gioi-thieu'],
                        ]],
                    ],
                ],
            ],
            'dealer' => [
                'description' => 'Thông tin liên hệ và khu vực phục vụ',
                'value' => [
                    'name' => config('dealer.name'),
                    'address' => config('dealer.address'),
                    'hotline' => config('dealer.hotline'),
                    'zalo' => config('dealer.zalo'),
                    'email' => config('dealer.email'),
                    'hours' => config('dealer.hours'),
                    'maps_url' => config('dealer.maps_url'),
                    'maps_embed_url' => '',
                    'service_area' => '',
                    'service_regions' => ['Kon Tum', 'Gia Lai', 'Quảng Ngãi'],
                ],
            ],
            'tracking' => [
                'description' => 'ID đo lường quảng cáo; mã được tải sau khi có consent',
                'value' => [
                    'ga4_id' => config('tracking.ga4_id') ?? '',
                    'gtm_id' => config('tracking.gtm_id') ?? '',
                    'meta_pixel_id' => config('tracking.meta.pixel_id') ?? '',
                ],
            ],
            'floating_contact' => [
                'description' => 'Nút liên hệ nổi cho máy tính và điện thoại',
                'value' => [
                    'enabled' => false,
                    'desktop_enabled' => true,
                    'mobile_enabled' => true,
                    'position' => 'right',
                    'call_enabled' => true,
                    'zalo_enabled' => true,
                    'contact_widget_enabled' => true,
                    'contact_call_enabled' => true,
                    'contact_zalo_enabled' => true,
                    'contact_quote_enabled' => true,
                    'test_drive_enabled' => true,
                    'test_drive_url' => '/dang-ky?type=test_drive',
                ],
            ],
        ] as $key => $settings) {
            $setting = ThemeSetting::firstOrCreate(['key' => $key], $settings);
            $value = $setting->value ?? [];

            foreach ($settings['value'] as $field => $default) {
                if (! array_key_exists($field, $value)) {
                    $value[$field] = $default;
                }
            }

            if ($value !== $setting->value) {
                $setting->value = $value;
                $setting->save();
            }
        }
    }
}
