<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\NoteService;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
#[Route('/api/notes/{id}', methods: ['GET'], requirements: ['id' => '[0-9a-f\-]++'])]
#[OA\Get(
    path: '/api/notes/{id}',
    summary: 'Получение заметки',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Заметка найдена'),
        new OA\Response(response: 404, description: 'Заметка не найдена'),
    ]
)]
class GetNoteAction
{
    public function __construct(private readonly NoteService $noteService)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => 'Заметка не найдена.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $response = $this->noteService->getById($uuid);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse((array) $response);
    }
}
