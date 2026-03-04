<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Response\NoteResponse;
use App\Application\DTO\Response\NoteVersionResponse;
use App\Application\DTO\Response\PaginatedNoteVersionsResponse;
use App\Application\DTO\Response\TagResponse;
use App\Domain\Entity\Note;
use App\Domain\Entity\NoteVersion;
use App\Domain\Entity\Tag;
use App\Domain\Repository\NoteRepositoryInterface;
use App\Domain\Repository\NoteVersionRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class NoteVersionService
{
    public function __construct(
        private readonly NoteRepositoryInterface $noteRepository,
        private readonly NoteVersionRepositoryInterface $versionRepository,
    ) {
    }

    public function getVersions(Uuid $noteId, int $page, int $perPage): PaginatedNoteVersionsResponse
    {
        $note = $this->fetchNote($noteId);

        $result = $this->versionRepository->findByNoteIdPaginated($noteId, $page, $perPage);

        $items = array_map(
            fn (NoteVersion $v) => $this->toVersionResponse($v),
            $result['items'],
        );

        $totalPages = $result['total'] > 0 ? (int) ceil($result['total'] / $perPage) : 1;

        return new PaginatedNoteVersionsResponse(
            items: $items,
            total: $result['total'],
            page: $page,
            perPage: $perPage,
            totalPages: max(1, $totalPages),
        );
    }

    public function getVersion(Uuid $noteId, Uuid $versionId): NoteVersionResponse
    {
        $this->fetchNote($noteId);

        $version = $this->versionRepository->findById($versionId);
        if (null === $version) {
            throw new NotFoundHttpException('Version not found.');
        }

        return $this->toVersionResponse($version);
    }

    public function revert(Uuid $noteId, Uuid $versionId): NoteResponse
    {
        $note = $this->noteRepository->findById($noteId);
        if (null === $note) {
            throw new NotFoundHttpException('Note not found.');
        }
        if (null !== $note->getDeletedAt()) {
            throw new ConflictHttpException('Cannot revert a deleted note.');
        }

        $version = $this->versionRepository->findById($versionId);
        if (null === $version || (string) $version->getNote()->getId() !== (string) $noteId) {
            throw new NotFoundHttpException('Version not found.');
        }

        $nextNum = $this->versionRepository->countByNoteId($noteId) + 1;
        $snapshot = new NoteVersion($note, $note->getTitle(), $note->getContent(), $nextNum);
        $this->versionRepository->save($snapshot);

        $note->setTitle($version->getTitle());
        $note->setContent($version->getContent());
        $this->noteRepository->save($note);

        return $this->toNoteResponse($note);
    }

    private function fetchNote(Uuid $noteId): Note
    {
        $note = $this->noteRepository->findById($noteId);
        if (null === $note || null !== $note->getDeletedAt()) {
            throw new NotFoundHttpException('Note not found.');
        }

        return $note;
    }

    private function toVersionResponse(NoteVersion $version): NoteVersionResponse
    {
        return new NoteVersionResponse(
            id: (string) $version->getId(),
            noteId: (string) $version->getNote()->getId(),
            title: $version->getTitle(),
            content: $version->getContent(),
            versionNumber: $version->getVersionNumber(),
            createdAt: $version->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    private function toNoteResponse(Note $note): NoteResponse
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
}
