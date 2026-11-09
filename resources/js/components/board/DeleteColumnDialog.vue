<script setup lang="ts">
import { computed, inject, ref, watch } from 'vue';
import { boardKey } from '@/components/board/boardContext';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BoardColumn } from '@/types';

const props = defineProps<{
    column: BoardColumn;
    columns: BoardColumn[];
}>();

const open = defineModel<boolean>('open', { required: true });

const board = inject(boardKey)!;
const targets = computed(() =>
    props.columns.filter(
        ({ id, pending }) => id !== props.column.id && !pending,
    ),
);
const target = ref<string>('');

watch(open, (isOpen) => {
    if (isOpen) {
        target.value = String(targets.value[0]?.id ?? '');
    }
});

const count = computed(() => props.column.tasks.length);

function confirm(): void {
    if (target.value === '') {
        return;
    }

    board.deleteColumn(props.column.id, Number(target.value));
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="space-y-3">
                <DialogTitle>Delete {{ column.name }}?</DialogTitle>
                <DialogDescription>
                    It has {{ count }} {{ count === 1 ? 'card' : 'cards' }}.
                    <template v-if="targets.length > 0">
                        Choose a column to move
                        {{ count === 1 ? 'it' : 'them' }} to; nothing is lost.
                    </template>
                    <template v-else>
                        Add another column first, so its cards have somewhere to
                        go.
                    </template>
                </DialogDescription>
            </DialogHeader>

            <div v-if="targets.length > 0" class="grid gap-2">
                <Label :for="`move-cards-${column.id}`">Move cards to</Label>
                <Select v-model="target">
                    <SelectTrigger
                        :id="`move-cards-${column.id}`"
                        class="w-full"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in targets"
                            :key="option.id"
                            :value="String(option.id)"
                        >
                            {{ option.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="targets.length === 0"
                    @click="confirm"
                >
                    Move cards and delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
