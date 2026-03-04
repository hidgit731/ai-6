<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Request\CreateNoteRequest;
use App\Application\DTO\Request\ListNotesRequest;
use App\Application\DTO\Request\MoveNoteToFolderRequest;
use App\Application\DTO\Request\UpdateNoteRequest;
use App\Application\DTO\Response\NoteListItemResponse;
use App\Application\DTO\Response\NoteResponse;
use App\Application\DTO\Response\PaginatedNotesResponse;
use App\Application\DTO\Response\TagResponse;
use App\Domain\Entity\Note;
use App\Domain\Entity\Tag;
use App\Domain\Repository\FolderRepositoryInterface;
use App\Domain\Repository\NoteRepositoryInterface;
use App\Domain\Repository\TagRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class NoteService
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly NoteRepositoryInterface $noteRepository,
        private readonly FolderRepositoryInterface $folderRepository,
        private readonly TagRepositoryInterface $tagRepository,
    ) {
    }

    public function create(CreateNoteRequest $request): NoteResponse
    {
        $note = new Note($request->title, $request->content);

        if (null !== $request->folderId) {
            $folder = $this->folderRepository->findById($request->folderId);
            if (null === $folder) {
                throw new NotFoundHttpException('Папка не найдена.');
            }
            $note->setFolder($folder);
        }

        $this->syncTags($note, $request->tags);
        $this->noteRepository->save($note);

        return $this->toResponse($note);
    }

    public function update(Uuid $id, UpdateNoteRequest $request, bool $changeFolderId = false): NoteResponse
    {
        $note = $this->noteRepository->findById($id);

        if (null === $note) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        $note->setTitle($request->title);
        $note->setContent($request->content);

        if ($changeFolderId) {
            if (null === $request->folderId) {
                $note->setFolder(null);
            } else {
                $folder = $this->folderRepository->findById($request->folderId);
                if (null === $folder) {
                    throw new NotFoundHttpException('Папка не найдена.');
                }
                $note->setFolder($folder);
            }
        }

        $this->syncTags($note, $request->tags);
        $this->noteRepository->save($note);

        return $this->toResponse($note);
    }

    public function delete(Uuid $id): void
    {
        $note = $this->noteRepository->findById($id);

        if (null === $note) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        if ($note->isDeleted()) {
            throw new \DomainException('Заметка уже удалена.');
        }

        $this->softDelete($note);
    }

    public function getById(Uuid $id): NoteResponse
    {
        $note = $this->noteRepository->findById($id);

        if (null === $note) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        return $this->toResponse($note);
    }

    public function list(ListNotesRequest $request): PaginatedNotesResponse
    {
        $validNames = array_values(array_filter(
            $request->tags,
            fn (string $n) => null !== $this->tagRepository->findByName($n),
        ));

        $result = $this->noteRepository->findFilteredPaginated(
            $request->folderId,
            $validNames,
            $request->page,
            self::PER_PAGE,
        );

        $items = array_map(
            fn (Note $note) => $this->toListItemResponse($note),
            $result['items'],
        );

        $totalPages = (int) ceil($result['total'] / self::PER_PAGE);

        return new PaginatedNotesResponse(
            items: $items,
            page: $request->page,
            perPage: self::PER_PAGE,
            total: $result['total'],
            totalPages: max(1, $totalPages),
        );
    }

    public function moveNoteToFolder(Uuid $id, MoveNoteToFolderRequest $request): NoteResponse
    {
        $note = $this->noteRepository->findById($id);
        if (null === $note) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        if (null === $request->folderId) {
            $note->setFolder(null);
        } else {
            $folder = $this->folderRepository->findById($request->folderId);
            if (null === $folder) {
                throw new NotFoundHttpException('Папка не найдена.');
            }
            $note->setFolder($folder);
        }

        $this->noteRepository->save($note);

        return $this->toResponse($note);
    }

    public function toggleFavorite(Note $note): Note
    {
        $note->toggleFavorite();
        $this->noteRepository->save($note);
        return $note;
    }

    public function softDelete(Note $note): Note
    {
        $note->softDelete();
        $this->noteRepository->save($note);
        return $note;
    }

    public function permanentDelete(Uuid $id): void
    {
        $note = $this->noteRepository->findById($id);

        if (null === $note) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        if (!$note->isDeleted()) {
            throw new \DomainException('Нельзя безвозвратно удалить заметку, не находящуюся в корзине.');
        }

        $this->noteRepository->delete($note);
    }

    public function restore(Note $note): Note
    {
        if (!$note->isDeleted()) {
            throw new \DomainException('Заметка не удалена.');
        }
        $note->restore();
        $this->noteRepository->save($note);
        return $note;
    }

    public function getFavorites(int $page = 1, int $limit = 20): PaginatedNotesResponse
    {
        $result = $this->noteRepository->findFavorites($page, $limit);
        $items = array_map(fn (Note $note) => $this->toListItemResponse($note), $result['items']);
        $totalPages = (int) ceil($result['total'] / $limit);

        return new PaginatedNotesResponse(
            items: $items,
            page: $page,
            perPage: $limit,
            total: $result['total'],
            totalPages: max(1, $totalPages),
        );
    }

    public function getTrash(int $page = 1, int $limit = 20): PaginatedNotesResponse
    {
        $result = $this->noteRepository->findTrash($page, $limit);
        $items = array_map(fn (Note $note) => $this->toListItemResponse($note), $result['items']);
        $totalPages = (int) ceil($result['total'] / $limit);

        return new PaginatedNotesResponse(
            items: $items,
            page: $page,
            perPage: $limit,
            total: $result['total'],
            totalPages: max(1, $totalPages),
        );
    }

    public function emptyTrash(): int
    {
        return $this->noteRepository->deleteExpiredTrash(new \DateTimeImmutable('now - 30 days'));
    }

    private function toResponse(Note $note): NoteResponse
    {
        return new NoteResponse(
            id: (string) $note->getId(),
            title: $note->getTitle(),
            content: $note->getContent(),
            createdAt: $note->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $note->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            folderId: $note->getFolderId(),
            folderName: $note->getFolder()?->getName(),
            tags: array_map(
                static fn (Tag $t) => TagResponse::fromEntity($t),
                $note->getTags()->toArray(),
            ),
            isFavorite: $note->isFavorite(),
            deletedAt: $note->getDeletedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    private function toListItemResponse(Note $note): NoteListItemResponse
    {
        return new NoteListItemResponse(
            id: (string) $note->getId(),
            title: $note->getTitle(),
            preview: $this->generatePreview($note->getContent()),
            createdAt: $note->getCreatedAt()->format(\DateTimeInterface::ATOM),
            folderId: $note->getFolderId(),
            tags: array_map(
                static fn (Tag $t) => TagResponse::fromEntity($t),
                $note->getTags()->toArray(),
            ),
            isFavorite: $note->isFavorite(),
            deletedAt: $note->getDeletedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    /**
     * @param string[] $tagNames
     */
    private function syncTags(Note $note, array $tagNames): void
    {
        $note->clearTags();
        foreach ($tagNames as $name) {
            $tag = $this->tagRepository->findByName($name);
            if (null === $tag) {
                $tag = new Tag($name);
                $this->tagRepository->save($tag);
            }
            $note->addTag($tag);
        }
    }

    private function generatePreview(?string $content): ?string
    {
        if (null === $content || '' === $content) {
            return null;
        }

        // Strip Markdown markup to plain text
        $plain = preg_replace('/[#*_`~\[\]()>!-]+/', '', $content) ?? $content;
        $plain = preg_replace('/\s+/', ' ', trim($plain)) ?? $plain;

        return mb_substr($plain, 0, 100);
    }
}
