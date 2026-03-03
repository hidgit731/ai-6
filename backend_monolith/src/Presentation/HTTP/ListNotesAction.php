<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Request\ListNotesRequest;
use App\Application\DTO\Response\NoteListItemResponse;
use App\Application\DTO\Response\PaginatedNotesResponse;
use App\Application\Service\NoteService;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/notes', methods: ['GET'])]
#[OA\Get(
    path: '/api/notes',
    summary: 'Список заметок с пагинацией',
    parameters: [
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Список заметок'),
        new OA\Response(response: 422, description: 'Ошибки валидации'),
    ]
)]
class ListNotesAction
{
    public function __construct(
        private readonly NoteService $noteService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);

        $dto = new ListNotesRequest(page: $page);

        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $response = $this->noteService->list($dto);

        return new JsonResponse($this->toArray($response));
    }

    private function toArray(PaginatedNotesResponse $response): array
    {
        return [
            'items' => array_map(
                static fn (NoteListItemResponse $item) => (array) $item,
                $response->items,
            ),
            'page' => $response->page,
            'perPage' => $response->perPage,
            'total' => $response->total,
            'totalPages' => $response->totalPages,
        ];
    }
}
