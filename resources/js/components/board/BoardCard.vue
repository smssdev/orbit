<script setup lang="ts">
import { AlignLeft, CalendarDays } from '@lucide/vue';
import { computed, inject } from 'vue';
import { boardKey } from '@/components/board/boardContext';
import { useI18n } from '@/composables/useI18n';
import { getInitials } from '@/composables/useInitials';
import { projectColors } from '@/lib/projects';
import { formatDueDate, isOverdue, taskPriorities } from '@/lib/tasks';
import { cn } from '@/lib/utils';
import type { BoardLabel, BoardTask } from '@/types';

const { t, locale } = useI18n();

const props = defineProps<{
    task: BoardTask;
    editable: boolean;
}>();

const board = inject(boardKey)!;

const labels = computed(() =>
    props.task.labelIds
        .map((id) => board.labelsById.value.get(id))
        .filter((label): label is BoardLabel => label !== undefined),
);
const assignee = computed(() =>
    props.task.assigneeId === null
        ? undefined
        : board.membersById.value.get(props.task.assigneeId),
);
const priority = computed(() =>
    props.task.priority ? taskPriorities[props.task.priority] : undefined,
);
const overdue = computed(
    () => props.task.dueOn !== null && isOverdue(props.task.dueOn),
);
const hasMeta = computed(
    () =>
        priority.value ||
        props.task.dueOn ||
        props.task.description ||
        assignee.value,
);

// A drag ends with a click on the card too; only a click that stayed put opens it.
let pressedAt: { x: number; y: number } | null = null;

function onPointerDown(event: PointerEvent): void {
    pressedAt = { x: event.clientX, y: event.clientY };
}

function onClick(event: MouseEvent): void {
    const moved =
        pressedAt !== null &&
        Math.hypot(event.clientX - pressedAt.x, event.clientY - pressedAt.y) >
            4;
    pressedAt = null;

    if (!moved && !props.task.pending) {
        board.showTask(props.task.id);
    }
}
</script>

<template>
    <li
        data-sortable-item
        :data-id="task.id"
        :data-task-id="task.id"
        :data-pending="task.pending ? '' : undefined"
        :tabindex="task.pending ? -1 : 0"
        :aria-roledescription="editable ? t('movable card') : t('card')"
        :aria-describedby="editable ? 'board-instructions' : undefined"
        :aria-busy="task.pending ? 'true' : undefined"
        :aria-haspopup="task.pending ? undefined : 'dialog'"
        :class="
            cn(
                'flex flex-col gap-2 rounded-lg border bg-card px-3 py-2.5 text-sm break-words shadow-xs outline-none',
                'transition-colors hover:border-foreground/20 focus-visible:ring-[3px] focus-visible:ring-ring/50',
                editable && !task.pending ? 'cursor-grab' : 'cursor-pointer',
                task.pending && 'cursor-default opacity-60',
                board.lift.value?.taskId === task.id &&
                    'rotate-1 border-primary shadow-md ring-[3px] ring-primary/30',
            )
        "
        @pointerdown="onPointerDown"
        @click="onClick"
        @keydown="board.onCardKeydown($event, task.id)"
    >
        <ul v-if="labels.length" class="flex flex-wrap gap-1">
            <li
                v-for="label in labels"
                :key="label.id"
                :class="
                    cn(
                        'max-w-full truncate rounded px-1.5 py-0.5 text-[11px] leading-none font-medium',
                        projectColors[label.color].tile,
                    )
                "
            >
                {{ label.name }}
            </li>
        </ul>

        <span>{{ task.title }}</span>

        <div
            v-if="hasMeta"
            class="flex items-center gap-2.5 text-xs text-muted-foreground"
        >
            <span
                v-if="priority && task.priority"
                :class="cn('flex items-center', priority.tone)"
                :title="
                    t('Priority: :priority', { priority: t(priority.label) })
                "
            >
                <component
                    :is="priority.icon"
                    class="size-4"
                    aria-hidden="true"
                />
                <span class="sr-only">{{
                    t('Priority: :priority', { priority: t(priority.label) })
                }}</span>
            </span>
            <span
                v-if="task.dueOn"
                :class="
                    cn(
                        'flex items-center gap-1',
                        overdue && 'font-medium text-destructive',
                    )
                "
            >
                <CalendarDays class="size-3.5" aria-hidden="true" />
                <span class="sr-only">{{
                    overdue ? t('Overdue, was due') : t('Due')
                }}</span>
                <time :datetime="task.dueOn">{{
                    formatDueDate(task.dueOn, locale.current)
                }}</time>
            </span>
            <span v-if="task.description" :title="t('Has a description')">
                <AlignLeft class="size-3.5" aria-hidden="true" />
                <span class="sr-only">{{ t('Has a description') }}</span>
            </span>
            <span
                v-if="assignee"
                class="ms-auto flex size-6 items-center justify-center rounded-full bg-primary/10 text-[10px] font-semibold text-primary"
                :title="t('Assigned to :name', { name: assignee.name })"
            >
                <span aria-hidden="true">{{ getInitials(assignee.name) }}</span>
                <span class="sr-only">{{
                    t('Assigned to :name', { name: assignee.name })
                }}</span>
            </span>
        </div>
    </li>
</template>
