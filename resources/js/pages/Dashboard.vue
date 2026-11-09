<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    FolderKanban,
    ListTodo,
    MailPlus,
    Plus,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import ProjectBadge from '@/components/projects/ProjectBadge.vue';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as projectsIndex, show as showProject } from '@/routes/projects';
import { index as membersIndex } from '@/routes/workspace/members';
import type { ProjectColor, ProjectIcon } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

type DashboardProject = {
    id: number;
    name: string;
    description: string | null;
    color: ProjectColor;
    icon: ProjectIcon;
    tasks: number;
    columns: { name: string; tasks: number }[];
};

const props = defineProps<{
    stats: {
        projects: number;
        tasks: number;
        members: number;
        invitations: number;
    };
    projects: DashboardProject[];
}>();

const { t, tc } = useI18n();
const page = usePage();

const firstName = computed(
    () => page.props.auth.user.name.trim().split(/\s+/)[0] ?? '',
);

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return t('Good morning, :name', { name: firstName.value });
    }

    return hour < 18
        ? t('Good afternoon, :name', { name: firstName.value })
        : t('Good evening, :name', { name: firstName.value });
});

const tiles = computed(() => [
    {
        label: t('Active projects'),
        value: props.stats.projects,
        icon: FolderKanban,
        href: projectsIndex(),
    },
    {
        label: t('Cards on boards'),
        value: props.stats.tasks,
        icon: ListTodo,
        href: projectsIndex(),
    },
    {
        label: t('Members'),
        value: props.stats.members,
        icon: Users,
        href: membersIndex(),
    },
    {
        label: t('Pending invitations'),
        value: props.stats.invitations,
        icon: MailPlus,
        href: membersIndex(),
    },
]);

// Column shades for the stacked bar: from the brand colour (first column) to the comet accent (last).
const segmentClasses = [
    'bg-primary',
    'bg-primary/70',
    'bg-primary/45',
    'bg-chart-2',
    'bg-comet',
];

function segmentClass(index: number, count: number): string {
    if (count > 1 && index === count - 1) {
        return 'bg-comet';
    }

    return segmentClasses[Math.min(index, segmentClasses.length - 2)];
}
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div class="flex flex-1 flex-col gap-8 p-4 md:p-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted-foreground">
                    {{ page.props.currentWorkspace?.name }}
                </p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">
                    {{ greeting }}
                </h1>
            </div>
            <Button as-child>
                <Link :href="projectsIndex({ query: { new: 1 } })">
                    <Plus />
                    {{ t('New project') }}
                </Link>
            </Button>
        </header>

        <section
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
            :aria-label="t('Overview')"
        >
            <Link
                v-for="tile in tiles"
                :key="tile.label"
                :href="tile.href"
                class="group rounded-xl border bg-card p-4 transition-colors hover:border-primary/40"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">
                        {{ tile.label }}
                    </span>
                    <span
                        class="grid size-8 place-items-center rounded-lg bg-accent text-accent-foreground"
                    >
                        <component :is="tile.icon" class="size-4" />
                    </span>
                </div>
                <p
                    class="mt-3 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ tile.value }}
                </p>
            </Link>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold tracking-tight">
                    {{ t('Projects') }}
                </h2>
                <Link
                    v-if="projects.length"
                    :href="projectsIndex()"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    {{ t('View all') }}
                </Link>
            </div>

            <div
                v-if="!projects.length"
                class="flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-14 text-center"
            >
                <FolderKanban class="size-10 text-muted-foreground" />
                <p class="font-medium">{{ t('No projects yet') }}</p>
                <p class="max-w-sm text-sm text-muted-foreground">
                    {{
                        t(
                            'Create the first one to give your team a place to plan and track work.',
                        )
                    }}
                </p>
                <Button variant="outline" as-child>
                    <Link :href="projectsIndex({ query: { new: 1 } })">
                        <Plus />
                        {{ t('New project') }}
                    </Link>
                </Button>
            </div>

            <ul v-else class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <li v-for="project in projects" :key="project.id">
                    <Link
                        :href="showProject(project.id)"
                        class="group flex h-full flex-col gap-4 rounded-xl border bg-card p-4 transition-colors hover:border-primary/40"
                    >
                        <div class="flex items-start gap-3">
                            <ProjectBadge
                                :color="project.color"
                                :icon="project.icon"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ project.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        tc(
                                            ':count card|:count cards',
                                            project.tasks,
                                        )
                                    }}
                                </p>
                            </div>
                            <ArrowUpRight
                                class="size-4 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 rtl:-scale-x-100"
                            />
                        </div>

                        <div class="mt-auto space-y-2">
                            <div
                                class="flex h-2 overflow-hidden rounded-full bg-muted"
                                role="img"
                                :aria-label="
                                    project.columns
                                        .map((c) => `${c.name}: ${c.tasks}`)
                                        .join(', ')
                                "
                            >
                                <div
                                    v-for="(column, index) in project.columns"
                                    v-show="column.tasks > 0"
                                    :key="column.name + index"
                                    :class="
                                        cn(
                                            'h-full',
                                            segmentClass(
                                                index,
                                                project.columns.length,
                                            ),
                                        )
                                    "
                                    :style="{
                                        width: `${(column.tasks / Math.max(project.tasks, 1)) * 100}%`,
                                    }"
                                />
                            </div>
                            <ul
                                class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground"
                            >
                                <li
                                    v-for="(column, index) in project.columns"
                                    :key="column.name + index"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        :class="
                                            cn(
                                                'size-2 rounded-full',
                                                segmentClass(
                                                    index,
                                                    project.columns.length,
                                                ),
                                            )
                                        "
                                    />
                                    {{ column.name }}
                                    <span class="tabular-nums">{{
                                        column.tasks
                                    }}</span>
                                </li>
                            </ul>
                        </div>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
