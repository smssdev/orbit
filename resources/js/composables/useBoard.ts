import { router, usePage } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { toast } from 'vue-sonner';
import ColumnController from '@/actions/App/Http/Controllers/Board/ColumnController';
import ColumnPositionController from '@/actions/App/Http/Controllers/Board/ColumnPositionController';
import TaskController from '@/actions/App/Http/Controllers/Board/TaskController';
import TaskPositionController from '@/actions/App/Http/Controllers/Board/TaskPositionController';
import { useI18n } from '@/composables/useI18n';
import { newTask, withChanges } from '@/lib/tasks';
import {
    clamp,
    locateTask,
    savedNeighbourAt,
    temporaryId,
    withColumnMoved,
    withTaskMoved,
} from '@/lib/board';
import type {
    BoardColumn,
    BoardLabel,
    BoardMember,
    BoardTask,
    TaskChanges,
} from '@/types';
import type { RouteDefinition } from '@/wayfinder';

type Options = {
    projectId: () => number;
    /** The board as the page props hold it: the server's, plus any change still on its way. */
    columns: () => BoardColumn[];
    editable: () => boolean;
    /** The project's labels and the workspace's members, which cards show and the details panel picks from. */
    labels: () => BoardLabel[];
    members: () => BoardMember[];
    /** The board's element, to put focus back on a card after it moved. */
    root: Ref<HTMLElement | null>;
};

/** A card picked up with the keyboard: where it started and where it would land now. */
type Lift = {
    taskId: number;
    fromColumnId: number;
    fromIndex: number;
    columnId: number;
    index: number;
};

/**
 * The board's state and every change to it.
 *
 * Each change is an optimistic Inertia visit: the page props change at once, the server saves the change and
 * answers with the board as it now is, and if it refuses, Inertia puts the props back and a toast says why.
 */
