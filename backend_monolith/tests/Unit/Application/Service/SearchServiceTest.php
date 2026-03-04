<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\SearchNotesRequest;
use App\Application\Service\SearchService;
use App\Domain\Repository\SearchRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchServiceTest extends TestCase
{
    private SearchRepositoryInterface&MockObject $searchRepository;
    private SearchService $service;

    protected function setUp(): void
    {
        $this->searchRepository = $this->createMock(SearchRepositoryInterface::class);
        $this->service = new SearchService($this->searchRepository);
    }

    public function testEmptyQueryReturnsEmptyPageWithoutCallingRepository(): void
    {
        $this->searchRepository->expects($this->never())->method('search');

        $request = new SearchNotesRequest(q: '', page: 1, limit: 20);
        $result = $this->service->search($request);

        $this->assertSame([], $result->items);
        $this->assertSame(0, $result->total);
        $this->assertSame(1, $result->totalPages);
    }

    public function testWhitespaceQueryReturnsEmptyPageWithoutCallingRepository(): void
    {
        $this->searchRepository->expects($this->never())->method('search');

        $request = new SearchNotesRequest(q: '   ', page: 1, limit: 20);
        $result = $this->service->search($request);

        $this->assertSame([], $result->items);
        $this->assertSame(0, $result->total);
        $this->assertSame(1, $result->totalPages);
    }

    public function testNonEmptyQueryCallsRepositoryAndMapsResults(): void
    {
        $repositoryItems = [
            ['id' => 'abc-123', 'title' => 'Test Note', 'headline' => 'Found <b>test</b> here', 'rank' => 0.75],
        ];

        $this->searchRepository
            ->expects($this->once())
            ->method('search')
            ->with('test', 1, 20)
            ->willReturn(['items' => $repositoryItems, 'total' => 1]);

        $request = new SearchNotesRequest(q: 'test', page: 1, limit: 20);
        $result = $this->service->search($request);

        $this->assertCount(1, $result->items);
        $this->assertSame('abc-123', $result->items[0]->id);
        $this->assertSame('Test Note', $result->items[0]->title);
        $this->assertSame('Found <b>test</b> here', $result->items[0]->headline);
        $this->assertSame(0.75, $result->items[0]->rank);
        $this->assertSame(1, $result->total);
        $this->assertSame(1, $result->page);
        $this->assertSame(20, $result->perPage);
        $this->assertSame(1, $result->totalPages);
    }

    public function testTotalPagesIsAtLeastOneEvenWhenTotalIsZero(): void
    {
        $this->searchRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(['items' => [], 'total' => 0]);

        $request = new SearchNotesRequest(q: 'noresults', page: 1, limit: 20);
        $result = $this->service->search($request);

        $this->assertSame(1, $result->totalPages);
    }

    public function testTotalPagesCalculatedCorrectly(): void
    {
        $this->searchRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn(['items' => [], 'total' => 45]);

        $request = new SearchNotesRequest(q: 'query', page: 1, limit: 20);
        $result = $this->service->search($request);

        $this->assertSame(3, $result->totalPages);
    }
}
