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

class UserTest extends ApiTestCase
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
    public function testSearchUserEndpoint(): void
    {
        $client = static::createClient();
        $testUser = $this->userRepository->findOneBy(['username' => 'test_user']);
        $client->loginUser($testUser);
        $response = $client->request('GET', '/api/user/search/all?query=Test');
        $this->assertResponseIsSuccessful();
        $responseData = $response->toArray(); // Convert response to array
        $this->assertArrayHasKey('value', $responseData[0]); // Check if 'value' key exists in the response data
    }
}