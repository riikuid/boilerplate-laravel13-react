const EVENT_LABELS: Record<string, string> = {
    'auth.login': 'Login',
    'auth.logout': 'Logout',
    'auth.login_failed': 'Login gagal',
    'user.created': 'Pengguna dibuat',
    'user.updated': 'Pengguna diubah',
    'user.deleted': 'Pengguna dihapus',
    'user.restored': 'Pengguna dipulihkan',
    'user.roles_changed': 'Peran pengguna diubah',
    'role.created': 'Peran dibuat',
    'role.updated': 'Peran diubah',
    'role.deleted': 'Peran dihapus',
    'role.permissions_changed': 'Hak akses peran diubah',
};

/** Falls back to the raw event name so a new event is never invisible. */
export function eventLabel(event: string): string {
    return EVENT_LABELS[event] ?? event;
}

export function formatValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    return JSON.stringify(value);
}
