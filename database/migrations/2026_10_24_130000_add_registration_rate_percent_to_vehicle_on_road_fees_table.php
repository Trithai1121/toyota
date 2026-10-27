<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_on_road_fees', function (Blueprint $table): void {
            $table->decimal('registration_rate_percent', 5, 2)->nullable()->after('registration_fee');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_on_road_fees', function (Blueprint $table): void {
            $table->dropColumn('registration_rate_percent');
        });
    }
};
