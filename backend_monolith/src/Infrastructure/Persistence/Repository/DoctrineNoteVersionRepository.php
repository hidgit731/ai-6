<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\NoteVersion;
use App\Domain\Repository\NoteVersionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

class DoctrineNoteVersionRepository implements NoteVersionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(NoteVersion $version): void
    {
        $this->entityManager->persist($version);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?NoteVersion
    {
        return $this->entityManager->getRepository(NoteVersion::class)->find($id);
    }

    public function findByNoteIdPaginated(Uuid $noteId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $listQb = $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(NoteVersion::class, 'v')
            ->where('v.note = :noteId')
            ->setParameter('noteId', $noteId, UuidType::NAME)
            ->orderBy('v.versionNumber', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        $countQb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(NoteVersion::class, 'v')
            ->where('v.note = :noteId')
            ->setParameter('noteId', $noteId, UuidType::NAME);

        $items = $listQb->getQuery()->getResult();
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        return ['items' => $items, 'total' => $total];
    }

    public function countByNoteId(Uuid $noteId): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(NoteVersion::class, 'v')
            ->where('v.note = :noteId')
            ->setParameter('noteId', $noteId, UuidType::NAME);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
