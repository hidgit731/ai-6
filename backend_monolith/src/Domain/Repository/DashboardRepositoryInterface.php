<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface DashboardRepositoryInterface
{
    public function countActiveNotes(): int;

    public function countTags(): int;

    public function countActiveFolders(): int;

    /**
     * Returns activity for days that have at least one note created.
     * Zero-fill is performed in the service layer.
     *
     * @return array<array{date: string, count: int}>
     */
    public function getActivityLast30Days(): array;
}
