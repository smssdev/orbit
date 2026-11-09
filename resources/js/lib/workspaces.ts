import type { WorkspaceRole } from '@/types';

// English source strings: show them through t().
export const roleLabels: Record<WorkspaceRole, string> = {
    owner: 'Owner',
    admin: 'Admin',
    member: 'Member',
};

export const roleDescriptions: Record<WorkspaceRole, string> = {
    owner: 'Full control of the workspace',
    admin: 'Can manage members and settings',
    member: 'Can work on projects',
};

const units: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31_536_000],
    ['month', 2_592_000],
    ['week', 604_800],
    ['day', 86_400],
    ['hour', 3_600],
    ['minute', 60],
];

/**
 * "in 6 days", "2 weeks ago", "now" (or the same in Arabic): a short, human distance from today.
 */
export function fromNow(
    iso: string,
    locale: string = 'en',
    now: Date = new Date(),
): string {
    const relativeTime = new Intl.RelativeTimeFormat(locale, {
        numeric: 'auto',
    });
    const seconds = (new Date(iso).getTime() - now.getTime()) / 1000;

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return relativeTime.format(Math.round(seconds / size), unit);
        }
    }

    return relativeTime.format(0, 'minute');
}
