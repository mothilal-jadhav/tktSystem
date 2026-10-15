import { Form, Link } from '@inertiajs/react';
import { AuthCard } from '@/components/auth-card';
import { FormField } from '@/components/form-field';
import { logout } from '@/routes';
import { update } from '@/routes/password';

export default function ChangePassword() {
    return (
        <AuthCard
            title="Set a new password"
            description="You signed in with a temporary password. Choose your own password to continue."
        >
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
                            {processing ? 'Saving...' : 'Save new password'}
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
        </AuthCard>
    );
}
