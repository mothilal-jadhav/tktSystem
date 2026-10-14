import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

// A Prev/Next pagination link; rendered disabled when there is no page to go to.
export function PageLink({
    href,
    label,
}: {
    href: string | null;
    label: string;
}) {
    const className =
        'rounded-md border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-700';

    if (!href) {
        return (
            <span
                aria-disabled="true"
                className={cn(className, 'cursor-not-allowed text-slate-400')}
            >
                {label}
            </span>
        );
    }

    return (
        <Link
            href={href}
            preserveScroll
            className={cn(
                className,
                'hover:bg-slate-100 dark:hover:bg-slate-700',
            )}
        >
            {label}
        </Link>
    );
}
