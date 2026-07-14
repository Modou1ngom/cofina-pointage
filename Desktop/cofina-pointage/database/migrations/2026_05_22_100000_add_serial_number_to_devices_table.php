<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'serial_number')) {
                $table->string('serial_number', 128)->nullable()->after('device_id');
            }
        });

        if (Schema::hasColumn('devices', 'serial_number')) {
            DB::table('devices')
                ->whereNull('serial_number')
                ->update(['serial_number' => DB::raw('device_id')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'serial_number')) {
                $table->dropColumn('serial_number');
            }
        });
    }
};
