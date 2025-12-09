<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RemoteService
{
    public function __construct(
        #[Autowire('%remote_container%')]
        private $remoteContainer,
        private readonly HttpClientInterface $client,

    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function send($url, $arg = [], $body = [], $method = 'GET', $files = []): string
    {
        $options = [];

        if (!empty($files)) {
            // Si des fichiers sont présents, utiliser body pour multipart/form-data
            $options['body'] = array_merge((array)$body, $files);
        } else if (!empty($body)) {
            // Sinon utiliser json pour les données normales
            $options = ['json' => $body];
        }


      $response = $this->client->request(
          $method,
          $this->remoteContainer['url'] . $url,
          [
              'query' => [
                  ...$arg
              ],
              ...$options,
              'headers' => [
                  'X-AUTH-TOKEN' => $this->remoteContainer['api_token'],
              ],
          ],
      );
      return $response->getContent();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function findUser($username): string
    {
        $fields = ["email","displayUserName","roles", "username"];
        return $this->send('user/'.$username, ['fields' => $fields]);
    }

    public function getUserData($username, $fields): string
    {
        return $this->send('user/'.$username, ['fields' => json_decode($fields)]);
    }

    public function searchUsers($arg): string
    {
        return $this->send('users/'.$arg);
    }

    public function searchUsersFromProvider($args): string
    {
        return $this->send('users/'.$args['provider'].'/'.$args['query']);
    }

    public function getUsers($args): string
    {
        return $this->send('users/filter/'.$args['query'], ['filters' => $args['filters']]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function executeActuator($actuatorName, $action, $params = []): string
    {
        try {
           return $this->send('actuator/execute/' . $actuatorName . '/' . $action, [],  $params, 'POST');
        } catch (\Exception $e) {
            throw new Exception('Error communicating with actuator');
        }
    }
}