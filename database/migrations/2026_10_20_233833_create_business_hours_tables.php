<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->unsignedTinyInteger('weekday')->primary();
            $table->boolean('is_open')->default(true);
            $table->time('opens_at');
            $table->time('closes_at');
            $table->time('break_starts_at')->nullable();
            $table->time('break_ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('blocked_times', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_times');
        Schema::dropIfExists('business_hours');
    }
};
