<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\DashboardService;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/dashboard', methods: ['GET'])]
class GetDashboardAction
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $stats = $this->dashboardService->getStats();

        return new JsonResponse($stats->toArray());
    }
}
