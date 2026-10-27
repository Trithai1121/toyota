<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table): void {
            $table->string('color_name', 80)->nullable()->after('source_note');
            $table->string('color_hex', 7)->nullable()->after('color_name');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table): void {
            $table->dropColumn(['color_name', 'color_hex']);
        });
    }
};
