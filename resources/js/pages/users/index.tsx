import { Head, Link, router, usePage } from '@inertiajs/react';
import { Card } from '@/components/dashboard/card';
import { Icon } from '@/components/icons';
import { PageLink } from '@/components/page-link';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { create, destroy, edit, index } from '@/routes/users';
import type { Paginator, RoleName, UserListItem } from '@/types';

type Filters = {
    role: RoleName | null;
    // A department name, or 'none' for users without one.
    department: string | null;
};

const roleBadge: Record<RoleName, string> = {
    employee:
        'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    manager:
        'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300',
    admin: 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
};

const selectClass =
    'rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-600 dark:bg-slate-700 dark:text-white';

function UserFilters({
    filters,
    roles,
    departments,
}: {
    filters: Filters;
    roles: RoleName[];
    departments: string[];
}) {
    // Reloads the list from the server with the new filters, back on page 1.
    const apply = (next: Filters) => {
        const query = Object.fromEntries(
            Object.entries(next).filter(([, value]) => value),
        );

        router.get(index.url(), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const active = filters.role !== null || filters.department !== null;

    return (
        <div className="mb-4 flex flex-wrap items-end gap-3">
            <label className="flex flex-col gap-1 text-xs font-medium text-slate-500">
                Role
                <select
                    value={filters.role ?? ''}
                    onChange={(event) =>
                        apply({
                            ...filters,
                            role: (event.target.value ||
                                null) as RoleName | null,
                        })
                    }
                    className={cn(selectClass, 'capitalize')}
                >
                    <option value="">All roles</option>
                    {roles.map((role) => (
                        <option key={role} value={role}>
                            {role}
                        </option>
                    ))}
                </select>
            </label>
            <label className="flex flex-col gap-1 text-xs font-medium text-slate-500">
                Department
                <select
                    value={filters.department ?? ''}
                    onChange={(event) =>
                        apply({
                            ...filters,
                            department: event.target.value || null,
                        })
                    }
                    className={selectClass}
                >
                    <option value="">All departments</option>
                    {departments.map((department) => (
                        <option key={department} value={department}>
                            {department}
                        </option>
                    ))}
                    <option value="none">No department</option>
                </select>
            </label>
            {active && (
                <button
                    type="button"
                    onClick={() => apply({ role: null, department: null })}
                    className="rounded-md px-3 py-2 text-sm text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                >
                    Clear filters
                </button>
            )}
        </div>
    );
}

function formatJoined(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function UserActions({ user }: { user: UserListItem }) {
    if (!user.can.update && !user.can.delete) {
        return <span className="text-slate-400">—</span>;
    }

    return (
        <div className="flex justify-end gap-2">
            {user.can.update && (
                <Link
                    href={edit(user.id)}
                    aria-label={`Edit ${user.name}`}
                    className="flex items-center gap-1.5 rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    <Icon name="pencil" className="size-3.5" />
                    Edit
                </Link>
            )}
            {user.can.delete && (
                <Link
                    href={destroy(user.id)}
                    as="button"
                    preserveScroll
                    onBefore={() =>
                        window.confirm(
                            `Move ${user.name} to the bin? They will no longer be able to sign in.`,
                        )
                    }
                    aria-label={`Delete ${user.name}`}
                    className="flex items-center gap-1.5 rounded-md border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-400 dark:hover:bg-rose-500/10"
                >
                    <Icon name="trash" className="size-3.5" />
                    Delete
                </Link>
            )}
        </div>
    );
}

export default function UsersIndex({
    users,
    canManageUsers,
    filters,
    roles,
    departments,
}: {
    users: Paginator<UserListItem>;
    canManageUsers: boolean;
    filters: Filters;
    roles: RoleName[];
    departments: string[];
}) {
    const { auth } = usePage().props;

    return (
        <AppLayout>
            <Head title="Users" />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-xl font-semibold text-slate-800 dark:text-white">
                    Users
                </h1>
                {auth.can.createUsers && (
                    <Link
                        href={create.url()}
                        className="flex items-center gap-2 rounded-md bg-indigo-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600"
                    >
                        <Icon name="userPlus" />
                        Create user
                    </Link>
                )}
            </div>

            <UserFilters
                filters={filters}
                roles={roles}
                departments={departments}
            />

            <Card>
                <div className="-mx-5 overflow-x-auto">
                    <table className="w-full min-w-[680px] text-left text-sm">
                        <thead className="text-xs text-slate-500">
                            <tr className="border-b border-slate-100 dark:border-slate-700">
                                <th className="px-5 py-2 font-semibold">
                                    Name
                                </th>
                                <th className="px-5 py-2 font-semibold">
                                    Email
                                </th>
                                <th className="px-5 py-2 font-semibold">
                                    Role
                                </th>
                                <th className="px-5 py-2 font-semibold">
                                    Department
                                </th>
                                {canManageUsers ? (
                                    <th className="px-5 py-2 text-right font-semibold">
                                        Actions
                                    </th>
                                ) : (
                                    <th className="px-5 py-2 font-semibold">
                                        Joined
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map((user) => (
                                <tr
                                    key={user.id}
                                    className="border-b border-slate-100 last:border-0 dark:border-slate-700"
                                >
                                    <td className="px-5 py-3 font-medium text-slate-800 dark:text-white">
                                        {user.name}
                                    </td>
                                    <td className="px-5 py-3">{user.email}</td>
                                    <td className="px-5 py-3">
                                        <span
                                            className={cn(
                                                'rounded px-2 py-0.5 text-xs font-medium capitalize',
                                                roleBadge[user.role],
                                            )}
                                        >
                                            {user.role}
                                        </span>
                                    </td>
                                    <td className="px-5 py-3">
                                        {user.department ?? (
                                            <span className="text-slate-400">
                                                —
                                            </span>
                                        )}
                                    </td>
                                    {canManageUsers ? (
                                        <td className="px-5 py-3 text-right">
                                            <UserActions user={user} />
                                        </td>
                                    ) : (
                                        <td className="px-5 py-3 whitespace-nowrap text-slate-500">
                                            {formatJoined(user.joined)}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                    <p>
                        {users.total === 0
                            ? filters.role || filters.department
                                ? 'No users match these filters'
                                : 'No users'
                            : `Showing ${users.from}–${users.to} of ${users.total}`}
                    </p>
                    <div className="flex gap-2">
                        <PageLink href={users.prev_page_url} label="Prev" />
                        <PageLink href={users.next_page_url} label="Next" />
                    </div>
                </div>
            </Card>
        </AppLayout>
    );
}
