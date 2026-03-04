<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Request\CreateNoteRequest;
use App\Application\Service\NoteService;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/notes', methods: ['POST'])]
#[OA\Post(
    path: '/api/notes',
    summary: 'Создание заметки',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title'],
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Новая заметка'),
                new OA\Property(property: 'content', type: 'string', nullable: true, example: '# Содержимое'),
                new OA\Property(property: 'folderId', type: 'string', nullable: true, example: null),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Заметка создана'),
        new OA\Response(response: 422, description: 'Ошибки валидации'),
    ]
)]
class CreateNoteAction
{
    public function __construct(
        private readonly NoteService $noteService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new CreateNoteRequest(
            title: (string) ($body['title'] ?? ''),
            content: isset($body['content']) ? (string) $body['content'] : null,
            folderId: isset($body['folderId']) ? (string) $body['folderId'] : null,
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

        try {
            $response = $this->noteService->create($dto);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse((array) $response, Response::HTTP_CREATED);
    }
}
