<?php

declare(strict_types=1);

namespace App\Tests\Integration\Action;

use App\Domain\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TagActionsTest extends WebTestCase
{
    private function getEm(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function cleanDatabase(): void
    {
        $this->getEm()->createQuery('DELETE FROM App\Domain\Entity\Tag t')->execute();
        $this->getEm()->clear();
    }

    public function testCreateTagReturns201(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $client->request('POST', '/api/tags', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'work']));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('work', $data['name']);
        $this->assertSame(0, $data['noteCount']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testCreateTagDuplicateReturns409(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $client->request('POST', '/api/tags', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'unique']));
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/tags', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => 'unique']));
        $this->assertResponseStatusCodeSame(409);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testCreateTagWithEmptyNameReturns422(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/tags', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['name' => '']));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testListTagsReturns200WithNoteCount(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $tag = new Tag('testlist');
        $em->persist($tag);
        $em->flush();

        $client->request('GET', '/api/tags');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('noteCount', $data[0]);
    }

    public function testSuggestTagsReturns200(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $tag1 = new Tag('workflow');
        $tag2 = new Tag('work');
        $em->persist($tag1);
        $em->persist($tag2);
        $em->flush();

        $client->request('GET', '/api/tags/suggest?q=wor');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function testDeleteTagReturns204(): void
    {
        $client = static::createClient();
        $this->cleanDatabase();

        $em = $this->getEm();
        $tag = new Tag('deleteme');
        $em->persist($tag);
        $em->flush();

        $id = (string) $tag->getId();

        $client->request('DELETE', "/api/tags/{$id}");

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteNonExistentTagReturns404(): void
    {
        $client = static::createClient();

        $fakeId = '00000000-0000-7000-8000-000000000001';
        $client->request('DELETE', "/api/tags/{$fakeId}");

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }
}
