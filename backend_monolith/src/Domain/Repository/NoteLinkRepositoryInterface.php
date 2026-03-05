<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\NoteLink;
use Symfony\Component\Uid\Uuid;

interface NoteLinkRepositoryInterface
{
    public function save(NoteLink $link): void;

    /**
     * @return NoteLink[]
     */
    public function findOutgoing(Uuid $sourceNoteId): array;

    /**
     * @return NoteLink[]
     */
    public function findIncoming(Uuid $targetNoteId): array;

    public function deleteBySourceNote(Uuid $sourceNoteId): void;

    /**
     * @return NoteLink[]
     */
    public function findAll(): array;
}
