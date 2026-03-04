<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Request\MoveNoteToFolderRequest;
use App\Application\Service\NoteService;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/notes/{id}/folder', methods: ['PATCH'], requirements: ['id' => '[0-9a-f\-]++'])]
class MoveNoteToFolderAction
{
    public function __construct(
        private readonly NoteService $noteService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => 'Заметка не найдена.'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new MoveNoteToFolderRequest(
            folderId: isset($body['folderId']) ? ((string) $body['folderId'] ?: null) : null,
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
            $response = $this->noteService->moveNoteToFolder($uuid, $dto);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse((array) $response);
    }
}
