<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Response\GraphEdgeDTO;
use App\Application\DTO\Response\GraphNodeDTO;
use App\Application\DTO\Response\GraphResponse;
use App\Application\DTO\Response\NoteLinksResponse;
use App\Application\DTO\Response\NoteRefDTO;
use App\Domain\Entity\NoteLink;
use App\Domain\Repository\NoteLinkRepositoryInterface;
use App\Domain\Repository\NoteRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class NoteLinkService
{
    public function __construct(
        private readonly NoteLinkRepositoryInterface $noteLinkRepository,
        private readonly NoteRepositoryInterface $noteRepository,
    ) {
    }

    /**
     * Extracts [[Title]] patterns from content, excluding fenced code blocks and inline code spans.
     *
     * @return string[] unique titles found
     */
    public function extractWikiLinkTitles(string $content): array
    {
        // Step 1: mask fenced code blocks (```...```)
        $masked = preg_replace('/```[\s\S]*?```/u', '', $content) ?? $content;

        // Step 2: mask inline code spans (`...`)
        $masked = preg_replace('/`[^`]*`/u', '', $masked) ?? $masked;

        // Step 3: find all [[Title]] patterns
        preg_match_all('/\[\[([^\[\]]+)\]\]/u', $masked, $matches);

        // Step 4: return unique titles
        return array_values(array_unique($matches[1]));
    }

    public function extractAndSyncLinks(mixed $note): void
    {
        $titles = $this->extractWikiLinkTitles($note->getContent() ?? '');

        $this->noteLinkRepository->deleteBySourceNote($note->getId());

        $seen = [];
        foreach ($titles as $title) {
            if ($title === $note->getTitle()) {
                continue;
            }

            $target = $this->noteRepository->findByTitle($title);
            if (null === $target) {
                continue;
            }

            $targetId = (string) $target->getId();
            if (isset($seen[$targetId])) {
                continue;
            }

            $seen[$targetId] = true;
            $this->noteLinkRepository->save(new NoteLink($note, $target));
        }
    }

    public function getLinks(Uuid $noteId): NoteLinksResponse
    {
        $note = $this->noteRepository->findById($noteId);
        if (null === $note) {
            throw new NotFoundHttpException('Заметка не найдена.');
        }

        $outgoingLinks = $this->noteLinkRepository->findOutgoing($noteId);
        $incomingLinks = $this->noteLinkRepository->findIncoming($noteId);

        $outgoing = array_map(
            static fn (NoteLink $link) => new NoteRefDTO(
                (string) $link->getTargetNote()->getId(),
                $link->getTargetNote()->getTitle(),
            ),
            $outgoingLinks,
        );

        $incoming = array_map(
            static fn (NoteLink $link) => new NoteRefDTO(
                (string) $link->getSourceNote()->getId(),
                $link->getSourceNote()->getTitle(),
            ),
            $incomingLinks,
        );

        return new NoteLinksResponse($incoming, $outgoing);
    }

    public function getGraph(): GraphResponse
    {
        $notes = $this->noteRepository->findAll();
        $links = $this->noteLinkRepository->findAll();

        $nodes = array_map(
            static fn ($note) => new GraphNodeDTO((string) $note->getId(), $note->getTitle()),
            $notes,
        );

        $edges = array_map(
            static fn (NoteLink $link) => new GraphEdgeDTO(
                (string) $link->getSourceNote()->getId(),
                (string) $link->getTargetNote()->getId(),
            ),
            $links,
        );

        return new GraphResponse($nodes, $edges);
    }
}
