<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { inject, nextTick, ref } from 'vue';
import { boardKey } from '@/components/board/boardContext';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    columnId: number;
    columnName: string;
}>();

const board = inject(boardKey)!;
const open = ref(false);
const title = ref('');
const input = ref<HTMLTextAreaElement | null>(null);
const opener = ref<InstanceType<typeof Button> | null>(null);

async function start(): Promise<void> {
    open.value = true;
    await nextTick();
    input.value?.focus();
}

function close(): void {
    open.value = false;
    title.value = '';
    void nextTick(() =>
        (opener.value?.$el as HTMLElement | undefined)?.focus(),
    );
}

// The form stays open after each card, so several go in one after the other.
function submit(): void {
    const value = title.value.trim();

    if (value === '') {
        return;
    }

    board.addTask(props.columnId, value);
    title.value = '';
    input.value?.focus();
}

function onBlur(event: FocusEvent): void {
    const form = (event.currentTarget as HTMLElement).closest('form');

    if (
        title.value.trim() === '' &&
        !form?.contains(event.relatedTarget as Node)
    ) {
        open.value = false;
    }
}
</script>

<template>
    <form v-if="open" class="grid gap-2 px-2 pb-2" @submit.prevent="submit">
        <label :for="`new-card-${columnId}`" class="sr-only"
            >Title of the new card in {{ columnName }}</label
        >
        <textarea
            :id="`new-card-${columnId}`"
            ref="input"
            v-model="title"
            rows="2"
            maxlength="200"
            required
            placeholder="What needs doing?"
            class="w-full resize-none rounded-lg border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            @keydown.enter.exact.prevent="submit"
            @keydown.esc.prevent="close"
            @blur="onBlur"
        />
        <div class="flex gap-2">
            <Button type="submit" size="sm">Add card</Button>
            <Button type="button" size="sm" variant="ghost" @click="close"
                >Cancel</Button
            >
        </div>
    </form>
    <div v-else class="px-2 pb-2">
        <Button
            ref="opener"
            variant="ghost"
            size="sm"
            class="w-full justify-start text-muted-foreground"
            @click="start"
        >
            <Plus />
            Add a card
        </Button>
    </div>
</template>
