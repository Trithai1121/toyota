<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'color_page_bg' => '#FFFFFF',
            'page_bg_gradient_enabled' => '1',
            'page_bg_gradient_end' => '#F3E8EA',
            'page_bg_gradient_direction' => '135deg',
            'color_text' => '#1F2328',
            'color_footer_bg' => '#2B2F33',
            'navbar_follow_footer' => '1',
            'color_navbar_bg' => '#2B2F33',
            'color_nav_button_border' => '#8A9096',
            'color_nav_button_hover' => '#EB0A1E',
        ] as $key => $value) {
            SiteSetting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
