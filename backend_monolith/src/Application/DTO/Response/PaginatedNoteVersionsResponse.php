<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

class PaginatedNoteVersionsResponse
{
    public function __construct(
        /** @var NoteVersionResponse[] */
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $totalPages,
    ) {
    }
}
