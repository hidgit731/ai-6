<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Request\CreateNoteRequest;
use App\Application\DTO\Request\ListNotesRequest;
use App\Application\DTO\Request\UpdateNoteRequest;
use App\Application\DTO\Response\NoteListItemResponse;
use App\Application\DTO\Response\NoteResponse;
use App\Application\DTO\Response\PaginatedNotesResponse;
use App\Domain\Entity\Note;
use App\Domain\Repository\NoteRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class NoteService
{
    private const PER_PAGE = 10;

    public function __construct(private readonly NoteRepositoryInterface $noteRepository)
    {
    }

    public function create(CreateNoteRequest $request): NoteResponse
    {
        $note = new Note($request->title, $request->content);
        $this->noteRepository->save($note);

        return $this->toResponse($note);
    }

    public function update(Uuid $id, UpdateNoteRequest $request): NoteResponse
    {
        $note = $this->noteRepository->findById($id);

        if (null === $note) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        $note->setTitle($request->title);
        $note->setContent($request->content);
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
        $result = $this->noteRepository->findPaginated($request->page, self::PER_PAGE);

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

    private function toResponse(Note $note): NoteResponse
    {
        return new NoteResponse(
            id: (string) $note->getId(),
            title: $note->getTitle(),
            content: $note->getContent(),
            createdAt: $note->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $note->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    private function toListItemResponse(Note $note): NoteListItemResponse
    {
        return new NoteListItemResponse(
            id: (string) $note->getId(),
            title: $note->getTitle(),
            preview: $this->generatePreview($note->getContent()),
            createdAt: $note->getCreatedAt()->format(\DateTimeInterface::ATOM),
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
