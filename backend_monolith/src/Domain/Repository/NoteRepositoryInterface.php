<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Note;
use Symfony\Component\Uid\Uuid;

interface NoteRepositoryInterface
{
    public function findById(Uuid $id): ?Note;

    /**
     * @return array{items: Note[], total: int}
     */
    public function findPaginated(int $page, int $perPage): array;

    public function save(Note $note): void;

    public function delete(Note $note): void;
}
