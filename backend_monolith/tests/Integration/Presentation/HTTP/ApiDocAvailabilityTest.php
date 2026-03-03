<?php

declare(strict_types=1);

namespace App\Tests\Integration\Presentation\HTTP;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiDocAvailabilityTest extends WebTestCase
{
    public function testApiDocJsonReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/doc.json');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('paths', $data);
    }

    public function testApiDocJsonContainsAllEndpoints(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/doc.json');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $paths = array_keys($data['paths']);
        $this->assertContains('/api/notes', $paths);
        $this->assertContains('/api/notes/{id}', $paths);
    }
}
