import type { RoleName } from './users';

export type DepartmentEmployee = {
    id: number;
    name: string;
    role: RoleName;
};

export type Department = {
    id: number;
    name: string;
    employees: DepartmentEmployee[];
};
