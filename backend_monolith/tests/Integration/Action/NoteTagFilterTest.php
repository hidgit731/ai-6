<?php

declare(strict_types=1);

namespace App\Tests\Integration\Action;

use App\Domain\Entity\Note;
use App\Domain\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NoteTagFilterTest extends WebTestCase
{
    private function getEm(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function cleanDatabase(): void
    {
        $this->getEm()->createQuery('DELETE FROM App\Domain\Entity\Note n')->execute();
        $this->getEm()->createQuery('DELETE FROM App\Domain\Entity\Tag t')->execute();
        $this->getEm()->clear();
    }

    public function testFilterBySingleTagReturnsMatchingNotes(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();

        $workTag = new Tag('work');
        $em->persist($workTag);

        $note1 = new Note('Note with work');
        $note1->addTag($workTag);
        $em->persist($note1);

        $note2 = new Note('Note without tags');
        $em->persist($note2);

        $em->flush();

        $client->request('GET', '/api/notes?tags[]=work');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['total']);
        $this->assertSame('Note with work', $data['items'][0]['title']);
    }

    public function testAndFilterReturnsonlyNotesWithAllTags(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();

        $workTag = new Tag('work');
        $urgentTag = new Tag('urgent');
        $em->persist($workTag);
        $em->persist($urgentTag);

        $note1 = new Note('Both tags');
        $note1->addTag($workTag);
        $note1->addTag($urgentTag);
        $em->persist($note1);

        $note2 = new Note('Only work');
        $note2->addTag($workTag);
        $em->persist($note2);

        $em->flush();

        $client->request('GET', '/api/notes?tags[]=work&tags[]=urgent');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['total']);
        $this->assertSame('Both tags', $data['items'][0]['title']);
    }

    public function testUnknownTagIsIgnoredSilently(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $note = new Note('A note');
        $em->persist($note);
        $em->flush();

        $client->request('GET', '/api/notes?tags[]=nonexistent');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        // Unknown tag is ignored — all notes returned
        $this->assertSame(1, $data['total']);
    }
}
