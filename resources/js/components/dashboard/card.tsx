import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function Card({
    title,
    action,
    className,
    children,
}: {
    title?: string;
    action?: ReactNode;
    className?: string;
    children: ReactNode;
}) {
    return (
        <section
            className={cn(
                'rounded-lg bg-white shadow-sm dark:bg-slate-800',
                className,
            )}
        >
            {title && (
                <header className="flex items-center justify-between px-5 pt-5">
                    <h2 className="text-sm font-semibold text-slate-800 dark:text-white">
                        {title}
                    </h2>
                    {action}
                </header>
            )}
            <div className="p-5">{children}</div>
        </section>
    );
}
