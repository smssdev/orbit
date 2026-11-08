<?php

namespace App\Models;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
 */
#[Fillable(['workspace_id', 'created_by', 'name', 'description', 'color', 'icon', 'archived_at'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

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
