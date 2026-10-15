import { Form, Head, Link } from '@inertiajs/react';
import { Card } from '@/components/dashboard/card';
import { DepartmentSelect } from '@/components/department-select';
import { RoleSelect } from '@/components/role-select';
import AppLayout from '@/layouts/app-layout';
import { index, update } from '@/routes/users';
import type { RoleName } from '@/types';

type EditableUser = {
    id: number;
    name: string;
    email: string;
    role: RoleName;
    department: string | null;
};

export default function EditUser({
    user,
    roles,
    departments,
}: {
    user: EditableUser;
    roles: RoleName[];
    departments: string[];
}) {
    return (
        <AppLayout>
            <Head title={`Edit ${user.name}`} />

            <h1 className="mb-5 text-xl font-semibold text-slate-800 dark:text-white">
                Edit user
            </h1>

            <div className="max-w-xl">
                <Card>
                    <dl className="mb-5 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                        <dt className="text-slate-500">Name</dt>
                        <dd className="font-medium text-slate-800 dark:text-white">
                            {user.name}
                        </dd>
                        <dt className="text-slate-500">Email</dt>
                        <dd className="break-all">{user.email}</dd>
                    </dl>

                    <Form
                        {...update.form(user.id)}
                        disableWhileProcessing
                        className="space-y-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <RoleSelect
                                    roles={roles}
                                    defaultValue={user.role}
                                    error={errors.role}
                                />
                                <DepartmentSelect
                                    departments={departments}
                                    defaultValue={user.department}
                                    error={errors.department}
                                />

                                <div className="flex items-center gap-3">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600 disabled:opacity-60"
                                    >
                                        {processing
                                            ? 'Saving...'
                                            : 'Save changes'}
                                    </button>
                                    <Link
                                        href={index.url()}
                                        className="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                                    >
                                        Cancel
                                    </Link>
                                </div>
                            </>
                        )}
                    </Form>
                </Card>
            </div>
        </AppLayout>
    );
}
