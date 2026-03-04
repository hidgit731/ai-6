<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\TagService;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
#[Route('/api/tags/{id}', methods: ['DELETE'])]
class DeleteTagAction
{
    public function __construct(private readonly TagService $tagService)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => 'Тег не найден.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->tagService->delete($uuid);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
