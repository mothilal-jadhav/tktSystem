import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

// Placeholder until tickets are built.
export default function TicketsIndex() {
    return (
        <AppLayout>
            <Head title="Tickets" />

            <div className="mb-5">
                <h1 className="text-xl font-semibold text-slate-800 dark:text-white">
                    Tickets
                </h1>
                <p className="mt-1 text-sm text-slate-500">
                    Helpdesk tickets will appear here.
                </p>
            </div>

            <p className="rounded-lg bg-white py-10 text-center text-sm text-slate-500 shadow-sm dark:bg-slate-800">
                No tickets yet.
            </p>
        </AppLayout>
    );
}
