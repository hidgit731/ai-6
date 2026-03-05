<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Response\GraphResponse;
use App\Application\DTO\Response\NoteLinksResponse;
use App\Application\Service\NoteLinkService;
use App\Domain\Entity\Note;
use App\Domain\Entity\NoteLink;
use App\Domain\Repository\NoteLinkRepositoryInterface;
use App\Domain\Repository\NoteRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class NoteLinkServiceTest extends TestCase
{
    private NoteLinkRepositoryInterface&MockObject $noteLinkRepository;
    private NoteRepositoryInterface&MockObject $noteRepository;
    private NoteLinkService $service;

    protected function setUp(): void
    {
        $this->noteLinkRepository = $this->createMock(NoteLinkRepositoryInterface::class);
        $this->noteRepository = $this->createMock(NoteRepositoryInterface::class);
        $this->service = new NoteLinkService($this->noteLinkRepository, $this->noteRepository);
    }

    private function makeNote(string $title = 'Title', ?string $content = null): Note
    {
        $note = new Note($title, $content);
        $reflection = new \ReflectionClass($note);
        $reflection->getMethod('initTimestamps')->invoke($note);

        return $note;
    }

    // ── extractWikiLinkTitles ─────────────────────────────────────────────────

    public function testExtractsSingleWikiLink(): void
    {
        $titles = $this->service->extractWikiLinkTitles('Hello [[Target Note]] world');
        $this->assertSame(['Target Note'], $titles);
    }

    public function testExtractsMultipleWikiLinks(): void
    {
        $titles = $this->service->extractWikiLinkTitles('See [[Alpha]] and [[Beta]]');
        $this->assertSame(['Alpha', 'Beta'], $titles);
    }

    public function testDeduplicatesTitles(): void
    {
        $titles = $this->service->extractWikiLinkTitles('[[Alpha]] and [[Alpha]] again');
        $this->assertSame(['Alpha'], $titles);
    }

    public function testEmptyContentReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->service->extractWikiLinkTitles(''));
        $this->assertSame([], $this->service->extractWikiLinkTitles('No links here'));
    }

    public function testSkipsFencedCodeBlock(): void
    {
        $content = "Normal text\n```\n[[InsideCode]]\n```\nAfter";
        $titles = $this->service->extractWikiLinkTitles($content);
        $this->assertSame([], $titles);
    }

    public function testSkipsInlineCode(): void
    {
        $content = 'Use `[[InlineCode]]` for this';
        $titles = $this->service->extractWikiLinkTitles($content);
        $this->assertSame([], $titles);
    }

    public function testExtractsLinksOutsideCodeBlock(): void
    {
        $content = "[[ValidLink]]\n```\n[[InsideCode]]\n```\n[[AnotherValid]]";
        $titles = $this->service->extractWikiLinkTitles($content);
        $this->assertSame(['ValidLink', 'AnotherValid'], $titles);
    }

    public function testDoesNotMatchNestedBrackets(): void
    {
        $titles = $this->service->extractWikiLinkTitles('[[[bad]]] and [[good]]');
        $this->assertContains('good', $titles);
        $this->assertNotContains('[bad]', $titles);
    }

    // ── extractAndSyncLinks ───────────────────────────────────────────────────

    public function testSyncLinksCreatesLink(): void
    {
        $source = $this->makeNote('Source', 'See [[Target]]');
        $target = $this->makeNote('Target');

        $this->noteRepository->method('findByTitle')
            ->with('Target')
            ->willReturn($target);

        $this->noteLinkRepository->expects($this->once())
            ->method('deleteBySourceNote')
            ->with($source->getId());

        $this->noteLinkRepository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(NoteLink::class));

        $this->service->extractAndSyncLinks($source);
    }

    public function testSyncLinksSkipsSelfReference(): void
    {
        $note = $this->makeNote('Self', 'I reference [[Self]]');

        $this->noteLinkRepository->expects($this->once())
            ->method('deleteBySourceNote');

        $this->noteLinkRepository->expects($this->never())
            ->method('save');

        $this->service->extractAndSyncLinks($note);
    }

    public function testSyncLinksSkipsUnresolvableTitle(): void
    {
        $note = $this->makeNote('Source', 'See [[NonExistent]]');

        $this->noteRepository->method('findByTitle')->willReturn(null);

        $this->noteLinkRepository->expects($this->once())
            ->method('deleteBySourceNote');

        $this->noteLinkRepository->expects($this->never())
            ->method('save');

        $this->service->extractAndSyncLinks($note);
    }

    public function testSyncLinksDeduplicatesRepeatedTitles(): void
    {
        $source = $this->makeNote('Source', '[[Alpha]] and [[Alpha]] again');
        $target = $this->makeNote('Alpha');

        $this->noteRepository->method('findByTitle')->willReturn($target);

        $this->noteLinkRepository->expects($this->exactly(1))
            ->method('save');

        $this->service->extractAndSyncLinks($source);
    }

    // ── getLinks ─────────────────────────────────────────────────────────────

    public function testGetLinksThrows404ForMissingNote(): void
    {
        $this->noteRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->service->getLinks(Uuid::v7());
    }

    public function testGetLinksReturnsResponse(): void
    {
        $note = $this->makeNote('Note A');
        $this->noteRepository->method('findById')->willReturn($note);
        $this->noteLinkRepository->method('findOutgoing')->willReturn([]);
        $this->noteLinkRepository->method('findIncoming')->willReturn([]);

        $response = $this->service->getLinks($note->getId());

        $this->assertInstanceOf(NoteLinksResponse::class, $response);
        $this->assertSame([], $response->incoming);
        $this->assertSame([], $response->outgoing);
    }

    // ── getGraph ─────────────────────────────────────────────────────────────

    public function testGetGraphReturnsEmptyForNoNotes(): void
    {
        $this->noteRepository->method('findAll')->willReturn([]);
        $this->noteLinkRepository->method('findAll')->willReturn([]);

        $response = $this->service->getGraph();

        $this->assertInstanceOf(GraphResponse::class, $response);
        $this->assertSame([], $response->nodes);
        $this->assertSame([], $response->edges);
    }

    public function testGetGraphReturnsNodesAndEdges(): void
    {
        $source = $this->makeNote('Source');
        $target = $this->makeNote('Target');
        $link = new NoteLink($source, $target);

        $this->noteRepository->method('findAll')->willReturn([$source, $target]);
        $this->noteLinkRepository->method('findAll')->willReturn([$link]);

        $response = $this->service->getGraph();

        $this->assertCount(2, $response->nodes);
        $this->assertCount(1, $response->edges);
        $this->assertSame((string) $source->getId(), $response->edges[0]->source);
        $this->assertSame((string) $target->getId(), $response->edges[0]->target);
    }
}
