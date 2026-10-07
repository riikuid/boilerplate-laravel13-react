const TIME_ZONE = 'Asia/Jakarta';

const dateTime = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: TIME_ZONE,
});

/** The API sends UTC ISO strings; the UI always shows Asia/Jakarta. */
export function formatDateTime(iso: string | null | undefined): string {
    return iso ? dateTime.format(new Date(iso)) : '-';
}
