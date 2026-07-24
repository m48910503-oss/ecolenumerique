<?php
declare(strict_types=1);

namespace App\Support;

final class Pagination
{
    /**
     * @param array<string,mixed> $query
     */
    public static function pageFromQuery(array $query, string $key = 'page', int $default = 1): int
    {
        $default = max(1, $default);
        $raw = $query[$key] ?? $default;
        if (!is_scalar($raw)) {
            return $default;
        }

        $page = (int) $raw;

        return $page > 0 ? $page : $default;
    }

    /**
     * @return array{
     *   page:int,
     *   per_page:int,
     *   total:int,
     *   total_pages:int,
     *   has_prev:bool,
     *   has_next:bool,
     *   prev_page:int,
     *   next_page:int,
     *   offset:int,
     *   start:int,
     *   end:int
     * }
     */
    public static function build(int $total, int $page, int $perPage): array
    {
        $total = max(0, $total);
        $perPage = max(1, $perPage);
        $totalPages = max(1, (int) ceil(($total > 0 ? $total : 1) / $perPage));
        $page = max(1, min($totalPages, $page));
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => max(1, $page - 1),
            'next_page' => min($totalPages, $page + 1),
            'offset' => $offset,
            'start' => $total > 0 ? ($offset + 1) : 0,
            'end' => $total > 0 ? min($total, $offset + $perPage) : 0,
        ];
    }

    /**
     * @template T
     * @param array<int,T> $rows
     * @param array{offset?:int,per_page?:int} $pagination
     * @return array<int,T>
     */
    public static function slice(array $rows, array $pagination): array
    {
        $offset = max(0, (int) ($pagination['offset'] ?? 0));
        $perPage = max(1, (int) ($pagination['per_page'] ?? count($rows)));

        return array_slice(array_values($rows), $offset, $perPage);
    }
}
