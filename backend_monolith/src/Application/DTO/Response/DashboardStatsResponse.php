<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

final readonly class DashboardStatsResponse
{
    /**
     * @param ActivityPointDTO[] $activity
     */
    public function __construct(
        public int $notesCount,
        public int $tagsCount,
        public int $foldersCount,
        public array $activity,
    ) {
    }

    public function toArray(): array
    {
        return [
            'notes_count' => $this->notesCount,
            'tags_count' => $this->tagsCount,
            'folders_count' => $this->foldersCount,
            'activity' => array_map(
                static fn (ActivityPointDTO $point) => ['date' => $point->date, 'count' => $point->count],
                $this->activity,
            ),
        ];
    }
}
