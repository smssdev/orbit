import {
    Book,
    Briefcase,
    Code,
    Folder,
    Heart,
    Megaphone,
    Palette,
    Rocket,
    Star,
    Zap,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import type { ProjectColor, ProjectIcon } from '@/types';

export const projectIcons: Record<ProjectIcon, LucideIcon> = {
    folder: Folder,
    rocket: Rocket,
    code: Code,
    palette: Palette,
    megaphone: Megaphone,
    book: Book,
    briefcase: Briefcase,
    heart: Heart,
    star: Star,
    zap: Zap,
};

/**
 * Each colour's tile (a soft background with a strong foreground) and its dot, readable in light and dark mode.
 * Spelled out in full so Tailwind sees every class.
 */
export const projectColors: Record<
    ProjectColor,
    { label: string; tile: string; dot: string }
> = {
    slate: {
        label: 'Slate',
        tile: 'bg-slate-500/15 text-slate-700 dark:text-slate-300',
        dot: 'bg-slate-500',
    },
    red: {
        label: 'Red',
        tile: 'bg-red-500/15 text-red-700 dark:text-red-300',
        dot: 'bg-red-500',
    },
    orange: {
        label: 'Orange',
        tile: 'bg-orange-500/15 text-orange-700 dark:text-orange-300',
        dot: 'bg-orange-500',
    },
    amber: {
        label: 'Amber',
        tile: 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
        dot: 'bg-amber-500',
    },
    green: {
        label: 'Green',
        tile: 'bg-green-500/15 text-green-700 dark:text-green-300',
        dot: 'bg-green-500',
    },
    teal: {
        label: 'Teal',
        tile: 'bg-teal-500/15 text-teal-700 dark:text-teal-300',
        dot: 'bg-teal-500',
    },
    blue: {
        label: 'Blue',
        tile: 'bg-blue-500/15 text-blue-700 dark:text-blue-300',
        dot: 'bg-blue-500',
    },
    indigo: {
        label: 'Indigo',
        tile: 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300',
        dot: 'bg-indigo-500',
    },
    violet: {
        label: 'Violet',
        tile: 'bg-violet-500/15 text-violet-700 dark:text-violet-300',
        dot: 'bg-violet-500',
    },
    pink: {
        label: 'Pink',
        tile: 'bg-pink-500/15 text-pink-700 dark:text-pink-300',
        dot: 'bg-pink-500',
    },
};
