<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->string('prior_status');
            $table->string('new_status');
            $table->text('reason');
            $table->foreignId('adjusted_by')->constrained('users');
            $table->timestamp('adjusted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustments');
    }
};