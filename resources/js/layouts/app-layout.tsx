import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Icon } from '@/components/icons';
import type { IconName } from '@/components/icons';
import { cn } from '@/lib/utils';
import { dashboard, logout } from '@/routes';
import { create as createUser, index as usersIndex } from '@/routes/users';
import type { Permissions } from '@/types';

type NavItem = {
    label: string;
    icon: IconName;
    href?: string;
    // Hidden unless the server says the user has this permission.
    permission?: keyof Permissions;
};

// Items without an href are features that haven't been built yet.
const navSections: { title: string; items: NavItem[] }[] = [
    {
        title: 'Main',
        items: [
            { label: 'Dashboard', icon: 'dashboard', href: dashboard.url() },
            { label: 'Tickets', icon: 'ticket' },
            { label: 'Departments', icon: 'building' },
            { label: 'Users', icon: 'users', href: usersIndex.url() },
        ],
    },
    {
        title: 'Admin',
        items: [
            {
                label: 'Create user',
                icon: 'userPlus',
                href: createUser.url(),
                permission: 'createUsers',
            },
            { label: 'Settings', icon: 'settings' },
        ],
    },
];

function initial(name: string): string {
    return name.charAt(0).toUpperCase();
}

function useDarkMode(): [boolean, () => void] {
    const [dark, setDark] = useState(() =>
        typeof document === 'undefined'
            ? false
            : document.documentElement.classList.contains('dark'),
    );

    const toggle = () => {
        const next = !dark;
        document.documentElement.classList.toggle('dark', next);

        try {
            localStorage.setItem('appearance', next ? 'dark' : 'light');
        } catch {
            // Storage can be unavailable (e.g. private mode); the toggle still works for this visit.
        }

        setDark(next);
    };

    return [dark, toggle];
}

function Sidebar({
    currentUrl,
    userName,
    can,
}: {
    currentUrl: string;
    userName: string;
    can: Permissions;
}) {
    return (
        <div className="flex h-full flex-col bg-slate-800 text-slate-300">
            <div className="flex h-16 items-center gap-2 border-b border-slate-700 px-6">
                <span className="grid size-7 rotate-45 place-items-center rounded-md bg-indigo-500">
                    <span className="size-2.5 -rotate-45 rounded-full bg-white" />
                </span>
                <span className="text-lg font-semibold tracking-wide text-white">
                    tktSystem
                </span>
            </div>

            <div className="flex items-center gap-3 bg-gradient-to-r from-teal-500 to-teal-400 px-6 py-4 text-white">
                <span className="grid size-9 place-items-center rounded-full bg-white/20 text-sm font-semibold">
                    {initial(userName)}
                </span>
                <div className="leading-tight">
                    <p className="text-sm font-semibold">{userName}</p>
                    <p className="text-xs text-white/80">Signed in</p>
                </div>
            </div>

            <nav className="flex-1 overflow-y-auto px-3 py-4">
                {navSections.map((section) => (
                    <div key={section.title} className="mb-4">
                        <p className="px-3 pb-2 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                            {section.title}
                        </p>
                        <ul className="space-y-1">
                            {section.items
                                .filter(
                                    (item) =>
                                        !item.permission ||
                                        can[item.permission],
                                )
                                .map((item) => (
                                    <li key={item.label}>
                                        {item.href ? (
                                            <Link
                                                href={item.href}
                                                className={cn(
                                                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition hover:bg-slate-700 hover:text-white',
                                                    currentUrl === item.href &&
                                                        'bg-slate-700 text-white',
                                                )}
                                            >
                                                <Icon name={item.icon} />
                                                {item.label}
                                            </Link>
                                        ) : (
                                            <span
                                                aria-disabled="true"
                                                className="flex cursor-not-allowed items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-500"
                                            >
                                                <Icon name={item.icon} />
                                                {item.label}
                                                <span className="ml-auto rounded bg-slate-700 px-1.5 py-0.5 text-[10px] text-slate-400">
                                                    soon
                                                </span>
                                            </span>
                                        )}
                                    </li>
                                ))}
                        </ul>
                    </div>
                ))}
            </nav>
        </div>
    );
}

export default function AppLayout({ children }: { children: ReactNode }) {
    const { url: currentUrl, props } = usePage();
    const userName = props.auth.user?.name ?? 'Guest';
    const can = props.auth.can;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [dark, toggleDark] = useDarkMode();

    return (
        <div className="min-h-screen bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300">
            {/* Desktop sidebar */}
            <aside className="fixed inset-y-0 left-0 hidden w-64 lg:block">
                <Sidebar
                    currentUrl={currentUrl}
                    userName={userName}
                    can={can}
                />
            </aside>

            {/* Mobile sidebar */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <button
                        type="button"
                        aria-label="Close menu"
                        className="absolute inset-0 bg-black/50"
                        onClick={() => setSidebarOpen(false)}
                    />
                    <aside className="relative h-full w-64">
                        <Sidebar
                            currentUrl={currentUrl}
                            userName={userName}
                            can={can}
                        />
                    </aside>
                </div>
            )}

            <div className="lg:pl-64">
                <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6 dark:border-slate-700 dark:bg-slate-800">
                    <button
                        type="button"
                        aria-label="Open menu"
                        className="rounded-md p-2 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-700"
                        onClick={() => setSidebarOpen(true)}
                    >
                        <Icon name="menu" className="size-5" />
                    </button>

                    <label className="relative hidden sm:block">
                        <span className="sr-only">Search</span>
                        <Icon
                            name="search"
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400"
                        />
                        <input
                            type="search"
                            placeholder="Search..."
                            className="w-64 rounded-md bg-slate-100 py-2 pr-3 pl-9 text-sm outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-slate-700"
                        />
                    </label>

                    <div className="ml-auto flex items-center gap-1">
                        <button
                            type="button"
                            aria-label={
                                dark
                                    ? 'Switch to light mode'
                                    : 'Switch to dark mode'
                            }
                            className="rounded-md p-2 hover:bg-slate-100 dark:hover:bg-slate-700"
                            onClick={toggleDark}
                        >
                            <Icon
                                name={dark ? 'sun' : 'moon'}
                                className="size-5"
                            />
                        </button>
                        <button
                            type="button"
                            aria-label="Notifications"
                            className="relative rounded-md p-2 hover:bg-slate-100 dark:hover:bg-slate-700"
                        >
                            <Icon name="bell" className="size-5" />
                            <span className="absolute top-1.5 right-1.5 size-2 rounded-full bg-rose-500" />
                        </button>
                        <div className="ml-2 flex items-center gap-2 border-l border-slate-200 pl-3 dark:border-slate-700">
                            <span className="grid size-8 place-items-center rounded-full bg-indigo-500 text-sm font-semibold text-white">
                                {initial(userName)}
                            </span>
                            <span className="hidden text-sm leading-tight sm:block">
                                <span className="block font-medium text-slate-800 dark:text-white">
                                    {userName}
                                </span>
                            </span>
                        </div>
                        <Link
                            href={logout()}
                            as="button"
                            aria-label="Sign out"
                            className="ml-1 rounded-md p-2 hover:bg-slate-100 dark:hover:bg-slate-700"
                        >
                            <Icon name="logout" className="size-5" />
                        </Link>
                    </div>
                </header>

                <main className="p-4 sm:p-6">{children}</main>
            </div>
        </div>
    );
}
