import { Head } from '@inertiajs/react';
import { BarChart } from '@/components/dashboard/bar-chart';
import { Card } from '@/components/dashboard/card';
import { DepartmentBars } from '@/components/dashboard/department-bars';
import { RecentTicketsTable } from '@/components/dashboard/recent-tickets-table';
import { RingGauge } from '@/components/dashboard/ring-gauge';
import { StatCard } from '@/components/dashboard/stat-card';
import { Icon } from '@/components/icons';
import type { IconName } from '@/components/icons';
import AppLayout from '@/layouts/app-layout';
import type { DashboardProps } from '@/types';

// Icon and colour for each stat card, in the order the server sends them.
const statStyles: { icon: IconName; className: string }[] = [
    { icon: 'inbox', className: 'bg-teal-500' },
    { icon: 'clock', className: 'bg-sky-500' },
    { icon: 'check', className: 'bg-indigo-500' },
    { icon: 'zap', className: 'bg-amber-400' },
    { icon: 'alert', className: 'bg-rose-500' },
    { icon: 'smile', className: 'bg-slate-700' },
];

export default function Dashboard({
    stats,
    ticketsByDay,
    resolutionRate,
    ticketsByDepartment,
    recentTickets,
}: DashboardProps) {
    const today = new Date().toLocaleDateString(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-lg font-semibold text-slate-800 dark:text-white">
                    Dashboard
                </h1>
                <span className="inline-flex items-center gap-2 rounded-md bg-white px-3 py-1.5 text-sm shadow-sm dark:bg-slate-800">
                    <Icon name="calendar" className="size-4 text-indigo-500" />
                    {today}
                </span>
            </div>

            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                {stats.map((stat, i) => {
                    const style = statStyles[i % statStyles.length];

                    return (
                        <StatCard
                            key={stat.label}
                            stat={stat}
                            icon={style.icon}
                            iconClassName={style.className}
                        />
                    );
                })}
            </div>

            <div className="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
                <Card title="Resolution rate">
                    <RingGauge value={resolutionRate} label="Resolved" />
                    <h3 className="mt-6 mb-3 text-xs font-semibold tracking-wider text-slate-500 uppercase">
                        Tickets by department
                    </h3>
                    <DepartmentBars departments={ticketsByDepartment} />
                </Card>

                <Card title="Tickets — last 12 days" className="xl:col-span-2">
                    <BarChart data={ticketsByDay} />
                </Card>
            </div>

            <Card title="Recent tickets" className="mt-5">
                <RecentTicketsTable tickets={recentTickets} />
            </Card>
        </AppLayout>
    );
}
