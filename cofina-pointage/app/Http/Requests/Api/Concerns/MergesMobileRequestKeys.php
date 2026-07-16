<?php

namespace App\Http\Requests\Api\Concerns;

trait MergesMobileRequestKeys
{
    /**
     * @param  array<int, array{0: string, 1: string}>  $pairs  [snake_case, camelCase]
     */
    protected function mergeSnakeFromCamelPairs(array $pairs): void
    {
        foreach ($pairs as [$snake, $camel]) {
            if (! $this->has($snake) && $this->has($camel)) {
                $this->merge([$snake => $this->input($camel)]);
            }
        }
    }
}
