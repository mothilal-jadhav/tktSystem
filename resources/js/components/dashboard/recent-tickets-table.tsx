import { cn } from '@/lib/utils';
import type { RecentTicket, TicketPriority, TicketStatus } from '@/types';

const priorityStyles: Record<TicketPriority, string> = {
    low: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    medium: 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300',
    high: 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
    urgent: 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
};

const statusStyles: Record<TicketStatus, string> = {
    open: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300',
    pending:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
    in_progress: 'bg-sky-100 text-sky-700 dark:bg-sky-500/20 dark:text-sky-300',
    resolved:
        'bg-teal-100 text-teal-700 dark:bg-teal-500/20 dark:text-teal-300',
};

function Badge({ className, label }: { className: string; label: string }) {
    return (
        <span
            className={cn(
                'inline-block rounded px-2 py-0.5 text-xs font-medium capitalize',
                className,
            )}
        >
            {label}
        </span>
    );
}

function formatUpdated(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function RecentTicketsTable({ tickets }: { tickets: RecentTicket[] }) {
    return (
        <div className="-mx-5 overflow-x-auto">
            <table className="w-full min-w-[640px] text-left text-sm">
                <thead className="text-xs text-slate-500">
                    <tr className="border-b border-slate-100 dark:border-slate-700">
                        <th className="px-5 py-2 font-semibold">Ticket</th>
                        <th className="px-5 py-2 font-semibold">Subject</th>
                        <th className="px-5 py-2 font-semibold">Department</th>
                        <th className="px-5 py-2 font-semibold">Priority</th>
                        <th className="px-5 py-2 font-semibold">Status</th>
                        <th className="px-5 py-2 font-semibold">Updated</th>
                    </tr>
                </thead>
                <tbody>
                    {tickets.map((ticket) => (
                        <tr
                            key={ticket.reference}
                            className="border-b border-slate-100 last:border-0 dark:border-slate-700"
                        >
                            <td className="px-5 py-3 font-medium text-indigo-600 dark:text-indigo-400">
                                {ticket.reference}
                            </td>
                            <td className="px-5 py-3 text-slate-800 dark:text-white">
                                {ticket.subject}
                            </td>
                            <td className="px-5 py-3">{ticket.department}</td>
                            <td className="px-5 py-3">
                                <Badge
                                    className={priorityStyles[ticket.priority]}
                                    label={ticket.priority}
                                />
                            </td>
                            <td className="px-5 py-3">
                                <Badge
                                    className={statusStyles[ticket.status]}
                                    label={ticket.status.replace('_', ' ')}
                                />
                            </td>
                            <td className="px-5 py-3 whitespace-nowrap text-slate-500">
                                {formatUpdated(ticket.updated_at)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
