<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes profiles liées aux modules Avances sur salaire / Habilitations (retirés).
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $columns = [
        'date_entree',
        'numero_compte',
        'code_agence',
        'statut_rh',
        'type_office',
    ];

    public function up(): void
    {
        $toDrop = array_values(array_filter(
            $this->columns,
            fn (string $col) => Schema::hasColumn('profiles', $col)
        ));

        if ($toDrop === []) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) use ($toDrop) {
            $table->dropColumn($toDrop);
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'date_entree')) {
                $table->date('date_entree')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'numero_compte')) {
                $table->string('numero_compte')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'code_agence')) {
                $table->string('code_agence')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'statut_rh')) {
                $table->string('statut_rh')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'type_office')) {
                $table->string('type_office')->nullable();
            }
        });
    }
};
