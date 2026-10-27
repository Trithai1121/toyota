<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CATEGORIES = [
        'hybrid' => ['name' => 'Hybrid EV', 'sort_order' => 1],
        'suv-crossover' => ['name' => 'SUV', 'sort_order' => 2],
        'sedan' => ['name' => 'Sedan', 'sort_order' => 3],
        'mpv' => ['name' => 'Đa dụng', 'sort_order' => 4],
        'pickup' => ['name' => 'Bán tải', 'sort_order' => 5],
        'hatchback' => ['name' => 'Hatchback', 'sort_order' => 6],
    ];

    public function up(): void
    {
        Schema::create('vehicle_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('vehicle_category', function (Blueprint $table): void {
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['vehicle_id', 'vehicle_category_id']);
        });

        $now = now();
        foreach (self::CATEGORIES as $slug => $category) {
            DB::table('vehicle_categories')->insert([
                'name' => $category['name'],
                'slug' => $slug,
                'sort_order' => $category['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $categoryIds = DB::table('vehicle_categories')->pluck('id', 'slug');
        $vehicleCategories = DB::table('vehicles')
            ->join('vehicle_lines', 'vehicle_lines.id', '=', 'vehicles.vehicle_line_id')
            ->whereIn('vehicle_lines.category', array_keys(self::CATEGORIES))
            ->get(['vehicles.id as vehicle_id', 'vehicle_lines.category as category_slug']);

        foreach ($vehicleCategories as $assignment) {
            $this->attachCategory((int) $assignment->vehicle_id, $categoryIds[$assignment->category_slug], $now);
        }

        $hybridVehicleIds = DB::table('variants')
            ->where('is_published', true)
            ->whereNotNull('list_price')
            ->where(function ($query): void {
                $query->where('name', 'like', '%HEV%')
                    ->orWhere('name', 'like', '%Hybrid%')
                    ->orWhere('fuel_type', 'like', '%Điện%');
            })
            ->distinct()
            ->pluck('vehicle_id');

        foreach ($hybridVehicleIds as $vehicleId) {
            $this->attachCategory((int) $vehicleId, $categoryIds['hybrid'], $now);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_category');
        Schema::dropIfExists('vehicle_categories');
    }

    private function attachCategory(int $vehicleId, int $categoryId, mixed $timestamp): void
    {
        DB::table('vehicle_category')->insertOrIgnore([
            'vehicle_id' => $vehicleId,
            'vehicle_category_id' => $categoryId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
};
