import { Form, Head } from '@inertiajs/react';
import { FormField } from '@/components/form-field';
import { store } from '@/routes/login';

export default function Login() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 dark:bg-slate-900">
            <Head title="Sign in" />

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
                        Sign in to your account
                    </h1>
                    <p className="mt-1 mb-6 text-sm text-slate-500">
                        Use the account your administrator gave you.
                    </p>

                    <Form
                        {...store.form()}
                        resetOnError={['password']}
                        disableWhileProcessing
                        className="space-y-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <FormField
                                    label="Email address"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    required
                                    autoFocus
                                    error={errors.email}
                                />
                                <FormField
                                    label="Password"
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    required
                                    error={errors.password}
                                />

                                <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        value="1"
                                        className="size-4 rounded accent-indigo-500"
                                    />
                                    Remember me
                                </label>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600 disabled:opacity-60"
                                >
                                    {processing ? 'Signing in...' : 'Sign in'}
                                </button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </div>
    );
}
