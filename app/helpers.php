<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

if (! function_exists('snake_keys')) {
    /**
     * Convert array keys from camelCase to snake_case.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    function snake_keys(array $attributes): array
    {
        return collect($attributes)
            ->mapWithKeys(fn (mixed $value, string $key) => [Str::snake($key) => $value])
            ->all();
    }
}

if (! function_exists('paginate')) {
    /**
     * Paginate a query using the request's page and perPage parameters.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>|Relation<*, TModel, *>  $query
     * @return LengthAwarePaginator<int, TModel>
     */
    function paginate(Builder|Relation $query, int $defaultPerPage = 15, int $maxPerPage = 100): LengthAwarePaginator
    {
        $perPage = (int) request()->input('perPage', $defaultPerPage);
        $perPage = max(1, min($perPage, $maxPerPage));

        return $query->paginate($perPage);
    }
}

if (! function_exists('pagination_meta')) {
    /**
     * Build a camelCase pagination meta payload from a paginator.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @return array{currentPage: int, lastPage: int, perPage: int, total: int, from: int|null, to: int|null}
     */
    function pagination_meta(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
