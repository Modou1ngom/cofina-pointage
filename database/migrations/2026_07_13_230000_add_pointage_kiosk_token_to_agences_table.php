<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agences')) {
            return;
        }

        Schema::table('agences', function (Blueprint $table) {
            if (! Schema::hasColumn('agences', 'pointage_kiosk_token')) {
                $table->string('pointage_kiosk_token', 64)
                    ->nullable()
                    ->unique()
                    ->after('pointage_qr_enrolled_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agences') || ! Schema::hasColumn('agences', 'pointage_kiosk_token')) {
            return;
        }

        Schema::table('agences', function (Blueprint $table) {
            $table->dropUnique(['pointage_kiosk_token']);
            $table->dropColumn('pointage_kiosk_token');
        });
    }
};
