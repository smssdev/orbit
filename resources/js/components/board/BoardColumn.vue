<script setup lang="ts">
import { ArrowLeft, ArrowRight, Ellipsis, Pencil, Trash2 } from '@lucide/vue';
import { computed, inject, nextTick, ref } from 'vue';
import AddTaskForm from '@/components/board/AddTaskForm.vue';
import BoardCard from '@/components/board/BoardCard.vue';
import { boardKey } from '@/components/board/boardContext';
import DeleteColumnDialog from '@/components/board/DeleteColumnDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/composables/useI18n';
import { useSortable } from '@/composables/useSortable';
import { cn } from '@/lib/utils';
import type { BoardColumn } from '@/types';

const props = defineProps<{
    column: BoardColumn;
    /** Its index on the board. */
    index: number;
    /** All columns, for moving it and for where its cards go when it is deleted. */
    columns: BoardColumn[];
    editable: boolean;
}>();

const board = inject(boardKey)!;
const { t, dir } = useI18n();
const list = ref<HTMLElement | null>(null);
const nameInput = ref<HTMLInputElement | null>(null);
const menuTrigger = ref<InstanceType<typeof Button> | null>(null);
const renaming = ref(false);
const name = ref('');
const deleting = ref(false);

const headingId = computed(() => `column-${props.column.id}-name`);
const editable = computed(() => props.editable && !props.column.pending);

useSortable(list, {
    group: 'cards',
    disabled: () => !editable.value,
    onMove: (taskId, columnId, index) =>
        board.dropTask(taskId, columnId, index),
});

function focusMenu(): void {
    void nextTick(() =>
        (menuTrigger.value?.$el as HTMLElement | undefined)?.focus(),
    );
}

async function startRename(): Promise<void> {
    name.value = props.column.name;
    renaming.value = true;
    await nextTick();
    nameInput.value?.select();
}

function saveName(): void {
    if (!renaming.value) {
        return;
    }

    const value = name.value.trim();
    renaming.value = false;

    if (value !== '' && value !== props.column.name) {
        board.renameColumn(props.column.id, value);
    }

    focusMenu();
}

function cancelRename(): void {
    renaming.value = false;
    focusMenu();
}

function move(step: number): void {
    board.moveColumn(props.column.id, props.index + step);
    focusMenu();
}

function remove(): void {
    if (props.column.tasks.length > 0) {
        deleting.value = true;
    } else {
        board.deleteColumn(props.column.id, null);
    }
}
</script>

<template>
    <section
        data-sortable-item
        :data-id="column.id"
        :data-pending="column.pending ? '' : undefined"
        :aria-labelledby="headingId"
        :aria-busy="column.pending ? 'true' : undefined"
        :class="
            cn(
                'flex w-72 shrink-0 flex-col rounded-xl border bg-muted/40',
                column.pending && 'opacity-60',
            )
        "
    >
        <header
            data-column-handle
            :class="
                cn(
                    'flex min-h-11 items-center gap-2 py-1.5 ps-3 pe-1.5',
                    editable && 'cursor-grab',
                )
            "
        >
            <form v-if="renaming" class="flex-1" @submit.prevent="saveName">
                <label :for="`${headingId}-input`" class="sr-only">{{
                    t('Column name')
                }}</label>
                <input
                    :id="`${headingId}-input`"
                    ref="nameInput"
                    v-model="name"
                    maxlength="40"
                    required
                    class="h-8 w-full rounded-md border border-input bg-background px-2 text-sm font-medium outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    @keydown.esc.prevent="cancelRename"
                    @blur="saveName"
                />
            </form>
            <h2
                v-else
                :id="headingId"
                class="min-w-0 flex-1 truncate text-sm font-medium"
                @dblclick="editable && startRename()"
            >
                {{ column.name }}
            </h2>
            <span
                class="rounded-full bg-muted px-2 text-xs text-muted-foreground tabular-nums"
                :aria-label="`${column.tasks.length} ${column.tasks.length === 1 ? 'card' : 'cards'}`"
                >{{ column.tasks.length }}</span
            >
            <DropdownMenu v-if="editable && !renaming">
                <DropdownMenuTrigger as-child>
                    <Button
                        ref="menuTrigger"
                        variant="ghost"
                        size="icon"
                        class="size-8"
                        :aria-label="
                            t('Actions for :name', { name: column.name })
                        "
                    >
                        <Ellipsis />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-44">
                    <DropdownMenuItem @select="startRename">
                        <Pencil />
                        {{ t('Rename') }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        :disabled="index === 0"
                        @select="move(-1)"
                    >
                        <ArrowRight v-if="dir === 'rtl'" />
                        <ArrowLeft v-else />
                        {{ dir === 'rtl' ? t('Move right') : t('Move left') }}
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        :disabled="index === columns.length - 1"
                        @select="move(1)"
                    >
                        <ArrowLeft v-if="dir === 'rtl'" />
                        <ArrowRight v-else />
                        {{ dir === 'rtl' ? t('Move left') : t('Move right') }}
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem variant="destructive" @select="remove">
                        <Trash2 />
                        {{ t('Delete') }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </header>

        <ol
            ref="list"
            :data-list-id="column.id"
            :aria-labelledby="headingId"
            class="flex min-h-14 flex-1 flex-col gap-2 px-2 pb-2"
        >
            <BoardCard
                v-for="task in column.tasks"
                :key="task.id"
                :task="task"
                :editable="editable"
            />
        </ol>

        <AddTaskForm
            v-if="editable"
            :column-id="column.id"
            :column-name="column.name"
        />

        <!-- Inside the section (its content renders elsewhere), so each column stays one element to drag. -->
        <DeleteColumnDialog
            v-if="editable"
            v-model:open="deleting"
            :column="column"
            :columns="columns"
        />
    </section>
</template>
