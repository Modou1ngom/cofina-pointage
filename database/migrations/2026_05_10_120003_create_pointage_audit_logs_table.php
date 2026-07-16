<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pointage_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 128);
            $table->text('description')->nullable();
            $table->foreignId('agence_id')->nullable()->constrained('agences')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('severity', 16)->default('ok');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointage_audit_logs');
    }
};
