<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, Plus, Trash2, X } from '@lucide/vue';
import { computed, inject, nextTick, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import LabelController from '@/actions/App/Http/Controllers/Board/LabelController';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useI18n } from '@/composables/useI18n';
import { projectColors } from '@/lib/projects';
import { taskPriorities } from '@/lib/tasks';
import { cn } from '@/lib/utils';
import { fromNow } from '@/lib/workspaces';
import type {
    BoardLabel,
    BoardMember,
    ProjectColor,
    TaskPriority,
} from '@/types';

const props = defineProps<{
    projectId: number;
    editable: boolean;
    labels: BoardLabel[];
    members: BoardMember[];
    priorities: TaskPriority[];
    colors: ProjectColor[];
}>();

const board = inject(boardKey)!;
const page = usePage();
const { t, locale, dir } = useI18n();

/** Mirrors App\Models\Label::MAX_PER_PROJECT. */
const maxLabels = 30;
/** Select items need a value; this one stands for "nobody" or "no priority". */
const NONE = 'none';

const task = computed(() => board.openTask.value);
const open = computed({
    get: () => task.value !== null,
    set: (value) => {
        if (!value) {
            board.closeTask();
        }
    },
});

const column = computed(
    () =>
        board.columns.value.find((candidate) =>
            candidate.tasks.some(({ id }) => id === task.value?.id),
        )?.name,
);

const managingLabels = ref(false);
const creatingLabel = ref(false);

// Title and description are drafts while focused, saved when they lose focus.
const title = ref('');
const description = ref('');

watch(
    task,
    (current, previous) => {
        if (!current) {
            return;
        }

        if (
            current.id !== previous?.id ||
            document.activeElement?.id !== 'task-title'
        ) {
            title.value = current.title;
        }

        if (
            current.id !== previous?.id ||
            document.activeElement?.id !== 'task-description'
        ) {
            description.value = current.description ?? '';
        }

        if (current.id !== previous?.id) {
            creatingLabel.value = false;
            managingLabels.value = false;
        }
    },
    { immediate: true },
);

function saveTitle(): void {
    const value = title.value.trim();

    if (!task.value) {
        return;
    }

    if (value === '') {
        title.value = task.value.title;

        return;
    }

    if (value !== task.value.title) {
        board.updateTask(task.value.id, { title: value });
    }
}

function saveDescription(): void {
    if (!task.value) {
        return;
    }

    const value = description.value.trim();

    if (value !== (task.value.description ?? '')) {
        board.updateTask(task.value.id, { description: value || null });
    }
}

const assignee = computed({
    get: () => String(task.value?.assigneeId ?? NONE),
    set: (value: string) => {
        if (task.value) {
            board.updateTask(task.value.id, {
                assignee_id: value === NONE ? null : Number(value),
            });
        }
    },
});

const priority = computed({
    get: () => task.value?.priority ?? NONE,
    set: (value: string) => {
        if (task.value) {
            board.updateTask(task.value.id, {
                priority: value === NONE ? null : (value as TaskPriority),
            });
        }
    },
});

function setDueOn(value: string): void {
    if (task.value && value !== (task.value.dueOn ?? '')) {
        board.updateTask(task.value.id, { due_on: value || null });
    }
}

// Labels

const labelName = ref('');
const labelColor = ref<ProjectColor>('blue');
const labelInput = ref<HTMLInputElement | null>(null);
const savingLabel = ref(false);

function hasLabel(labelId: number): boolean {
    return task.value?.labelIds.includes(labelId) ?? false;
}

function toggleLabel(labelId: number): void {
    if (!task.value) {
        return;
    }

    const ids = hasLabel(labelId)
        ? task.value.labelIds.filter((id) => id !== labelId)
        : [...task.value.labelIds, labelId];

    board.updateTask(task.value.id, { labels: ids });
}

async function startLabel(): Promise<void> {
    creatingLabel.value = true;
    labelName.value = '';
    // A colour no label wears yet, so new labels tell apart at a glance.
    labelColor.value =
        props.colors.find(
            (color) => !props.labels.some((label) => label.color === color),
        ) ?? 'blue';
    await nextTick();
    labelInput.value?.focus();
}

