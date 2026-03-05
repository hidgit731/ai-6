<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\Service\ExportService;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/notes/{id}/export/pdf', methods: ['GET'], priority: 1)]
class ExportPdfAction
{
    public function __construct(private readonly ExportService $exportService)
    {
    }

    public function __invoke(string $id): Response
    {
        try {
            $file = $this->exportService->exportPdf($id);
        } catch (NotFoundHttpException $e) {
            return new Response($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return new Response(
            $file->content,
            Response::HTTP_OK,
            [
                'Content-Type' => $file->mimeType,
                'Content-Disposition' => 'attachment; filename="'.$file->filename.'"',
            ]
        );
    }
}
