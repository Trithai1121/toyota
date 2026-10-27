<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_landing_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_type')->index();
            $table->string('full_name');
            $table->string('phone', 20)->index();
            $table->date('desired_date')->nullable();
            $table->string('region')->nullable()->index();
            $table->text('customer_note')->nullable();
            $table->string('status')->default('new')->index();
            $table->boolean('consent_given')->default(false);
            $table->string('consent_version')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->string('source')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->uuid('event_id')->nullable()->unique();
            $table->timestamps();
            $table->index(['status', 'assigned_to', 'created_at']);
        });

        Schema::create('lead_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
    }
};
