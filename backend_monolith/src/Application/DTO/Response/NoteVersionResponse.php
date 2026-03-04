<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

class NoteVersionResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $noteId,
        public readonly string $title,
        public readonly ?string $content,
        public readonly int $versionNumber,
        public readonly string $createdAt,
    ) {
    }
}
