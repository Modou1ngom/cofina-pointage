<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pointage_horaire_profiles')) {
            Schema::create('pointage_horaire_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('libelle', 191);
                $table->string('scope_type', 32)->default('global');
                $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
                $table->foreignId('profile_id')->nullable()->constrained('profiles')->nullOnDelete();
                $table->boolean('actif')->default(true);
                $table->json('weekend_jours')->nullable();
                $table->boolean('weekend_samedi_matin_ouvrable')->default(false);
                $table->time('weekend_samedi_matin_fin')->nullable();
                $table->boolean('weekend_dimanche_matin_ouvrable')->default(false);
                $table->time('weekend_dimanche_matin_fin')->nullable();
                $table->decimal('weekend_travail_majoration_pct', 8, 2)->default(25);
                $table->timestamps();

                $table->index(['scope_type', 'actif']);
            });
        }

        if (! Schema::hasTable('pointage_horaire_jours_semaine')) {
            Schema::create('pointage_horaire_jours_semaine', function (Blueprint $table) {
                $table->id();
                $table->foreignId('horaire_profile_id')->constrained('pointage_horaire_profiles')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week');
                $table->boolean('est_ouvrable')->default(false);
                $table->time('heure_debut')->nullable();
                $table->time('heure_fin')->nullable();
                $table->decimal('duree_theorique_heures', 6, 2)->nullable();
                $table->timestamps();

                $table->unique(['horaire_profile_id', 'day_of_week']);
            });
        }

        if (! Schema::hasTable('pointage_pauses_regle')) {
            Schema::create('pointage_pauses_regle', function (Blueprint $table) {
                $table->id();
                $table->foreignId('horaire_profile_id')->unique()->constrained('pointage_horaire_profiles')->cascadeOnDelete();
                $table->unsignedSmallInteger('dejeuner_duree_minutes')->default(60);
                $table->time('dejeuner_fenetre_debut')->default('11:30:00');
                $table->time('dejeuner_fenetre_fin')->default('14:30:00');
                $table->string('dejeuner_mode', 32)->default('auto_deduct');
                $table->unsignedTinyInteger('technique_nb_max')->default(2);
                $table->unsignedSmallInteger('technique_duree_max_minutes')->default(15);
                $table->boolean('technique_decompte_temps_travail')->default(true);
                $table->unsignedSmallInteger('pause_totale_max_minutes')->nullable();
                $table->boolean('alerte_depassement_pause')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('pointage_horaire_profiles') && DB::table('pointage_horaire_profiles')->count() === 0) {
            $pid = DB::table('pointage_horaire_profiles')->insertGetId([
                'libelle' => 'Standard (global)',
                'scope_type' => 'global',
                'departement_id' => null,
                'profile_id' => null,
                'actif' => true,
                'weekend_jours' => json_encode([0, 6]),
                'weekend_samedi_matin_ouvrable' => false,
                'weekend_samedi_matin_fin' => null,
                'weekend_dimanche_matin_ouvrable' => false,
                'weekend_dimanche_matin_fin' => null,
                'weekend_travail_majoration_pct' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (range(0, 6) as $dow) {
                $ouvrable = $dow >= 1 && $dow <= 5;
                DB::table('pointage_horaire_jours_semaine')->insert([
                    'horaire_profile_id' => $pid,
                    'day_of_week' => $dow,
                    'est_ouvrable' => $ouvrable,
                    'heure_debut' => $ouvrable ? '08:00:00' : null,
                    'heure_fin' => $ouvrable ? '17:00:00' : null,
                    'duree_theorique_heures' => $ouvrable ? 8 : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('pointage_pauses_regle')->insert([
                'horaire_profile_id' => $pid,
                'dejeuner_duree_minutes' => 60,
                'dejeuner_fenetre_debut' => '11:30:00',
                'dejeuner_fenetre_fin' => '14:30:00',
                'dejeuner_mode' => 'auto_deduct',
                'technique_nb_max' => 2,
                'technique_duree_max_minutes' => 15,
                'technique_decompte_temps_travail' => true,
                'pause_totale_max_minutes' => 120,
                'alerte_depassement_pause' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pointage_pauses_regle');
        Schema::dropIfExists('pointage_horaire_jours_semaine');
        Schema::dropIfExists('pointage_horaire_profiles');
    }
};
