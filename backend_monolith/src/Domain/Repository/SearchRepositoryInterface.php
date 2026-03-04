<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface SearchRepositoryInterface
{
    /**
     * @return array{items: array<array{id: string, title: string, headline: string, rank: float}>, total: int}
     */
    public function search(string $query, int $page, int $limit): array;
}
