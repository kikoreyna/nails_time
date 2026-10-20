<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->boolean('is_admin')->default(false)->after('password');
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email')->unique();
            $table->string('phone', 30);
            $table->boolean('is_blacklisted')->default(false);
            $table->timestamps();
        });

        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('appointments')->where('status', 'pending')->update(['status' => 'pending_payment_validation']);

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('status', 32)->default('confirmed')->change();
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->boolean('deposit_required')->default(false);
            $table->decimal('deposit_amount', 8, 2)->default(0);
            $table->dateTime('expires_at')->nullable();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->dateTime('uploaded_at');
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_status', 20)->default('pending');
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['status', 'expires_at']);
            $table->dropConstrainedForeignId('technician_id');
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['deposit_required', 'deposit_amount', 'expires_at']);
        });

        Schema::dropIfExists('technicians');
        Schema::dropIfExists('clients');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'is_admin']);
        });
    }
};
