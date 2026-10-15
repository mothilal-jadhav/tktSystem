import { Head, Link } from '@inertiajs/react';
import { Card } from '@/components/dashboard/card';
import { Icon } from '@/components/icons';
import { PageLink } from '@/components/page-link';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/departments';
import type { DepartmentMember, Paginator } from '@/types';

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

export default function DepartmentShow({
    department,
    managers,
    employees,
}: {
    department: { id: number; name: string; employeeCount: number };
    managers: DepartmentMember[];
    employees: Paginator<DepartmentMember>;
}) {
    return (
        <AppLayout>
            <Head title={department.name} />

            <Link
                href={index.url()}
                className="mb-4 inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
            >
                <Icon name="arrowLeft" />
                All departments
            </Link>

            <div className="mb-5 flex items-center gap-4">
                <span className="grid size-14 place-items-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300">
                    <Icon name="building" className="size-7" />
                </span>
                <div>
                    <h1 className="text-xl font-semibold text-slate-800 dark:text-white">
                        {department.name}
                    </h1>
                    <p className="mt-0.5 text-sm text-slate-500">
                        {department.employeeCount === 1
                            ? '1 employee'
                            : `${department.employeeCount} employees`}
                    </p>
                </div>
            </div>

            <section className="mb-5">
                <h2 className="mb-2 text-xs font-semibold tracking-wider text-slate-500 uppercase">
                    {managers.length > 1 ? 'Managers' : 'Manager'}
                </h2>
                {managers.length === 0 ? (
                    <p className="rounded-lg bg-white px-5 py-4 text-sm text-slate-500 shadow-sm dark:bg-slate-800">
                        No manager yet.
                    </p>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {managers.map((manager) => (
                            <li
                                key={manager.id}
                                className="flex items-center gap-4 rounded-lg border-l-4 border-indigo-500 bg-white p-5 shadow-sm dark:bg-slate-800"
                            >
                                <span className="grid size-12 shrink-0 place-items-center rounded-full bg-indigo-500 text-lg font-semibold text-white">
                                    {manager.name.charAt(0).toUpperCase()}
                                </span>
                                <div className="min-w-0">
                                    <p className="truncate font-semibold text-slate-800 dark:text-white">
                                        {manager.name}
                                    </p>
                                    <p className="truncate text-sm text-slate-500">
                                        {manager.email}
                                    </p>
                                    <p className="text-xs text-slate-400">
                                        {`Joined ${formatJoined(manager.joined)}`}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <h2 className="mb-2 text-xs font-semibold tracking-wider text-slate-500 uppercase">
                Team
            </h2>

            <Card>
                {employees.total === 0 ? (
                    <p className="py-6 text-center text-sm text-slate-500">
                        No other employees in this department yet.
                    </p>
                ) : (
                    <>
                        <div className="-mx-5 overflow-x-auto">
                            <table className="w-full min-w-[480px] text-left text-sm">
                                <thead className="text-xs text-slate-500">
                                    <tr className="border-b border-slate-100 dark:border-slate-700">
                                        <th className="px-5 py-2 font-semibold">
                                            Name
                                        </th>
                                        <th className="px-5 py-2 font-semibold">
                                            Email
                                        </th>
                                        <th className="px-5 py-2 font-semibold">
                                            Joined
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {employees.data.map((employee) => (
                                        <tr
                                            key={employee.id}
                                            className="border-b border-slate-100 last:border-0 dark:border-slate-700"
                                        >
                                            <td className="px-5 py-3 font-medium text-slate-800 dark:text-white">
                                                {employee.name}
                                            </td>
                                            <td className="px-5 py-3">
                                                {employee.email}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-slate-500">
                                                {formatJoined(employee.joined)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
                            <p>{`Showing ${employees.from}–${employees.to} of ${employees.total}`}</p>
                            <div className="flex gap-2">
                                <PageLink
                                    href={employees.prev_page_url}
                                    label="Prev"
                                />
                                <PageLink
                                    href={employees.next_page_url}
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
