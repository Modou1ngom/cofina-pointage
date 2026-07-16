<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Profil extends Model
{
    use HasFactory;

    protected $table = 'profiles';

    protected $hidden = [
        'pointage_pin_hash',
    ];

    protected $fillable = [
        'matricule',
        'prenom',
        'nom',
        'fonction',
        'departement',
        'email',
        'telephone',
        'site',
        'type_contrat',
        'statut',
        'n_plus_1_id',
        'n_plus_2_id',
        'filiale_id',
    ];

    /**
     * Déverrouillage pointage : PIN enregistré (hash) ou 4 derniers chiffres du téléphone professionnel.
     */
    public function validatePointageUnlockCode(string $code): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }

        if ($this->pointage_pin_hash && Hash::check($code, $this->pointage_pin_hash)) {
            return true;
        }

        $digitsIn = preg_replace('/\D/', '', $code) ?? '';
        $telDigits = preg_replace('/\D/', '', (string) $this->telephone) ?? '';
        if ($digitsIn !== '' && strlen($telDigits) >= 4 && strlen($digitsIn) >= 4) {
            return substr($digitsIn, -4) === substr($telDigits, -4);
        }

        return false;
    }

    // Relations
    public function nPlus1()
    {
        return $this->belongsTo(Profil::class, 'n_plus_1_id');
    }

    public function nPlus2()
    {
        return $this->belongsTo(Profil::class, 'n_plus_2_id');
    }

    public function subordonnes()
    {
        return $this->hasMany(Profil::class, 'n_plus_1_id');
    }

    // Alias pour compatibilité ascendante
    public function superieurHierarchique()
    {
        return $this->nPlus1();
    }

    /**
     * Relation avec les rôles (many-to-many)
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'profile_role', 'profile_id', 'role_id');
    }

    /**
     * Relation avec la filiale
     */
    public function filiale()
    {
        return $this->belongsTo(Filiale::class, 'filiale_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }

    /**
     * Normalise "informatique" en "IT" pour le département
     */
    public function getDepartementAttribute($value)
    {
        if (! $value) {
            return $value;
        }

        // Normaliser "informatique" en "IT" (insensible à la casse)
        $normalized = preg_replace('/informatique/i', 'IT', $value);

        return $normalized;
    }

    /**
     * Normalise "informatique" en "IT" pour la fonction
     */
    public function getFonctionAttribute($value)
    {
        if (! $value) {
            return $value;
        }

        // Normaliser "informatique" en "IT" (insensible à la casse)
        $normalized = preg_replace('/informatique/i', 'IT', $value);

        return $normalized;
    }

    /**
     * Génère un matricule unique automatiquement
     * Format: M suivi d'un numéro incrémenté (ex: M1, M2, M3, etc.)
     */
    public static function generateMatricule(): string
    {
        $prefix = 'M0';

        // Récupérer tous les matricules qui commencent par "M"
        $matricules = self::where('matricule', 'like', "{$prefix}%")
            ->pluck('matricule')
            ->toArray();

        $maxNumber = 0;

        foreach ($matricules as $matricule) {
            // Extraire le numéro après "M"
            // Gère les formats: M1, M-2025-0001, etc.
            $numberPart = substr($matricule, 1); // Enlève le "M"

            // Si le format est M-YYYY-XXXX, extraire le dernier nombre
            if (preg_match('/-(\d+)$/', $numberPart, $matches)) {
                $number = (int) $matches[1];
            } elseif (preg_match('/^(\d+)/', $numberPart, $matches)) {
                // Format simple M1, M2, etc.
                $number = (int) $matches[1];
            } else {
                // Essayer de convertir directement en extrayant tous les chiffres
                $number = (int) preg_replace('/[^0-9]/', '', $numberPart);
            }

            if ($number > $maxNumber) {
                $maxNumber = $number;
            }
        }

        $nextNumber = $maxNumber + 1;

        return "{$prefix}{$nextNumber}";
    }
}
