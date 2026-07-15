<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agence_user', function (Blueprint $table) {
            if (! Schema::hasColumn('agence_user', 'date_debut_autorisation')) {
                $table->date('date_debut_autorisation')->nullable()->after('is_default');
            }
            if (! Schema::hasColumn('agence_user', 'date_fin_autorisation')) {
                $table->date('date_fin_autorisation')->nullable()->after('date_debut_autorisation');
            }
            if (! Schema::hasColumn('agence_user', 'statut_agence')) {
                $table->string('statut_agence', 16)->default('actif')->after('date_fin_autorisation');
            }
            if (! Schema::hasColumn('agence_user', 'niveau_acces')) {
                $table->string('niveau_acces', 32)->default('pointage_complet')->after('statut_agence');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agence_user', function (Blueprint $table) {
            $cols = [];
            foreach (['date_debut_autorisation', 'date_fin_autorisation', 'statut_agence', 'niveau_acces'] as $col) {
                if (Schema::hasColumn('agence_user', $col)) {
                    $cols[] = $col;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
