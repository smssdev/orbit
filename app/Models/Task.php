<?php

namespace App\Models;

use App\Enums\TaskPriority;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A card on a project's board. Its position orders it within its column (see App\Support\Positions).
 *
 * @property int $id
 * @property int $project_id
 * @property int $column_id
 * @property int|null $created_by
 * @property int|null $assignee_id
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $due_on
 * @property TaskPriority|null $priority
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read Column $column
 * @property-read User|null $creator
 * @property-read User|null $assignee
 * @property-read Collection<int, Label> $labels
 */
#[Fillable(['project_id', 'column_id', 'created_by', 'assignee_id', 'title', 'description', 'due_on', 'priority', 'position'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'due_on' => 'date',
            'priority' => TaskPriority::class,
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
     * @return BelongsTo<Column, $this>
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsToMany<Label, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    /**
     * The shape the board renders. The details panel reads the same data, so opening a card costs no request.
     *
     * @return array{id: int, title: string, description: string|null, assigneeId: int|null, dueOn: string|null, priority: TaskPriority|null, labelIds: list<int>, createdBy: string|null, createdAt: string|null}
     */
    public function toBoard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'assigneeId' => $this->assignee_id,
            'dueOn' => $this->due_on?->toDateString(),
            'priority' => $this->priority,
            'labelIds' => array_values(array_map(fn (Label $label): int => $label->id, $this->labels->all())),
            'createdBy' => $this->creator?->name,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
