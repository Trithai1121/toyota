<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->foreignId('content_page_id')->nullable()->constrained('pages')->nullOnDelete();
        });

        Schema::table('campaign_landing_pages', function (Blueprint $table): void {
            $table->foreignId('content_page_id')->nullable()->constrained('pages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_landing_pages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('content_page_id');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('content_page_id');
        });
    }
};
