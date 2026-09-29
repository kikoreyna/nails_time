<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('Manos');
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('price', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 120);
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->date('appointment_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('total_minutes');
            $table->decimal('total_price', 8, 2);
            $table->string('status', 20)->default('confirmed');
            $table->timestamps();
            $table->index(['appointment_date', 'status', 'starts_at', 'ends_at']);
        });

        Schema::create('appointment_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('service_name');
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('price', 8, 2);
            $table->timestamps();
            $table->unique(['appointment_id', 'service_id']);
        });

        Schema::create('appointment_day_locks', function (Blueprint $table) {
            $table->date('appointment_date')->primary();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_day_locks');
        Schema::dropIfExists('appointment_service');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('services');
    }
};
