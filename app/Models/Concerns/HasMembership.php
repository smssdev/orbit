<?php

namespace App\Models\Concerns;

use App\Models\Membership;
use LogicException;

/**
 * Typed access to the membership pivot attached when a model is loaded through
 * the users ⇄ workspaces relation (which names its pivot "membership").
 */
trait HasMembership
{
    public function membership(): Membership
    {
        $membership = $this->relationLoaded('membership') ? $this->getRelation('membership') : null;

        if (! $membership instanceof Membership) {
            throw new LogicException(static::class.' was not loaded through a workspace membership relation.');
        }

        return $membership;
    }
}
