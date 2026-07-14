<?php

namespace Database\Seeders;

use App\Models\Agence;
use App\Models\Departement;
use App\Models\Filiale;
use App\Models\PointageAuditLog;
use App\Models\Profil;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Utilisateurs et données de démo pour tester le module Pointage intelligent.
 * Mot de passe pour tous les comptes démo : password
 */
class PointageDemoSeeder extends Seeder
{
    /** Ancien e-mail démo employé (migration automatique vers EMPLOYE_DEMO_EMAIL). */
    private const EMPLOYE_DEMO_EMAIL_LEGACY = 'employe.demo@cofina.sn';

    /** Compte employé démo pointage / OTP (e-mail + SMS). */
    private const EMPLOYE_DEMO_EMAIL = 'yacin36juz@gmail.com';

    public function run(): void
    {
        $pwd = Hash::make('password');

        $roleSuper = Role::query()->where('slug', 'super_admin')->first();
        $roleAdmin = Role::query()->where('slug', 'admin')->first();
        $roleRh = Role::query()->where('slug', 'rh')->first();
        $roleMetier = Role::query()->where('slug', 'metier')->first();

        if (! $roleSuper || ! $roleAdmin || ! $roleRh || ! $roleMetier) {
            $this->command?->warn('PointageDemoSeeder : exécutez RoleSeeder avant (slugs super_admin, admin, rh, metier).');

            return;
        }

        $this->migrateEmployeDemoEmailIfNeeded();

        $filiale = Filiale::query()->firstOrCreate(
            ['nom' => 'Cofina Démo'],
            ['description' => 'Environnement de démonstration pointage', 'actif' => true]
        );

        $agence = Agence::query()->firstWhere('nom', 'Dakar Plateau');
        if ($agence) {
            $agence->update([
                'code_agent' => $agence->code_agent ?: 'DKR-DEMO-PT',
                'description' => 'Plateau — Av. Pompidou, Dakar, Sénégal',
                'latitude' => 14.7167,
                'longitude' => -17.4677,
                'rayon_geofencing_metres' => 50,
                'pointage_qr_type' => 'dynamic',
                'pointage_qr_enrolled_at' => $agence->pointage_qr_enrolled_at ?? now(),
                'pointage_qr_enabled' => true,
                'actif' => true,
                'filiale_id' => $filiale->id,
            ]);
        } else {
            $agence = Agence::query()->create([
                'nom' => 'Dakar Plateau',
                'code_agent' => 'DKR-DEMO-PT',
                'description' => 'Plateau — Av. Pompidou, Dakar, Sénégal',
                'latitude' => 14.7167,
                'longitude' => -17.4677,
                'rayon_geofencing_metres' => 50,
                'pointage_qr_type' => 'dynamic',
                'pointage_qr_enrolled_at' => now(),
                'pointage_qr_enabled' => true,
                'actif' => true,
                'filiale_id' => $filiale->id,
            ]);
        }

        $departement = Departement::query()->firstOrCreate(
            ['nom' => 'Finance'],
            ['description' => 'Démo — équipe pointage', 'actif' => true]
        );

        $profilManager = Profil::query()->updateOrCreate(
            ['email' => 'manager.demo@cofina.sn'],
            [
                'matricule' => 'MGR-DEMO-001',
                'prenom' => 'Fatou',
                'nom' => 'Sarr',
                'fonction' => 'Manager',
                'departement' => 'Finance',
                'telephone' => '+221770000001',
                'site' => $agence->nom,
                'statut' => 'actif',
                'filiale_id' => $filiale->id,
                'n_plus_1_id' => null,
                'n_plus_2_id' => null,
            ]
        );

        $profilEmploye = Profil::query()->updateOrCreate(
            ['email' => self::EMPLOYE_DEMO_EMAIL],
            [
                'matricule' => 'EMP-DEMO-001',
                'prenom' => 'Amadou',
                'nom' => 'Diallo',
                'fonction' => 'Collaborateur',
                'departement' => 'Finance',
                /** Téléphone démo OTP / pointage (ex. 777377821 — même code que les 4 derniers pour secours PIN). */
                'telephone' => '+221777377821',
                'site' => $agence->nom,
                'statut' => 'actif',
                'filiale_id' => $filiale->id,
                'n_plus_1_id' => $profilManager->id,
                'n_plus_2_id' => null,
            ]
        );
        /** PIN démo pointage : 1234 (sinon 4 derniers du téléphone : 7821). */
        $profilEmploye->forceFill(['pointage_pin_hash' => Hash::make('1234')])->saveQuietly();

        $departement->responsable_departement_id = $profilManager->id;
        $departement->save();

        $users = [
            [
                'email' => 'superadmin.demo@cofina.sn',
                'name' => 'Directeur Général Démo',
                /** Même module web « Pointage & Présence » que rh.demo (rôle rh en plus du super_admin). */
                'roles' => [$roleSuper->id, $roleRh->id],
            ],
            [
                'email' => 'admin.demo@cofina.sn',
                'name' => 'Admin IT Démo',
                'roles' => [$roleAdmin->id, $roleRh->id],
            ],
            [
                'email' => 'rh.demo@cofina.sn',
                'name' => 'Ibrahim Ndiaye Démo',
                'roles' => [$roleRh->id],
            ],
            [
                'email' => self::EMPLOYE_DEMO_EMAIL,
                'name' => 'Amadou Diallo',
                'roles' => [$roleMetier->id],
            ],
        ];

        foreach ($users as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => $pwd,
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'must_change_password' => false,
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at' => null,
                ]
            );

