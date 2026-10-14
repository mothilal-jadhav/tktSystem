// Only the fields the server shares in HandleInertiaRequests.
export type User = {
    id: number;
    name: string;
};

// What the signed-in user may do. Only for showing or hiding UI;
// the server enforces every permission itself.
export type Permissions = {
    createUsers: boolean;
    viewBin: boolean;
};

export type Auth = {
    user: User | null;
    can: Permissions;
};
