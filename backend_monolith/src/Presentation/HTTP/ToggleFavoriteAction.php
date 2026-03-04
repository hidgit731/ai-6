<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\NoteService;
use App\Domain\Repository\NoteRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
#[Route('/api/notes/{id}/favorite', methods: ['POST'], requirements: ['id' => '[0-9a-f\-]++'])]
final class ToggleFavoriteAction
{
    public function __invoke(
        string $id,
        NoteService $noteService,
        NoteRepositoryInterface $noteRepository,
    ): JsonResponse {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\Exception) {
            return new JsonResponse(['message' => 'Invalid note ID format'], Response::HTTP_BAD_REQUEST);
        }

        $note = $noteRepository->findById($uuid);
        if (null === $note) {
            return new JsonResponse(['message' => 'Note not found'], Response::HTTP_NOT_FOUND);
        }

        if ($note->isDeleted()) {
            return new JsonResponse(['message' => 'Cannot toggle favorite on deleted note'], Response::HTTP_CONFLICT);
        }

        $noteService->toggleFavorite($note);
        $response = $noteService->getById($uuid);

        return new JsonResponse((array) $response, Response::HTTP_OK);
    }
}
