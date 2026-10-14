import { Form, Head } from '@inertiajs/react';
import type { InputHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';
import { store } from '@/routes/register';

function Field({
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

export default function Register() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 dark:bg-slate-900">
            <Head title="Create an account" />

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
                        Create an account
                    </h1>
                    <p className="mt-1 mb-6 text-sm text-slate-500">
                        Sign up to submit and track helpdesk tickets.
                    </p>

                    <Form
                        {...store.form()}
                        resetOnSuccess={['password', 'password_confirmation']}
                        disableWhileProcessing
                        className="space-y-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <Field
                                    label="Name"
                                    name="name"
                                    type="text"
                                    autoComplete="name"
                                    required
                                    autoFocus
                                    error={errors.name}
                                />
                                <Field
                                    label="Email address"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    required
                                    error={errors.email}
                                />
                                <Field
                                    label="Password"
                                    name="password"
                                    type="password"
                                    autoComplete="new-password"
                                    required
                                    error={errors.password}
                                />
                                <Field
                                    label="Confirm password"
                                    name="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                    required
                                    error={errors.password_confirmation}
                                />

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600 disabled:opacity-60"
                                >
                                    {processing
                                        ? 'Creating account...'
                                        : 'Create account'}
                                </button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </div>
    );
}
