import { Form, Head, Link } from '@inertiajs/react';
import { FormField } from '@/components/form-field';
import { logout } from '@/routes';
import { update } from '@/routes/password';

export default function ChangePassword() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 dark:bg-slate-900">
            <Head title="Set a new password" />

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
                        Set a new password
                    </h1>
                    <p className="mt-1 mb-6 text-sm text-slate-500">
                        You signed in with a temporary password. Choose your own
                        password to continue.
                    </p>

                    <Form
                        {...update.form()}
                        resetOnError
                        disableWhileProcessing
                        className="space-y-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <FormField
                                    label="New password"
                                    name="password"
                                    type="password"
                                    autoComplete="new-password"
                                    required
                                    autoFocus
                                    error={errors.password}
                                />
                                <FormField
                                    label="Confirm new password"
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
                                        ? 'Saving...'
                                        : 'Save new password'}
                                </button>
                            </>
                        )}
                    </Form>

                    <Link
                        href={logout()}
                        as="button"
                        className="mt-4 w-full text-center text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                    >
                        Sign out
                    </Link>
                </div>
            </div>
        </div>
    );
}
