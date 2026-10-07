import { fireEvent, render, screen } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import type { Paginated } from '@/types';

const router = vi.hoisted(() => ({ get: vi.fn() }));

vi.mock('@inertiajs/react', () => ({ router }));

type Row = { id: number; name: string };

const columns: Column<Row>[] = [
    { key: 'name', header: 'Nama', sortable: true, cell: (row) => row.name },
    { key: 'note', header: 'Catatan', cell: () => '-' },
];

function table(overrides: Partial<Paginated<Row>> = {}): Paginated<Row> {
    return {
        data: [{ id: 1, name: 'Budi' }],
        meta: { page: 2, per_page: 15, total: 40, last_page: 3 },
        filters: { search: '', sort: 'created_at', direction: 'desc' },
        ...overrides,
    };
}

function tableElement(data: Paginated<Row>) {
    return (
        <DataTable
            table={data}
            columns={columns}
            rowKey={(row) => row.id}
            url="/users"
            only="users"
        />
    );
}

function renderTable(data: Paginated<Row>) {
    return render(tableElement(data));
}

/** The params of the most recent router.get call. */
function lastVisit() {
    const [url, params, options] = router.get.mock.lastCall as [
        string,
        Record<string, unknown>,
        Record<string, unknown>,
    ];

    return { url, params, options };
}

describe('DataTable', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        router.get.mockReset();
    });

    it('renders rows and the pagination summary', () => {
        renderTable(table());

        expect(screen.getByText('Budi')).not.toBeNull();
        expect(screen.getByText(/Halaman 2 dari 3/)).not.toBeNull();
        expect(screen.getByText(/40 data/)).not.toBeNull();
    });

    it('shows the empty message when there are no rows', () => {
        renderTable(table({ data: [] }));

        expect(screen.getByText('Tidak ada data.')).not.toBeNull();
    });

    it('sorts ascending first, then flips the direction, resetting to page 1', () => {
        const view = renderTable(table());

        fireEvent.click(screen.getByRole('button', { name: /Nama/ }));
        expect(lastVisit().params).toMatchObject({
            sort: 'name',
            direction: 'asc',
            page: 1,
        });

        // The server answers with the sort applied; clicking again must flip it.
        view.rerender(
            tableElement(
                table({
                    filters: { search: '', sort: 'name', direction: 'asc' },
                }),
            ),
        );
        fireEvent.click(screen.getByRole('button', { name: /Nama/ }));
        expect(lastVisit().params).toMatchObject({
            sort: 'name',
            direction: 'desc',
        });
    });

    it('does not offer sorting on columns that are not sortable', () => {
        renderTable(table());

        expect(screen.queryByRole('button', { name: /Catatan/ })).toBeNull();
    });

    it('goes to the neighbouring page and keeps the current filters', () => {
        renderTable(
            table({
                filters: { search: 'bu', sort: 'name', direction: 'asc' },
            }),
        );

        fireEvent.click(screen.getByRole('button', { name: 'Berikutnya' }));

        expect(lastVisit().params).toMatchObject({
            search: 'bu',
            sort: 'name',
            direction: 'asc',
            page: 3,
        });
    });

    it('disables previous on the first page and next on the last', () => {
        renderTable(
            table({
                meta: { page: 1, per_page: 15, total: 40, last_page: 1 },
            }),
        );

        expect(
            (
                screen.getByRole('button', {
                    name: 'Sebelumnya',
                }) as HTMLButtonElement
            ).disabled,
        ).toBe(true);
        expect(
            (
                screen.getByRole('button', {
                    name: 'Berikutnya',
                }) as HTMLButtonElement
            ).disabled,
        ).toBe(true);
    });

    it('debounces the search and only reloads the table prop', () => {
        renderTable(table());

        fireEvent.change(screen.getByLabelText('Cari'), {
            target: { value: 'budi' },
        });

        vi.advanceTimersByTime(299);
        expect(router.get).not.toHaveBeenCalled();

        vi.advanceTimersByTime(1);
        const { url, params, options } = lastVisit();
        expect(url).toBe('/users');
        expect(params).toMatchObject({ search: 'budi', page: 1 });
        expect(options).toMatchObject({ only: ['users'], preserveState: true });
    });

    it('leaves empty filters out of the query string', () => {
        renderTable(table());

        fireEvent.click(screen.getByRole('button', { name: 'Berikutnya' }));

        expect(lastVisit().params).not.toHaveProperty('search');
    });

    it('hides the search box when nothing is searchable', () => {
        render(
            <DataTable
                table={table()}
                columns={columns}
                rowKey={(row) => row.id}
                url="/x"
                only="x"
                searchable={false}
            />,
        );

        expect(screen.queryByLabelText('Cari')).toBeNull();
    });
});
