<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Projects created before the board get the same three columns as new ones (values copied from App\Models\Column, so
 * later changes there do not rewrite history). It only adds: a project that already
 * has a column is left alone, so running it again changes nothing.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('projects')
            ->whereNotExists(fn (Builder $query): Builder => $query->select(DB::raw(1))->from('columns')->whereColumn('columns.project_id', 'projects.id'))
            ->orderBy('id')
            ->chunkById(200, function ($projects) use ($now): void {
                $rows = [];

                foreach ($projects as $project) {
                    foreach (['To do', 'In progress', 'Done'] as $index => $name) {
                        $rows[] = [
                            'project_id' => $project->id,
                            'name' => $name,
                            'position' => ($index + 1) * 1024,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                DB::table('columns')->insert($rows);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to undo: dropping the columns table in the previous migration removes these rows.
    }
};
