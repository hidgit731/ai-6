<?php

declare(strict_types=1);

namespace App\Tests\Integration\Presentation\HTTP;

use App\Domain\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchNotesActionTest extends WebTestCase
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

    public function testSearchReturnsMatchingNotes(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $note1 = new Note('Проект планирования', 'Этот документ описывает проект по разработке.');
        $note2 = new Note('Список покупок', 'Молоко, хлеб, масло.');
        $em->persist($note1);
        $em->persist($note2);
        $em->flush();

        $client->request('GET', '/api/notes/search?q=проект');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('page', $data);
        $this->assertArrayHasKey('perPage', $data);
        $this->assertArrayHasKey('totalPages', $data);

        $this->assertGreaterThanOrEqual(1, $data['total']);

        $item = $data['items'][0];
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('title', $item);
        $this->assertArrayHasKey('headline', $item);
        $this->assertArrayHasKey('rank', $item);
    }

    public function testSearchExcludesSoftDeletedNotes(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $deletedNote = new Note('Удалённая заметка', 'Содержимое удалённой заметки.');
        $deletedNote->softDelete();
        $em->persist($deletedNote);
        $em->flush();

        $client->request('GET', '/api/notes/search?q=удалённая');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(0, $data['total']);
        $this->assertSame([], $data['items']);
    }

    public function testEmptyQueryReturns200WithEmptyItems(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/notes/search?q=');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame([], $data['items']);
        $this->assertSame(0, $data['total']);
        $this->assertSame(1, $data['totalPages']);
    }

    public function testSearchReturns422WhenQueryTooLong(): void
    {
        $client = static::createClient();

        $longQuery = str_repeat('а', 501);
        $client->request('GET', '/api/notes/search?q='.urlencode($longQuery));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }
}
