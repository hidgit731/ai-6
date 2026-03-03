<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateNoteRequest;
use App\Application\DTO\Response\NoteResponse;
use App\Application\Service\NoteService;
use App\Domain\Entity\Note;
use App\Domain\Repository\NoteRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NoteServiceCreateTest extends TestCase
{
    private NoteRepositoryInterface&MockObject $repository;
    private NoteService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(NoteRepositoryInterface::class);
        $this->service = new NoteService($this->repository);
    }

    public function testCreateWithValidDataReturnsNoteResponse(): void
    {
        $request = new CreateNoteRequest(
            title: 'Test Note',
            content: '# Hello\n\nWorld',
        );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Note::class))
            ->willReturnCallback(static function (Note $note): void {
                // Simulate lifecycle callback
                $reflection = new \ReflectionClass($note);
                $initMethod = $reflection->getMethod('initTimestamps');
                $initMethod->invoke($note);
            });

        $response = $this->service->create($request);

        $this->assertInstanceOf(NoteResponse::class, $response);
        $this->assertSame('Test Note', $response->title);
        $this->assertSame('# Hello\n\nWorld', $response->content);
        $this->assertNotEmpty($response->id);
        $this->assertNotEmpty($response->createdAt);
        $this->assertNotEmpty($response->updatedAt);
    }

    public function testCreateWithNullContentReturnsNoteResponseWithNullContent(): void
    {
        $request = new CreateNoteRequest(title: 'Empty Note');

        $this->repository
            ->method('save')
            ->willReturnCallback(static function (Note $note): void {
                $reflection = new \ReflectionClass($note);
                $initMethod = $reflection->getMethod('initTimestamps');
                $initMethod->invoke($note);
            });

        $response = $this->service->create($request);

        $this->assertInstanceOf(NoteResponse::class, $response);
        $this->assertSame('Empty Note', $response->title);
        $this->assertNull($response->content);
    }
}
