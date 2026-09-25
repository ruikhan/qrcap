<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('token_id')->unique();
            $table->foreignId('session_id')->constrained('attendance_sessions')->cascadeOnDelete();
            $table->string('nonce', 64);
            $table->string('signature');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->boolean('revoked')->default(false);
            $table->timestamps();

            $table->index(['session_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_tokens');
    }
};