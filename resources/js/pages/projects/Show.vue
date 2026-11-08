<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, Columns3, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ProjectArchiveController from '@/actions/App/Http/Controllers/Projects/ProjectArchiveController';
import ProjectController from '@/actions/App/Http/Controllers/Projects/ProjectController';
import ConfirmAction from '@/components/ConfirmAction.vue';
import ProjectBadge from '@/components/projects/ProjectBadge.vue';
import ProjectFormDialog from '@/components/projects/ProjectFormDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { fromNow } from '@/lib/workspaces';
import { index as projectsIndex } from '@/routes/projects';
import type { ProjectColor, ProjectDetails, ProjectIcon } from '@/types';

const props = defineProps<{
    project: ProjectDetails;
    colors: ProjectColor[];
    icons: ProjectIcon[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: projectsIndex() }],
    },
});

const editing = ref(false);
const working = ref(false);

// Archiving is undone in one click, so it needs no confirmation; deleting does.
function toggleArchive(): void {
    const action = props.project.archivedAt
        ? ProjectArchiveController.destroy(props.project.id)
        : ProjectArchiveController.store(props.project.id);

    router.visit(action, {
        onStart: () => (working.value = true),
        onFinish: () => (working.value = false),
    });
}
</script>

<template>
    <Head :title="project.name" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <header class="flex flex-wrap items-start gap-4">
            <ProjectBadge
                :color="project.color"
                :icon="project.icon"
                size="lg"
            />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="truncate text-xl font-semibold tracking-tight">
                        {{ project.name }}
                    </h1>
                    <Badge v-if="project.archivedAt" variant="secondary"
                        >Archived</Badge
                    >
                </div>
                <p
                    v-if="project.description"
                    class="mt-1 max-w-2xl text-sm text-muted-foreground"
                >
                    {{ project.description }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    <template v-if="project.createdBy"
                        >Created by {{ project.createdBy }}</template
                    >
                    <template v-if="project.createdBy && project.createdAt">
                        ·
                    </template>
                    <template v-if="project.createdAt">{{
                        fromNow(project.createdAt)
                    }}</template>
                </p>
            </div>

            <div v-if="project.can.update" class="flex flex-wrap gap-2">
                <Button
                    v-if="!project.archivedAt"
                    variant="outline"
                    data-test="edit-project-button"
                    @click="editing = true"
                >
                    <Pencil />
                    Edit
                </Button>
                <Button
                    variant="outline"
                    :disabled="working"
                    data-test="archive-project-button"
                    @click="toggleArchive"
                >
                    <Spinner v-if="working" />
                    <ArchiveRestore v-else-if="project.archivedAt" />
                    <Archive v-else />
                    {{ project.archivedAt ? 'Restore' : 'Archive' }}
                </Button>
                <ConfirmAction
                    v-if="project.can.delete"
                    title="Delete this project?"
                    :description="`${project.name} and everything in it will be deleted for everyone. This cannot be undone.`"
                    confirm-label="Delete project"
                    :action="ProjectController.destroy(project.id)"
                >
                    <Button
                        variant="destructive"
                        data-test="delete-project-button"
                    >
                        <Trash2 />
                        Delete
                    </Button>
                </ConfirmAction>
            </div>
        </header>

        <section
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed px-6 py-16 text-center"
            aria-labelledby="board-heading"
        >
            <Columns3 class="size-10 text-muted-foreground" />
            <h2 id="board-heading" class="font-medium">The board comes next</h2>
            <p class="max-w-sm text-sm text-muted-foreground">
                Columns and cards you can drag between them arrive in the next
                update of Orbit.
            </p>
        </section>
    </div>

    <ProjectFormDialog
        v-model:open="editing"
        :project="project"
        :colors="colors"
        :icons="icons"
    />
</template>
