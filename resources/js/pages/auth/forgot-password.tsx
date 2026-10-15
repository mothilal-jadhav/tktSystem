import { Form, Link, usePage } from '@inertiajs/react';
import { AuthCard, AuthStatus } from '@/components/auth-card';
import { FormField } from '@/components/form-field';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword() {
    const { flash } = usePage();

    return (
        <AuthCard
            title="Forgot your password?"
            description="Enter your email address and we'll send you a link to choose a new password."
        >
            <AuthStatus message={flash.status} />

            <Form
                {...email.form()}
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

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600 disabled:opacity-60"
                        >
                            {processing ? 'Sending...' : 'Email reset link'}
                        </button>
                    </>
                )}
            </Form>

            <Link
                href={login()}
                className="mt-4 block text-center text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
            >
                Back to sign in
            </Link>
        </AuthCard>
    );
}
