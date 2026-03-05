<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\NoteLinkService;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
#[Route('/api/notes/{id}/links', methods: ['GET'], priority: 1)]
#[OA\Get(
    path: '/api/notes/{id}/links',
    summary: 'Получение входящих и исходящих ссылок заметки',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Список входящих и исходящих ссылок'),
        new OA\Response(response: 404, description: 'Заметка не найдена'),
    ]
)]
class GetNoteLinksAction
{
    public function __construct(private readonly NoteLinkService $noteLinkService)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => 'Неверный формат UUID.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $response = $this->noteLinkService->getLinks($uuid);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($response->toArray());
    }
}
