<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointageFerieImportPref extends Model
{
    protected $table = 'pointage_ferie_import_prefs';

    protected $fillable = [
        'country_code',
        'auto_importer_annuel',
    ];

    protected function casts(): array
    {
        return [
            'auto_importer_annuel' => 'boolean',
        ];
    }
}
