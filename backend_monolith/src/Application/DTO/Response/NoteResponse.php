<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

class NoteResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $content,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }
}
