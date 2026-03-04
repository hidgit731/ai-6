<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateNoteRequest;
use App\Application\DTO\Request\UpdateNoteRequest;
use App\Application\Service\NoteService;
use App\Domain\Entity\Note;
use App\Domain\Entity\Tag;
use App\Domain\Repository\FolderRepositoryInterface;
use App\Domain\Repository\NoteRepositoryInterface;
use App\Domain\Repository\NoteVersionRepositoryInterface;
use App\Domain\Repository\TagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class NoteServiceTagTest extends TestCase
{
    private NoteRepositoryInterface&MockObject $noteRepository;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private TagRepositoryInterface&MockObject $tagRepository;
    private NoteService $service;

    protected function setUp(): void
    {
        $this->noteRepository = $this->createMock(NoteRepositoryInterface::class);
        $this->folderRepository = $this->createMock(FolderRepositoryInterface::class);
        $this->tagRepository = $this->createMock(TagRepositoryInterface::class);
        $this->service = new NoteService(
            $this->noteRepository,
            $this->folderRepository,
            $this->tagRepository,
            $this->createMock(NoteVersionRepositoryInterface::class),
        );
    }

    public function testCreateWithTagsSyncsTagsToNote(): void
    {
        $workTag = $this->makeTag('work');
        $urgentTag = $this->makeTag('urgent');

        $this->tagRepository
            ->method('findByName')
            ->willReturnMap([
                ['work', $workTag],
                ['urgent', null],
            ]);

        $this->tagRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Tag::class))
            ->willReturnCallback(static function (Tag $tag): void {
                $reflection = new \ReflectionClass($tag);
                $reflection->getMethod('initTimestamps')->invoke($tag);
            });

        $this->noteRepository
            ->method('save')
            ->willReturnCallback(static function (Note $note): void {
                $reflection = new \ReflectionClass($note);
                $reflection->getMethod('initTimestamps')->invoke($note);
            });

        $this->noteRepository
            ->method('findFilteredPaginated')
            ->willReturn(['items' => [], 'total' => 0]);

        $request = new CreateNoteRequest(title: 'Test', tags: ['work', 'urgent']);
        $response = $this->service->create($request);

        $this->assertCount(2, $response->tags);
    }

    public function testUpdateWithTagsReplacesExistingTags(): void
    {
        $id = Uuid::v7();
        $note = $this->makeNote('Old Title');
        $workTag = $this->makeTag('work');

        $this->noteRepository
            ->method('findById')
            ->with($id)
            ->willReturn($note);

        $this->tagRepository
            ->method('findByName')
            ->with('work')
            ->willReturn($workTag);

        $this->noteRepository
            ->method('save')
            ->willReturnCallback(static function (Note $note): void {
                // no-op (timestamps already initialized)
            });

        $request = new UpdateNoteRequest(title: 'New Title', tags: ['work']);
        $response = $this->service->update($id, $request);

        $this->assertSame('New Title', $response->title);
        $this->assertCount(1, $response->tags);
        $this->assertSame('work', $response->tags[0]->name);
    }

    public function testUpdateWithEmptyTagsRemovesAllTags(): void
    {
        $id = Uuid::v7();
        $note = $this->makeNote('Title');
        $existingTag = $this->makeTag('work');
        $note->addTag($existingTag);

        $this->noteRepository
            ->method('findById')
            ->with($id)
            ->willReturn($note);

        $this->tagRepository->expects($this->never())->method('findByName');
        $this->noteRepository->method('save');

        $request = new UpdateNoteRequest(title: 'Title', tags: []);
        $response = $this->service->update($id, $request);

        $this->assertCount(0, $response->tags);
    }

    private function makeNote(string $title): Note
    {
        $note = new Note($title);
        $reflection = new \ReflectionClass($note);
        $reflection->getMethod('initTimestamps')->invoke($note);

        return $note;
    }

    private function makeTag(string $name): Tag
    {
        $tag = new Tag($name);
        $reflection = new \ReflectionClass($tag);
        $reflection->getMethod('initTimestamps')->invoke($tag);

        return $tag;
    }
}
