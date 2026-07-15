<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agences', function (Blueprint $table) {
            $table->unsignedSmallInteger('rayon_geofencing_metres')->default(50);
            $table->string('pointage_qr_type', 16)->default('dynamic');
            $table->string('pointage_qr_secret', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agences', function (Blueprint $table) {
            $table->dropColumn(['rayon_geofencing_metres', 'pointage_qr_type', 'pointage_qr_secret']);
        });
    }
};
