<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
        });
        DB::table('pages')->where('is_published', true)->update(['status' => 'published']);

        Schema::create('page_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('background_color', 7)->nullable();
            $table->string('alignment')->default('left');
            $table->unsignedTinyInteger('spacing_top')->default(8);
            $table->unsignedTinyInteger('spacing_bottom')->default(8);
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('cta_style')->default('primary');
            $table->boolean('show_desktop')->default(true);
            $table->boolean('show_mobile')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->json('settings')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['page_id', 'is_enabled', 'sort_order']);
        });

        Schema::create('block_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->json('data');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('theme_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('ui_strings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('page_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('snapshot');
            $table->string('summary')->nullable();
            $table->timestamps();
            $table->index(['page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_revisions');
        Schema::dropIfExists('ui_strings');
        Schema::dropIfExists('theme_settings');
        Schema::dropIfExists('block_presets');
        Schema::dropIfExists('page_blocks');

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'published_at']);
        });
    }
};
