<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Request\ListNoteVersionsRequest;
use App\Application\DTO\Response\NoteVersionResponse;
use App\Application\DTO\Response\PaginatedNoteVersionsResponse;
use App\Application\Service\NoteVersionService;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/notes/{id}/versions', methods: ['GET'], requirements: ['id' => '[0-9a-f\-]++'])]
#[OA\Get(
    path: '/api/notes/{id}/versions',
    summary: 'Version history for a note',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated version list'),
        new OA\Response(response: 404, description: 'Note not found'),
        new OA\Response(response: 422, description: 'Validation errors'),
    ]
)]
class ListNoteVersionsAction
{
    public function __construct(
        private readonly NoteVersionService $noteVersionService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        try {
            $noteId = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => 'Note not found.'], Response::HTTP_NOT_FOUND);
        }

        $dto = new ListNoteVersionsRequest(
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 20),
        );

        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $response = $this->noteVersionService->getVersions($noteId, $dto->page, $dto->limit);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->toArray($response));
    }

    private function toArray(PaginatedNoteVersionsResponse $response): array
    {
        return [
            'items' => array_map(
                static fn (NoteVersionResponse $item) => (array) $item,
                $response->items,
            ),
            'total' => $response->total,
            'page' => $response->page,
            'perPage' => $response->perPage,
            'totalPages' => $response->totalPages,
        ];
    }
}
