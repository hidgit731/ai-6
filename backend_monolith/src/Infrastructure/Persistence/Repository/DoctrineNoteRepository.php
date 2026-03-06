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
            ->where('n.deletedAt IS NULL')
            ->orderBy('n.createdAt', 'DESC');

        $countQb = $this->entityManager->createQueryBuilder();
        $total = (int) $countQb
            ->select('COUNT(n.id)')
            ->from(Note::class, 'n')
            ->where('n.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    public function findByFolderIdPaginated(?string $folderId, int $page, int $perPage): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('n')
            ->from(Note::class, 'n')
            ->where('n.deletedAt IS NULL')
            ->orderBy('n.createdAt', 'DESC');

        $this->applyFolderFilter($qb, $folderId);

        $countQb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(Note::class, 'n')
            ->where('n.deletedAt IS NULL');

        $this->applyFolderFilter($countQb, $folderId);

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    public function countByFolderId(?string $folderId): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(Note::class, 'n');

        $this->applyFolderFilter($qb, $folderId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findFilteredPaginated(?string $folderId, array $tagNames, int $page, int $perPage): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('n')
            ->from(Note::class, 'n')
            ->where('n.deletedAt IS NULL')
            ->orderBy('n.createdAt', 'DESC');

        foreach ($tagNames as $i => $name) {
            $qb->innerJoin('n.tags', "t{$i}")
               ->andWhere("t{$i}.name = :name{$i}")
               ->setParameter("name{$i}", $name);
        }

        $this->applyFolderFilter($qb, $folderId);

        $countQb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT n.id)')
            ->from(Note::class, 'n')
            ->where('n.deletedAt IS NULL');

        foreach ($tagNames as $i => $name) {
            $countQb->innerJoin('n.tags', "t{$i}")
                    ->andWhere("t{$i}.name = :name{$i}")
                    ->setParameter("name{$i}", $name);
        }

        $this->applyFolderFilter($countQb, $folderId);

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

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

    public function findFavorites(int $page = 1, int $limit = 20): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('n')
            ->from(Note::class, 'n')
            ->where('n.isFavorite = true')
            ->andWhere('n.deletedAt IS NULL')
            ->orderBy('n.createdAt', 'DESC');

        $countQb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(Note::class, 'n')
            ->where('n.isFavorite = true')
            ->andWhere('n.deletedAt IS NULL');

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $pages = (int) ceil($total / $limit);

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page, 'limit' => $limit];
    }

    public function findTrash(int $page = 1, int $limit = 20): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('n')
            ->from(Note::class, 'n')
            ->where('n.deletedAt IS NOT NULL')
            ->orderBy('n.deletedAt', 'DESC');

        $countQb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(Note::class, 'n')
            ->where('n.deletedAt IS NOT NULL');

        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $pages = (int) ceil($total / $limit);

        return ['items' => $items, 'total' => $total, 'pages' => $pages, 'page' => $page, 'limit' => $limit];
    }

    public function deleteAllTrash(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->delete(Note::class, 'n')
            ->where('n.deletedAt IS NOT NULL')
            ->getQuery()
            ->execute();
    }

    public function deleteExpiredTrash(\DateTimeImmutable $before): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->delete(Note::class, 'n')
            ->where('n.deleted_at IS NOT NULL')
            ->andWhere('n.deleted_at < :before')
            ->andWhere('n.updated_at < :grace_period')
            ->setParameter('before', $before)
            ->setParameter('grace_period', new \DateTimeImmutable('now - 5 seconds'));

        return (int) $qb->getQuery()->execute();
    }

    public function findByTitle(string $title): ?Note
    {
        return $this->entityManager->createQueryBuilder()
            ->select('n')
            ->from(Note::class, 'n')
            ->where('n.title = :title')
            ->andWhere('n.deletedAt IS NULL')
            ->orderBy('n.createdAt', 'ASC')
            ->setMaxResults(1)
            ->setParameter('title', $title)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('n')
            ->from(Note::class, 'n')
            ->where('n.deletedAt IS NULL')
            ->orderBy('n.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function applyFolderFilter(\Doctrine\ORM\QueryBuilder $qb, ?string $folderId): void
    {
        if (null === $folderId) {
            // no filter — return all
        } elseif ('none' === $folderId) {
            $qb->andWhere('n.folder IS NULL');
        } else {
            $qb->andWhere('IDENTITY(n.folder) = :folderId')
               ->setParameter('folderId', $folderId);
        }
    }
}
