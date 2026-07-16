<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pointage_affectations')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->dropSqliteUniqueIndexOnColumn('pointage_affectations', 'user_id');
        } else {
            $indexes = collect(DB::select('SHOW INDEX FROM pointage_affectations WHERE Column_name = ?', ['user_id']))
                ->unique('Key_name');

            foreach ($indexes as $idx) {
                if ($idx->Key_name === 'PRIMARY') {
                    continue;
                }
                if ((int) $idx->Non_unique !== 0) {
                    continue;
                }
                $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $idx->Key_name);
                DB::statement('ALTER TABLE pointage_affectations DROP INDEX `'.$name.'`');
            }
        }

        if (! $this->hasNonUniqueIndexOnColumn('pointage_affectations', 'user_id')) {
            Schema::table('pointage_affectations', function (Blueprint $table) {
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('pointage_affectations')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->dropSqliteNonUniqueIndexOnColumn('pointage_affectations', 'user_id');
        } else {
            $indexes = collect(DB::select('SHOW INDEX FROM pointage_affectations WHERE Column_name = ?', ['user_id']))
                ->unique('Key_name');

            foreach ($indexes as $idx) {
                if ($idx->Key_name === 'PRIMARY') {
                    continue;
                }
                if ((int) $idx->Non_unique === 1) {
                    $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $idx->Key_name);
                    try {
                        DB::statement('ALTER TABLE pointage_affectations DROP INDEX `'.$name.'`');
                    } catch (\Throwable) {
                        //
                    }
                }
            }
        }

        if (! $this->hasUniqueIndexOnColumn('pointage_affectations', 'user_id')) {
            Schema::table('pointage_affectations', function (Blueprint $table) {
                $table->unique('user_id');
            });
        }
    }

    private function dropSqliteUniqueIndexOnColumn(string $table, string $column): void
    {
        foreach ($this->sqliteIndexesOnTable($table) as $idx) {
            if ((int) ($idx->unique ?? 0) !== 1) {
                continue;
            }
            if ($this->sqliteIndexColumns($idx->name) === [$column]) {
                DB::statement('DROP INDEX IF EXISTS "'.$idx->name.'"');
            }
        }
    }

    private function dropSqliteNonUniqueIndexOnColumn(string $table, string $column): void
    {
        foreach ($this->sqliteIndexesOnTable($table) as $idx) {
            if ((int) ($idx->unique ?? 0) === 1) {
                continue;
            }
            if ($this->sqliteIndexColumns($idx->name) === [$column]) {
                DB::statement('DROP INDEX IF EXISTS "'.$idx->name.'"');
            }
        }
    }

    /**
     * @return array<int, object{name: string, unique?: int}>
     */
    private function sqliteIndexesOnTable(string $table): array
    {
        return DB::select('PRAGMA index_list('.DB::getPdo()->quote($table).')');
    }

    /**
     * @return list<string>
     */
    private function sqliteIndexColumns(string $indexName): array
    {
        return array_values(array_filter(array_map(
            static fn ($row) => $row->name ?? null,
            DB::select('PRAGMA index_info('.DB::getPdo()->quote($indexName).')')
        )));
    }

    private function hasNonUniqueIndexOnColumn(string $table, string $column): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach ($this->sqliteIndexesOnTable($table) as $idx) {
                if ((int) ($idx->unique ?? 0) === 1) {
                    continue;
                }
                if (in_array($column, $this->sqliteIndexColumns($idx->name), true)) {
                    return true;
                }
            }

            return false;
        }

        return collect(DB::select('SHOW INDEX FROM '.$table.' WHERE Column_name = ?', [$column]))
            ->contains(fn ($row) => $row->Key_name !== 'PRIMARY' && (int) $row->Non_unique === 1);
    }

    private function hasUniqueIndexOnColumn(string $table, string $column): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach ($this->sqliteIndexesOnTable($table) as $idx) {
                if ((int) ($idx->unique ?? 0) !== 1) {
                    continue;
                }
                if ($this->sqliteIndexColumns($idx->name) === [$column]) {
                    return true;
                }
            }

            return false;
        }

        return collect(DB::select('SHOW INDEX FROM '.$table.' WHERE Column_name = ?', [$column]))
            ->contains(fn ($row) => $row->Key_name !== 'PRIMARY' && (int) $row->Non_unique === 0);
    }
};
