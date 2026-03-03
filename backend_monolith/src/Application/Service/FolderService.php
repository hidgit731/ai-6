<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Request\CreateFolderRequest;
use App\Application\DTO\Request\MoveFolderRequest;
use App\Application\DTO\Request\UpdateFolderRequest;
use App\Application\DTO\Response\FolderResponse;
use App\Application\DTO\Response\FolderTreeNodeResponse;
use App\Domain\Entity\Folder;
use App\Domain\Repository\FolderRepositoryInterface;
use App\Domain\Repository\NoteRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FolderService
{
    private const MAX_DEPTH = 5;

    public function __construct(
        private readonly FolderRepositoryInterface $folderRepository,
        private readonly NoteRepositoryInterface $noteRepository,
    ) {
    }

    public function create(CreateFolderRequest $request): FolderResponse
    {
        $parent = null;
        if (null !== $request->parentId) {
            $parent = $this->folderRepository->findById($request->parentId);
            if (null === $parent) {
                throw new NotFoundHttpException('Папка не найдена.');
            }
        }

        $duplicate = $this->folderRepository->findByParentAndName($request->parentId, $request->name);
        if (null !== $duplicate) {
            throw new \DomainException('Папка с таким названием уже существует в этой папке.');
        }

        $all = $this->folderRepository->findAll();
        if (null !== $parent && $this->getDepth($parent, $all) >= self::MAX_DEPTH - 1) {
            throw new \OverflowException('Превышена максимальная глубина вложенности (5 уровней).');
        }

        $folder = new Folder($request->name, $parent);
        $this->folderRepository->save($folder);

        return $this->toResponse($folder);
    }

    public function update(string $id, UpdateFolderRequest $request): FolderResponse
    {
        $folder = $this->folderRepository->findById($id);
        if (null === $folder) {
            throw new NotFoundHttpException('Папка не найдена.');
        }

        $parentId = $folder->getParent()?->getId()->toRfc4122();
        if ($folder->getName() !== $request->name) {
            $duplicate = $this->folderRepository->findByParentAndName($parentId, $request->name);
            if (null !== $duplicate) {
                throw new \DomainException('Папка с таким названием уже существует в этой папке.');
            }
        }

        $folder->setName($request->name);
        $this->folderRepository->save($folder);

        return $this->toResponse($folder);
    }

    /**
     * @throws \DomainException when folder has content and strategy is missing
     */
    public function delete(string $id, ?string $strategy): void
    {
        $folder = $this->folderRepository->findById($id);
        if (null === $folder) {
            throw new NotFoundHttpException('Папка не найдена.');
        }

        $hasNotes = $folder->getNotes()->count() > 0;
        $hasFolders = $folder->getChildren()->count() > 0;

        if (($hasNotes || $hasFolders) && null === $strategy) {
            throw new \DomainException(json_encode(['error' => 'Папка содержит заметки или дочерние папки. Укажите параметр strategy=move_to_root или strategy=delete_recursive.', 'hasNotes' => $hasNotes, 'hasFolders' => $hasFolders]));
        }

        if ('move_to_root' === $strategy) {
            foreach ($folder->getNotes() as $note) {
                $note->setFolder(null);
            }
            foreach ($folder->getChildren() as $child) {
                $child->setParent(null);
            }
            $this->folderRepository->save($folder);
        } elseif ('delete_recursive' === $strategy) {
            $all = $this->folderRepository->findAll();
            $descendantIds = $this->getDescendantIds($folder, $all);
            foreach ($descendantIds as $descendantId) {
                $descendant = $this->folderRepository->findById($descendantId);
                if (null !== $descendant) {
                    foreach ($descendant->getNotes() as $note) {
                        $note->setFolder(null);
                    }
                    $this->folderRepository->delete($descendant);
                }
            }
        }

        $this->folderRepository->delete($folder);
    }

    /**
     * @return FolderTreeNodeResponse[]
     */
    public function getTree(): array
    {
        $all = $this->folderRepository->findAll();

        return $this->buildTree($all, null);
    }

    public function move(string $folderId, MoveFolderRequest $request): FolderResponse
    {
        $folder = $this->folderRepository->findById($folderId);
        if (null === $folder) {
            throw new NotFoundHttpException('Папка не найдена.');
        }

        $all = $this->folderRepository->findAll();

        $targetParent = null;
        if (null !== $request->targetParentId) {
            $targetParent = $this->folderRepository->findById($request->targetParentId);
            if (null === $targetParent) {
                throw new NotFoundHttpException('Папка не найдена.');
            }

            // Cycle detection
            $descendantIds = $this->getDescendantIds($folder, $all);
            if (\in_array($request->targetParentId, $descendantIds, true)
                || $request->targetParentId === $folder->getId()->toRfc4122()) {
                throw new \InvalidArgumentException('Невозможно переместить папку в её собственную дочернюю папку.');
            }
        }

        // Duplicate name check in target
        $newParentId = $request->targetParentId;
        $duplicate = $this->folderRepository->findByParentAndName($newParentId, $folder->getName());
        if (null !== $duplicate && $duplicate->getId()->toRfc4122() !== $folderId) {
            throw new \DomainException('Папка с таким названием уже существует в этой папке.');
        }

        // Depth check after move
        if (null !== $targetParent) {
            $targetDepth = $this->getDepth($targetParent, $all);
            $subtreeHeight = $this->getSubtreeHeight($folder, $all);
            if ($targetDepth + $subtreeHeight >= self::MAX_DEPTH - 1) {
                throw new \OverflowException('Превышена максимальная глубина вложенности (5 уровней).');
            }
        }

        $folder->setParent($targetParent);
        $this->folderRepository->save($folder);

        return $this->toResponse($folder);
    }

    private function toResponse(Folder $folder): FolderResponse
    {
        return new FolderResponse(
            id: $folder->getId()->toRfc4122(),
            name: $folder->getName(),
            parentId: $folder->getParent()?->getId()->toRfc4122(),
            createdAt: $folder->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $folder->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @param Folder[] $all
     *
     * @return FolderTreeNodeResponse[]
     */
    private function buildTree(array $all, ?string $parentId): array
    {
        $nodes = [];
        foreach ($all as $folder) {
            $pid = $folder->getParent()?->getId()->toRfc4122();
            if ($pid === $parentId) {
                $children = $this->buildTree($all, $folder->getId()->toRfc4122());
                $nodes[] = new FolderTreeNodeResponse(
                    id: $folder->getId()->toRfc4122(),
                    name: $folder->getName(),
                    parentId: $folder->getParent()?->getId()->toRfc4122(),
                    children: $children,
                );
            }
        }

        return $nodes;
    }

    /**
     * @param Folder[] $all
     *
     * @return string[]
     */
    public function getDescendantIds(Folder $folder, array $all): array
    {
        $ids = [];
        foreach ($all as $f) {
            if ($f->getParent()?->getId()->equals($folder->getId())) {
                $ids[] = $f->getId()->toRfc4122();
                $ids = array_merge($ids, $this->getDescendantIds($f, $all));
            }
        }

        return $ids;
    }

    /**
     * @param Folder[] $all
     */
    private function getDepth(Folder $folder, array $all): int
    {
        $depth = 0;
        $current = $folder;
        while (null !== $current->getParent()) {
            ++$depth;
            $parentId = $current->getParent()->getId()->toRfc4122();
            $current = null;
            foreach ($all as $f) {
                if ($f->getId()->toRfc4122() === $parentId) {
                    $current = $f;
                    break;
                }
            }
            if (null === $current) {
                break;
            }
        }

        return $depth;
    }

    /**
     * Returns max depth from this folder downwards (0 = leaf node).
     *
     * @param Folder[] $all
     */
    private function getSubtreeHeight(Folder $folder, array $all): int
    {
        $folderId = $folder->getId()->toRfc4122();
        $maxChildHeight = 0;
        foreach ($all as $f) {
            if ($f->getParent()?->getId()->toRfc4122() === $folderId) {
                $childHeight = 1 + $this->getSubtreeHeight($f, $all);
                if ($childHeight > $maxChildHeight) {
                    $maxChildHeight = $childHeight;
                }
            }
        }

        return $maxChildHeight;
    }
}
