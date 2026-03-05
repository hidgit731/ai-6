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

    /**
     * @param string|null $folderId null = all folders, 'none' = no folder, UUID = specific folder
     * @param string[]    $tagNames Empty = no tag filter; AND-logic when > 1 tag
     *
     * @return array{items: Note[], total: int}
     */
    public function findFilteredPaginated(?string $folderId, array $tagNames, int $page, int $perPage): array;

    public function save(Note $note): void;

    public function delete(Note $note): void;

    /**
     * @return array{items: Note[], total: int, pages: int, page: int, limit: int}
     */
    public function findFavorites(int $page = 1, int $limit = 20): array;

    /**
     * @return array{items: Note[], total: int, pages: int, page: int, limit: int}
     */
    public function findTrash(int $page = 1, int $limit = 20): array;

    public function deleteExpiredTrash(\DateTimeImmutable $before): int;

    public function findByTitle(string $title): ?Note;

    /**
     * @return Note[]
     */
    public function findAll(): array;
}
