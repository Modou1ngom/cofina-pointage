<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agences')) {
            return;
        }

        Schema::table('agences', function (Blueprint $table) {
            if (! Schema::hasColumn('agences', 'pointage_qr_enrolled_at')) {
                $table->timestamp('pointage_qr_enrolled_at')->nullable()->after('pointage_qr_enabled');
            }
        });

        // Agences déjà configurées pour le pointage QR avant cette migration.
        DB::table('agences')
            ->whereNull('pointage_qr_enrolled_at')
            ->where(function ($q) {
                $q->whereNotNull('pointage_qr_secret')
                    ->orWhereNotNull('pointage_qr_activated_on');
            })
            ->update(['pointage_qr_enrolled_at' => now()]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('agences') || ! Schema::hasColumn('agences', 'pointage_qr_enrolled_at')) {
            return;
        }

        Schema::table('agences', function (Blueprint $table) {
            $table->dropColumn('pointage_qr_enrolled_at');
        });
    }
};
