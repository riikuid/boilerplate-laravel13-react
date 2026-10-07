<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Server-side search / sort / pagination driven by the query string
 * (?search=&sort=&direction=&page=&per_page=). Pairs with the React DataTable.
 *
 * Only whitelisted columns can be searched or sorted.
 *
 * @template TModel of Model
 */
final class TableQuery
{
    public const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    /** @var list<string> */
    private array $searchable = [];

    /** @var list<string> */
    private array $sortable = [];

    private string $defaultSort = 'created_at';

    /** @var 'asc'|'desc' */
    private string $defaultDirection = 'desc';

    /** @param  Builder<TModel>  $query */
    private function __construct(private readonly Builder $query, private readonly Request $request) {}

    /**
     * @template T of Model
     *
     * @param  Builder<T>  $query
     * @return self<T>
     */
    public static function for(Builder $query, Request $request): self
    {
        return new self($query, $request);
    }

    /** @return $this */
    public function searchable(string ...$columns): self
    {
        $this->searchable = array_values($columns);

        return $this;
    }

    /** @return $this */
    public function sortable(string ...$columns): self
    {
        $this->sortable = array_values($columns);

        return $this;
    }

    /**
     * @param  'asc'|'desc'  $direction
     * @return $this
     */
    public function defaultSort(string $column, string $direction = 'desc'): self
    {
        $this->defaultSort = $column;
        $this->defaultDirection = $direction;

        return $this;
    }

    /**
     * @param  callable(TModel): mixed  $transform  maps each row to what the frontend receives
     * @return array{
     *     data: list<mixed>,
     *     meta: array{page: int, per_page: int, total: int, last_page: int},
     *     filters: array{search: string, sort: string, direction: string},
     * }
     */
    public function paginate(callable $transform): array
    {
        $search = trim((string) $this->request->query('search', ''));
        $sort = (string) $this->request->query('sort', '');
        $sort = in_array($sort, $this->sortable, true) ? $sort : $this->defaultSort;
        $direction = match ($this->request->query('direction')) {
            'asc' => 'asc',
            'desc' => 'desc',
            default => $this->defaultDirection,
        };

        $perPage = (int) $this->request->query('per_page', self::PER_PAGE_OPTIONS[1]);
        $perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::PER_PAGE_OPTIONS[1];

        $this->applySearch($search);

        $paginator = $this->query
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => array_values(array_map($transform, $paginator->items())),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
        ];
    }

    private function applySearch(string $search): void
    {
        if ($search === '' || $this->searchable === []) {
            return;
        }

        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $term = '%'.addcslashes($search, '\\%_').'%';

        $this->query->where(function (Builder $query) use ($operator, $term): void {
            foreach ($this->searchable as $column) {
                $query->orWhere($column, $operator, $term);
            }
        });
    }
}
