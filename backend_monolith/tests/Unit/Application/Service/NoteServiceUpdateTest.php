<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\UpdateNoteRequest;
use App\Application\DTO\Response\NoteResponse;
use App\Application\Service\NoteService;
use App\Domain\Entity\Note;
use App\Domain\Repository\FolderRepositoryInterface;
use App\Domain\Repository\NoteRepositoryInterface;
use App\Domain\Repository\TagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class NoteServiceUpdateTest extends TestCase
{
    private NoteRepositoryInterface&MockObject $repository;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private NoteService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(NoteRepositoryInterface::class);
        $this->folderRepository = $this->createMock(FolderRepositoryInterface::class);
        $this->service = new NoteService(
            $this->repository,
            $this->folderRepository,
            $this->createMock(TagRepositoryInterface::class),
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
