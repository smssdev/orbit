<?php

namespace App\Models;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use App\Support\Positions;
use Closure;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int|null $created_by
 * @property string $name
 * @property string|null $description
 * @property ProjectColor $color
 * @property ProjectIcon $icon
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read User|null $creator
 * @property-read Collection<int, Column> $columns
 * @property-read Collection<int, Task> $tasks
 * @property-read Collection<int, Label> $labels
 */
#[Fillable(['workspace_id', 'created_by', 'name', 'description', 'color', 'icon', 'archived_at'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * Every project starts with a board ready to use.
     */
    protected static function booted(): void
    {
        static::created(function (Project $project): void {
            foreach (Column::DEFAULTS as $index => $name) {
                $project->columns()->create(['name' => $name, 'position' => ($index + 1) * Positions::GAP]);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => ProjectColor::class,
            'icon' => ProjectIcon::class,
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Column, $this>
     */
    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<Label, $this>
     */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class)->orderBy('name');
    }

    /**
     * The board: its columns in order, each with its cards in order.
     *
     * @return list<array{id: int, name: string, tasks: list<array<string, mixed>>}>
     */
    public function board(): array
    {
        return array_values($this->columns()
            ->with(['tasks.labels', 'tasks.creator'])
            ->get()
            ->map(fn (Column $column): array => $column->toBoard())
            ->all());
    }

    /**
     * Runs a change to the board in a transaction that holds the project's row, so concurrent moves on one board
     * happen one after the other and each sees the order the previous one left.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $change
     * @return TResult
     */
    public function changeBoard(Closure $change): mixed
    {
        return DB::transaction(function () use ($change): mixed {
            self::query()->whereKey($this->id)->lockForUpdate()->first();

            return $change();
        });
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function archived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /**
     * The shape every page and the sidebar share.
     *
     * @return array{id: int, name: string, description: string|null, color: ProjectColor, icon: ProjectIcon, archivedAt: string|null}
     */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'archivedAt' => $this->archived_at?->toIso8601String(),
        ];
    }
}
