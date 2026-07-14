<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pointage_jours_feries')) {
            return;
        }

        Schema::create('pointage_jours_feries', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 191);
            $table->date('date_unique');
            $table->date('date_fin')->nullable();
            $table->boolean('recurrence_annuelle')->default(false);
            $table->string('pays_region', 191)->nullable();
            $table->string('country_code', 3)->nullable();
            $table->string('type', 32)->default('national');
            $table->boolean('travaille_avec_majoration')->default(false);
            $table->decimal('taux_majoration_pct', 5, 2)->default(0);
            $table->string('source', 16)->default('manual');
            $table->unsignedSmallInteger('annee')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['date_unique', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointage_jours_feries');
    }
};
