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
use App\Domain\Entity\Note;
use App\Domain\Repository\FolderRepositoryInterface;
use App\Domain\Repository\NoteRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class NoteService
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly NoteRepositoryInterface $noteRepository,
        private readonly FolderRepositoryInterface $folderRepository,
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

        $this->noteRepository->save($note);

        return $this->toResponse($note);
    }

    public function delete(Uuid $id): void
    {
        $note = $this->noteRepository->findById($id);

        if (null === $note) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        $this->noteRepository->delete($note);
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
        $result = null !== $request->folderId
            ? $this->noteRepository->findByFolderIdPaginated($request->folderId, $request->page, self::PER_PAGE)
            : $this->noteRepository->findPaginated($request->page, self::PER_PAGE);

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
        );
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
