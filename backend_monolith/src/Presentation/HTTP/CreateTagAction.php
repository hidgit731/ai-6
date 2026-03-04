<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Request\CreateTagRequest;
use App\Application\DTO\Response\TagCloudItemResponse;
use App\Application\Service\TagService;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/tags', methods: ['POST'])]
class CreateTagAction
{
    public function __construct(
        private readonly TagService $tagService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new CreateTagRequest(
            name: (string) ($body['name'] ?? ''),
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
            $tag = $this->tagService->create($dto->name);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(
            (array) TagCloudItemResponse::fromArray(['tag' => $tag, 'noteCount' => 0]),
            Response::HTTP_CREATED
        );
    }
}
