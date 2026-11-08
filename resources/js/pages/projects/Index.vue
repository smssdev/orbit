<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FolderOpen, Plus } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import ProjectBadge from '@/components/projects/ProjectBadge.vue';
import ProjectFormDialog from '@/components/projects/ProjectFormDialog.vue';
import { Button } from '@/components/ui/button';
import { fromNow } from '@/lib/workspaces';
import { cn } from '@/lib/utils';
import { index as projectsIndex, show as showProject } from '@/routes/projects';
import type { ProjectColor, ProjectIcon, ProjectSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: projectsIndex() }],
    },
});

defineProps<{
    projects: ProjectSummary[];
    filter: 'active' | 'archived';
    counts: { active: number; archived: number };
    colors: ProjectColor[];
    icons: ProjectIcon[];
}>();

const creating = ref(false);

// The sidebar's «+» lands here with ?new=1: open the dialog straight away.
onMounted(() => {
    if (new URLSearchParams(window.location.search).has('new')) {
        creating.value = true;
    }
});
</script>

<template>
    <Head title="Projects" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Projects"
                description="Everything your workspace is working on"
            />
            <Button data-test="new-project-button" @click="creating = true">
                <Plus />
                New project
            </Button>
        </div>

        <nav class="flex gap-1 border-b" aria-label="Project filter">
            <Link
                v-for="tab in ['active', 'archived'] as const"
                :key="tab"
                :href="
                    projectsIndex({
                        query: tab === 'archived' ? { filter: 'archived' } : {},
                    })
                "
                :class="
                    cn(
                        '-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                        filter === tab
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground',
                    )
                "
                :aria-current="filter === tab ? 'page' : undefined"
                preserve-scroll
            >
                {{ tab === 'active' ? 'Active' : 'Archived' }}
                <span class="ml-1 text-xs text-muted-foreground tabular-nums">{{
                    counts[tab]
                }}</span>
            </Link>
        </nav>

        <div
            v-if="!projects.length"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-16 text-center"
        >
            <FolderOpen class="size-10 text-muted-foreground" />
            <p class="font-medium">
                {{
                    filter === 'active' ? 'No projects yet' : 'Nothing archived'
                }}
            </p>
            <p class="max-w-sm text-sm text-muted-foreground">
                {{
                    filter === 'active'
                        ? 'Create the first one to give your team a place to plan and track work.'
                        : 'Archived projects leave the sidebar but keep everything. You can restore them any time.'
                }}
            </p>
            <Button
                v-if="filter === 'active'"
                variant="outline"
                @click="creating = true"
            >
                <Plus />
                New project
            </Button>
        </div>

        <ul v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="project in projects" :key="project.id">
                <Link
                    :href="showProject(project.id)"
                    class="flex h-full items-start gap-3 rounded-xl border bg-card p-4 transition-colors hover:border-primary/40 hover:bg-accent/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    :data-test="`project-card-${project.id}`"
                >
                    <ProjectBadge :color="project.color" :icon="project.icon" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ project.name }}</p>
                        <p
                            v-if="project.description"
                            class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                        >
                            {{ project.description }}
                        </p>
                        <p
                            v-if="project.archivedAt"
                            class="mt-2 text-xs text-muted-foreground"
                        >
                            Archived {{ fromNow(project.archivedAt) }}
                        </p>
                    </div>
                </Link>
            </li>
        </ul>
    </div>

    <ProjectFormDialog
        v-model:open="creating"
        :colors="colors"
        :icons="icons"
    />
</template>
