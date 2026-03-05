<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\NoteLinkService;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/graph', methods: ['GET'])]
#[OA\Get(
    path: '/api/graph',
    summary: 'Граф знаний — все заметки и связи между ними',
    responses: [
        new OA\Response(response: 200, description: 'Узлы и рёбра графа'),
    ]
)]
class GetGraphAction
{
    public function __construct(private readonly NoteLinkService $noteLinkService)
    {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->noteLinkService->getGraph()->toArray());
    }
}
