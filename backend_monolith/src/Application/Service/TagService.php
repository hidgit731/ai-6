<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Tag;
use App\Domain\Repository\TagRepositoryInterface;
use Symfony\Component\Uid\Uuid;

class TagService
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
    ) {
    }

    public function findOrCreate(string $name): Tag
    {
        $tag = $this->tagRepository->findByName($name);
        if (null === $tag) {
            $tag = new Tag($name);
            $this->tagRepository->save($tag);
        }

        return $tag;
    }

    /**
     * @return Tag[]
     */
    public function suggest(string $query): array
    {
        return $this->tagRepository->findByNameContaining($query);
    }

    public function create(string $name): Tag
    {
        if (null !== $this->tagRepository->findByName($name)) {
            throw new \DomainException('Тег с таким именем уже существует.');
        }

        $tag = new Tag($name);
        $this->tagRepository->save($tag);

        return $tag;
    }

    public function getCloud(): array
    {
        return $this->tagRepository->findCloud();
    }

    public function delete(Uuid $id): void
    {
        $tag = $this->tagRepository->findById($id);
        if (null === $tag) {
            throw new \InvalidArgumentException('Тег не найден.');
        }

        $this->tagRepository->delete($tag);
    }
}
