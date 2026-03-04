<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Request\SearchNotesRequest;
use App\Application\DTO\Response\SearchResultItemResponse;
use App\Application\DTO\Response\SearchResultPageResponse;
use App\Domain\Repository\SearchRepositoryInterface;

class SearchService
{
    public function __construct(private readonly SearchRepositoryInterface $searchRepository)
    {
    }

    public function search(SearchNotesRequest $request): SearchResultPageResponse
    {
        if ('' === trim($request->q)) {
            return new SearchResultPageResponse(
                items: [],
                total: 0,
                page: $request->page,
                perPage: $request->limit,
                totalPages: 1,
            );
        }

        $result = $this->searchRepository->search($request->q, $request->page, $request->limit);

        $items = array_map(
            static fn (array $row) => new SearchResultItemResponse(
                id: $row['id'],
                title: $row['title'],
                headline: $row['headline'],
                rank: $row['rank'],
            ),
            $result['items'],
        );

        $totalPages = max(1, (int) ceil($result['total'] / $request->limit));

        return new SearchResultPageResponse(
            items: $items,
            total: $result['total'],
            page: $request->page,
            perPage: $request->limit,
            totalPages: $totalPages,
        );
    }
}
