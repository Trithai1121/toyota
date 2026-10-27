<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULTS = [
        'color_page_bg' => '#FFFFFF',
        'color_text' => '#1F2328',
        'color_footer_bg' => '#2B2F33',
        'navbar_follow_footer' => '1',
        'color_navbar_bg' => '#2B2F33',
        'color_nav_button_border' => '#8A9096',
        'color_nav_button_hover' => '#EB0A1E',
    ];

    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('site_settings')->insert(collect(self::DEFAULTS)->map(fn (string $value, string $key): array => [
            'key' => $key,
            'value' => $value,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
