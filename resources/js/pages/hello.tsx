import { Head } from '@inertiajs/react';

export default function Hello() {
    return (
        <>
            <Head title="Hello" />
            <div className="flex min-h-screen items-center justify-center bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <h1 className="text-4xl font-medium">
                    Hello{' '}
                    <span role="img" aria-label="waving hand">
                        👋
                    </span>
                </h1>
            </div>
        </>
    );
}
