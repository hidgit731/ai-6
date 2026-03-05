<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Response\ExportedFile;
use App\Application\Service\ExportService;
use App\Domain\Entity\Note;
use App\Domain\Repository\NoteRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class ExportServiceTest extends TestCase
{
    private NoteRepositoryInterface&MockObject $noteRepository;
    private ExportService $service;

    protected function setUp(): void
    {
        $this->noteRepository = $this->createMock(NoteRepositoryInterface::class);
        $this->service = new ExportService($this->noteRepository);
    }

    // ---- exportMarkdown ----

    public function testExportMarkdownReturnsExportedFileWithCorrectContent(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote($noteId, 'My Title', '## Hello\n\nWorld');

        $this->noteRepository->method('findById')->with($noteId)->willReturn($note);

        $result = $this->service->exportMarkdown($noteId->toRfc4122());

        $this->assertInstanceOf(ExportedFile::class, $result);
        $this->assertSame('text/markdown; charset=UTF-8', $result->mimeType);
        $this->assertStringEndsWith('.md', $result->filename);
        $this->assertStringStartsWith('# My Title', $result->content);
        $this->assertStringContainsString('## Hello', $result->content);
    }

    public function testExportMarkdownSanitizesFilename(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote($noteId, 'My/Note: File*Name', 'content');

        $this->noteRepository->method('findById')->willReturn($note);

        $result = $this->service->exportMarkdown($noteId->toRfc4122());

        $this->assertStringNotContainsString('/', $result->filename);
        $this->assertStringNotContainsString(':', $result->filename);
        $this->assertStringNotContainsString('*', $result->filename);
        $this->assertStringEndsWith('.md', $result->filename);
    }

    public function testExportMarkdownThrowsNotFoundForNonExistentNote(): void
    {
        $this->noteRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->service->exportMarkdown(Uuid::v7()->toRfc4122());
    }

    public function testExportMarkdownHandlesEmptyContent(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote($noteId, 'Only Title', '');

        $this->noteRepository->method('findById')->willReturn($note);

        $result = $this->service->exportMarkdown($noteId->toRfc4122());

        $this->assertStringStartsWith('# Only Title', $result->content);
    }

    // ---- exportPdf ----

    public function testExportPdfReturnsExportedFileWithPdfMimeType(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote($noteId, 'PDF Note', '## Section\n\nSome content here.');

        $this->noteRepository->method('findById')->willReturn($note);

        $result = $this->service->exportPdf($noteId->toRfc4122());

        $this->assertInstanceOf(ExportedFile::class, $result);
        $this->assertSame('application/pdf', $result->mimeType);
        $this->assertStringEndsWith('.pdf', $result->filename);
        $this->assertNotEmpty($result->content);
    }

    public function testExportPdfThrowsNotFoundForNonExistentNote(): void
    {
        $this->noteRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->service->exportPdf(Uuid::v7()->toRfc4122());
    }

    public function testExportPdfStripsWikiLinkSyntax(): void
    {
        $noteId = Uuid::v7();
        $note = $this->makeNote($noteId, 'Wiki Note', 'See [[Other Note]] and [[Another]] for more.');

        $this->noteRepository->method('findById')->willReturn($note);

        $result = $this->service->exportPdf($noteId->toRfc4122());

        // PDF content is binary; we verify no exception was thrown and file is valid
        $this->assertSame('application/pdf', $result->mimeType);
        $this->assertNotEmpty($result->content);
    }

    // ---- helper ----

    private function makeNote(Uuid $id, string $title, string $content): Note
    {
        $note = new Note($title, $content);

        $reflection = new \ReflectionClass($note);
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($note, $id);

        return $note;
    }
}
