<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Suppression des tables des modules retirés (Habilitations, Applications,
 * Avances sur salaire, Suivi signature). Migration irréversible côté données.
 */
return new class extends Migration
{
    /** @var list<string> Ordre : tables dépendantes d'abord. */
    private array $tables = [
        'avance_salaire_integration_lignes',
        'avance_salaire_integrations',
        'avance_salaire_demandes',
        'avance_salaire_baremes',
        'sig_staff_encours_conformite_events',
        'sig_personne_liee_sig_staff',
        'sig_staffs',
        'sig_personnes_liees',
        'habilitations',
        'applications',
        'notifications',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Irréversible : les anciennes migrations de création restent dans l'historique
        // pour une réinstallation complète (migrate:fresh) si nécessaire.
    }
};
