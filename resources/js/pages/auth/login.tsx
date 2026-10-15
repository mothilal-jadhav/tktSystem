import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { FormField } from '@/components/form-field';
import { store } from '@/routes/login';

export default function Login() {
    const [showForgotPassword, setShowForgotPassword] = useState(false);

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

                                <div className="flex items-center justify-between gap-4">
                                    <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                        <input
                                            type="checkbox"
                                            name="remember"
                                            value="1"
                                            className="size-4 rounded accent-indigo-500"
                                        />
                                        Remember me
                                    </label>

                                    {/* Placeholder until password reset is built: no route yet. */}
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowForgotPassword(
                                                (shown) => !shown,
                                            )
                                        }
                                        aria-expanded={showForgotPassword}
                                        aria-controls="forgot-password-notice"
                                        className="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        Forgot password?
                                    </button>
                                </div>

                                {showForgotPassword && (
                                    <div
                                        id="forgot-password-notice"
                                        role="status"
                                        className="rounded-md border border-indigo-200 bg-indigo-50 p-3 text-sm text-slate-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-slate-300"
                                    >
                                        <p className="font-semibold text-slate-800 dark:text-white">
                                            Forgot your password?
                                        </p>
                                        <p className="mt-1">
                                            Self-service reset isn't available
                                            yet. Please contact your
                                            administrator, who can reset it for
                                            you.
                                        </p>
                                    </div>
                                )}

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
