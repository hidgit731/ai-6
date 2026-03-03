<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

readonly class FolderResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $parentId,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
