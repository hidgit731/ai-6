<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Response\NoteListItemResponse;
use App\Application\DTO\Response\PaginatedNotesResponse;
use App\Application\Service\NoteService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/notes/trash', name: 'app_get_trash', methods: ['GET'])]
final class GetTrashAction
{
    public function __construct(private readonly NoteService $noteService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $page = (int) ($request->query->get('page') ?? 1);
        $limit = (int) ($request->query->get('limit') ?? 20);

        if ($page < 1 || $limit < 1 || $limit > 100) {
            return new JsonResponse(['message' => 'Invalid pagination parameters'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->noteService->getTrash($page, $limit);

        return new JsonResponse($this->toArray($result));
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
