<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\FolderService;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/folders/{id}', methods: ['DELETE'])]
class DeleteFolderAction
{
    public function __construct(private readonly FolderService $folderService)
    {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $strategy = $request->query->get('strategy') ?: null;

        try {
            $this->folderService->delete($id, $strategy);
        } catch (NotFoundHttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\DomainException $e) {
            $payload = json_decode($e->getMessage(), true);

            return new JsonResponse($payload, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
