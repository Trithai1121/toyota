<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->string('official_source_url')->nullable();
            $table->date('content_verified_at')->nullable();
            $table->string('catalogue_url')->nullable();
            $table->string('manual_url')->nullable();
        });

        Schema::table('variants', function (Blueprint $table): void {
            $table->string('source_url')->nullable();
            $table->date('verified_at')->nullable();
        });

        Schema::table('specs', function (Blueprint $table): void {
            $table->string('source_url')->nullable();
            $table->date('verified_at')->nullable();
        });

        Schema::table('vehicle_images', function (Blueprint $table): void {
            $table->string('source_url')->nullable();
            $table->date('rights_verified_at')->nullable();
        });

        Schema::table('promotions', function (Blueprint $table): void {
            $table->string('source_url')->nullable();
            $table->date('verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->dropColumn(['source_url', 'verified_at']);
        });

        Schema::table('vehicle_images', function (Blueprint $table): void {
            $table->dropColumn(['source_url', 'rights_verified_at']);
        });

        Schema::table('specs', function (Blueprint $table): void {
            $table->dropColumn(['source_url', 'verified_at']);
        });

        Schema::table('variants', function (Blueprint $table): void {
            $table->dropColumn(['source_url', 'verified_at']);
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn(['official_source_url', 'content_verified_at', 'catalogue_url', 'manual_url']);
        });
    }
};
