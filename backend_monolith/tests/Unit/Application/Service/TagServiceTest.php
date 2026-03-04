<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\TagService;
use App\Domain\Entity\Tag;
use App\Domain\Repository\TagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TagServiceTest extends TestCase
{
    private TagRepositoryInterface&MockObject $tagRepository;
    private TagService $service;

    protected function setUp(): void
    {
        $this->tagRepository = $this->createMock(TagRepositoryInterface::class);
        $this->service = new TagService($this->tagRepository);
    }

    public function testFindOrCreateReturnsExistingTag(): void
    {
        $tag = $this->makeTag('work');

        $this->tagRepository
            ->expects($this->once())
            ->method('findByName')
            ->with('work')
            ->willReturn($tag);

        $this->tagRepository->expects($this->never())->method('save');

        $result = $this->service->findOrCreate('work');

        $this->assertSame($tag, $result);
    }

    public function testFindOrCreateCreatesNewTag(): void
    {
        $this->tagRepository
            ->expects($this->once())
            ->method('findByName')
            ->with('work')
            ->willReturn(null);

        $this->tagRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Tag::class));

        $result = $this->service->findOrCreate('work');

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertSame('work', $result->getName());
    }

    public function testSuggestDelegatesToRepository(): void
    {
        $tags = [$this->makeTag('work'), $this->makeTag('workflow')];

        $this->tagRepository
            ->expects($this->once())
            ->method('findByNameContaining')
            ->with('wor')
            ->willReturn($tags);

        $result = $this->service->suggest('wor');

        $this->assertSame($tags, $result);
    }

    public function testCreateSuccessfully(): void
    {
        $this->tagRepository
            ->expects($this->once())
            ->method('findByName')
            ->with('important')
            ->willReturn(null);

        $this->tagRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Tag::class));

        $result = $this->service->create('important');

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertSame('important', $result->getName());
    }

    public function testCreateThrowsDomainExceptionOnDuplicate(): void
    {
        $existingTag = $this->makeTag('important');

        $this->tagRepository
            ->expects($this->once())
            ->method('findByName')
            ->with('important')
            ->willReturn($existingTag);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Тег с таким именем уже существует.');

        $this->service->create('important');
    }

    public function testDeleteSuccessfully(): void
    {
        $id = Uuid::v7();
        $tag = $this->makeTag('work');

        $this->tagRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($tag);

        $this->tagRepository
            ->expects($this->once())
            ->method('delete')
            ->with($tag);

        $this->service->delete($id);
    }

    public function testDeleteThrowsInvalidArgumentExceptionWhenNotFound(): void
    {
        $id = Uuid::v7();

        $this->tagRepository
            ->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Тег не найден.');

        $this->service->delete($id);
    }

    private function makeTag(string $name): Tag
    {
        $tag = new Tag($name);
        $reflection = new \ReflectionClass($tag);
        $reflection->getMethod('initTimestamps')->invoke($tag);

        return $tag;
    }
}
