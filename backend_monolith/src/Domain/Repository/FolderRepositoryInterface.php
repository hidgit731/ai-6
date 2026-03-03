<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Folder;

interface FolderRepositoryInterface
{
    public function save(Folder $folder): void;

    public function findById(string $id): ?Folder;

    /**
     * @return Folder[]
     */
    public function findAll(): array;

    public function findByParentAndName(?string $parentId, string $name): ?Folder;

    public function delete(Folder $folder): void;
}
