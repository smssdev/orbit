import { ChevronDown, ChevronsUp, ChevronUp, Equal } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import type { BoardTask, TaskChanges, TaskPriority } from '@/types';

/** Each priority's name (an English source string: show it through t()), icon and colour. */
export const taskPriorities: Record<
    TaskPriority,
    { label: string; icon: LucideIcon; tone: string }
> = {
    low: {
        label: 'Low',
        icon: ChevronDown,
        tone: 'text-slate-500 dark:text-slate-400',
    },
    medium: {
        label: 'Medium',
        icon: Equal,
        tone: 'text-blue-600 dark:text-blue-400',
    },
    high: {
        label: 'High',
        icon: ChevronUp,
        tone: 'text-orange-600 dark:text-orange-400',
    },
    urgent: {
        label: 'Urgent',
        icon: ChevronsUp,
        tone: 'text-red-600 dark:text-red-400',
    },
};

/** Today as `YYYY-MM-DD`, in the viewer's own time zone. */
export function today(now: Date = new Date()): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/** Whether a due date (`YYYY-MM-DD`) has passed. Dates in this form compare as strings. */
export function isOverdue(dueOn: string, now: Date = new Date()): boolean {
    return dueOn < today(now);
}

/** "12 Dec" or "١٢ ديسمبر": a short due date, with the year only when it is not this one. */
export function formatDueDate(
    dueOn: string,
    locale: string = 'en',
    now: Date = new Date(),
): string {
    const [year, month, day] = dueOn.split('-').map(Number);
    const date = new Date(year, month - 1, day);

    return new Intl.DateTimeFormat(locale, {
        day: 'numeric',
        month: 'short',
        year: year === now.getFullYear() ? undefined : 'numeric',
    }).format(date);
}

/** A card with the changes applied, as the server will save them. */
export function withChanges(task: BoardTask, changes: TaskChanges): BoardTask {
    return {
        ...task,
        ...(changes.title !== undefined && { title: changes.title }),
        ...(changes.description !== undefined && {
            description: changes.description?.trim() || null,
        }),
        ...(changes.assignee_id !== undefined && {
            assigneeId: changes.assignee_id,
        }),
        ...(changes.due_on !== undefined && { dueOn: changes.due_on }),
        ...(changes.priority !== undefined && { priority: changes.priority }),
        ...(changes.labels !== undefined && { labelIds: changes.labels }),
    };
}

/** A card the board shows before the server has saved it. */
export function newTask(id: number, title: string): BoardTask {
    return {
        id,
        title,
        description: null,
        assigneeId: null,
        dueOn: null,
        priority: null,
        labelIds: [],
        createdBy: null,
        createdAt: null,
        pending: true,
    };
}
