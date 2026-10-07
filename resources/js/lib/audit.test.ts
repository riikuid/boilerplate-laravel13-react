import { describe, expect, it } from 'vite-plus/test';
import { eventLabel, formatValue } from './audit';

describe('eventLabel', () => {
    it('translates known events', () => {
        expect(eventLabel('user.created')).toBe('Pengguna dibuat');
    });

    it('falls back to the raw name so a new event is never invisible', () => {
        expect(eventLabel('invoice.paid')).toBe('invoice.paid');
    });
});

describe('formatValue', () => {
    it('renders empty values as a dash', () => {
        expect(formatValue(null)).toBe('-');
        expect(formatValue(undefined)).toBe('-');
    });

    it('renders primitives as text', () => {
        expect(formatValue('abc')).toBe('abc');
        expect(formatValue(42)).toBe('42');
        expect(formatValue(false)).toBe('false');
    });

    it('renders structures as JSON', () => {
        expect(formatValue(['a', 'b'])).toBe('["a","b"]');
        expect(formatValue({ roles: ['staff'] })).toBe('{"roles":["staff"]}');
    });
});
