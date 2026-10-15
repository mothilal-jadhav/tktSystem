import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';

// The centred logo and white card shared by the signed-out pages.
export function AuthCard({
    title,
    description,
    children,
}: {
    title: string;
    description: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 dark:bg-slate-900">
            <Head title={title} />

            <div className="w-full max-w-md">
                <div className="mb-6 flex items-center justify-center gap-2">
                    <span className="grid size-8 rotate-45 place-items-center rounded-md bg-indigo-500">
                        <span className="size-3 -rotate-45 rounded-full bg-white" />
                    </span>
                    <span className="text-xl font-semibold tracking-wide text-slate-800 dark:text-white">
                        tktSystem
                    </span>
                </div>

                <div className="rounded-lg bg-white p-6 shadow-sm sm:p-8 dark:bg-slate-800">
                    <h1 className="text-lg font-semibold text-slate-800 dark:text-white">
                        {title}
                    </h1>
                    <p className="mt-1 mb-6 text-sm text-slate-500">
                        {description}
                    </p>

                    {children}
                </div>
            </div>
        </div>
    );
}

// A one-line success message, e.g. "reset link sent".
export function AuthStatus({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <div
            role="status"
            className="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
        >
            {message}
        </div>
    );
}
