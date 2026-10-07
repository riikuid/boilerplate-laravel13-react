import { describe, expect, it } from 'vite-plus/test';
import { formatDateTime } from './format';

describe('formatDateTime', () => {
    it('shows UTC timestamps in Asia/Jakarta (UTC+7)', () => {
        expect(formatDateTime('2026-10-07T05:00:00Z')).toContain('12.00');
    });

    it('rolls over to the next day when the offset crosses midnight', () => {
        expect(formatDateTime('2026-10-07T20:30:00Z')).toContain('8 Okt 2026');
    });

    it.each([null, undefined, ''])('returns a dash for %j', (value) => {
        expect(formatDateTime(value)).toBe('-');
    });
});
