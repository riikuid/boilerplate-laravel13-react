import { AuditChanges } from '@/components/audit-changes';
import { eventLabel } from '@/lib/audit';
import { formatDateTime } from '@/lib/format';
import type { AuditLogEntry } from '@/types';

/** Recent history of one record, newest first. Pass the `history` prop from the controller. */
export function AuditTimeline({ entries }: { entries: AuditLogEntry[] }) {
    return (
        <section className="space-y-3">
            <h2 className="text-base font-medium">Riwayat perubahan</h2>

            {entries.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Belum ada riwayat.
                </p>
            ) : (
                <ol className="space-y-4 border-l pl-4">
                    {entries.map((entry) => (
                        <li key={entry.id} className="space-y-1">
                            <p className="text-sm font-medium">
                                {eventLabel(entry.event)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {entry.actor ?? 'Sistem'} ·{' '}
                                {formatDateTime(entry.created_at)}
                            </p>
                            <AuditChanges entry={entry} />
                        </li>
                    ))}
                </ol>
            )}
        </section>
    );
}
