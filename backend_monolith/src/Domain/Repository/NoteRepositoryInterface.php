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

    /**
     * @param string|null $folderId null = all, 'none' = no folder, UUID = specific folder
     *
     * @return array{items: Note[], total: int}
     */
    public function findByFolderIdPaginated(?string $folderId, int $page, int $perPage): array;

    public function countByFolderId(?string $folderId): int;

    public function save(Note $note): void;

    public function delete(Note $note): void;
}
