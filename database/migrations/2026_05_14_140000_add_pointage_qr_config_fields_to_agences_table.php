<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agences', function (Blueprint $table) {
            $table->date('pointage_qr_activated_on')->nullable()->after('pointage_qr_secret');
            $table->date('pointage_qr_expires_on')->nullable()->after('pointage_qr_activated_on');
            $table->time('pointage_plage_debut')->nullable()->after('pointage_qr_expires_on');
            $table->time('pointage_plage_fin')->nullable()->after('pointage_plage_debut');
            $table->boolean('pointage_qr_enabled')->default(true)->after('pointage_plage_fin');
        });
    }

    public function down(): void
    {
        Schema::table('agences', function (Blueprint $table) {
            $table->dropColumn([
                'pointage_qr_activated_on',
                'pointage_qr_expires_on',
                'pointage_plage_debut',
                'pointage_plage_fin',
                'pointage_qr_enabled',
            ]);
        });
    }
};
