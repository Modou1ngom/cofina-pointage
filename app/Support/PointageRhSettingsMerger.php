<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class PointageRhSettingsMerger
{
    public static function mergeStoredPayloadIntoConfig(): void
    {
        if (! Schema::hasTable('pointage_rh_settings')) {
            return;
        }

        $row = \App\Models\PointageRhSetting::query()->orderBy('id')->first();
        /** @var array<string, mixed>|null $payload */
        $payload = $row?->payload;
        if ($payload === null || $payload === []) {
            return;
        }

        foreach ($payload as $key => $value) {
            if ($key === 'declaration_motifs_autorises' && is_array($value)) {
                $base = config('pointage.declaration_motifs_autorises', []);
                config(['pointage.declaration_motifs_autorises' => array_replace($base, $value)]);

                continue;
            }

            if (config()->has('pointage.'.$key)) {
                config(['pointage.'.$key => $value]);
            }
        }
    }
}
