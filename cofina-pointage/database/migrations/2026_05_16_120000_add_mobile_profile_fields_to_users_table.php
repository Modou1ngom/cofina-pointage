<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'matricule')) {
                $table->string('matricule', 64)->nullable()->index()->after('email');
            }
            if (! Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url', 2048)->nullable()->after('matricule');
            }
        });

        if (Schema::hasColumn('users', 'full_name')) {
            DB::table('users')->whereNull('full_name')->update(['full_name' => DB::raw('name')]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['full_name', 'matricule', 'avatar_url'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
