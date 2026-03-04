<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\NoteVersionService;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
#[Route(
    '/api/notes/{id}/versions/{versionId}/revert',
    methods: ['POST'],
    requirements: ['id' => '[0-9a-f\-]++', 'versionId' => '[0-9a-f\-]++']
)]
#[OA\Post(
    path: '/api/notes/{id}/versions/{versionId}/revert',
    summary: 'Revert note to a previous version',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'versionId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Note reverted'),
        new OA\Response(response: 404, description: 'Note or version not found'),
        new OA\Response(response: 409, description: 'Cannot revert deleted note'),
    ]
)]
class RevertNoteVersionAction
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
            $response = $this->noteVersionService->revert($noteId, $vId);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (ConflictHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse((array) $response);
    }
}
