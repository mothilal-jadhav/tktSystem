import { Form, Link } from '@inertiajs/react';
import { AuthCard } from '@/components/auth-card';
import { FormField } from '@/components/form-field';
import { request, store } from '@/routes/password';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    return (
        <AuthCard
            title="Choose a new password"
            description="Enter a new password for your account."
        >
            <Form
                {...store.form()}
                resetOnError={['password', 'password_confirmation']}
                disableWhileProcessing
                className="space-y-4"
            >
                {({ errors, processing }) => (
                    <>
                        {/* The server checks this token against the email. */}
                        <input type="hidden" name="token" value={token} />

                        <FormField
                            label="Email address"
                            name="email"
                            type="email"
                            autoComplete="email"
                            defaultValue={email}
                            required
                            error={errors.email}
                        />
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
                            {processing ? 'Saving...' : 'Reset password'}
                        </button>
                    </>
                )}
            </Form>

            <Link
                href={request()}
                className="mt-4 block text-center text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
            >
                Request a new link
            </Link>
        </AuthCard>
    );
}
