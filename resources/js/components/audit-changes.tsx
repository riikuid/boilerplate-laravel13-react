import { formatValue } from '@/lib/audit';
import type { AuditLogEntry } from '@/types';

/** Lists `field: old → new` for one audit entry. */
export function AuditChanges({ entry }: { entry: AuditLogEntry }) {
    const oldValues = entry.old_values ?? {};
    const newValues = entry.new_values ?? {};
    const keys = [
        ...new Set([...Object.keys(oldValues), ...Object.keys(newValues)]),
    ];

    if (keys.length === 0) {
        return null;
    }

    return (
        <ul className="space-y-0.5 text-xs text-muted-foreground">
            {keys.map((key) => (
                <li key={key}>
                    <span className="font-medium text-foreground">{key}</span>
                    {': '}
                    {key in oldValues && (
                        <>
                            <span className="line-through">
                                {formatValue(oldValues[key])}
                            </span>
                            {' → '}
                        </>
                    )}
                    {formatValue(newValues[key])}
                </li>
            ))}
        </ul>
    );
}
