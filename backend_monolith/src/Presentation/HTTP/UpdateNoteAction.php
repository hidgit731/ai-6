<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Request\UpdateNoteRequest;
use App\Application\Service\NoteService;
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
#[Route('/api/notes/{id}', methods: ['PUT'], requirements: ['id' => '[0-9a-f\-]++'])]
#[OA\Put(
    path: '/api/notes/{id}',
    summary: 'Обновление заметки',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title'],
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Обновлённый заголовок'),
                new OA\Property(property: 'content', type: 'string', nullable: true, example: '# Обновлённое содержимое'),
                new OA\Property(property: 'folderId', type: 'string', nullable: true, example: null),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Заметка обновлена'),
        new OA\Response(response: 404, description: 'Заметка не найдена'),
        new OA\Response(response: 422, description: 'Ошибки валидации'),
    ]
)]
class UpdateNoteAction
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

        $dto = new UpdateNoteRequest(
            title: (string) ($body['title'] ?? ''),
            content: isset($body['content']) ? (string) $body['content'] : null,
            folderId: isset($body['folderId']) ? ((string) $body['folderId'] ?: null) : null,
            tags: isset($body['tags']) && \is_array($body['tags']) ? array_filter(array_map('strval', $body['tags'])) : [],
        );

        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $changeFolderId = \array_key_exists('folderId', $body);

        try {
            $response = $this->noteService->update($uuid, $dto, $changeFolderId);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse((array) $response);
    }
}
