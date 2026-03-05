<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\NoteLink;
use App\Domain\Repository\NoteLinkRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineNoteLinkRepository implements NoteLinkRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(NoteLink $link): void
    {
        $this->entityManager->persist($link);
        $this->entityManager->flush();
    }

    public function findOutgoing(Uuid $sourceNoteId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('nl')
            ->from(NoteLink::class, 'nl')
            ->where('IDENTITY(nl.sourceNote) = :sourceNoteId')
            ->setParameter('sourceNoteId', $sourceNoteId, 'uuid')
            ->getQuery()
            ->getResult();
    }

    public function findIncoming(Uuid $targetNoteId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('nl')
            ->from(NoteLink::class, 'nl')
            ->where('IDENTITY(nl.targetNote) = :targetNoteId')
            ->setParameter('targetNoteId', $targetNoteId, 'uuid')
            ->getQuery()
            ->getResult();
    }

    public function deleteBySourceNote(Uuid $sourceNoteId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(NoteLink::class, 'nl')
            ->where('IDENTITY(nl.sourceNote) = :sourceNoteId')
            ->setParameter('sourceNoteId', $sourceNoteId, 'uuid')
            ->getQuery()
            ->execute();
    }

    public function findAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('nl')
            ->from(NoteLink::class, 'nl')
            ->getQuery()
            ->getResult();
    }
}
