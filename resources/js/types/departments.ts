import type { RoleName } from './users';

export type DepartmentEmployee = {
    id: number;
    name: string;
    role: RoleName;
};

export type DepartmentMember = {
    id: number;
    name: string;
    email: string;
    role: RoleName;
    joined: string | null;
};

export type Department = {
    id: number;
    name: string;
    employees: DepartmentEmployee[];
};
