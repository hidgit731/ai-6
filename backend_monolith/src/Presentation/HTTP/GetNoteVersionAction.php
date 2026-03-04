<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\NoteVersionService;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
#[Route(
    '/api/notes/{id}/versions/{versionId}',
    methods: ['GET'],
    requirements: ['id' => '[0-9a-f\-]++', 'versionId' => '[0-9a-f\-]++']
)]
#[OA\Get(
    path: '/api/notes/{id}/versions/{versionId}',
    summary: 'Get a single note version',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'versionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Version found'),
        new OA\Response(response: 404, description: 'Note or version not found'),
    ]
)]
class GetNoteVersionAction
{
    public function __construct(private readonly NoteVersionService $noteVersionService)
    {
    }

    public function __invoke(string $id, string $versionId): JsonResponse
    {
        try {
            $noteId = Uuid::fromString($id);
            $vId = Uuid::fromString($versionId);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => 'Note not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $response = $this->noteVersionService->getVersion($noteId, $vId);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse((array) $response);
    }
}
