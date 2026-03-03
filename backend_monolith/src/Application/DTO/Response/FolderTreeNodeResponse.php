<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

readonly class FolderTreeNodeResponse
{
    /**
     * @param FolderTreeNodeResponse[] $children
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $parentId,
        public array $children,
    ) {
    }
}
