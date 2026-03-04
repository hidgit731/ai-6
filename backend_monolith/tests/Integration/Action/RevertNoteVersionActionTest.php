<?php

declare(strict_types=1);

namespace App\Tests\Integration\Action;

use App\Domain\Entity\Note;
use App\Domain\Entity\NoteVersion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

class RevertNoteVersionActionTest extends WebTestCase
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

    public function testRevertReturns200WithUpdatedNote(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $note = new Note('New Title', 'New Content');
        $em->persist($note);
        $version = new NoteVersion($note, 'Old Title', 'Old Content', 1);
        $em->persist($version);
        $em->flush();

        $client->request('POST', '/api/notes/'.$note->getId().'/versions/'.$version->getId().'/revert');

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Old Title', $data['title']);
        $this->assertSame('Old Content', $data['content']);
    }

    public function testRevertReturns404ForUnknownNote(): void
    {
        $client = static::createClient();

        $fakeId = Uuid::v7();
        $fakeVid = Uuid::v7();
        $client->request('POST', '/api/notes/'.$fakeId.'/versions/'.$fakeVid.'/revert');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Note not found.', $data['error']);
    }

    public function testRevertReturns404ForUnknownVersion(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $note = new Note('Title', null);
        $em->persist($note);
        $em->flush();

        $fakeVid = Uuid::v7();
        $client->request('POST', '/api/notes/'.$note->getId().'/versions/'.$fakeVid.'/revert');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Version not found.', $data['error']);
    }

    public function testRevertReturns409ForDeletedNote(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $note = new Note('Title', null);
        $note->softDelete();
        $em->persist($note);
        $version = new NoteVersion($note, 'Title', null, 1);
        $em->persist($version);
        $em->flush();

        $client->request('POST', '/api/notes/'.$note->getId().'/versions/'.$version->getId().'/revert');

        $this->assertResponseStatusCodeSame(409);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Cannot revert a deleted note.', $data['error']);
    }
}
