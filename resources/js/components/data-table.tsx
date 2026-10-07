import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

export type Column<T> = {
    key: string;
    header: string;
    /** Must be whitelisted in TableQuery::sortable() on the server. */
    sortable?: boolean;
    className?: string;
    cell: (row: T) => ReactNode;
};

const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];
const SEARCH_DEBOUNCE_MS = 300;

type Props<T> = {
    /** The prop returned by TableQuery::paginate(). */
    table: Paginated<T>;
    columns: Column<T>[];
    rowKey: (row: T) => string | number;
    /** Current page URL, e.g. index().url. */
    url: string;
    /** Name of the Inertia prop holding the table, so only it is reloaded. */
    only: string;
    searchPlaceholder?: string;
    /** Omit to hide the search box (nothing is searchable server-side). */
    searchable?: boolean;
    emptyMessage?: string;
};

export function DataTable<T>({
    table,
    columns,
    rowKey,
    url,
    only,
    searchPlaceholder = 'Cari...',
    searchable = true,
    emptyMessage = 'Tidak ada data.',
}: Props<T>) {
    const { meta, filters } = table;
    const [search, setSearch] = useState(filters.search);

    const visit = (changes: Record<string, string | number | undefined>) => {
        const params = {
            search: filters.search,
            sort: filters.sort,
            direction: filters.direction,
            per_page: meta.per_page,
            ...changes,
        };

        router.get(
            url,
            Object.fromEntries(
                Object.entries(params).filter(
                    ([, value]) => value !== '' && value !== undefined,
                ),
            ),
            {
                only: [only],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    useEffect(() => {
        if (search === filters.search) {
            return;
        }

        const timer = setTimeout(
            () => visit({ search, page: 1 }),
            SEARCH_DEBOUNCE_MS,
        );

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const toggleSort = (key: string) =>
        visit({
            sort: key,
            direction:
                filters.sort === key && filters.direction === 'asc'
                    ? 'desc'
                    : 'asc',
            page: 1,
        });

    const SortIcon = ({ column }: { column: string }) => {
        if (filters.sort !== column) {
            return <ArrowUpDown className="size-3.5 opacity-50" />;
        }

        return filters.direction === 'asc' ? (
            <ArrowUp className="size-3.5" />
        ) : (
            <ArrowDown className="size-3.5" />
        );
    };

    return (
        <div className="space-y-4">
            {searchable && (
                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={searchPlaceholder}
                    className="max-w-sm"
                    aria-label="Cari"
                />
            )}

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            {columns.map((column) => (
                                <TableHead
                                    key={column.key}
                                    className={column.className}
                                >
                                    {column.sortable ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                toggleSort(column.key)
                                            }
                                            className="inline-flex items-center gap-1.5 hover:text-foreground"
                                        >
                                            {column.header}
                                            <SortIcon column={column.key} />
                                        </button>
                                    ) : (
                                        column.header
                                    )}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {table.data.length === 0 ? (
                            <TableRow className="hover:bg-transparent">
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-24 text-center text-muted-foreground"
                                >
                                    {emptyMessage}
                                </TableCell>
                            </TableRow>
                        ) : (
                            table.data.map((row) => (
                                <TableRow key={rowKey(row)}>
                                    {columns.map((column) => (
                                        <TableCell
                                            key={column.key}
                                            className={cn(column.className)}
                                        >
                                            {column.cell(row)}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
                <div className="flex items-center gap-2">
                    <span>Baris per halaman</span>
                    <Select
                        value={String(meta.per_page)}
                        onValueChange={(value) =>
                            visit({ per_page: Number(value), page: 1 })
                        }
                    >
                        <SelectTrigger className="w-[72px]" size="sm">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {PER_PAGE_OPTIONS.map((option) => (
                                <SelectItem key={option} value={String(option)}>
                                    {option}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex items-center gap-3">
                    <span>
                        Halaman {meta.page} dari {meta.last_page} · {meta.total}{' '}
                        data
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={meta.page <= 1}
                        onClick={() => visit({ page: meta.page - 1 })}
                    >
                        Sebelumnya
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={meta.page >= meta.last_page}
                        onClick={() => visit({ page: meta.page + 1 })}
                    >
                        Berikutnya
                    </Button>
                </div>
            </div>
        </div>
    );
}
