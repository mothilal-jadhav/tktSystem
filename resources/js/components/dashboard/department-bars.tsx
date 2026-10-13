import type { DepartmentCount } from '@/types';

export function DepartmentBars({
    departments,
}: {
    departments: DepartmentCount[];
}) {
    const max = Math.max(1, ...departments.map((d) => d.count));

    return (
        <ul className="space-y-4">
            {departments.map((department) => (
                <li key={department.name}>
                    <div className="mb-1.5 flex justify-between text-sm">
                        <span>{department.name}</span>
                        <span className="font-medium text-slate-800 dark:text-white">
                            {department.count}
                        </span>
                    </div>
                    <div className="h-1.5 rounded-full bg-slate-100 dark:bg-slate-700">
                        <div
                            className="h-full rounded-full bg-indigo-500"
                            style={{
                                width: `${(department.count / max) * 100}%`,
                            }}
                        />
                    </div>
                </li>
            ))}
        </ul>
    );
}
