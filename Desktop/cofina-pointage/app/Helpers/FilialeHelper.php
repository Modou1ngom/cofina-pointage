<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;

class FilialeHelper
{
    /**
     * Récupère l'ID de la filiale actuelle depuis la session
     */
    public static function getCurrentFilialeId(): ?int
    {
        return session('current_filiale_id');
    }

    /**
     * Définit la filiale actuelle dans la session
     */
    public static function setCurrentFilialeId(?int $filialeId): void
    {
        session(['current_filiale_id' => $filialeId]);
    }

    /**
     * Filtre une requête par la filiale actuelle
     */
    public static function scopeForCurrentFiliale($query, string $column = 'filiale_id')
    {
        $filialeId = self::getCurrentFilialeId();
        
        if ($filialeId) {
            return $query->where($column, $filialeId);
        }
        
        return $query;
    }
}

