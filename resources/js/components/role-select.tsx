import { cn } from '@/lib/utils';
import type { RoleName } from '@/types';

// The role dropdown used by the create and edit user forms.
export function RoleSelect({
    roles,
    defaultValue,
    error,
}: {
    roles: RoleName[];
    defaultValue: RoleName;
    error?: string;
}) {
    return (
        <div>
            <label
                htmlFor="role"
                className="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300"
            >
                Role
            </label>
            <select
                id="role"
                name="role"
                defaultValue={defaultValue}
                required
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? 'role-error' : undefined}
                className={cn(
                    'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 capitalize outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-600 dark:bg-slate-700 dark:text-white',
                    error && 'border-rose-500 dark:border-rose-500',
                )}
            >
                {roles.map((role) => (
                    <option key={role} value={role} className="capitalize">
                        {role}
                    </option>
                ))}
            </select>
            {error && (
                <p id="role-error" className="mt-1 text-xs text-rose-600">
                    {error}
                </p>
            )}
        </div>
    );
}
