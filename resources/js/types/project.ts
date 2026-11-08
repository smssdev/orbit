export type ProjectColor =
    | 'slate'
    | 'red'
    | 'orange'
    | 'amber'
    | 'green'
    | 'teal'
    | 'blue'
    | 'indigo'
    | 'violet'
    | 'pink';

export type ProjectIcon =
    | 'folder'
    | 'rocket'
    | 'code'
    | 'palette'
    | 'megaphone'
    | 'book'
    | 'briefcase'
    | 'heart'
    | 'star'
    | 'zap';

/** What the sidebar knows about each active project. */
export type ProjectLink = {
    id: number;
    name: string;
    color: ProjectColor;
    icon: ProjectIcon;
};

export type ProjectSummary = ProjectLink & {
    description: string | null;
    archivedAt: string | null;
    can: { update: boolean; delete: boolean };
};

export type ProjectDetails = ProjectSummary & {
    createdBy: string | null;
    createdAt: string | null;
};
