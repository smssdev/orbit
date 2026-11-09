<script setup lang="ts">
import { inject } from 'vue';
import { boardKey } from '@/components/board/boardContext';
import { cn } from '@/lib/utils';
import type { BoardTask } from '@/types';

defineProps<{
    task: BoardTask;
    editable: boolean;
}>();

const board = inject(boardKey)!;
</script>

<template>
    <li
        data-sortable-item
        :data-id="task.id"
        :data-task-id="task.id"
        :data-pending="task.pending ? '' : undefined"
        :tabindex="task.pending ? -1 : 0"
        :aria-roledescription="editable ? 'movable card' : 'card'"
        :aria-describedby="editable ? 'board-instructions' : undefined"
        :aria-busy="task.pending ? 'true' : undefined"
        :class="
            cn(
                'rounded-lg border bg-card px-3 py-2.5 text-sm break-words shadow-xs outline-none',
                'focus-visible:ring-[3px] focus-visible:ring-ring/50',
                editable && !task.pending && 'cursor-grab',
                task.pending && 'opacity-60',
                board.lift.value?.taskId === task.id &&
                    'rotate-1 border-primary shadow-md ring-[3px] ring-primary/30',
            )
        "
        @keydown="board.onCardKeydown($event, task.id)"
    >
        {{ task.title }}
    </li>
</template>
