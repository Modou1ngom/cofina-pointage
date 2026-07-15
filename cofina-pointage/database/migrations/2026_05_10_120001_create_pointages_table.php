<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agence_id')->nullable()->constrained('agences')->nullOnDelete();
            $table->string('type', 16);
            $table->timestamp('clocked_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('qr_verified')->default(false);
            $table->boolean('biometric_ok')->default(false);
            $table->string('statut', 32)->default('normal');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'clocked_at']);
            $table->index(['agence_id', 'clocked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointages');
    }
};
