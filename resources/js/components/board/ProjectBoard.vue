<script setup lang="ts">
import { computed, provide, ref } from 'vue';
import AddColumnForm from '@/components/board/AddColumnForm.vue';
import BoardColumn from '@/components/board/BoardColumn.vue';
import TaskDetailsSheet from '@/components/board/TaskDetailsSheet.vue';
import { boardKey } from '@/components/board/boardContext';
import { useBoard } from '@/composables/useBoard';
import { useSortable } from '@/composables/useSortable';
import type {
    BoardColumn as Column,
    BoardLabel,
    BoardMember,
    ProjectColor,
    TaskPriority,
} from '@/types';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const props = defineProps<{
    projectId: number;
    columns: Column[];
    editable: boolean;
    labels: BoardLabel[];
    members: BoardMember[];
    priorities: TaskPriority[];
    colors: ProjectColor[];
}>();

/** Mirrors App\Models\Column::MAX_PER_PROJECT. */
const maxColumns = 20;

const root = ref<HTMLElement | null>(null);
const list = ref<HTMLElement | null>(null);

const board = useBoard({
    projectId: () => props.projectId,
    columns: () => props.columns,
    editable: () => props.editable,
    labels: () => props.labels,
    members: () => props.members,
    root,
});

provide(boardKey, board);

const columns = board.columns;
const canAddColumn = computed(
    () => props.editable && props.columns.length < maxColumns,
);

useSortable(list, {
    group: 'columns',
    handle: '[data-column-handle]',
    disabled: () => !props.editable,
    onMove: (columnId, _list, index) => board.moveColumn(columnId, index),
});
</script>

<template>
    <section
        ref="root"
        aria-labelledby="board-heading"
        class="flex min-h-0 flex-1 flex-col"
    >
        <h2 id="board-heading" class="sr-only">{{ t('Board') }}</h2>
        <p v-if="editable" id="board-instructions" class="sr-only">
            {{
                t(
                    'To move a card with the keyboard, press Space to pick it up, the arrow keys to move it, then Space to drop it, or Escape to cancel.',
                )
            }}
        </p>
        <div class="sr-only" aria-live="assertive" aria-atomic="true">
            {{ board.announcement.value }}
        </div>

        <div
            class="-mx-4 flex flex-1 items-start gap-3 overflow-x-auto px-4 pb-4"
        >
            <div ref="list" :data-list-id="0" class="flex items-start gap-3">
                <BoardColumn
                    v-for="(column, index) in columns"
                    :key="column.id"
                    :column="column"
                    :index="index"
                    :columns="columns"
                    :editable="editable"
                />
            </div>
            <AddColumnForm v-if="canAddColumn" />
            <p
                v-if="columns.length === 0 && !editable"
                class="text-sm text-muted-foreground"
            >
                {{ t('This board has no columns.') }}
            </p>
        </div>

        <TaskDetailsSheet
            :project-id="projectId"
            :editable="editable"
            :labels="labels"
            :members="members"
            :priorities="priorities"
            :colors="colors"
        />
    </section>
</template>
