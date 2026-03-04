<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Tag;
use Symfony\Component\Uid\Uuid;

interface TagRepositoryInterface
{
    public function findById(Uuid $id): ?Tag;

    public function findByName(string $name): ?Tag;

    /**
     * @return Tag[]
     */
    public function findByNameContaining(string $query): array;

    /**
     * @return Tag[]
     */
    public function findAll(): array;

    /**
     * @return array<array{tag: Tag, noteCount: int}>
     */
    public function findCloud(): array;

    public function save(Tag $tag): void;

    public function delete(Tag $tag): void;
}
