<?php

declare(strict_types=1);

namespace App\Tests\Integration\Action;

use App\Domain\Entity\Note;
use App\Domain\Entity\NoteVersion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ListNoteVersionsActionTest extends WebTestCase
{
    private function getEm(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function cleanDatabase(): void
    {
        $em = $this->getEm();
        $em->createQuery('DELETE FROM App\Domain\Entity\NoteVersion v')->execute();
        $em->createQuery('DELETE FROM App\Domain\Entity\Note n')->execute();
        $em->clear();
    }

    private function persistNote(string $title = 'Test Note', ?string $content = null): Note
    {
        $em = $this->getEm();
        $note = new Note($title, $content);
        $em->persist($note);
        $em->flush();

        return $note;
    }

    private function persistVersion(Note $note, int $num): NoteVersion
    {
        $em = $this->getEm();
        $version = new NoteVersion($note, $note->getTitle(), $note->getContent(), $num);
        $em->persist($version);
        $em->flush();

        return $version;
    }

    public function testListVersionsReturns200WithPaginatedShape(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $note = $this->persistNote('My Note', 'Content v1');
        $this->persistVersion($note, 1);
        $this->persistVersion($note, 2);

        $client->request('GET', '/api/notes/'.$note->getId().'/versions');

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('page', $data);
        $this->assertArrayHasKey('perPage', $data);
        $this->assertArrayHasKey('totalPages', $data);
        $this->assertCount(2, $data['items']);
        $this->assertSame(2, $data['total']);

        $item = $data['items'][0];
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('noteId', $item);
        $this->assertArrayHasKey('title', $item);
        $this->assertArrayHasKey('versionNumber', $item);
        $this->assertArrayHasKey('createdAt', $item);
    }

    public function testListVersionsReturns404ForNonExistentNote(): void
    {
        $client = static::createClient();

        $fakeId = \Symfony\Component\Uid\Uuid::v7();
        $client->request('GET', '/api/notes/'.$fakeId.'/versions');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Note not found.', $data['error']);
    }

    public function testListVersionsReturnsEmptyForNoteWithNoVersions(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $note = $this->persistNote('Fresh Note');

        $client->request('GET', '/api/notes/'.$note->getId().'/versions');

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(0, $data['total']);
        $this->assertCount(0, $data['items']);
    }
}
