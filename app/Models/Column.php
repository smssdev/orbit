<?php

namespace App\Models;

use Database\Factories\ColumnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A column of a project's board. Its position orders it among the project's columns (see App\Support\Positions).
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 */
#[Fillable(['project_id', 'name', 'position'])]
class Column extends Model
{
    /** @use HasFactory<ColumnFactory> */
    use HasFactory;

    /** The columns every new project starts with. */
    public const array DEFAULTS = ['To do', 'In progress', 'Done'];

    /** How many columns one board holds, so it stays usable. */
    public const int MAX_PER_PROJECT = 20;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position')->orderBy('id');
    }

    /**
     * The shape the board renders.
     *
     * @return array{id: int, name: string, tasks: list<array{id: int, title: string}>}
     */
    public function toBoard(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tasks' => array_values($this->tasks->map(fn (Task $task): array => $task->toBoard())->all()),
        ];
    }
}
