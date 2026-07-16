<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Équivalent métier du schéma « holidays » : extension de pointage_jours_feries
 * (import API Nager.date, plages, source, notes).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pointage_jours_feries')) {
            return;
        }

        Schema::table('pointage_jours_feries', function (Blueprint $table) {
            if (! Schema::hasColumn('pointage_jours_feries', 'date_fin')) {
                $table->date('date_fin')->nullable()->after('date_unique');
            }
            if (! Schema::hasColumn('pointage_jours_feries', 'country_code')) {
                $table->string('country_code', 3)->nullable()->after('pays_region');
            }
            if (! Schema::hasColumn('pointage_jours_feries', 'source')) {
                $table->string('source', 16)->default('manual')->after('taux_majoration_pct');
            }
            if (! Schema::hasColumn('pointage_jours_feries', 'annee')) {
                $table->unsignedSmallInteger('annee')->nullable()->after('source');
            }
            if (! Schema::hasColumn('pointage_jours_feries', 'notes')) {
                $table->text('notes')->nullable()->after('annee');
            }
        });

        \Illuminate\Support\Facades\DB::table('pointage_jours_feries')->whereNull('source')->update(['source' => 'manual']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('pointage_jours_feries')) {
            return;
        }

        Schema::table('pointage_jours_feries', function (Blueprint $table) {
            $cols = [];
            foreach (['date_fin', 'country_code', 'source', 'annee', 'notes'] as $c) {
                if (Schema::hasColumn('pointage_jours_feries', $c)) {
                    $cols[] = $c;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
