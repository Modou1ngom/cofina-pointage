<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications mobiles POINTRUST (Sanctum).
 * La table Laravel `notifications` (UUID + morphs) est réservée au canal database du framework.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pointage_api_notifications')) {
            return;
        }

        Schema::create('pointage_api_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('body');
            $table->boolean('read')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pointage_api_notifications');
    }
};
