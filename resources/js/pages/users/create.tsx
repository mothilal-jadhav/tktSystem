import { Form, Head, usePage } from '@inertiajs/react';
import { Card } from '@/components/dashboard/card';
import { FormField } from '@/components/form-field';
import { RoleSelect } from '@/components/role-select';
import AppLayout from '@/layouts/app-layout';
import { store } from '@/routes/users';
import type { RoleName } from '@/types';

export default function CreateUser({ roles }: { roles: RoleName[] }) {
    const { createdUser } = usePage().flash;

    return (
        <AppLayout>
            <Head title="Create user" />

            <h1 className="mb-5 text-xl font-semibold text-slate-800 dark:text-white">
                Create user
            </h1>

            <div className="max-w-xl space-y-5">
                {createdUser && (
                    <div
                        role="status"
                        className="rounded-lg border border-teal-200 bg-teal-50 p-5 text-sm text-teal-900 dark:border-teal-800 dark:bg-teal-950 dark:text-teal-100"
                    >
                        <p className="mb-3 font-semibold">
                            {createdUser.name} was created.
                        </p>
                        <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                            <dt className="text-teal-700 dark:text-teal-300">
                                Email
                            </dt>
                            <dd className="font-mono break-all">
                                {createdUser.email}
                            </dd>
                            <dt className="text-teal-700 dark:text-teal-300">
                                Role
                            </dt>
                            <dd className="capitalize">{createdUser.role}</dd>
                            <dt className="text-teal-700 dark:text-teal-300">
                                Password
                            </dt>
                            <dd className="font-mono break-all">
                                {createdUser.password}
                            </dd>
                        </dl>
                        <p className="mt-3 text-xs text-teal-700 dark:text-teal-300">
                            Copy this password now — it won't be shown again.
                            The user must change it on first login.
                        </p>
                    </div>
                )}

                <Card>
                    <Form
                        {...store.form()}
                        resetOnSuccess
                        disableWhileProcessing
                        className="space-y-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <FormField
                                    label="Name"
                                    name="name"
                                    type="text"
                                    autoComplete="off"
                                    required
                                    autoFocus
                                    error={errors.name}
                                />
                                <RoleSelect
                                    roles={roles}
                                    defaultValue="employee"
                                    error={errors.role}
                                />
                                <p className="text-xs text-slate-500">
                                    Email is generated from the name; a
                                    temporary password is generated
                                    automatically.
                                </p>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600 disabled:opacity-60"
                                >
                                    {processing
                                        ? 'Creating user...'
                                        : 'Create user'}
                                </button>
                            </>
                        )}
                    </Form>
                </Card>
            </div>
        </AppLayout>
    );
}
