import { Form, Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Card } from '@/components/dashboard/card';
import { FormField } from '@/components/form-field';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { cancel, confirm, update } from '@/routes/settings/password';
import type { RoleName } from '@/types';

type Account = {
    name: string;
    email: string;
    role: RoleName;
    department: string | null;
};

const primaryButton =
    'rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600 disabled:opacity-60';
const secondaryButton =
    'rounded-md px-4 py-2 text-sm text-slate-500 transition hover:text-slate-700 dark:hover:text-slate-300';

export default function Settings({
    account,
    passwordConfirmed,
}: {
    account: Account;
    // True for a few minutes after the current password was confirmed.
    passwordConfirmed: boolean;
}) {
    const { errors } = usePage().props;
    // Stay open after a step reloads the page, or to show an error.
    const [open, setOpen] = useState(
        passwordConfirmed || Boolean(errors.current_password),
    );

    // The Back button restores this page from browser history without asking
    // the server, so a step 2 shown from history may already be reset. Check.
    useEffect(() => {
        if (passwordConfirmed) {
            router.reload({ only: ['passwordConfirmed'] });
        }
        // Only on arrival; later changes come from the server responses.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // The server reset step 1 (e.g. after leaving the page): close the section.
    useEffect(() => {
        if (!passwordConfirmed && !errors.current_password) {
            setOpen(false);
        }
    }, [passwordConfirmed, errors.current_password]);

    const details = [
        { label: 'Name', value: account.name },
        { label: 'Email', value: account.email },
        { label: 'Role', value: account.role, className: 'capitalize' },
        { label: 'Department', value: account.department ?? 'None' },
    ];

    return (
        <AppLayout>
            <Head title="Settings" />

            <h1 className="mb-5 text-xl font-semibold text-slate-800 dark:text-white">
                Settings
            </h1>

            <Card>
                <div className="flex flex-col gap-6 md:flex-row md:items-start">
                    {/* Profile picture, with the password option beneath it. */}
                    <div className="flex shrink-0 flex-col items-center gap-4 md:w-44">
                        <span
                            aria-hidden="true"
                            className="grid size-28 place-items-center rounded-full bg-indigo-500 text-4xl font-semibold text-white ring-4 ring-indigo-100 dark:ring-indigo-500/20"
                        >
                            {account.name.charAt(0).toUpperCase()}
                        </span>
                        <button
                            type="button"
                            onClick={() => setOpen((shown) => !shown)}
                            aria-expanded={open}
                            aria-controls="change-password"
                            className="w-full rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            Change password
                        </button>
                    </div>

                    {/* Account details: two big blocks per row; all four in one
                        row only on very wide screens, with extra room for email. */}
                    <div className="min-w-0 flex-1">
                        <dl className="grid gap-4 sm:grid-cols-2 2xl:grid-cols-[minmax(0,1fr)_minmax(0,1.75fr)_minmax(0,1fr)_minmax(0,1fr)]">
                            {details.map((detail) => (
                                <div
                                    key={detail.label}
                                    className="flex min-h-24 flex-col justify-center rounded-lg border border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-700/40"
                                >
                                    <dt className="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                        {detail.label}
                                    </dt>
                                    <dd
                                        className={cn(
                                            'mt-1.5 text-base font-medium wrap-anywhere text-slate-800 dark:text-white',
                                            detail.className,
                                        )}
                                    >
                                        {detail.value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                        <p className="mt-3 text-xs text-slate-500">
                            Your administrator manages these details.
                        </p>
                    </div>
                </div>

                {open && (
                    <div
                        id="change-password"
                        className="mt-6 border-t border-slate-200 pt-6 dark:border-slate-700"
                    >
                        {passwordConfirmed ? (
                            <NewPasswordStep onDone={() => setOpen(false)} />
                        ) : (
                            <CurrentPasswordStep
                                onCancel={() => setOpen(false)}
                            />
                        )}
                    </div>
                )}
            </Card>
        </AppLayout>
    );
}

function StepHeading({ step, title }: { step: number; title: string }) {
    return (
        <div className="mb-4">
            <p className="text-xs font-semibold tracking-wider text-indigo-500 uppercase">
                Step {step} of 2
            </p>
            <h2 className="mt-1 text-base font-semibold text-slate-800 dark:text-white">
                {title}
            </h2>
        </div>
    );
}

// Step 1: the server checks the current password before step 2 is shown.
function CurrentPasswordStep({ onCancel }: { onCancel: () => void }) {
    return (
        <>
            <StepHeading step={1} title="Enter your current password" />
            <Form
                {...confirm.form()}
                resetOnError
                disableWhileProcessing
                className="flex max-w-xl flex-col gap-3 sm:flex-row sm:items-start"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="flex-1">
                            <FormField
                                label="Current password"
                                name="current_password"
                                type="password"
                                autoComplete="current-password"
                                required
                                autoFocus
                                error={errors.current_password}
                            />
                        </div>
                        <div className="flex gap-2 sm:mt-6.5">
                            <button
                                type="submit"
                                disabled={processing}
                                className={primaryButton}
                            >
                                {processing ? 'Checking...' : 'Continue'}
                            </button>
                            <button
                                type="button"
                                onClick={onCancel}
                                className={secondaryButton}
                            >
                                Cancel
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

// Step 2: only reachable for a few minutes after step 1 (checked on the server).
function NewPasswordStep({ onDone }: { onDone: () => void }) {
    // Cancelling clears the confirmation on the server, so coming back
    // later starts at step 1 again.
    const cancelChange = () =>
        router.delete(cancel.url(), {
            preserveScroll: true,
            onSuccess: onDone,
        });

    return (
        <>
            <StepHeading step={2} title="Choose a new password" />
            <Form
                {...update.form()}
                resetOnError
                resetOnSuccess
                disableWhileProcessing
                onSuccess={onDone}
                className="max-w-2xl space-y-4"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
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
                        </div>
                        <p className="text-xs text-slate-500">
                            Use a long password you don't use anywhere else.
                            Other devices where you ticked "Remember me" will
                            need to sign in again.
                        </p>
                        <div className="flex gap-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className={primaryButton}
                            >
                                {processing ? 'Saving...' : 'Save new password'}
                            </button>
                            <button
                                type="button"
                                onClick={cancelChange}
                                className={secondaryButton}
                            >
                                Cancel
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
