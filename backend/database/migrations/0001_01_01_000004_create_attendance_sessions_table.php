<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('draft'); // draft, open, paused, closed, cancelled
            $table->string('location')->nullable();

            $table->unsignedSmallInteger('present_grace_minutes')->default(10);
            $table->unsignedSmallInteger('qr_rotation_seconds')->default(30);
            $table->unsignedSmallInteger('qr_expiry_seconds')->default(60);
            $table->boolean('require_location')->default(false);
            $table->decimal('geofence_lat', 10, 7)->nullable();
            $table->decimal('geofence_lng', 10, 7)->nullable();
            $table->unsignedInteger('geofence_radius_meters')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};