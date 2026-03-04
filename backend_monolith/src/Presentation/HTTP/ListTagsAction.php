<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Response\TagCloudItemResponse;
use App\Application\Service\TagService;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/tags', methods: ['GET'])]
class ListTagsAction
{
    public function __construct(private readonly TagService $tagService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $cloud = $this->tagService->getCloud();

        return new JsonResponse(
            array_map(
                static fn (array $row) => (array) TagCloudItemResponse::fromArray($row),
                $cloud,
            )
        );
    }
}
