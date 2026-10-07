export type UserRow = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: string[];
    created_at: string | null;
    updated_at: string | null;
};

export type RoleRow = {
    id: number;
    name: string;
    is_system: boolean;
    is_super_admin: boolean;
    permissions_count?: number;
    users_count?: number;
    created_at: string | null;
};

export type PermissionGroup = {
    key: string;
    label: string;
    permissions: { name: string; action: string }[];
};

export type AuditLogEntry = {
    id: number;
    event: string;
    actor: string | null;
    auditable_type: string | null;
    auditable_id: number | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    meta: Record<string, unknown> | null;
    created_at: string;
};
