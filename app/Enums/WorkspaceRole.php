<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }

    public function canManageMembers(): bool
    {
        return $this !== self::Member;
    }

    /**
     * The roles this role is allowed to give to others, through invitations or role changes.
     *
     * Ownership is never assignable: each workspace has exactly one owner, its creator.
     *
     * @return list<self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::Owner => [self::Admin, self::Member],
            self::Admin => [self::Member],
            self::Member => [],
        };
    }
}
