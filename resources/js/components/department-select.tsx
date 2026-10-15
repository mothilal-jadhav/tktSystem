import { cn } from '@/lib/utils';

// The department dropdown used by the create and edit user forms.
export function DepartmentSelect({
    departments,
    defaultValue,
    error,
}: {
    departments: string[];
    defaultValue: string | null;
    error?: string;
}) {
    return (
        <div>
            <label
                htmlFor="department"
                className="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300"
            >
                Department
            </label>
            <select
                id="department"
                name="department"
                defaultValue={defaultValue ?? ''}
                required
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? 'department-error' : undefined}
                className={cn(
                    'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-600 dark:bg-slate-700 dark:text-white',
                    error && 'border-rose-500 dark:border-rose-500',
                )}
            >
                {/* Nothing is pre-selected, so a department has to be chosen. */}
                <option value="" disabled>
                    Choose a department
                </option>
                {departments.map((department) => (
                    <option key={department} value={department}>
                        {department}
                    </option>
                ))}
            </select>
            {error && (
                <p id="department-error" className="mt-1 text-xs text-rose-600">
                    {error}
                </p>
            )}
        </div>
    );
}
