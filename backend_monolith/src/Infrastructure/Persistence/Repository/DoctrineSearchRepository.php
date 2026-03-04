<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Repository\SearchRepositoryInterface;
use Doctrine\DBAL\Connection;

class DoctrineSearchRepository implements SearchRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function search(string $query, int $page, int $limit): array
    {
        if ('' === trim($query)) {
            return ['items' => [], 'total' => 0];
        }

        $offset = ($page - 1) * $limit;

        $sql = <<<'SQL'
            SELECT
                n.id,
                n.title,
                ts_headline(
                    'russian',
                    coalesce(n.title, '') || ' ' || coalesce(n.content, ''),
                    websearch_to_tsquery('russian', :q),
                    'MaxWords=35,MinWords=15,MaxFragments=2,FragmentDelimiter=" … "'
                ) AS headline,
                ts_rank(n.search_vector, websearch_to_tsquery('russian', :q)) AS rank
            FROM note n
            WHERE n.deleted_at IS NULL
              AND n.search_vector @@ websearch_to_tsquery('russian', :q)
            ORDER BY rank DESC
            LIMIT :limit OFFSET :offset
        SQL;

        $rows = $this->connection->fetchAllAssociative($sql, [
            'q' => $query,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $countSql = <<<'SQL'
            SELECT COUNT(*)
            FROM note n
            WHERE n.deleted_at IS NULL
              AND n.search_vector @@ websearch_to_tsquery('russian', :q)
        SQL;

        $total = (int) $this->connection->fetchOne($countSql, ['q' => $query]);

        $items = array_map(static fn (array $row) => [
            'id' => $row['id'],
            'title' => (string) $row['title'],
            'headline' => (string) $row['headline'],
            'rank' => (float) $row['rank'],
        ], $rows);

        return ['items' => $items, 'total' => $total];
    }
}
