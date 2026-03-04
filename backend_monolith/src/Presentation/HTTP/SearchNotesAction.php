<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Request\SearchNotesRequest;
use App\Application\Service\SearchService;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// IMPORTANT: This route MUST appear before /api/notes/{id} in routing order
// to prevent "search" from being matched as a UUID segment.
#[AsController]
#[Route('/api/notes/search', methods: ['GET'], priority: 1)]
#[OA\Get(
    path: '/api/notes/search',
    summary: 'Full-text search for notes',
    parameters: [
        new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string', maxLength: 500)),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Search results'),
        new OA\Response(response: 422, description: 'Validation errors'),
    ]
)]
class SearchNotesAction
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $dto = new SearchNotesRequest(
            q: (string) $request->query->get('q', ''),
            page: (int) $request->query->get('page', 1),
            limit: (int) $request->query->get('limit', 20),
        );

        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->searchService->search($dto);

        return new JsonResponse($result->toArray());
    }
}
