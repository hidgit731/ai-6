<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Response\ActivityPointDTO;
use App\Application\DTO\Response\DashboardStatsResponse;
use App\Domain\Repository\DashboardRepositoryInterface;

final class DashboardService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {
    }

    public function getStats(): DashboardStatsResponse
    {
        $notesCount = $this->dashboardRepository->countActiveNotes();
        $tagsCount = $this->dashboardRepository->countTags();
        $foldersCount = $this->dashboardRepository->countActiveFolders();
        $rawActivity = $this->dashboardRepository->getActivityLast30Days();

        $activity = $this->buildActivitySeries($rawActivity);

        return new DashboardStatsResponse(
            notesCount: $notesCount,
            tagsCount: $tagsCount,
            foldersCount: $foldersCount,
            activity: $activity,
        );
    }

    /**
     * @param array<array{date: string, count: int}> $rawActivity
     *
     * @return ActivityPointDTO[]
     */
    private function buildActivitySeries(array $rawActivity): array
    {
        $activityByDate = [];
        foreach ($rawActivity as $row) {
            $activityByDate[$row['date']] = $row['count'];
        }

        $series = [];
        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));

        for ($i = 29; $i >= 0; --$i) {
            $date = $today->modify("-{$i} days")->format('Y-m-d');
            $series[] = new ActivityPointDTO(
                date: $date,
                count: $activityByDate[$date] ?? 0,
            );
        }

        return $series;
    }
}
