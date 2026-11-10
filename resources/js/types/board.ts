import type { ProjectColor } from './project';

export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

export type BoardTask = {
    id: number;
    title: string;
    description: string | null;
    assigneeId: number | null;
    /** A calendar date, `YYYY-MM-DD`. */
    dueOn: string | null;
    priority: TaskPriority | null;
    labelIds: number[];
    createdBy: string | null;
    createdAt: string | null;
    /** Shown before the server has saved it (its ID is a temporary, negative one). */
    pending?: boolean;
};

export type BoardColumn = {
    id: number;
    name: string;
    tasks: BoardTask[];
    pending?: boolean;
};

/** A project's label, which its cards can wear. */
export type BoardLabel = {
    id: number;
    name: string;
    color: ProjectColor;
};

/** A workspace member a card can be assigned to. */
export type BoardMember = {
    id: number;
    name: string;
};

/** The details of a card the panel changes, named as the server expects them. Only those sent change. */
export type TaskChanges = {
    title?: string;
    description?: string | null;
    assignee_id?: number | null;
    due_on?: string | null;
    priority?: TaskPriority | null;
    labels?: number[];
};
