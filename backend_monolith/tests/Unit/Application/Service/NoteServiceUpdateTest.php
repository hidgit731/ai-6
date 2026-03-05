<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\UpdateNoteRequest;
use App\Application\DTO\Response\NoteResponse;
use App\Application\Service\NoteLinkService;
use App\Application\Service\NoteService;
use App\Domain\Entity\Note;
use App\Domain\Entity\NoteVersion;
use App\Domain\Repository\FolderRepositoryInterface;
use App\Domain\Repository\NoteRepositoryInterface;
use App\Domain\Repository\NoteVersionRepositoryInterface;
use App\Domain\Repository\TagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class NoteServiceUpdateTest extends TestCase
{
    private NoteRepositoryInterface&MockObject $repository;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private NoteVersionRepositoryInterface&MockObject $versionRepository;
    private NoteService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(NoteRepositoryInterface::class);
        $this->folderRepository = $this->createMock(FolderRepositoryInterface::class);
        $this->versionRepository = $this->createMock(NoteVersionRepositoryInterface::class);
        $this->service = new NoteService(
            $this->repository,
            $this->folderRepository,
            $this->createMock(TagRepositoryInterface::class),
            $this->versionRepository,
            $this->createMock(NoteLinkService::class),
        );
    }

    private function makeNote(string $title, ?string $content = null): Note
    {
        $note = new Note($title, $content);
        $reflection = new \ReflectionClass($note);
        $reflection->getMethod('initTimestamps')->invoke($note);

        return $note;
    }

    public function testUpdateExistingNoteReturnsUpdatedResponse(): void
    {
        $id = Uuid::v7();
        $note = $this->makeNote('Old Title', 'Old Content');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($note);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($note);

        $request = new UpdateNoteRequest(title: 'New Title', content: 'New Content');
        $response = $this->service->update($id, $request);

        $this->assertInstanceOf(NoteResponse::class, $response);
        $this->assertSame('New Title', $response->title);
        $this->assertSame('New Content', $response->content);
    }

    public function testUpdateSavesVersionBeforeApplyingChanges(): void
    {
        $id = Uuid::v7();
        $note = $this->makeNote('Old Title', 'Old Content');

        $this->repository
            ->method('findById')
            ->with($id)
            ->willReturn($note);

        $this->versionRepository
            ->method('countByNoteId')
            ->willReturn(0);

        $capturedVersion = null;
        $this->versionRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (NoteVersion $version) use (&$capturedVersion): void {
                $capturedVersion = $version;
            });

        $noteAfterVersionSave = null;
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(static function (Note $n) use (&$noteAfterVersionSave): void {
                $noteAfterVersionSave = $n;
            });

        $request = new UpdateNoteRequest(title: 'New Title', content: 'New Content');
        $this->service->update($id, $request);

        $this->assertNotNull($capturedVersion);
        $this->assertSame('Old Title', $capturedVersion->getTitle());
        $this->assertSame('Old Content', $capturedVersion->getContent());
        $this->assertSame(1, $capturedVersion->getVersionNumber());

        $this->assertNotNull($noteAfterVersionSave);
        $this->assertSame('New Title', $noteAfterVersionSave->getTitle());
        $this->assertSame('New Content', $noteAfterVersionSave->getContent());
    }

    public function testUpdateNonExistentNoteThrowsNotFoundException(): void
    {
        $id = Uuid::v7();

        $this->repository
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Заметка не найдена.');

        $request = new UpdateNoteRequest(title: 'Title', content: null);
        $this->service->update($id, $request);
    }
}
