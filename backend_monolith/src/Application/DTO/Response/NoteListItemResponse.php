<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

class NoteListItemResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $preview,
        public readonly string $createdAt,
        public readonly ?string $folderId = null,
    ) {
    }
}
