import { render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { Can } from '@/components/can';
import { useCan } from '@/hooks/use-can';
import { Permission } from '@/lib/permissions';

const page = vi.hoisted(() => ({ permissions: [] as string[] }));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { auth: { permissions: page.permissions } } }),
}));

function Probe() {
    const { can, canAny } = useCan();

    return (
        <p>
            {String(can(Permission.User.Read))}/
            {String(canAny(Permission.Role.Read, Permission.Role.Create))}
        </p>
    );
}

describe('permissions in the UI', () => {
    afterEach(() => {
        page.permissions = [];
    });

    it('renders children only when the permission is granted', () => {
        page.permissions = [Permission.User.Create];

        render(
            <>
                <Can permission={Permission.User.Create}>allowed</Can>
                <Can permission={Permission.User.Delete}>denied</Can>
            </>,
        );

        expect(screen.queryByText('allowed')).not.toBeNull();
        expect(screen.queryByText('denied')).toBeNull();
    });

    it('useCan answers single and any-of checks', () => {
        page.permissions = [Permission.User.Read, Permission.Role.Create];

        render(<Probe />);

        expect(screen.getByText('true/true')).not.toBeNull();
    });

    it('grants nothing without permissions', () => {
        render(<Probe />);

        expect(screen.getByText('false/false')).not.toBeNull();
    });
});
