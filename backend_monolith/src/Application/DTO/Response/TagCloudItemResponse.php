<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

use App\Domain\Entity\Tag;

class TagCloudItemResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $noteCount,
        public readonly string $createdAt,
    ) {
    }

    public static function fromArray(array $row): self
    {
        /** @var Tag $tag */
        $tag = $row['tag'];

        return new self(
            id: (string) $tag->getId(),
            name: $tag->getName(),
            noteCount: (int) $row['noteCount'],
            createdAt: $tag->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
