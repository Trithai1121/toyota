<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table): void {
            $table->string('category', 32)->nullable()->after('color_hex')->index();
        });

        Schema::table('specs', function (Blueprint $table): void {
            $table->string('category', 32)->nullable()->after('value')->index();
        });
    }

    public function down(): void
    {
        Schema::table('specs', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });

        Schema::table('vehicle_images', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
