<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Folder;
use App\Domain\Repository\FolderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineFolderRepository implements FolderRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(Folder $folder): void
    {
        $this->entityManager->persist($folder);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?Folder
    {
        return $this->entityManager->find(Folder::class, $id);
    }

    public function findAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(Folder::class, 'f')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByParentAndName(?string $parentId, string $name): ?Folder
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('f')
            ->from(Folder::class, 'f')
            ->where('f.name = :name')
            ->setParameter('name', $name);

        if (null === $parentId) {
            $qb->andWhere('f.parent IS NULL');
        } else {
            $qb->andWhere('IDENTITY(f.parent) = :parentId')
               ->setParameter('parentId', $parentId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function delete(Folder $folder): void
    {
        $this->entityManager->remove($folder);
        $this->entityManager->flush();
    }
}
