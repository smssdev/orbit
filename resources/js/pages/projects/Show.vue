<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ProjectArchiveController from '@/actions/App/Http/Controllers/Projects/ProjectArchiveController';
import ProjectController from '@/actions/App/Http/Controllers/Projects/ProjectController';
import ProjectBoard from '@/components/board/ProjectBoard.vue';
import ConfirmAction from '@/components/ConfirmAction.vue';
import ProjectBadge from '@/components/projects/ProjectBadge.vue';
import ProjectFormDialog from '@/components/projects/ProjectFormDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/composables/useI18n';
import { fromNow } from '@/lib/workspaces';
import { index as projectsIndex } from '@/routes/projects';
import type {
    BoardColumn,
    ProjectColor,
    ProjectDetails,
    ProjectIcon,
} from '@/types';

const props = defineProps<{
    project: ProjectDetails;
    columns: BoardColumn[];
    colors: ProjectColor[];
    icons: ProjectIcon[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: projectsIndex() }],
    },
});

const { t, locale } = useI18n();

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
                    <Badge v-if="project.archivedAt" variant="secondary">{{
                        t('Archived')
                    }}</Badge>
                </div>
                <p
                    v-if="project.description"
                    class="mt-1 max-w-2xl text-sm text-muted-foreground"
                >
                    {{ project.description }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    <template v-if="project.createdBy">{{
                        t('Created by :name', { name: project.createdBy })
                    }}</template>
                    <template v-if="project.createdBy && project.createdAt">
                        ·
                    </template>
                    <template v-if="project.createdAt">{{
                        fromNow(project.createdAt, locale.current)
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
                    {{ t('Edit') }}
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
                    {{ project.archivedAt ? t('Restore') : t('Archive') }}
                </Button>
                <ConfirmAction
                    v-if="project.can.delete"
                    :title="t('Delete this project?')"
                    :description="
                        t(
                            ':name and everything in it will be deleted for everyone. This cannot be undone.',
                            { name: project.name },
                        )
                    "
                    :confirm-label="t('Delete project')"
                    :action="ProjectController.destroy(project.id)"
                >
                    <Button
                        variant="destructive"
                        data-test="delete-project-button"
                    >
                        <Trash2 />
                        {{ t('Delete') }}
                    </Button>
                </ConfirmAction>
            </div>
        </header>

        <p
            v-if="project.archivedAt"
            class="-mt-2 text-sm text-muted-foreground"
            role="note"
        >
            {{
                t(
                    'This project is archived, so its board is read-only. Restore it to make changes.',
                )
            }}
        </p>

        <ProjectBoard
            :project-id="project.id"
            :columns="columns"
            :editable="project.can.editBoard"
        />
    </div>

    <ProjectFormDialog
        v-model:open="editing"
        :project="project"
        :colors="colors"
        :icons="icons"
    />
</template>
