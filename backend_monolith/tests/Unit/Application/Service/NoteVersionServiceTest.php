<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Response\NoteVersionResponse;
use App\Application\DTO\Response\PaginatedNoteVersionsResponse;
use App\Application\Service\NoteVersionService;
use App\Domain\Entity\Note;
use App\Domain\Entity\NoteVersion;
use App\Domain\Repository\NoteRepositoryInterface;
use App\Domain\Repository\NoteVersionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class NoteVersionServiceTest extends TestCase
{
    private NoteRepositoryInterface&MockObject $noteRepository;
    private NoteVersionRepositoryInterface&MockObject $versionRepository;
    private NoteVersionService $service;

    protected function setUp(): void
    {
        $this->noteRepository = $this->createMock(NoteRepositoryInterface::class);
        $this->versionRepository = $this->createMock(NoteVersionRepositoryInterface::class);
        $this->service = new NoteVersionService($this->noteRepository, $this->versionRepository);
    }

    private function makeNote(string $title = 'Title', ?string $content = null): Note
    {
        $note = new Note($title, $content);
        $reflection = new \ReflectionClass($note);
        $reflection->getMethod('initTimestamps')->invoke($note);

        return $note;
    }

    private function makeVersion(Note $note, int $num = 1): NoteVersion
    {
        return new NoteVersion($note, $note->getTitle(), $note->getContent(), $num);
    }

    // ── US1: getVersions ─────────────────────────────────────────────────────

    public function testGetVersionsReturnsPaginatedList(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote('My Note', 'Content');

        $this->noteRepository->method('findById')->with($noteId)->willReturn($note);

        $v1 = $this->makeVersion($note, 1);
        $v2 = $this->makeVersion($note, 2);

        $this->versionRepository
            ->method('findByNoteIdPaginated')
            ->with($noteId, 1, 20)
            ->willReturn(['items' => [$v2, $v1], 'total' => 2]);

        $result = $this->service->getVersions($noteId, 1, 20);

        $this->assertInstanceOf(PaginatedNoteVersionsResponse::class, $result);
        $this->assertSame(2, $result->total);
        $this->assertSame(1, $result->page);
        $this->assertSame(20, $result->perPage);
        $this->assertSame(1, $result->totalPages);
        $this->assertCount(2, $result->items);
        $this->assertInstanceOf(NoteVersionResponse::class, $result->items[0]);
    }

    public function testGetVersionsThrows404ForUnknownNote(): void
    {
        $noteId = Uuid::v7();
        $this->noteRepository->method('findById')->with($noteId)->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Note not found.');

        $this->service->getVersions($noteId, 1, 20);
    }

    public function testGetVersionsThrows404ForDeletedNote(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote('Title');
        $note->softDelete();

        $this->noteRepository->method('findById')->with($noteId)->willReturn($note);

        $this->expectException(NotFoundHttpException::class);

        $this->service->getVersions($noteId, 1, 20);
    }

    // ── US2: getVersion ──────────────────────────────────────────────────────

    public function testGetVersionReturnsVersionResponse(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote('Note', 'Content');

        $this->noteRepository->method('findById')->with($noteId)->willReturn($note);

        $version = $this->makeVersion($note, 1);
        $this->versionRepository->method('findById')->willReturn($version);

        $result = $this->service->getVersion($noteId, $version->getId());

        $this->assertInstanceOf(NoteVersionResponse::class, $result);
        $this->assertSame('Note', $result->title);
        $this->assertSame('Content', $result->content);
        $this->assertSame(1, $result->versionNumber);
    }

    public function testGetVersionThrows404ForUnknownNote(): void
    {
        $noteId = Uuid::v7();
        $this->noteRepository->method('findById')->with($noteId)->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Note not found.');

        $this->service->getVersion($noteId, Uuid::v7());
    }

    public function testGetVersionThrows404ForUnknownVersion(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote('Title');

        $this->noteRepository->method('findById')->with($noteId)->willReturn($note);
        $this->versionRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Version not found.');

        $this->service->getVersion($noteId, Uuid::v7());
    }

    // ── US3: revert ──────────────────────────────────────────────────────────

    public function testRevertRestoresNoteAndSavesPreRevertSnapshot(): void
    {
        $note = $this->makeNote('Current Title', 'Current Content');
        $noteId = $note->getId();

        $this->noteRepository->method('findById')->with($noteId)->willReturn($note);

        $oldVersion = new NoteVersion($note, 'Old Title', 'Old Content', 1);
        $this->versionRepository->method('findById')->willReturn($oldVersion);
        $this->versionRepository->method('countByNoteId')->willReturn(1);

        $savedSnapshot = null;
        $this->versionRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (NoteVersion $v) use (&$savedSnapshot): void {
                $savedSnapshot = $v;
            });

        $savedNote = null;
        $this->noteRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (Note $n) use (&$savedNote): void {
                $savedNote = $n;
            });

        $this->service->revert($note->getId(), $oldVersion->getId());

        $this->assertNotNull($savedSnapshot);
        $this->assertSame('Current Title', $savedSnapshot->getTitle());
        $this->assertSame('Current Content', $savedSnapshot->getContent());
        $this->assertSame(2, $savedSnapshot->getVersionNumber());

        $this->assertNotNull($savedNote);
        $this->assertSame('Old Title', $savedNote->getTitle());
        $this->assertSame('Old Content', $savedNote->getContent());
    }

    public function testRevertThrows404ForUnknownNote(): void
    {
        $noteId = Uuid::v7();
        $this->noteRepository->method('findById')->with($noteId)->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Note not found.');

        $this->service->revert($noteId, Uuid::v7());
    }

    public function testRevertThrows404ForUnknownVersion(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote('Title');

        $this->noteRepository->method('findById')->with($noteId)->willReturn($note);
        $this->versionRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Version not found.');

        $this->service->revert($noteId, Uuid::v7());
    }

    public function testRevertThrows409ForDeletedNote(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote('Title');
        $note->softDelete();

        $this->noteRepository->method('findById')->with($noteId)->willReturn($note);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\ConflictHttpException::class);
        $this->expectExceptionMessage('Cannot revert a deleted note.');

        $this->service->revert($noteId, Uuid::v7());
    }
}
