<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_on_road_fees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->string('region', 32)->default('kon-tum');
            $table->unsignedBigInteger('registration_fee')->nullable();
            $table->decimal('body_insurance_rate_percent', 5, 2)->nullable();
            $table->unsignedBigInteger('license_plate_fee')->nullable();
            $table->unsignedBigInteger('inspection_fee')->nullable();
            $table->unsignedBigInteger('road_use_fee')->nullable();
            $table->unsignedBigInteger('compulsory_insurance_fee')->nullable();
            $table->unsignedBigInteger('service_fee')->default(0);
            $table->unsignedBigInteger('other_fee')->default(0);
            $table->text('source_note')->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->timestamps();
            $table->unique(['variant_id', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_on_road_fees');
    }
};
