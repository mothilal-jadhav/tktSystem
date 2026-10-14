import { Head, Link } from '@inertiajs/react';
import { Card } from '@/components/dashboard/card';
import { Icon } from '@/components/icons';
import { PageLink } from '@/components/page-link';
import AppLayout from '@/layouts/app-layout';
import { destroy, restore } from '@/routes/bin';
import type { BinUser, Paginator } from '@/types';

function formatDeleted(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function BinActions({ user }: { user: BinUser }) {
    if (!user.can.restore && !user.can.forceDelete) {
        return <span className="text-slate-400">—</span>;
    }

    return (
        <div className="flex justify-end gap-2">
            {user.can.restore && (
                <Link
                    href={restore(user.id)}
                    as="button"
                    preserveScroll
                    aria-label={`Restore ${user.name}`}
                    className="flex items-center gap-1.5 rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700"
                >
                    <Icon name="restore" className="size-3.5" />
                    Restore
                </Link>
            )}
            {user.can.forceDelete && (
                <Link
                    href={destroy(user.id)}
                    as="button"
                    preserveScroll
                    onBefore={() =>
                        window.confirm(
                            `Permanently delete ${user.name}? This cannot be undone.`,
                        )
                    }
                    aria-label={`Delete ${user.name} forever`}
                    className="flex items-center gap-1.5 rounded-md bg-rose-500 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-rose-600"
                >
                    <Icon name="trash" className="size-3.5" />
                    Delete forever
                </Link>
            )}
        </div>
    );
}

export default function BinIndex({ users }: { users: Paginator<BinUser> }) {
    return (
        <AppLayout>
            <Head title="Bin" />

            <div className="mb-5">
                <h1 className="text-xl font-semibold text-slate-800 dark:text-white">
                    Bin
                </h1>
                <p className="mt-1 text-sm text-slate-500">
                    Deleted users can't sign in. Restore them, or delete them
                    forever.
                </p>
            </div>

            <Card>
                {users.total === 0 ? (
                    <p className="py-6 text-center text-sm text-slate-500">
                        The bin is empty.
                    </p>
                ) : (
                    <>
                        <div className="-mx-5 overflow-x-auto">
                            <table className="w-full min-w-[640px] text-left text-sm">
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
                                            Deleted
                                        </th>
                                        <th className="px-5 py-2 text-right font-semibold">
                                            Actions
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
                                            <td className="px-5 py-3">
                                                {user.email}
                                            </td>
                                            <td className="px-5 py-3 capitalize">
                                                {user.role}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-slate-500">
                                                {formatDeleted(user.deleted)}
                                            </td>
                                            <td className="px-5 py-3 text-right">
                                                <BinActions user={user} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                            <p>{`Showing ${users.from}–${users.to} of ${users.total}`}</p>
                            <div className="flex gap-2">
                                <PageLink
                                    href={users.prev_page_url}
                                    label="Prev"
                                />
                                <PageLink
                                    href={users.next_page_url}
                                    label="Next"
                                />
                            </div>
                        </div>
                    </>
                )}
            </Card>
        </AppLayout>
    );
}
