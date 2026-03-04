<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\NoteVersion;
use Symfony\Component\Uid\Uuid;

interface NoteVersionRepositoryInterface
{
    public function save(NoteVersion $version): void;

    public function findById(Uuid $id): ?NoteVersion;

    /**
     * Returns paginated list of versions for a note, ordered by version_number DESC.
     *
     * @return array{items: NoteVersion[], total: int}
     */
    public function findByNoteIdPaginated(Uuid $noteId, int $page, int $perPage): array;

    public function countByNoteId(Uuid $noteId): int;
}
