import type { BoardColumn, BoardTask } from '@/types';

/** Where a card sits: its column's index on the board and its own index in that column. */
export type CardLocation = { column: number; index: number };

export function locateTask(
    columns: BoardColumn[],
    taskId: number,
): CardLocation | null {
    for (const [column, { tasks }] of columns.entries()) {
        const index = tasks.findIndex((task) => task.id === taskId);

        if (index !== -1) {
            return { column, index };
        }
    }

    return null;
}

/**
 * The board with a card moved to `index` of the column `columnId` (counted without the card itself).
 * Returns new arrays and leaves the given ones untouched.
 */
export function withTaskMoved(
    columns: BoardColumn[],
    taskId: number,
    columnId: number,
    index: number,
): BoardColumn[] {
    const from = locateTask(columns, taskId);

    if (!from) {
        return columns;
    }

    const task = columns[from.column].tasks[from.index];
    const board = columns.map((column) => ({
        ...column,
        tasks: column.tasks.filter(({ id }) => id !== taskId),
    }));
    const target = board.find(({ id }) => id === columnId);

    if (!target) {
        return columns;
    }

    target.tasks.splice(clamp(index, 0, target.tasks.length), 0, task);

    return board;
}

/** The board with a column moved to `index` (counted without the column itself). */
export function withColumnMoved(
    columns: BoardColumn[],
    columnId: number,
    index: number,
): BoardColumn[] {
    const column = columns.find(({ id }) => id === columnId);

    if (!column) {
        return columns;
    }

    const board = columns.filter(({ id }) => id !== columnId);
    board.splice(clamp(index, 0, board.length), 0, column);

    return board;
}

/**
 * The saved item that ends up right after one inserted at `index` of `siblings` (the list without it): the
 * server places items before a neighbour. Unsaved items only ever sit at the end, so they are skipped, and
 * landing before them is landing at the end (null).
 */
export function savedNeighbourAt(
    siblings: Array<BoardTask | BoardColumn>,
    index: number,
): number | null {
    return siblings.slice(index).find((item) => !item.pending)?.id ?? null;
}

export function clamp(value: number, min: number, max: number): number {
    return Math.min(Math.max(value, min), max);
}

let lastTemporaryId = 0;

/** A temporary ID for an item the server has not saved yet; negative, so it never matches a real one. */
export function temporaryId(): number {
    lastTemporaryId -= 1;

    return lastTemporaryId;
}
