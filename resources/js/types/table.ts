/** Shape returned by App\Support\TableQuery::paginate(). */
export type Paginated<T> = {
    data: T[];
    meta: {
        page: number;
        per_page: number;
        total: number;
        last_page: number;
    };
    filters: {
        search: string;
        sort: string;
        direction: 'asc' | 'desc';
    };
};
