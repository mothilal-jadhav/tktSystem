import type { InputHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

// A labelled text input that shows its server-side validation error underneath.
export function FormField({
    label,
    error,
    ...input
}: InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    name: string;
    error?: string;
}) {
    const id = input.name;

    return (
        <div>
            <label
                htmlFor={id}
                className="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300"
            >
                {label}
            </label>
            <input
                id={id}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                className={cn(
                    'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-600 dark:bg-slate-700 dark:text-white',
                    error && 'border-rose-500 dark:border-rose-500',
                )}
                {...input}
            />
            {error && (
                <p id={`${id}-error`} className="mt-1 text-xs text-rose-600">
                    {error}
                </p>
            )}
        </div>
    );
}
