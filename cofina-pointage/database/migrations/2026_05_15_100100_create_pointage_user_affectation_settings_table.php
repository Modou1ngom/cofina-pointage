<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pointage_user_affectation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type_pointage', 32)->default('qr_et_gps');
            $table->string('mode_validation', 32)->default('validation_manager');
            $table->date('date_affectation')->nullable();
            $table->date('date_fin_affectation')->nullable();
            $table->boolean('statut_activation')->default(true);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointage_user_affectation_settings');
    }
};
