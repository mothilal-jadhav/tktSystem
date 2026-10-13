import type { TicketsByDay } from '@/types';

const WIDTH = 720;
const HEIGHT = 260;
const PAD = { top: 16, right: 8, bottom: 28, left: 32 };
const TICKS = 4;

function formatDay(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        day: '2-digit',
        month: 'short',
    });
}

// Grouped bar chart drawn with plain SVG: one pair of bars per day.
export function BarChart({ data }: { data: TicketsByDay[] }) {
    const max = Math.max(1, ...data.flatMap((d) => [d.created, d.resolved]));
    // Round the top of the axis up to a multiple of 10 so tick labels are tidy.
    const top = Math.ceil(max / 10) * 10;
    const plotWidth = WIDTH - PAD.left - PAD.right;
    const plotHeight = HEIGHT - PAD.top - PAD.bottom;
    const groupWidth = plotWidth / Math.max(1, data.length);
    const barWidth = Math.min(14, groupWidth / 3);

    const y = (value: number) =>
        PAD.top + plotHeight - (value / top) * plotHeight;

    return (
        <div>
            <svg
                viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
                className="h-auto w-full"
                role="img"
                aria-label="Tickets created and resolved per day"
            >
                {Array.from({ length: TICKS + 1 }, (_, i) => {
                    const value = (top / TICKS) * i;

                    return (
                        <g key={i}>
                            <line
                                x1={PAD.left}
                                x2={WIDTH - PAD.right}
                                y1={y(value)}
                                y2={y(value)}
                                className="stroke-slate-200 dark:stroke-slate-700"
                                strokeDasharray="3 3"
                            />
                            <text
                                x={PAD.left - 8}
                                y={y(value)}
                                textAnchor="end"
                                dominantBaseline="middle"
                                className="fill-slate-400 text-[11px]"
                            >
                                {value}
                            </text>
                        </g>
                    );
                })}

                {data.map((day, i) => {
                    const center = PAD.left + groupWidth * i + groupWidth / 2;

                    return (
                        <g key={day.date}>
                            <rect
                                x={center - barWidth - 1}
                                y={y(day.created)}
                                width={barWidth}
                                height={PAD.top + plotHeight - y(day.created)}
                                rx={2}
                                className="fill-indigo-500"
                            >
                                <title>{`${day.created} created`}</title>
                            </rect>
                            <rect
                                x={center + 1}
                                y={y(day.resolved)}
                                width={barWidth}
                                height={PAD.top + plotHeight - y(day.resolved)}
                                rx={2}
                                className="fill-teal-400"
                            >
                                <title>{`${day.resolved} resolved`}</title>
                            </rect>
                            <text
                                x={center}
                                y={HEIGHT - 8}
                                textAnchor="middle"
                                className="fill-slate-400 text-[11px]"
                            >
                                {formatDay(day.date)}
                            </text>
                        </g>
                    );
                })}
            </svg>

            <div className="mt-2 flex justify-center gap-5 text-xs">
                <span className="flex items-center gap-1.5">
                    <span className="size-2.5 rounded-full bg-indigo-500" />
                    Created
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="size-2.5 rounded-full bg-teal-400" />
                    Resolved
                </span>
            </div>
        </div>
    );
}
