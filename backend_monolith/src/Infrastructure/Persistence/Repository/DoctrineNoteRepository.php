<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Note;
use App\Domain\Repository\NoteRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineNoteRepository implements NoteRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findById(Uuid $id): ?Note
    {
        return $this->entityManager->find(Note::class, $id);
    }

    public function findPaginated(int $page, int $perPage): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('n')
            ->from(Note::class, 'n')
            ->orderBy('n.createdAt', 'DESC');

        $countQb = $this->entityManager->createQueryBuilder();
        $total = (int) $countQb
            ->select('COUNT(n.id)')
            ->from(Note::class, 'n')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    public function save(Note $note): void
    {
        $this->entityManager->persist($note);
        $this->entityManager->flush();
    }

    public function delete(Note $note): void
    {
        $this->entityManager->remove($note);
        $this->entityManager->flush();
    }
}
