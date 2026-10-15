import { Form, Link, usePage } from '@inertiajs/react';
import { AuthCard, AuthStatus } from '@/components/auth-card';
import { FormField } from '@/components/form-field';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

export default function Login() {
    const { flash } = usePage();

    return (
        <AuthCard
            title="Sign in to your account"
            description="Use the account your administrator gave you."
        >
            <AuthStatus message={flash.status} />

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

                            <Link
                                href={request()}
                                className="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                Forgot password?
                            </Link>
                        </div>

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
        </AuthCard>
    );
}
