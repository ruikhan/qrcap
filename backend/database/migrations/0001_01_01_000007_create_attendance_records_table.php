<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('attendance_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status'); // present, late, absent, excused, pending_review, rejected, cancelled
            $table->timestamp('checked_in_at')->nullable();
            $table->string('check_in_method')->default('qr'); // qr, manual, system
            $table->unsignedInteger('late_minutes')->default(0);
            $table->foreignId('qr_token_id')->nullable()->constrained('qr_tokens')->nullOnDelete();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->string('device_meta')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};