import { Head, Link, usePage } from '@inertiajs/react';
import { Card } from '@/components/dashboard/card';
import { Icon } from '@/components/icons';
import { PageLink } from '@/components/page-link';
import AppLayout from '@/layouts/app-layout';
import { create, destroy } from '@/routes/users';
import type { Paginator, UserListItem } from '@/types';

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

    // Edit is a placeholder until editing is built.
    return (
        <div className="flex justify-end gap-2">
            {user.can.update && (
                <button
                    type="button"
                    aria-label={`Edit ${user.name}`}
                    className="flex items-center gap-1.5 rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    <Icon name="pencil" className="size-3.5" />
                    Edit
                </button>
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
}: {
    users: Paginator<UserListItem>;
    canManageUsers: boolean;
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

            <Card>
                <div className="-mx-5 overflow-x-auto">
                    <table className="w-full min-w-[560px] text-left text-sm">
                        <thead className="text-xs text-slate-500">
                            <tr className="border-b border-slate-100 dark:border-slate-700">
                                <th className="px-5 py-2 font-semibold">
                                    Name
                                </th>
                                <th className="px-5 py-2 font-semibold">
                                    Email
                                </th>
                                {canManageUsers ? (
                                    <th className="px-5 py-2 text-right font-semibold">
                                        Actions
                                    </th>
                                ) : (
                                    <>
                                        <th className="px-5 py-2 font-semibold">
                                            Department
                                        </th>
                                        <th className="px-5 py-2 font-semibold">
                                            Joined
                                        </th>
                                    </>
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
                                    {canManageUsers ? (
                                        <td className="px-5 py-3 text-right">
                                            <UserActions user={user} />
                                        </td>
                                    ) : (
                                        <>
                                            {/* Departments are not built yet. */}
                                            <td className="px-5 py-3 text-slate-400">
                                                —
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-slate-500">
                                                {formatJoined(user.joined)}
                                            </td>
                                        </>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                    <p>
                        {users.total === 0
                            ? 'No users'
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
