<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\NoteService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/notes/trash', methods: ['DELETE'], priority: 1)]
final class EmptyTrashAction
{
    public function __construct(private readonly NoteService $noteService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $deleted = $this->noteService->emptyAllTrash();

        return new JsonResponse(['deleted' => $deleted]);
    }
}
