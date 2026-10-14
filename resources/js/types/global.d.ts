import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        flashDataType: {
            // Shown once after creating a user; never stored in history.
            createdUser?: { name: string; email: string; password: string };
            // One-line success message, e.g. after deleting or restoring a user.
            status?: string;
        };
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