function createLabel(): void {
    const name = labelName.value.trim();
    const taskId = task.value?.id;

    if (name === '' || taskId === undefined) {
        return;
    }

    router.post(
        LabelController.store(props.projectId).url,
        { name, color: labelColor.value },
        {
            only: ['labels'],
            preserveScroll: true,
            preserveState: true,
            showProgress: false,
            onStart: () => (savingLabel.value = true),
            onFinish: () => (savingLabel.value = false),
            onSuccess: () => {
                creatingLabel.value = false;
                // Put the new label on the card it was made from.
                const created = (page.props.labels as BoardLabel[]).find(
                    (label) => label.name === name,
                );
                const current = board.openTask.value;

                if (created && current?.id === taskId) {
                    board.updateTask(taskId, {
                        labels: [...current.labelIds, created.id],
                    });
                }
            },
            onError: (errors) => {
                toast.error(
                    Object.values(errors)[0] ??
                        t('That change could not be saved.'),
                );
                labelInput.value?.focus();
            },
        },
    );
}

const deletingLabel = ref<BoardLabel | null>(null);

function deleteLabel(): void {
    const label = deletingLabel.value;

    if (!label) {
        return;
    }

    router.delete(LabelController.destroy([props.projectId, label.id]).url, {
        only: ['labels', 'columns'],
        preserveScroll: true,
        preserveState: true,
        showProgress: false,
        onSuccess: () => {
            deletingLabel.value = null;
            toast.success(t(':name was deleted.', { name: label.name }));
        },
        onError: () => toast.error(t('That change could not be saved.')),
    });
}

// Deleting the card

const confirmingDelete = ref(false);

function deleteTask(): void {
    if (task.value) {
        confirmingDelete.value = false;
        board.deleteTask(task.value.id);
    }
}

const fieldClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-input/30';
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent
            :side="dir === 'rtl' ? 'left' : 'right'"
            class="w-full gap-0 overflow-y-auto sm:max-w-lg"
            data-test="task-details"
        >
            <template v-if="task">
                <SheetHeader class="gap-1 border-b pe-12">
                    <SheetDescription class="text-xs">
                        {{ column ? t('In :column', { column }) : '' }}
                    </SheetDescription>
                    <SheetTitle class="sr-only">{{ task.title }}</SheetTitle>
                    <label for="task-title" class="sr-only">{{
                        t('Title')
                    }}</label>
                    <textarea
                        id="task-title"
                        v-model="title"
                        rows="1"
                        maxlength="200"
                        :readonly="!editable"
                        class="-mx-2 field-sizing-content resize-none rounded-md border border-transparent bg-transparent px-2 py-1 text-lg font-semibold outline-none hover:border-input read-only:hover:border-transparent focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        @keydown.enter.prevent="
                            ($event.target as HTMLElement).blur()
                        "
                        @blur="saveTitle"
                    />
                </SheetHeader>

                <div class="flex flex-col gap-6 p-4">
                    <dl
                        class="grid grid-cols-[7rem_1fr] items-center gap-x-3 gap-y-3 text-sm"
                    >
                        <dt
                            id="task-assignee-label"
                            class="text-muted-foreground"
                        >
                            {{ t('Assignee') }}
                        </dt>
                        <dd>
                            <Select v-model="assignee" :disabled="!editable">
                                <SelectTrigger
                                    class="w-full"
                                    aria-labelledby="task-assignee-label"
                                    data-test="task-assignee"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="NONE">{{
                                        t('Unassigned')
                                    }}</SelectItem>
                                    <SelectItem
                                        v-for="member in members"
                                        :key="member.id"
                                        :value="String(member.id)"
                                        >{{ member.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </dd>

                        <dt
                            id="task-priority-label"
                            class="text-muted-foreground"
                        >
                            {{ t('Priority') }}
                        </dt>
                        <dd>
                            <Select v-model="priority" :disabled="!editable">
                                <SelectTrigger
                                    class="w-full"
                                    aria-labelledby="task-priority-label"
                                    data-test="task-priority"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="NONE">{{
                                        t('No priority')
                                    }}</SelectItem>
                                    <SelectItem
                                        v-for="value in priorities"
                                        :key="value"
                                        :value="value"
                                    >
                                        <component
                                            :is="taskPriorities[value].icon"
                                            :class="taskPriorities[value].tone"
                                        />
                                        {{ t(taskPriorities[value].label) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </dd>

                        <dt class="text-muted-foreground">
                            <label for="task-due">{{ t('Due date') }}</label>
                        </dt>
                        <dd class="flex items-center gap-1">
                            <input
                                id="task-due"
                                type="date"
                                :value="task.dueOn ?? ''"
                                :disabled="!editable"
                                :class="cn(fieldClass, 'h-9 py-1')"
                                data-test="task-due"
                                @change="
                                    setDueOn(
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    )
                                "
                            />
                            <Button
                                v-if="editable && task.dueOn"
                                variant="ghost"
                                size="icon"
                                class="size-9 shrink-0"
                                :aria-label="t('Clear the due date')"
                                @click="setDueOn('')"
                            >
                                <X />
                            </Button>
                        </dd>
                    </dl>

                    <section aria-labelledby="task-labels-heading">
                        <div class="mb-2 flex items-center justify-between">
                            <h3
                                id="task-labels-heading"
                                class="text-sm text-muted-foreground"
                            >
                                {{ t('Labels') }}
                            </h3>
                            <Button
                                v-if="editable && labels.length"
                                variant="ghost"
                                size="sm"
                                class="h-7 text-xs"
                                :aria-pressed="managingLabels"
                                @click="managingLabels = !managingLabels"
                            >
                                {{ managingLabels ? t('Done') : t('Manage') }}
                            </Button>
                        </div>

                        <ul class="flex flex-wrap gap-1.5">
                            <li v-for="label in labels" :key="label.id">
                                <button
                                    v-if="managingLabels"
                                    type="button"
                                    :class="
                                        cn(
                                            'flex items-center gap-1 rounded-md border border-dashed px-2 py-1 text-xs font-medium outline-none hover:border-destructive hover:text-destructive focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                        )
                                    "
                                    :aria-label="
                                        t('Delete the label :name', {
                                            name: label.name,
                                        })
                                    "
                                    @click="deletingLabel = label"
                                >
                                    <Trash2 class="size-3" />
                                    {{ label.name }}
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    :disabled="!editable"
                                    :aria-pressed="hasLabel(label.id)"
                                    :class="
                                        cn(
                                            'flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-medium outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-default',
                                            hasLabel(label.id)
                                                ? [
                                                      projectColors[label.color]
                                                          .tile,
                                                      'border-transparent',
                                                  ]
                                                : 'text-muted-foreground hover:bg-muted',
                                            !editable &&
                                                !hasLabel(label.id) &&
                                                'hidden',
                                        )
                                    "
                                    @click="toggleLabel(label.id)"
                                >
                                    <Check
                                        v-if="hasLabel(label.id)"
                                        class="size-3"
                                    />
                                    <span
                                        v-else
                                        :class="
                                            cn(
                                                'size-2 rounded-full',
                                                projectColors[label.color].dot,
                                            )
                                        "
                                    />
                                    {{ label.name }}
                                </button>
                            </li>
                            <li
                                v-if="
                                    editable &&
                                    !creatingLabel &&
                                    !managingLabels &&
                                    labels.length < maxLabels
                                "
                            >
                                <button
                                    type="button"
                                    class="flex items-center gap-1 rounded-md border border-dashed px-2 py-1 text-xs text-muted-foreground outline-none hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    data-test="new-label-button"
                                    @click="startLabel"
                                >
                                    <Plus class="size-3" />
                                    {{ t('New label') }}
                                </button>
                            </li>
                        </ul>
                        <p
                            v-if="!editable && task.labelIds.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('No labels') }}
                        </p>

                        <form
                            v-if="creatingLabel"
                            class="mt-3 flex flex-col gap-3 rounded-lg border bg-muted/40 p-3"
                            @submit.prevent="createLabel"
                        >
                            <div>
                                <label
                                    for="new-label-name"
                                    class="mb-1 block text-xs font-medium"
                                    >{{ t('Label name') }}</label
                                >
                                <input
                                    id="new-label-name"
                                    ref="labelInput"
                                    v-model="labelName"
                                    maxlength="30"
                                    required
                                    :class="cn(fieldClass, 'h-8 py-1')"
                                    @keydown.esc.stop.prevent="
                                        creatingLabel = false
                                    "
                                />
                            </div>
                            <fieldset>
                                <legend class="mb-1 text-xs font-medium">
                                    {{ t('Colour') }}
                                </legend>
                                <div class="flex flex-wrap gap-1.5">
                                    <label
                                        v-for="color in colors"
                                        :key="color"
                                        class="relative cursor-pointer"
                                    >
                                        <input
                                            v-model="labelColor"
                                            type="radio"
                                            name="new-label-color"
                                            :value="color"
                                            class="peer sr-only"
                                        />
                                        <span
                                            :class="
                                                cn(
                                                    'block size-6 rounded-full ring-offset-2 ring-offset-background peer-checked:ring-2 peer-checked:ring-foreground peer-focus-visible:ring-2 peer-focus-visible:ring-ring',
                                                    projectColors[color].dot,
                                                )
                                            "
                                        />
                                        <span class="sr-only">{{
                                            t(projectColors[color].label)
                                        }}</span>
                                    </label>
                                </div>
                            </fieldset>
                            <div class="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="creatingLabel = false"
                                    >{{ t('Cancel') }}</Button
                                >
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="savingLabel"
                                    >{{ t('Add label') }}</Button
                                >
                            </div>
                        </form>
                    </section>

                    <section class="flex flex-col gap-2">
                        <label
                            for="task-description"
                            class="text-sm text-muted-foreground"
                            >{{ t('Description') }}</label
                        >
                        <textarea
                            id="task-description"
                            v-model="description"
                            rows="6"
                            maxlength="10000"
                            :readonly="!editable"
                            :placeholder="
                                editable
                                    ? t(
                                          'Add more detail, links or a checklist…',
                                      )
                                    : t('No description')
                            "
                            :class="cn(fieldClass, 'min-h-32 resize-y')"
                            data-test="task-description"
                            @blur="saveDescription"
                        />
                    </section>

                    <footer
                        class="flex flex-wrap items-center justify-between gap-3 border-t pt-4 text-xs text-muted-foreground"
                    >
                        <p>
                            <template v-if="task.createdBy">{{
                                t('Created by :name', { name: task.createdBy })
                            }}</template>
                            <template v-if="task.createdBy && task.createdAt">
                                ·
                            </template>
                            <template v-if="task.createdAt">{{
                                fromNow(task.createdAt, locale.current)
                            }}</template>
                        </p>
                        <Button
                            v-if="editable"
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            data-test="delete-task-button"
                            @click="confirmingDelete = true"
                        >
                            <Trash2 />
                            {{ t('Delete card') }}
                        </Button>
                    </footer>
                </div>
            </template>
        </SheetContent>
    </Sheet>

    <Dialog v-model:open="confirmingDelete">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ t('Delete this card?') }}</DialogTitle>
                <DialogDescription>{{
                    t(
                        ':name will be deleted for everyone. This cannot be undone.',
                        {
                            name: task?.title ?? '',
                        },
                    )
                }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">{{ t('Cancel') }}</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    data-test="confirm-delete-task"
                    @click="deleteTask"
                    >{{ t('Delete card') }}</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="deletingLabel !== null"
        @update:open="(value) => !value && (deletingLabel = null)"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ t('Delete this label?') }}</DialogTitle>
                <DialogDescription>{{
                    t(
                        ':name will be taken off every card of this project. The cards stay.',
                        { name: deletingLabel?.name ?? '' },
                    )
                }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">{{ t('Cancel') }}</Button>
                </DialogClose>
                <Button variant="destructive" @click="deleteLabel">{{
                    t('Delete label')
                }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
