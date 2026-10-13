import { Icon } from '@/components/icons';
import type { IconName } from '@/components/icons';
import { cn } from '@/lib/utils';
import type { DashboardStat } from '@/types';

export function StatCard({
    stat,
    icon,
    iconClassName,
}: {
    stat: DashboardStat;
    icon: IconName;
    iconClassName: string;
}) {
    const up = stat.trend === 'up';

    return (
        <div className="rounded-lg bg-white p-5 shadow-sm dark:bg-slate-800">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                        {stat.label}
                    </p>
                    <p className="mt-2 text-2xl font-medium text-slate-800 dark:text-white">
                        {stat.value}
                    </p>
                </div>
                <span
                    className={cn(
                        'grid size-11 shrink-0 rotate-45 place-items-center rounded-lg text-white',
                        iconClassName,
                    )}
                >
                    <Icon name={icon} className="size-5 -rotate-45" />
                </span>
            </div>
            <p className="mt-4 flex items-center gap-2 text-xs text-slate-500">
                <span
                    className={cn(
                        'inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 font-medium text-white',
                        up ? 'bg-teal-500' : 'bg-rose-500',
                    )}
                >
                    <Icon
                        name={up ? 'arrowUp' : 'arrowDown'}
                        className="size-3"
                    />
                    {stat.change}%
                </span>
                Since last month
            </p>
        </div>
    );
}
