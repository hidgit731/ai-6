<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

final readonly class NoteLinksResponse
{
    /**
     * @param NoteRefDTO[] $incoming
     * @param NoteRefDTO[] $outgoing
     */
    public function __construct(
        public array $incoming,
        public array $outgoing,
    ) {
    }

    public function toArray(): array
    {
        return [
            'incoming' => array_map(static fn (NoteRefDTO $d) => $d->toArray(), $this->incoming),
            'outgoing' => array_map(static fn (NoteRefDTO $d) => $d->toArray(), $this->outgoing),
        ];
    }
}
