<?php

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
