<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('name');
            $table->string('engine')->nullable()->after('engine_displacement_cc');
            $table->string('body_type')->nullable()->after('seats');
            $table->json('specs')->nullable()->after('origin');
        });

        DB::table('variants')
            ->join('vehicles', 'variants.vehicle_id', '=', 'vehicles.id')
            ->select('variants.id', 'variants.name', 'vehicles.name as vehicle_name')
            ->orderBy('variants.id')
            ->get()
            ->each(function (object $variant): void {
                $name = trim(preg_replace('/^'.preg_quote($variant->vehicle_name, '/').'\s*/iu', '', $variant->name) ?? $variant->name);
                $slug = Str::slug(preg_replace('/(?<=\d)\.(?=\d)/', '-', $name !== '' ? $name : $variant->name) ?? $variant->name);

                DB::table('variants')->where('id', $variant->id)->update(['slug' => $slug]);
            });

        Schema::table('variants', function (Blueprint $table): void {
            $table->unique(['vehicle_id', 'slug']);
        });

        Schema::create('vehicle_colors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('hex_code', 7)->default('#D1D5DB');
            $table->unsignedBigInteger('surcharge')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['vehicle_id', 'slug']);
        });

        Schema::create('vehicle_variant_color', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variant_id')->constrained('variants')->cascadeOnDelete();
            $table->foreignId('vehicle_color_id')->constrained('vehicle_colors')->cascadeOnDelete();
            $table->unsignedBigInteger('surcharge_override')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->unique(['variant_id', 'vehicle_color_id']);
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('variant_id')->nullable()->after('vehicle_id')->constrained('variants')->nullOnDelete();
            $table->foreignId('vehicle_color_id')->nullable()->after('variant_id')->constrained('vehicle_colors')->nullOnDelete();
            $table->unsignedBigInteger('selected_price')->nullable()->after('vehicle_color_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_color_id');
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn('selected_price');
        });

        Schema::dropIfExists('vehicle_variant_color');
        Schema::dropIfExists('vehicle_colors');

        Schema::table('variants', function (Blueprint $table): void {
            $table->dropUnique(['vehicle_id', 'slug']);
            $table->dropColumn(['slug', 'engine', 'body_type', 'specs']);
        });
    }
};
