export type WorkspaceRole = 'owner' | 'admin' | 'member';

export type WorkspaceSummary = {
    id: number;
    name: string;
    role: WorkspaceRole;
};

export type WorkspaceMember = {
    id: number;
    name: string;
    email: string;
    role: WorkspaceRole;
    joinedAt: string | null;
    isYou: boolean;
    can: {
        updateRole: boolean;
        remove: boolean;
    };
};

export type PendingInvitation = {
    id: number;
    email: string;
    role: WorkspaceRole;
    invitedBy: string | null;
    expiresAt: string;
    isExpired: boolean;
};

export type InvitationStatus = 'valid' | 'expired' | 'wrong-account';
