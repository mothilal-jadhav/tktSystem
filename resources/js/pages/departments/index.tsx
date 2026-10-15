import { Head } from '@inertiajs/react';
import { Icon } from '@/components/icons';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { Department } from '@/types';

// Accent colours for the icon tiles, cycled by position. Presentation only.
const accents = [
    'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300',
    'bg-teal-100 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300',
    'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
    'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
    'bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
    'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
];

export default function DepartmentsIndex({
    departments,
}: {
    departments: Department[];
}) {
    return (
        <AppLayout>
            <Head title="Departments" />

            <div className="mb-5">
                <h1 className="text-xl font-semibold text-slate-800 dark:text-white">
                    Departments
                </h1>
                <p className="mt-1 text-sm text-slate-500">
                    The departments in the organisation.
                </p>
            </div>

            {departments.length === 0 ? (
                <p className="rounded-lg bg-white py-10 text-center text-sm text-slate-500 shadow-sm dark:bg-slate-800">
                    No departments yet.
                </p>
            ) : (
                <ul className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    {departments.map((department, index) => (
                        <li
                            key={department.id}
                            className="flex min-h-40 flex-col gap-5 rounded-lg bg-white p-6 shadow-sm transition duration-300 ease-out hover:-translate-y-1 hover:scale-[1.03] hover:shadow-lg motion-reduce:transition-none motion-reduce:hover:transform-none dark:bg-slate-800"
                        >
                            <span
                                className={cn(
                                    'grid size-14 place-items-center rounded-xl',
                                    accents[index % accents.length],
                                )}
                            >
                                <Icon name="building" className="size-7" />
                            </span>
                            <h2 className="text-xl font-semibold text-slate-800 dark:text-white">
                                {department.name}
                            </h2>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
