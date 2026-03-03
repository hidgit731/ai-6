<?php

declare(strict_types=1);

namespace App\Tests\Integration\Presentation\HTTP;

use App\Domain\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ListNotesActionTest extends WebTestCase
{
    private function getEm(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function cleanDatabase(): void
    {
        $this->getEm()->createQuery('DELETE FROM App\Domain\Entity\Note n')->execute();
        $this->getEm()->clear();
    }

    public function testListReturnsEmptyPaginatedResponse(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $client->request('GET', '/api/notes');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame([], $data['items']);
        $this->assertSame(1, $data['page']);
        $this->assertSame(10, $data['perPage']);
        $this->assertSame(0, $data['total']);
        $this->assertSame(1, $data['totalPages']);
    }

    public function testListReturnsSortedByCreatedAtDesc(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $note1 = new Note('First Note', 'Content 1');
        $em->persist($note1);
        $em->flush();

        $note2 = new Note('Second Note', 'Content 2');
        $em->persist($note2);
        $em->flush();

        $client->request('GET', '/api/notes');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['page']);
        $this->assertSame(10, $data['perPage']);
        $this->assertCount(2, $data['items']);

        // Verify structure
        $this->assertArrayHasKey('id', $data['items'][0]);
        $this->assertArrayHasKey('preview', $data['items'][0]);
        $this->assertArrayHasKey('createdAt', $data['items'][0]);
    }

    public function testListPaginationResponse(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        for ($i = 1; $i <= 15; ++$i) {
            $note = new Note("Note {$i}");
            $em->persist($note);
        }
        $em->flush();

        $client->request('GET', '/api/notes?page=2');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(2, $data['page']);
        $this->assertSame(10, $data['perPage']);
        $this->assertSame(15, $data['total']);
        $this->assertSame(2, $data['totalPages']);
        $this->assertCount(5, $data['items']);
    }

    public function testListReturnsValidationErrorForInvalidPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/notes?page=0');

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }
}
