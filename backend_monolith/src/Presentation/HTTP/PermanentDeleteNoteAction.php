<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\NoteService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
#[Route('/api/notes/{id}/permanent', methods: ['DELETE'], requirements: ['id' => '[0-9a-f\-]++'])]
final class PermanentDeleteNoteAction
{
    public function __construct(private readonly NoteService $noteService)
    {
    }

    public function __invoke(string $id): Response
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['message' => 'Invalid note ID format'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->noteService->permanentDelete($uuid);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