export function useBoard(options: Options) {
    const lift = ref<Lift | null>(null);
    const announcement = ref('');
    const { t, dir } = useI18n();
    const page = usePage();
    /** The card whose details panel is open. Kept in the URL (`?task=`), so the panel can be linked to. */
    const openTaskId = ref<number | null>(taskInUrl(page.url));

    const columns = computed<BoardColumn[]>(() => {
        const board = options.columns();

        return lift.value
            ? withTaskMoved(
                  board,
                  lift.value.taskId,
                  lift.value.columnId,
                  lift.value.index,
              )
            : board;
    });

    function announce(message: string): void {
        // Clearing first makes screen readers repeat a message that did not change.
        announcement.value = '';
        void nextTick(() => (announcement.value = message));
    }

    function send(
        action: RouteDefinition<'post' | 'patch' | 'put' | 'delete'>,
        data: Record<string, string | number | number[] | null>,
        change: (board: BoardColumn[]) => BoardColumn[],
    ): void {
        router.visit(action, {
            data,
            preserveScroll: true,
            preserveState: true,
            only: ['columns'],
            showProgress: false,
            optimistic: (props) => ({
                columns: change(props.columns as BoardColumn[]),
            }),
            onError: (errors) => {
                const message =
                    Object.values(errors)[0] ??
                    t('That change could not be saved.');
                toast.error(message);
                announce(message);

                // Inertia puts back the board from before the change; load the one the server has now.
                reload();
            },
            onHttpException: (response) => {
                const message =
                    response.status === 403
                        ? t('This board is read-only now.')
                        : response.status === 404
                          ? t('That is no longer on the board.')
                          : t(
                                'That change could not be saved. Please try again.',
                            );
                toast.error(message);
                announce(message);

                reload();

                return false;
            },
            onNetworkError: () => {
                toast.error(
                    t('You seem to be offline. That change was undone.'),
                );

                return false;
            },
        });
    }

    // Also reloads the project, in case it was archived meanwhile (which makes the board read-only).
    function reload(): void {
        router.reload({ only: ['columns', 'project'] });
    }

    function columnOf(id: number): BoardColumn | undefined {
        return options.columns().find((column) => column.id === id);
    }

    function describe(board: BoardColumn[], taskId: number): string {
        const at = locateTask(board, taskId);

        if (!at) {
            return '';
        }

        const column = board[at.column];

        return t(':column, position :position of :total', {
            column: column.name,
            position: at.index + 1,
            total: column.tasks.length,
        });
    }

    // Columns

    function addColumn(name: string): void {
        send(ColumnController.store(options.projectId()), { name }, (board) => [
            ...board,
            { id: temporaryId(), name, tasks: [], pending: true },
        ]);
    }

    function renameColumn(columnId: number, name: string): void {
        send(
            ColumnController.update([options.projectId(), columnId]),
            { name },
            (board) =>
                board.map((column) =>
                    column.id === columnId ? { ...column, name } : column,
                ),
        );
    }

    /** Deletes a column; its cards, if any, go to the end of `moveTo`. */
    function deleteColumn(columnId: number, moveTo: number | null): void {
        const name = columnOf(columnId)?.name ?? t('The column');

        send(
            ColumnController.destroy([options.projectId(), columnId]),
            { move_to: moveTo },
            (board) => {
                const cards =
                    board.find(({ id }) => id === columnId)?.tasks ?? [];

                return board
                    .filter(({ id }) => id !== columnId)
                    .map((column) =>
                        column.id === moveTo
                            ? { ...column, tasks: [...column.tasks, ...cards] }
                            : column,
                    );
            },
        );
        announce(t(':name was deleted.', { name }));
    }

    /** Moves a column to `index` of the board (counted without it). */
    function moveColumn(columnId: number, index: number): void {
        const board = options.columns();
        const column = columnOf(columnId);
        const siblings = board.filter(({ id }) => id !== columnId);
        const to = clamp(index, 0, siblings.length);

        if (!column || column.pending || board.indexOf(column) === to) {
            return;
        }

        send(
            ColumnPositionController.update([options.projectId(), columnId]),
            { before: savedNeighbourAt(siblings, to) },
            (current) => withColumnMoved(current, columnId, to),
        );
        announce(
            t(':name moved to position :position of :total.', {
                name: column.name,
                position: to + 1,
                total: board.length,
            }),
        );
    }

    // Cards

    function addTask(columnId: number, title: string): void {
        send(
            TaskController.store([options.projectId(), columnId]),
            { title },
            (board) =>
                board.map((column) =>
                    column.id === columnId
                        ? {
                              ...column,
                              tasks: [
                                  ...column.tasks,
                                  newTask(temporaryId(), title),
                              ],
                          }
                        : column,
                ),
        );
    }

    /**
     * Moves a card to `index` of a column (counted without the card). Returns the board as it will look, or null
     * when nothing moved.
     */
    function moveTask(
        taskId: number,
        columnId: number,
        index: number,
    ): BoardColumn[] | null {
        const board = options.columns();
        const from = locateTask(board, taskId);
        const target = columnOf(columnId);

        if (!from || !target || board[from.column].tasks[from.index].pending) {
            return null;
        }

        const siblings = target.tasks.filter(({ id }) => id !== taskId);
        const to = clamp(index, 0, siblings.length);

        if (board[from.column].id === columnId && from.index === to) {
            return null;
        }

        send(
            TaskPositionController.update([options.projectId(), taskId]),
            { column: columnId, before: savedNeighbourAt(siblings, to) },
            (current) => withTaskMoved(current, taskId, columnId, to),
        );

        return withTaskMoved(board, taskId, columnId, to);
    }

    /** Moves a card dragged with the pointer, and says where it went. */
    function dropTask(taskId: number, columnId: number, index: number): void {
        const title = titleOf(taskId);
        const moved = moveTask(taskId, columnId, index);

        if (moved) {
            announce(
                t(':title moved to :place.', {
                    title,
                    place: describe(moved, taskId),
                }),
            );
        }
    }

    function taskOf(taskId: number): BoardTask | undefined {
        const at = locateTask(options.columns(), taskId);

        return at ? options.columns()[at.column].tasks[at.index] : undefined;
    }

    /** Saves some of a card's details, showing them at once. */
    function updateTask(taskId: number, changes: TaskChanges): void {
        send(
            TaskController.update([options.projectId(), taskId]),
            changes,
            (board) =>
                board.map((column) => ({
                    ...column,
                    tasks: column.tasks.map((task) =>
                        task.id === taskId ? withChanges(task, changes) : task,
                    ),
                })),
        );
    }

    function deleteTask(taskId: number): void {
        const title = titleOf(taskId);

        send(
            TaskController.destroy([options.projectId(), taskId]),
            {},
            (board) =>
                board.map((column) => ({
                    ...column,
                    tasks: column.tasks.filter(({ id }) => id !== taskId),
                })),
        );
        closeTask();
        toast.success(t(':name was deleted.', { name: title }));
        announce(t(':name was deleted.', { name: title }));
    }

    // The details panel

    const openTask = computed<BoardTask | null>(() =>
        openTaskId.value === null ? null : (taskOf(openTaskId.value) ?? null),
    );

    function showTask(taskId: number): void {
        if (taskOf(taskId)?.pending) {
            return;
        }

        openTaskId.value = taskId;
        replaceUrl(taskId);
    }

    function closeTask(): void {
        const taskId = openTaskId.value;
        openTaskId.value = null;
        replaceUrl(null);

        if (taskId !== null) {
            focusTask(taskId);
        }
    }

    function replaceUrl(taskId: number | null): void {
        const url = new URL(page.url, window.location.origin);

        if (taskId === null) {
            url.searchParams.delete('task');
        } else {
            url.searchParams.set('task', String(taskId));
        }

        router.replace({
            url: `${url.pathname}${url.search}${url.hash}`,
            preserveState: true,
            preserveScroll: true,
        });
    }

    // A card someone else deleted (or one linked to that is gone) closes its panel.
    watch(openTask, (task) => {
        if (task === null && openTaskId.value !== null) {
            openTaskId.value = null;
            replaceUrl(null);
        }
    });

    const labelsById = computed(
        () => new Map(options.labels().map((label) => [label.id, label])),
    );
    const membersById = computed(
        () => new Map(options.members().map((member) => [member.id, member])),
    );

    function titleOf(taskId: number): string {
        const at = locateTask(options.columns(), taskId);

        return at ? options.columns()[at.column].tasks[at.index].title : '';
    }

    // Keyboard moves: Space picks a card up, the arrows move it, Space or Enter drops it, Escape puts it back.

    function focusTask(taskId: number): void {
        void nextTick(() => {
            options.root.value
                ?.querySelector<HTMLElement>(`[data-task-id="${taskId}"]`)
                ?.focus();
        });
    }

    function pickUp(taskId: number): void {
        const board = options.columns();
        const at = locateTask(board, taskId);

        if (!at || board[at.column].tasks[at.index].pending) {
            return;
        }

        lift.value = {
            taskId,
            fromColumnId: board[at.column].id,
            fromIndex: at.index,
            columnId: board[at.column].id,
            index: at.index,
        };
        announce(
            t(
                'Picked up :title, in :place. Use the arrow keys to move it, Space to drop it, Escape to cancel.',
                { title: titleOf(taskId), place: describe(board, taskId) },
            ),
        );
    }

    function drop(): void {
        if (!lift.value) {
            return;
        }

        const { taskId, columnId, index } = lift.value;
        const title = titleOf(taskId);
        const moved = moveTask(taskId, columnId, index);

        announce(
            t('Dropped :title in :place.', {
                title,
                place: describe(moved ?? options.columns(), taskId),
            }),
        );

        if (!moved) {
            lift.value = null;
            focusTask(taskId);

            return;
        }

        // The props take the optimistic board on the next render. Until then the preview keeps showing the card
        // where it lands, so it does not flash back to where it started.
        const stop = watch(options.columns, () => {
            stop();

            if (lift.value?.taskId === taskId) {
                lift.value = null;
            }

            focusTask(taskId);
        });
    }

    function cancel(): void {
        if (!lift.value) {
            return;
        }

        const { taskId } = lift.value;
        lift.value = null;
        announce(
            t('Move cancelled. :title is back in :place.', {
                title: titleOf(taskId),
                place: describe(columns.value, taskId),
            }),
        );
        focusTask(taskId);
    }

    function step(columnStep: number, indexStep: number): void {
        if (!lift.value) {
            return;
        }

        const board = options.columns();
        const columnIndex = clamp(
            board.findIndex(({ id }) => id === lift.value?.columnId) +
                columnStep,
            0,
            board.length - 1,
        );
        const target = board[columnIndex];
        const siblings = target.tasks.filter(
            ({ id }) => id !== lift.value?.taskId,
        );

        lift.value = {
            ...lift.value,
            columnId: target.id,
            index: clamp(lift.value.index + indexStep, 0, siblings.length),
        };
        announce(
            t(':title: :place.', {
                title: titleOf(lift.value.taskId),
                place: describe(columns.value, lift.value.taskId),
            }),
        );
        focusTask(lift.value.taskId);
    }

    function onCardKeydown(event: KeyboardEvent, taskId: number): void {
        const lifted = lift.value?.taskId === taskId;

        // Enter opens a card's details, also on a read-only board.
        if (event.key === 'Enter' && !lifted) {
            event.preventDefault();
            showTask(taskId);

            return;
        }

        if (!options.editable()) {
            return;
        }

        // Columns run right to left in Arabic, so the arrow keys follow what the eye sees.
        const forward = dir.value === 'rtl' ? -1 : 1;
        const actions: Record<string, () => void> = lifted
            ? {
                  ' ': drop,
                  Enter: drop,
                  Escape: cancel,
                  ArrowUp: () => step(0, -1),
                  ArrowDown: () => step(0, 1),
                  ArrowLeft: () => step(-forward, 0),
                  ArrowRight: () => step(forward, 0),
              }
            : { ' ': () => pickUp(taskId) };
        const action = actions[event.key];

        if (action) {
            event.preventDefault();
            action();
        }
    }

    // Clicking anywhere else puts a lifted card back.
    function onPointerDown(): void {
        cancel();
    }

    onMounted(() => window.addEventListener('pointerdown', onPointerDown));
    onBeforeUnmount(() =>
        window.removeEventListener('pointerdown', onPointerDown),
    );

    return {
        columns,
        lift,
        announcement,
        addColumn,
        renameColumn,
        deleteColumn,
        moveColumn,
        addTask,
        dropTask,
        updateTask,
        deleteTask,
        onCardKeydown,
        openTask,
        showTask,
        closeTask,
        labelsById,
        membersById,
    };
}

export type Board = ReturnType<typeof useBoard>;

function taskInUrl(url: string): number | null {
    const id = Number(
        new URL(url, 'http://localhost').searchParams.get('task'),
    );

    return Number.isInteger(id) && id > 0 ? id : null;
}
