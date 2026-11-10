<?php

namespace App\Models;

use App\Enums\ProjectColor;
use Database\Factories\LabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A tag the cards of one project share, such as "Bug" or "Design". It wears one of the project colours.
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property ProjectColor $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read Collection<int, Task> $tasks
 */
#[Fillable(['project_id', 'name', 'color'])]
class Label extends Model
{
    /** @use HasFactory<LabelFactory> */
    use HasFactory;

    /** How many labels one project holds, so the picker stays usable. */
    public const int MAX_PER_PROJECT = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => ProjectColor::class,
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
     * @return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    /**
     * @return array{id: int, name: string, color: ProjectColor}
     */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
        ];
    }
}
