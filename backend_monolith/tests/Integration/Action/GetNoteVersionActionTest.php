<?php

declare(strict_types=1);

namespace App\Tests\Integration\Action;

use App\Domain\Entity\Note;
use App\Domain\Entity\NoteVersion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

class GetNoteVersionActionTest extends WebTestCase
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

    public function testGetVersionReturns200WithFullJson(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $note = new Note('My Note', 'Hello World');
        $em->persist($note);
        $version = new NoteVersion($note, 'My Note', 'Hello World', 1);
        $em->persist($version);
        $em->flush();

        $client->request('GET', '/api/notes/'.$note->getId().'/versions/'.$version->getId());

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame((string) $version->getId(), $data['id']);
        $this->assertSame((string) $note->getId(), $data['noteId']);
        $this->assertSame('My Note', $data['title']);
        $this->assertSame('Hello World', $data['content']);
        $this->assertSame(1, $data['versionNumber']);
        $this->assertArrayHasKey('createdAt', $data);
    }

    public function testGetVersionReturns404ForUnknownNote(): void
    {
        $client = static::createClient();

        $fakeId = Uuid::v7();
        $fakeVid = Uuid::v7();
        $client->request('GET', '/api/notes/'.$fakeId.'/versions/'.$fakeVid);

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Note not found.', $data['error']);
    }

    public function testGetVersionReturns404ForUnknownVersion(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $note = new Note('Note', null);
        $em->persist($note);
        $em->flush();

        $fakeVid = Uuid::v7();
        $client->request('GET', '/api/notes/'.$note->getId().'/versions/'.$fakeVid);

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Version not found.', $data['error']);
    }
}
