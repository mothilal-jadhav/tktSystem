const RADIUS = 70;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

// A circle whose stroke is split into a visible dash (the percentage) and a gap
// (the rest) using stroke-dasharray.
export function RingGauge({ value, label }: { value: number; label: string }) {
    const clamped = Math.min(100, Math.max(0, value));
    const filled = (clamped / 100) * CIRCUMFERENCE;

    return (
        <div className="relative mx-auto size-48">
            <svg viewBox="0 0 180 180" className="size-full -rotate-90">
                <circle
                    cx="90"
                    cy="90"
                    r={RADIUS}
                    fill="none"
                    strokeWidth="18"
                    className="stroke-slate-100 dark:stroke-slate-700"
                />
                <circle
                    cx="90"
                    cy="90"
                    r={RADIUS}
                    fill="none"
                    strokeWidth="18"
                    strokeLinecap="round"
                    strokeDasharray={`${filled} ${CIRCUMFERENCE}`}
                    className="stroke-indigo-500"
                />
            </svg>
            <div className="absolute inset-0 grid place-content-center text-center">
                <span className="text-3xl font-medium text-slate-800 dark:text-white">
                    {clamped}%
                </span>
                <span className="text-xs font-medium text-indigo-500">
                    {label}
                </span>
            </div>
        </div>
    );
}
