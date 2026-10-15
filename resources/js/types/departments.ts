export type DepartmentMember = {
    id: number;
    name: string;
    email: string;
    joined: string | null;
};

export type Department = {
    id: number;
    name: string;
    // Everyone in the department, managers included.
    employeeCount: number;
    managers: string[];
};
