<?php

namespace App\Service;

use App\Repository\ActuatorRepository;

class ActuatorService
{
    public function __construct(private readonly ActuatorRepository $actuatorRepository)
    {
    }

    public function mergeLocalAndRemoteActuators($remoteActuators): array
    {
        //compare with local actuator
        $localActuators = $this->actuatorRepository->findAll();
        $mergeActuatorArray = [];

        // Créer une liste plate des actionneurs distants pour faciliter la recherche
        $flatRemoteActuators = [];
        foreach ($remoteActuators as $type => $actuatorByType) {
            foreach ($actuatorByType as $actuator) {
                $flatRemoteActuators[] = [
                    'title' => $actuator,
                    'type' => $type
                ];
            }
        }

        // Parcourir les actionneurs locaux et vérifier s'ils existent encore à distance
        foreach ($localActuators as $localActuator) {
            $existsInRemote = false;
            foreach ($flatRemoteActuators as $remoteActuator) {
                if ($remoteActuator['title'] === $localActuator->getTitle()) {
                    $existsInRemote = true;
                    break;
                }
            }

            $mergeActuatorArray[] = [
                'title' => $localActuator->getTitle(),
                'type' => $localActuator->getType(),
                'id' => $localActuator->getId(),
                'added' => true,
                'linked' => $existsInRemote // true si présent à distance, false sinon
            ];
        }

        // Ajouter les actionneurs distants qui ne sont pas encore en local
        foreach ($flatRemoteActuators as $remoteActuator) {
            $existsInLocal = false;
            foreach ($localActuators as $localActuator) {
                if ($localActuator->getTitle() === $remoteActuator['title']) {
                    $existsInLocal = true;
                    break;
                }
            }

            if (!$existsInLocal) {
                $mergeActuatorArray[] = [
                    'title' => $remoteActuator['title'],
                    'type' => $remoteActuator['type'],
                    'added' => false,
                    'linked' => false
                ];
            }
        }
        return $mergeActuatorArray;
    }
}