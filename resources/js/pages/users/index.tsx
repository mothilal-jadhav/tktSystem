import { Head, Link, usePage } from '@inertiajs/react';
import { Card } from '@/components/dashboard/card';
import { Icon } from '@/components/icons';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { create } from '@/routes/users';
import type { Paginator, UserListItem } from '@/types';

const roleStyles: Record<UserListItem['role'], string> = {
    requester:
        'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    agent: 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300',
    admin: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300',
};

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

function PageLink({ href, label }: { href: string | null; label: string }) {
    const className =
        'rounded-md border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-700';

    if (!href) {
        return (
            <span
                aria-disabled="true"
                className={cn(className, 'cursor-not-allowed text-slate-400')}
            >
                {label}
            </span>
        );
    }

    return (
        <Link
            href={href}
            preserveScroll
            className={cn(
                className,
                'hover:bg-slate-100 dark:hover:bg-slate-700',
            )}
        >
            {label}
        </Link>
    );
}

export default function UsersIndex({
    users,
}: {
    users: Paginator<UserListItem>;
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
                                <th className="px-5 py-2 font-semibold">
                                    Role
                                </th>
                                <th className="px-5 py-2 font-semibold">
                                    Joined
                                </th>
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
                                                'inline-block rounded px-2 py-0.5 text-xs font-medium capitalize',
                                                roleStyles[user.role],
                                            )}
                                        >
                                            {user.role}
                                        </span>
                                    </td>
                                    <td className="px-5 py-3 whitespace-nowrap text-slate-500">
                                        {formatJoined(user.joined)}
                                    </td>
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
