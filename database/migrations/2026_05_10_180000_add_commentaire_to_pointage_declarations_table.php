<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pointage_declarations', function (Blueprint $table) {
            $table->text('commentaire')->nullable()->after('motif');
        });
    }

    public function down(): void
    {
        Schema::table('pointage_declarations', function (Blueprint $table) {
            $table->dropColumn('commentaire');
        });
    }
};
