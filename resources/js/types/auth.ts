// Only the fields the server shares in HandleInertiaRequests.
export type User = {
    id: number;
    name: string;
};

export type Auth = {
    user: User | null;
};
