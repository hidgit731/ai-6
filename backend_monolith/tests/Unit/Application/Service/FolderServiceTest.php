<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\DTO\Request\CreateFolderRequest;
use App\Application\DTO\Request\MoveFolderRequest;
use App\Application\DTO\Request\UpdateFolderRequest;
use App\Application\DTO\Response\FolderResponse;
use App\Application\Service\FolderService;
use App\Domain\Entity\Folder;
use App\Domain\Repository\FolderRepositoryInterface;
use App\Domain\Repository\NoteRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FolderServiceTest extends TestCase
{
    private FolderRepositoryInterface&MockObject $folderRepository;
    private NoteRepositoryInterface&MockObject $noteRepository;
    private FolderService $service;

    protected function setUp(): void
    {
        $this->folderRepository = $this->createMock(FolderRepositoryInterface::class);
        $this->noteRepository = $this->createMock(NoteRepositoryInterface::class);
        $this->service = new FolderService($this->folderRepository, $this->noteRepository);
    }

    // -------------------------------------------------------
    // create()
    // -------------------------------------------------------

    public function testCreateRootFolderSuccess(): void
    {
        $this->folderRepository->method('findAll')->willReturn([]);
        $this->folderRepository->method('findByParentAndName')->willReturn(null);
        $this->folderRepository->method('save')->willReturnCallback(
            static function (Folder $folder): void {
                $reflection = new \ReflectionClass($folder);
                $reflection->getMethod('initTimestamps')->invoke($folder);
            }
        );

        $request = new CreateFolderRequest(name: 'Работа', parentId: null);
        $response = $this->service->create($request);

        $this->assertInstanceOf(FolderResponse::class, $response);
        $this->assertSame('Работа', $response->name);
        $this->assertNull($response->parentId);
    }

    public function testCreateDuplicateThrows409(): void
    {
        $existing = $this->makeFolder('Работа');
        $this->folderRepository->method('findAll')->willReturn([]);
        $this->folderRepository->method('findByParentAndName')->willReturn($existing);

        $this->expectException(\DomainException::class);

        $this->service->create(new CreateFolderRequest(name: 'Работа'));
    }

    public function testCreateExceedsMaxDepthThrows422(): void
    {
        // Build a chain of 5 nested folders (levels 0-4 = depth 4 already)
        $root = $this->makeFolder('L0');
        $l1 = $this->makeFolder('L1', $root);
        $l2 = $this->makeFolder('L2', $l1);
        $l3 = $this->makeFolder('L3', $l2);
        $l4 = $this->makeFolder('L4', $l3); // depth=4

        $all = [$root, $l1, $l2, $l3, $l4];

        $this->folderRepository->method('findAll')->willReturn($all);
        $this->folderRepository->method('findById')->willReturn($l4);
        $this->folderRepository->method('findByParentAndName')->willReturn(null);

        $this->expectException(\OverflowException::class);

        $this->service->create(new CreateFolderRequest(name: 'L5', parentId: $l4->getId()->toRfc4122()));
    }

    // -------------------------------------------------------
    // update()
    // -------------------------------------------------------

    public function testUpdateSuccess(): void
    {
        $folder = $this->makeFolder('Old');
        $this->folderRepository->method('findById')->willReturn($folder);
        $this->folderRepository->method('findByParentAndName')->willReturn(null);
        $this->folderRepository->method('save')->willReturnCallback(
            static function (Folder $f): void {
                $reflection = new \ReflectionClass($f);
                $reflection->getMethod('initTimestamps')->invoke($f);
            }
        );

        $response = $this->service->update($folder->getId()->toRfc4122(), new UpdateFolderRequest(name: 'New'));

        $this->assertSame('New', $response->name);
    }

    public function testUpdateNotFoundThrows404(): void
    {
        $this->folderRepository->method('findById')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->service->update('non-existent-id', new UpdateFolderRequest(name: 'X'));
    }

    // -------------------------------------------------------
    // delete()
    // -------------------------------------------------------

    public function testDeleteEmptyFolderSuccess(): void
    {
        $folder = $this->makeFolder('Empty');
        $this->folderRepository->method('findById')->willReturn($folder);
        $this->folderRepository->expects($this->once())->method('delete')->with($folder);

        $this->service->delete($folder->getId()->toRfc4122(), null);
    }

    public function testDeleteWithContentAndNoStrategyThrows(): void
    {
        $parent = $this->makeFolder('Parent');
        $child = $this->makeFolder('Child', $parent);
        // Inject child into parent's collection via reflection
        $this->injectChild($parent, $child);

        $this->folderRepository->method('findById')->willReturn($parent);

        $this->expectException(\DomainException::class);

        $this->service->delete($parent->getId()->toRfc4122(), null);
    }

    public function testDeleteWithMoveToRootStrategy(): void
    {
        $folder = $this->makeFolder('ToDelete');
        $this->folderRepository->method('findById')->willReturn($folder);
        $this->folderRepository->method('findAll')->willReturn([]);
        $this->folderRepository->expects($this->atLeastOnce())->method('delete');

        $this->service->delete($folder->getId()->toRfc4122(), 'move_to_root');
    }

    public function testDeleteWithDeleteRecursiveStrategy(): void
    {
        $folder = $this->makeFolder('ToDelete');
        $this->folderRepository->method('findById')->willReturn($folder);
        $this->folderRepository->method('findAll')->willReturn([]);
        $this->folderRepository->expects($this->once())->method('delete')->with($folder);

        $this->service->delete($folder->getId()->toRfc4122(), 'delete_recursive');
    }

    // -------------------------------------------------------
    // getTree()
    // -------------------------------------------------------

    public function testGetTreeBuildsCorrectNestedStructure(): void
    {
        $root = $this->makeFolder('Работа');
        $child = $this->makeFolder('Проекты', $root);
        $leaf = $this->makeFolder('Проект А', $child);

        $this->folderRepository->method('findAll')->willReturn([$root, $child, $leaf]);

        $tree = $this->service->getTree();

        $this->assertCount(1, $tree);
        $this->assertSame('Работа', $tree[0]->name);
        $this->assertCount(1, $tree[0]->children);
        $this->assertSame('Проекты', $tree[0]->children[0]->name);
        $this->assertCount(1, $tree[0]->children[0]->children);
        $this->assertSame('Проект А', $tree[0]->children[0]->children[0]->name);
    }

    // -------------------------------------------------------
    // move()
    // -------------------------------------------------------

    public function testMoveFolderSuccess(): void
    {
        $src = $this->makeFolder('Личное');
        $target = $this->makeFolder('Работа');
        $all = [$src, $target];

        $this->folderRepository->method('findById')
            ->willReturnCallback(static fn (string $id) => match (true) {
                $id === $src->getId()->toRfc4122() => $src,
                $id === $target->getId()->toRfc4122() => $target,
                default => null,
            });
        $this->folderRepository->method('findAll')->willReturn($all);
        $this->folderRepository->method('findByParentAndName')->willReturn(null);
        $this->folderRepository->method('save')->willReturnCallback(
            static function (Folder $f): void {
                $reflection = new \ReflectionClass($f);
                $reflection->getMethod('initTimestamps')->invoke($f);
            }
        );

        $request = new MoveFolderRequest(targetParentId: $target->getId()->toRfc4122());
        $response = $this->service->move($src->getId()->toRfc4122(), $request);

        $this->assertSame($target->getId()->toRfc4122(), $response->parentId);
    }

    public function testMoveFolderToTopLevel(): void
    {
        $parent = $this->makeFolder('Parent');
        $child = $this->makeFolder('Child', $parent);
        $all = [$parent, $child];

        $this->folderRepository->method('findById')->willReturn($child);
        $this->folderRepository->method('findAll')->willReturn($all);
        $this->folderRepository->method('findByParentAndName')->willReturn(null);
        $this->folderRepository->method('save')->willReturnCallback(
            static function (Folder $f): void {
                $reflection = new \ReflectionClass($f);
                $reflection->getMethod('initTimestamps')->invoke($f);
            }
        );

        $response = $this->service->move($child->getId()->toRfc4122(), new MoveFolderRequest(targetParentId: null));

        $this->assertNull($response->parentId);
    }

    public function testMoveFolderIntoCycleThrows422(): void
    {
        $parent = $this->makeFolder('Parent');
        $child = $this->makeFolder('Child', $parent);
        $all = [$parent, $child];

        $this->folderRepository->method('findById')
            ->willReturnCallback(static fn (string $id) => match (true) {
                $id === $parent->getId()->toRfc4122() => $parent,
                $id === $child->getId()->toRfc4122() => $child,
                default => null,
            });
        $this->folderRepository->method('findAll')->willReturn($all);

        $this->expectException(\InvalidArgumentException::class);

        // Try to move Parent into Child (cycle)
        $this->service->move($parent->getId()->toRfc4122(), new MoveFolderRequest(targetParentId: $child->getId()->toRfc4122()));
    }

    public function testMoveFolderExceedsDepthThrows422(): void
    {
        $root = $this->makeFolder('L0');
        $l1 = $this->makeFolder('L1', $root);
        $l2 = $this->makeFolder('L2', $l1);
        $l3 = $this->makeFolder('L3', $l2);
        $l4 = $this->makeFolder('L4', $l3); // depth=4

        $src = $this->makeFolder('Src');
        $srcChild = $this->makeFolder('SrcChild', $src); // subtree height = 1

        $all = [$root, $l1, $l2, $l3, $l4, $src, $srcChild];

        $this->folderRepository->method('findById')
            ->willReturnCallback(static fn (string $id) => match (true) {
                $id === $src->getId()->toRfc4122() => $src,
                $id === $l4->getId()->toRfc4122() => $l4,
                default => null,
            });
        $this->folderRepository->method('findAll')->willReturn($all);
        $this->folderRepository->method('findByParentAndName')->willReturn(null);

        $this->expectException(\OverflowException::class);

        // Moving src (which has child) into l4 (depth=4) would put srcChild at depth 6 > 5
        $this->service->move($src->getId()->toRfc4122(), new MoveFolderRequest(targetParentId: $l4->getId()->toRfc4122()));
    }

    public function testMoveFolderDuplicateNameThrows409(): void
    {
        $src = $this->makeFolder('Работа');
        $target = $this->makeFolder('Корень');
        $duplicate = $this->makeFolder('Работа', $target);
        $all = [$src, $target, $duplicate];

        $this->folderRepository->method('findById')
            ->willReturnCallback(static fn (string $id) => match (true) {
                $id === $src->getId()->toRfc4122() => $src,
                $id === $target->getId()->toRfc4122() => $target,
                default => null,
            });
        $this->folderRepository->method('findAll')->willReturn($all);
        $this->folderRepository->method('findByParentAndName')->willReturn($duplicate);

        $this->expectException(\DomainException::class);

        $this->service->move($src->getId()->toRfc4122(), new MoveFolderRequest(targetParentId: $target->getId()->toRfc4122()));
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function makeFolder(string $name, ?Folder $parent = null): Folder
    {
        $folder = new Folder($name, $parent);
        $reflection = new \ReflectionClass($folder);
        $reflection->getMethod('initTimestamps')->invoke($folder);

        return $folder;
    }

    private function injectChild(Folder $parent, Folder $child): void
    {
        $collection = $parent->getChildren();
        $reflection = new \ReflectionClass($collection);
        $elementsProperty = $reflection->getProperty('elements');
        $elements = $elementsProperty->getValue($collection);
        $elements[] = $child;
        $elementsProperty->setValue($collection, $elements);
    }
}
