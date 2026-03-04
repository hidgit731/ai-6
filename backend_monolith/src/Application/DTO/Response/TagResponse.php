<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

use App\Domain\Entity\Tag;

class TagResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $createdAt,
    ) {
    }

    public static function fromEntity(Tag $tag): self
    {
        return new self(
            id: (string) $tag->getId(),
            name: $tag->getName(),
            createdAt: $tag->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
