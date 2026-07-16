<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pointage_affectation_agences')) {
            return;
        }

        Schema::create('pointage_affectation_agences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pointage_affectation_id')
                ->constrained('pointage_affectations')
                ->cascadeOnDelete();
            $table->foreignId('agence_id')->constrained('agences')->cascadeOnDelete();
            $table->date('date_debut_autorisation')->nullable();
            $table->date('date_fin_autorisation')->nullable();
            $table->string('statut_agence', 16)->default('actif');
            $table->string('niveau_acces', 32)->default('pointage_complet');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['pointage_affectation_id', 'agence_id'], 'ptg_aff_agence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointage_affectation_agences');
    }
};
