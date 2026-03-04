<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Request\SuggestTagsRequest;
use App\Application\DTO\Response\TagResponse;
use App\Application\Service\TagService;
use App\Domain\Entity\Tag;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/tags/suggest', methods: ['GET'])]
class SuggestTagsAction
{
    public function __construct(
        private readonly TagService $tagService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $q = (string) $request->query->get('q', '');
        $dto = new SuggestTagsRequest(q: $q);

        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tags = $this->tagService->suggest($dto->q);

        return new JsonResponse(
            array_map(
                static fn (Tag $tag) => (array) TagResponse::fromEntity($tag),
                $tags,
            )
        );
    }
}
