export type Trend = 'up' | 'down';

export type DashboardStat = {
    label: string;
    value: string;
    change: number;
    trend: Trend;
};

export type TicketsByDay = {
    date: string;
    created: number;
    resolved: number;
};

export type DepartmentCount = {
    name: string;
    count: number;
};

export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';

export type TicketStatus = 'open' | 'pending' | 'in_progress' | 'resolved';

export type RecentTicket = {
    reference: string;
    subject: string;
    department: string;
    priority: TicketPriority;
    status: TicketStatus;
    updated_at: string;
};

export type DashboardProps = {
    stats: DashboardStat[];
    ticketsByDay: TicketsByDay[];
    resolutionRate: number;
    ticketsByDepartment: DepartmentCount[];
    recentTickets: RecentTicket[];
};
