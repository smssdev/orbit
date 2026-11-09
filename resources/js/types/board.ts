export type BoardTask = {
    id: number;
    title: string;
    /** Shown before the server has saved it (its ID is a temporary, negative one). */
    pending?: boolean;
};

export type BoardColumn = {
    id: number;
    name: string;
    tasks: BoardTask[];
    pending?: boolean;
};
