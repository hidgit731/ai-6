<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Repository\DashboardRepositoryInterface;
use Doctrine\DBAL\Connection;

class DoctrineDashboardRepository implements DashboardRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function countActiveNotes(): int
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(n.id) FROM note n WHERE n.deleted_at IS NULL'
        );

        return (int) $result;
    }

    public function countTags(): int
    {
        $result = $this->connection->fetchOne('SELECT COUNT(t.id) FROM tag t');

        return (int) $result;
    }

    public function countActiveFolders(): int
    {
        $result = $this->connection->fetchOne('SELECT COUNT(f.id) FROM folder f');

        return (int) $result;
    }

    public function getActivityLast30Days(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    TO_CHAR(DATE(n.created_at AT TIME ZONE 'UTC'), 'YYYY-MM-DD') AS date,
                    COUNT(n.id)::int AS count
                FROM note n
                WHERE
                    n.deleted_at IS NULL
                    AND n.created_at >= NOW() - INTERVAL '29 days'
                GROUP BY DATE(n.created_at AT TIME ZONE 'UTC')
                ORDER BY date ASC
            SQL
        );

        return array_map(
            static fn (array $row) => ['date' => (string) $row['date'], 'count' => (int) $row['count']],
            $rows,
        );
    }
}
