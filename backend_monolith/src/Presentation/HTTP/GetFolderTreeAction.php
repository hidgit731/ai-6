<?php

declare(strict_types=1);

namespace App\Presentation\HTTP;

use App\Application\DTO\Response\FolderTreeNodeResponse;
use App\Application\Service\FolderService;
use Symfony\Component\DependencyInjection\Attribute\AsController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/folders/tree', methods: ['GET'])]
class GetFolderTreeAction
{
    public function __construct(private readonly FolderService $folderService)
    {
    }

    public function __invoke(): JsonResponse
    {
        $tree = $this->folderService->getTree();

        return new JsonResponse(['tree' => $this->serializeTree($tree)]);
    }

    /**
     * @param FolderTreeNodeResponse[] $nodes
     */
    private function serializeTree(array $nodes): array
    {
        return array_map(fn (FolderTreeNodeResponse $node) => [
            'id' => $node->id,
            'name' => $node->name,
            'parentId' => $node->parentId,
            'children' => $this->serializeTree($node->children),
        ], $nodes);
    }
}