            $user->roles()->sync($row['roles']);

            $user->filiales()->syncWithoutDetaching([$filiale->id]);
        }

        $employeUser = User::query()->where('email', self::EMPLOYE_DEMO_EMAIL)->first();

        Profil::query()->updateOrCreate(
            ['email' => 'rh.demo@cofina.sn'],
            [
                'matricule' => 'M03',
                'prenom' => 'Ibrahim',
                'nom' => 'Ndiaye',
                'fonction' => 'Responsable RH',
                'departement' => 'Finance',
                'telephone' => '+221777367821',
                'site' => $agence->nom,
                'statut' => 'actif',
                'filiale_id' => $filiale->id,
            ]
        );

        /** manager.demo : profil N+1 habilitations uniquement — pas de compte / agence pointage démo. */
        User::query()->where('email', 'manager.demo@cofina.sn')->each(function (User $u) {
            DB::table('agence_user')->where('user_id', $u->id)->delete();
        });

        if ($employeUser) {
            DB::table('agence_user')->updateOrInsert(
                ['agence_id' => $agence->id, 'user_id' => $employeUser->id],
                ['is_default' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $thies = Agence::query()->firstOrCreate(
            ['nom' => 'Thiès'],
            [
                'code_agent' => 'THIES-DEMO',
                'description' => 'Site démo — Centre d’urgence',
                'latitude' => 14.7886,
                'longitude' => -16.9260,
                'rayon_geofencing_metres' => 50,
                'pointage_qr_type' => 'dynamic',
                'pointage_qr_enrolled_at' => now(),
                'pointage_qr_enabled' => true,
                'actif' => true,
                'filiale_id' => $filiale->id,
            ]
        );
        if ($thies->pointage_qr_enrolled_at === null) {
            $thies->forceFill(['pointage_qr_enrolled_at' => now()])->saveQuietly();
        }
        $thies->forceFill(['pointage_qr_enabled' => true, 'actif' => true])->saveQuietly();

        if (! PointageAuditLog::query()->where('action', 'DEMO_GPS_SPOOFING')->exists()) {
            PointageAuditLog::query()->create([
                'actor_user_id' => null,
                'action' => 'DEMO_GPS_SPOOFING',
                'description' => 'Analyse : position GPS signalée à 450 m du site mais signal d’authentification incohérent. Technique de falsification GPS suspectée (VPN + faux signal). L’adresse IP appartient à une plage mobile non enregistrée.',
                'agence_id' => $thies->id,
                'ip_address' => '41.82.14.9',
                'severity' => 'fraude',
                'meta' => [
                    'incident_code' => 'INC-001',
                    'titre' => 'GPS Spoofing détecté',
                    'details' => [
                        ['label' => 'IP SOURCE', 'value' => '41.82.14.9'],
                        ['label' => 'UTILISATEUR', 'value' => 'Inconnu (non authentifié)'],
                        ['label' => 'ACTION SYSTÈME', 'value' => 'Pointage bloqué auto'],
                    ],
                ],
            ]);
        }

        if (! PointageAuditLog::query()->where('action', 'DEMO_DOUBLE_POINTAGE')->exists()) {
            PointageAuditLog::query()->create([
                'actor_user_id' => null,
                'action' => 'DEMO_DOUBLE_POINTAGE',
                'description' => 'Double pointage tenté : une arrivée existe déjà pour la journée.',
                'agence_id' => $agence->id,
                'ip_address' => '41.82.190.5',
                'severity' => 'alerte',
                'meta' => [
                    'incident_code' => 'INC-002',
                    'titre' => 'Double pointage tenté',
                    'pointage_existant' => 'Arrivée 08:02',
                    'tentative' => 'Nouvelle arrivée 12:04',
                    'details' => [
                        ['label' => 'IP SOURCE', 'value' => '41.82.190.5'],
                        ['label' => 'POINTAGE EXISTANT', 'value' => 'Arrivée 08:02'],
                        ['label' => 'TENTATIVE', 'value' => 'Nouvelle arrivée 12:04'],
                    ],
                ],
            ]);
        }

        if (! PointageAuditLog::query()->where('action', 'DEMO_RESOLU_QR')->exists()) {
            PointageAuditLog::query()->create([
                'actor_user_id' => null,
                'action' => 'DEMO_RESOLU_QR',
                'description' => 'Démo historique',
                'agence_id' => $thies->id,
                'ip_address' => null,
                'severity' => 'alerte',
                'meta' => [
                    'resolved' => true,
                    'resolved_at' => '2025-01-20T10:00:00+00:00',
                    'resolved_by_name' => 'Moussa Sy',
                    'resolution_action' => 'IP bannie + alerte manager',
                    'incident_code' => 'INC-000',
                    'titre' => 'QR Code partagé',
                ],
            ]);
        }

        $siegeDakar = Agence::query()->firstOrCreate(
            ['nom' => 'Siège Dakar'],
            [
                'code_agent' => 'SIEGE-DKR-DEMO',
                'description' => 'Site démo',
                'latitude' => 14.6928,
                'longitude' => -17.4467,
                'rayon_geofencing_metres' => 50,
                'pointage_qr_type' => 'dynamic',
                'pointage_qr_enrolled_at' => now(),
                'pointage_qr_enabled' => true,
                'actif' => true,
                'filiale_id' => $filiale->id,
            ]
        );

        if (! PointageAuditLog::query()->where('action', 'DEMO_RESOLU_GPS')->exists()) {
            PointageAuditLog::query()->create([
                'actor_user_id' => null,
                'action' => 'DEMO_RESOLU_GPS',
                'description' => 'Démo historique',
                'agence_id' => $siegeDakar->id,
                'ip_address' => null,
                'severity' => 'fraude',
                'meta' => [
                    'resolved' => true,
                    'resolved_at' => '2025-01-15T14:00:00+00:00',
                    'resolved_by_name' => 'Ibrahim Ndiaye',
                    'resolution_action' => 'Avertissement écrit employé',
                    'incident_code' => 'INC-009',
                    'titre' => 'GPS hors zone répété',
                ],
            ]);
        }

        if (! PointageAuditLog::query()->where('action', 'DEMO_RESOLU_API')->exists()) {
            PointageAuditLog::query()->create([
                'actor_user_id' => null,
                'action' => 'DEMO_RESOLU_API',
                'description' => 'Démo historique',
                'agence_id' => null,
                'ip_address' => null,
                'severity' => 'fraude',
                'meta' => [
                    'resolved' => true,
                    'resolved_at' => '2025-01-08T09:30:00+00:00',
                    'resolved_by_name' => 'Admin IT',
                    'resolution_action' => 'IP bloquée — logs analysés',
                    'incident_code' => 'INC-008',
                    'titre' => 'Tentative intrusion API',
                ],
            ]);
        }

        $this->command?->info('Pointage démo : comptes créés (mot de passe : password)');
        $this->command?->table(
            ['Rôle', 'Email', 'Module pointage'],
            [
                ['Super Admin + RH', 'superadmin.demo@cofina.sn', 'Pointage & Présence (comme RH)'],
                ['Admin IT + RH', 'admin.demo@cofina.sn', 'Pointage & Présence (comme RH)'],
                ['RH', 'rh.demo@cofina.sn', 'Pointage & Présence'],
                ['Profil N+1 (sans compte pointage)', 'manager.demo@cofina.sn', '—'],
                ['Employé', self::EMPLOYE_DEMO_EMAIL, 'Pointer (métier)'],
            ]
        );
    }

    /**
     * Met à jour l’e-mail du compte employé démo si l’ancienne valeur est encore en base.
     */
    private function migrateEmployeDemoEmailIfNeeded(): void
    {
        Profil::query()->where('email', self::EMPLOYE_DEMO_EMAIL_LEGACY)->update(['email' => self::EMPLOYE_DEMO_EMAIL]);
        User::query()->where('email', self::EMPLOYE_DEMO_EMAIL_LEGACY)->update(['email' => self::EMPLOYE_DEMO_EMAIL]);
    }
}
