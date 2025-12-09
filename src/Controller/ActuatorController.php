<?php

namespace App\Controller;

use App\Repository\ActuatorRepository;
use App\Service\ActuatorService;
use App\Service\RemoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;


class ActuatorController extends AbstractController
{
    public function __construct(private ActuatorRepository $actuatorRepository, private RemoteService $remoteService, private ActuatorService $actuatorService){}


    #[Route(path: '/api/actuator/health', name: 'actuators_health', methods: ['GET'])]
    #[isGranted('ROLE_ADMIN')]
    public function get_actuators_health()
    {
        $response = $this->remoteService->send('health/check');
        $health = json_decode($response, true);
        return new JsonResponse($health);
    }
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route(path: '/api/actuator/read', name: 'actuators', methods: ['GET'])]
    public function get_actuators(): JsonResponse
    {
        $response = $this->remoteService->send('actuator/read');
        $remoteActuators = json_decode($response, true);
        $actuators = $this->actuatorService->mergeLocalAndRemoteActuators($remoteActuators);
        return new JsonResponse($actuators);
    }

    #[Route(path: '/api/actuators/{id}/profiles', name: 'actuator_profiles', methods: ['GET'])]
    public function get_actuator_profiles($id) {
        $actuator = $this->actuatorRepository->find($id);
        if($actuator) {
            $response = $this->remoteService->send("actuator/execute/".strtolower($actuator->getType())."/read?name=".$actuator->getTitle());
            $profiles = json_decode($response, true);
            return new JsonResponse($profiles);
        }
        return new JsonResponse(['error' => 'Actuator not found'], 404);
    }
}
