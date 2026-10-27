<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table): void {
            $table->string('transmission')->nullable()->after('fuel_type');
            $table->unsignedSmallInteger('engine_displacement_cc')->nullable()->after('transmission');
            $table->string('origin')->nullable()->after('engine_displacement_cc');
            $table->json('color_options')->nullable()->after('origin');
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table): void {
            $table->dropColumn(['transmission', 'engine_displacement_cc', 'origin', 'color_options']);
        });
    }
};
