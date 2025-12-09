<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Repository\UserRepository;
use Hautelook\AliceBundle\PhpUnit\FixtureStore;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class LocalizationsTest extends ApiTestCase
{
    private UserRepository $userRepository;
    use RefreshDatabaseTrait;
    public function setUp(): void
    {
        FixtureStore::setPurgeWithTruncate(false); // Disable purge with truncate
        static::$alwaysBootKernel = true;
        $this->userRepository = static::getContainer()->get(UserRepository::class); // Get UserRepository service
    }
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function testLocalizations(): void
    {
        $client = static::createClient();
        $testUser = $this->userRepository->findOneBy(['username' => 'test_user']);
        $client->loginUser($testUser);
        $response = $client->request('GET', '/api/localizations');
        $responseData = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('member', $responseData);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testLocalizationID(): void
    {
        $client = static::createClient();
        $testUser = $this->userRepository->findOneBy(['username' => 'admin_user']);

        // Log user in with the test user account
        $client->loginUser($testUser);

        // 1ère requête
        $response = $client->request('GET', '/api/localizations');
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $this->assertArrayHasKey('member', $responseData);
        $localizationId = $responseData['member'][0]['id'];

        // 2ème requête avec l'id récupéré de la 1ère requête
        $response = $client->request('GET', '/api/localizations/' . $localizationId);
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('title', $response->toArray());
    }
}