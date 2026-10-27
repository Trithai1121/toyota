<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('cover_image_path')->nullable()->after('excerpt');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->uuid('submission_token')->nullable()->unique()->after('event_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $table) => $table->dropUnique(['submission_token']));
        Schema::table('leads', fn (Blueprint $table) => $table->dropColumn('submission_token'));
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn('cover_image_path'));
    }
};
