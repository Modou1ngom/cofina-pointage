<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pointage_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->date('date_concernee');
            $table->string('motif', 512);
            $table->string('justificatif_path')->nullable();
            $table->string('statut', 32)->default('en_attente_manager');
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_decided_at')->nullable();
            $table->text('manager_comment')->nullable();
            $table->foreignId('rh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rh_decided_at')->nullable();
            $table->text('rh_comment')->nullable();
            $table->timestamps();

            $table->index(['statut', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointage_declarations');
    }
};
