<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pointage_affectations')) {
            return;
        }

        Schema::create('pointage_affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profil_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type_pointage', 32)->default('qr_et_gps');
            $table->string('mode_validation', 32)->default('validation_manager');
            $table->date('date_affectation')->nullable();
            $table->date('date_fin_affectation')->nullable();
            $table->boolean('statut_activation')->default(true);
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->unique('profil_id');
            $table->unique('user_id');
            $table->index('statut_activation');
        });

        if (Schema::hasTable('pointage_user_affectation_settings') && Schema::hasTable('profiles')) {
            $settings = \Illuminate\Support\Facades\DB::table('pointage_user_affectation_settings')->get();
            foreach ($settings as $s) {
                $user = \Illuminate\Support\Facades\DB::table('users')->where('id', $s->user_id)->first();
                if ($user === null) {
                    continue;
                }
                $profil = \Illuminate\Support\Facades\DB::table('profiles')
                    ->whereNotNull('email')
                    ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim((string) $user->email))])
                    ->first();
                if ($profil === null) {
                    continue;
                }
                $exists = \Illuminate\Support\Facades\DB::table('pointage_affectations')
                    ->where('profil_id', $profil->id)
                    ->exists();
                if ($exists) {
                    continue;
                }
                \Illuminate\Support\Facades\DB::table('pointage_affectations')->insert([
                    'profil_id' => $profil->id,
                    'user_id' => $s->user_id,
                    'type_pointage' => $s->type_pointage,
                    'mode_validation' => $s->mode_validation,
                    'date_affectation' => $s->date_affectation,
                    'date_fin_affectation' => $s->date_fin_affectation,
                    'statut_activation' => $s->statut_activation,
                    'enrolled_at' => $s->created_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pointage_affectations');
    }
};
