export type UserListItem = {
    id: number;
    name: string;
    email: string;
    role: 'requester' | 'agent' | 'admin';
    joined: string | null;
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
