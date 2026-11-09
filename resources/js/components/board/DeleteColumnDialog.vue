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
import { useI18n } from '@/composables/useI18n';

const { t, tc } = useI18n();

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
                <DialogTitle>{{
                    t('Delete :name?', { name: column.name })
                }}</DialogTitle>
                <DialogDescription>
                    {{ tc('It has :count card.|It has :count cards.', count) }}
                    <template v-if="targets.length > 0">
                        {{
                            t(
                                'Choose a column to move them to; nothing is lost.',
                            )
                        }}
                    </template>
                    <template v-else>
                        {{
                            t(
                                'Add another column first, so its cards have somewhere to go.',
                            )
                        }}
                    </template>
                </DialogDescription>
            </DialogHeader>

            <div v-if="targets.length > 0" class="grid gap-2">
                <Label :for="`move-cards-${column.id}`">{{
                    t('Move cards to')
                }}</Label>
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
                    <Button variant="secondary">{{ t('Cancel') }}</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="targets.length === 0"
                    @click="confirm"
                >
                    {{ t('Move cards and delete') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
