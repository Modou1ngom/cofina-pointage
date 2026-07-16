<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pointage_jours_feries') && ! Schema::hasColumn('pointage_jours_feries', 'departement_id')) {
            Schema::table('pointage_jours_feries', function (Blueprint $table) {
                $table->foreignId('departement_id')->nullable()->after('pays_region')->constrained('departements')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('pointage_ferie_import_prefs')) {
            Schema::create('pointage_ferie_import_prefs', function (Blueprint $table) {
                $table->id();
                $table->string('country_code', 3);
                $table->boolean('auto_importer_annuel')->default(false);
                $table->timestamps();

                $table->unique(['country_code']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pointage_jours_feries') && Schema::hasColumn('pointage_jours_feries', 'departement_id')) {
            Schema::table('pointage_jours_feries', function (Blueprint $table) {
                $table->dropConstrainedForeignId('departement_id');
            });
        }

        Schema::dropIfExists('pointage_ferie_import_prefs');
    }
};
