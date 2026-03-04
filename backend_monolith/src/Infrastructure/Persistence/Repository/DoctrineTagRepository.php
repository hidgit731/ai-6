<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Tag;
use App\Domain\Repository\TagRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineTagRepository implements TagRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(Uuid $id): ?Tag
    {
        return $this->entityManager->find(Tag::class, $id);
    }

    public function findByName(string $name): ?Tag
    {
        return $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Tag::class, 't')
            ->where('t.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByNameContaining(string $query): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Tag::class, 't')
            ->where('LOWER(t.name) LIKE LOWER(:q)')
            ->setParameter('q', '%'.$query.'%')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(Tag::class, 't')
            ->getQuery()
            ->getResult();
    }

    public function findCloud(): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('t', 'COUNT(n.id) as noteCount')
            ->from(Tag::class, 't')
            ->leftJoin('t.notes', 'n')
            ->groupBy('t.id')
            ->orderBy('noteCount', 'DESC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (array $row) => ['tag' => $row[0], 'noteCount' => (int) $row['noteCount']],
            $rows,
        );
    }

    public function save(Tag $tag): void
    {
        $this->entityManager->persist($tag);
        $this->entityManager->flush();
    }

    public function delete(Tag $tag): void
    {
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }
}
