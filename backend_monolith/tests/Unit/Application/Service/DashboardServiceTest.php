<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Response\ActivityPointDTO;
use App\Application\DTO\Response\DashboardStatsResponse;
use App\Application\Service\DashboardService;
use App\Domain\Repository\DashboardRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DashboardServiceTest extends TestCase
{
    private DashboardRepositoryInterface&MockObject $repository;
    private DashboardService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(DashboardRepositoryInterface::class);
        $this->service = new DashboardService($this->repository);
    }

    public function testGetStatsReturnsCorrectCounts(): void
    {
        $this->repository->method('countActiveNotes')->willReturn(42);
        $this->repository->method('countTags')->willReturn(15);
        $this->repository->method('countActiveFolders')->willReturn(8);
        $this->repository->method('getActivityLast30Days')->willReturn([]);

        $result = $this->service->getStats();

        $this->assertInstanceOf(DashboardStatsResponse::class, $result);
        $this->assertSame(42, $result->notesCount);
        $this->assertSame(15, $result->tagsCount);
        $this->assertSame(8, $result->foldersCount);
    }

    public function testGetStatsReturnsExactly30ActivityPoints(): void
    {
        $this->repository->method('countActiveNotes')->willReturn(0);
        $this->repository->method('countTags')->willReturn(0);
        $this->repository->method('countActiveFolders')->willReturn(0);
        $this->repository->method('getActivityLast30Days')->willReturn([]);

        $result = $this->service->getStats();

        $this->assertCount(30, $result->activity);
    }

    public function testGetStatsZeroFillsMissingDays(): void
    {
        $this->repository->method('countActiveNotes')->willReturn(5);
        $this->repository->method('countTags')->willReturn(3);
        $this->repository->method('countActiveFolders')->willReturn(1);
        $this->repository->method('getActivityLast30Days')->willReturn([]);

        $result = $this->service->getStats();

        foreach ($result->activity as $point) {
            $this->assertInstanceOf(ActivityPointDTO::class, $point);
            $this->assertSame(0, $point->count);
        }
    }

    public function testGetStatsMergesRepositoryActivityData(): void
    {
        $today = (new \DateTimeImmutable('today', new \DateTimeZone('UTC')))->format('Y-m-d');
        $yesterday = (new \DateTimeImmutable('yesterday', new \DateTimeZone('UTC')))->format('Y-m-d');

        $this->repository->method('countActiveNotes')->willReturn(2);
        $this->repository->method('countTags')->willReturn(0);
        $this->repository->method('countActiveFolders')->willReturn(0);
        $this->repository->method('getActivityLast30Days')->willReturn([
            ['date' => $today, 'count' => 3],
            ['date' => $yesterday, 'count' => 5],
        ]);

        $result = $this->service->getStats();

        $activityByDate = [];
        foreach ($result->activity as $point) {
            $activityByDate[$point->date] = $point->count;
        }

        $this->assertSame(3, $activityByDate[$today]);
        $this->assertSame(5, $activityByDate[$yesterday]);
    }

    public function testGetStatsActivityIsOrderedChronologically(): void
    {
        $this->repository->method('countActiveNotes')->willReturn(0);
        $this->repository->method('countTags')->willReturn(0);
        $this->repository->method('countActiveFolders')->willReturn(0);
        $this->repository->method('getActivityLast30Days')->willReturn([]);

        $result = $this->service->getStats();

        $dates = array_map(static fn (ActivityPointDTO $p) => $p->date, $result->activity);
        $sortedDates = $dates;
        sort($sortedDates);

        $this->assertSame($sortedDates, $dates);
    }

    public function testGetStatsAllZerosWhenDatabaseEmpty(): void
    {
        $this->repository->method('countActiveNotes')->willReturn(0);
        $this->repository->method('countTags')->willReturn(0);
        $this->repository->method('countActiveFolders')->willReturn(0);
        $this->repository->method('getActivityLast30Days')->willReturn([]);

        $result = $this->service->getStats();

        $this->assertSame(0, $result->notesCount);
        $this->assertSame(0, $result->tagsCount);
        $this->assertSame(0, $result->foldersCount);
        $this->assertCount(30, $result->activity);
        $totalActivity = array_sum(array_map(static fn (ActivityPointDTO $p) => $p->count, $result->activity));
        $this->assertSame(0, $totalActivity);
    }
}
