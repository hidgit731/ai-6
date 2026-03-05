<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

final readonly class GraphResponse
{
    /**
     * @param GraphNodeDTO[] $nodes
     * @param GraphEdgeDTO[] $edges
     */
    public function __construct(
        public array $nodes,
        public array $edges,
    ) {
    }

    public function toArray(): array
    {
        return [
            'nodes' => array_map(static fn (GraphNodeDTO $n) => $n->toArray(), $this->nodes),
            'edges' => array_map(static fn (GraphEdgeDTO $e) => $e->toArray(), $this->edges),
        ];
    }
}
