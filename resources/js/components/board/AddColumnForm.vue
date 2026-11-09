<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { inject, nextTick, ref } from 'vue';
import { boardKey } from '@/components/board/boardContext';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const board = inject(boardKey)!;
const open = ref(false);
const name = ref('');
const input = ref<HTMLInputElement | null>(null);
const opener = ref<InstanceType<typeof Button> | null>(null);

async function start(): Promise<void> {
    open.value = true;
    await nextTick();
    input.value?.focus();
}

function close(): void {
    open.value = false;
    name.value = '';
    void nextTick(() =>
        (opener.value?.$el as HTMLElement | undefined)?.focus(),
    );
}

function submit(): void {
    const value = name.value.trim();

    if (value === '') {
        return;
    }

    board.addColumn(value);
    name.value = '';
    input.value?.focus();
}
</script>

<template>
    <div class="w-72 shrink-0">
        <form
            v-if="open"
            class="grid gap-2 rounded-xl border bg-muted/40 p-2"
            @submit.prevent="submit"
        >
            <label for="new-column-name" class="sr-only">{{
                t('Column name')
            }}</label>
            <input
                id="new-column-name"
                ref="input"
                v-model="name"
                maxlength="40"
                required
                :placeholder="t('Column name')"
                autocomplete="off"
                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                @keydown.esc.prevent="close"
            />
            <div class="flex gap-2">
                <Button type="submit" size="sm">{{ t('Add column') }}</Button>
                <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    @click="close"
                    >{{ t('Cancel') }}</Button
                >
            </div>
        </form>
        <Button
            v-else
            ref="opener"
            variant="outline"
            class="w-full justify-start border-dashed text-muted-foreground"
            @click="start"
        >
            <Plus />
            {{ t('Add a column') }}
        </Button>
    </div>
</template>
