import { Head } from '@inertiajs/react';
import { AuditChanges } from '@/components/audit-changes';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { eventLabel, formatValue } from '@/lib/audit';
import { formatDateTime } from '@/lib/format';
import { index } from '@/routes/audit-logs';
import type { AuditLogEntry, Paginated } from '@/types';

const columns: Column<AuditLogEntry>[] = [
    {
        key: 'created_at',
        header: 'Waktu',
        sortable: true,
        className: 'whitespace-nowrap',
        cell: (log) => formatDateTime(log.created_at),
    },
    {
        key: 'event',
        header: 'Kejadian',
        sortable: true,
        cell: (log) => (
            <div className="space-y-1">
                <span className="font-medium">{eventLabel(log.event)}</span>
                <div>
                    <Badge variant="outline">{log.event}</Badge>
                </div>
            </div>
        ),
    },
    { key: 'actor', header: 'Pelaku', cell: (log) => log.actor ?? 'Sistem' },
    {
        key: 'target',
        header: 'Objek',
        cell: (log) =>
            log.auditable_type
                ? `${log.auditable_type} #${log.auditable_id}`
                : '-',
    },
    {
        key: 'changes',
        header: 'Perubahan',
        cell: (log) => <AuditChanges entry={log} />,
    },
    {
        key: 'ip',
        header: 'IP',
        cell: (log) => formatValue(log.meta?.ip),
    },
];

export default function AuditLogsIndex({
    logs,
}: {
    logs: Paginated<AuditLogEntry>;
}) {
    return (
        <>
            <Head title="Audit Log" />
            <div className="space-y-6 p-4">
                <PageHeader
                    title="Audit Log"
                    description="Catatan perubahan data dan aktivitas login. Hanya bisa dibaca."
                />
                <DataTable
                    table={logs}
                    columns={columns}
                    rowKey={(log) => log.id}
                    url={index().url}
                    only="logs"
                    searchPlaceholder="Cari kejadian, mis. user.created..."
                />
            </div>
        </>
    );
}

AuditLogsIndex.layout = {
    breadcrumbs: [{ title: 'Audit Log', href: index() }],
};
