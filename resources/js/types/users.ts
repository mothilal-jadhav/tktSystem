export type RoleName = 'employee' | 'manager' | 'admin';

export type UserListItem = {
    id: number;
    name: string;
    email: string;
    role: RoleName;
    department: string | null;
    joined: string | null;
    // Decided by UserPolicy on the server; only used to show or hide buttons.
    can: {
        update: boolean;
        delete: boolean;
    };
};

export type BinUser = {
    id: number;
    name: string;
    email: string;
    role: RoleName;
    deleted: string | null;
    // Decided by UserPolicy on the server; only used to show or hide buttons.
    can: {
        restore: boolean;
        forceDelete: boolean;
    };
};

// The fields of Laravel's LengthAwarePaginator that the UI uses.
export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
