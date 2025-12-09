<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Statistics;
use App\Entity\User;
use App\Repository\StatisticsRepository;
use App\Repository\WorkflowLogRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class StatisticService
{

    public function __construct(
        private readonly StatisticsRepository $statisticsRepository,
        private readonly RemoteService $remoteService,
        private readonly WorkflowLogRepository $workflowLogRepository,
        #[Autowire('%env(REMOTE_STATISTICS_USER_FIELDS)%')]
        private $remoteStatisticsUserFields,
    )
    {
    }

    public function addNewBooking(Booking $booking, User|null $author, $users = null, $flush = false): void
    {
        $statistic = $this->generateStatisticFromBooking($booking);
        // Auteur
        $selectedAuthor = 'Non défini';
        if ($author) {
            $username = $author->getUsername();
            try {
                $data = $this->remoteService->getUsers(['query' => $username, 'filters' => $this->remoteStatisticsUserFields]);
                $data = json_decode($data, true);
                $data = $this->clearData($data[$username]);
                $selectedAuthor = $data;
            } catch (\Exception $e) {
                $selectedAuthor = ["ROLE_USER"];
            }
        }
        $statistic->setAuthor(json_encode($selectedAuthor));
        // Participants
        if ($users) {
            $statistic->setParticipants($this->parseUserData($users));
        }

        $this->statisticsRepository->add($statistic, $flush);
    }

    public function updateStatisticBooking(Booking $booking, Statistics $statistics, $users = null): void
    {
        $editedStatistics = $this->generateStatisticFromBooking($booking, $statistics);
        //supprime un élément à la fin de mon tableau $users
        $users = $this->findAuthorAndRemoveFromParticipants($booking, $users);
        if ($users) {
            //enlever le premier user du tableau $users
            $editedStatistics->setParticipants($this->parseUserData($users));
        } else {
            $editedStatistics->setParticipants(null);
        }
        $this->statisticsRepository->add($editedStatistics, true);
    }

    private function findAuthorAndRemoveFromParticipants(Booking $booking, $users)
    {
        if($users) {
            $workflowId = $this->workflowLogRepository->findBookingCreator($booking->getId());
            if($workflowId) {
                $comment = json_decode($workflowId->getComment());
                $username = $comment->author;
                //delete user from array $users if exists
                foreach ($users as $key => $user) {
                    if(method_exists($user, 'getUsername')) {
                        $userUsername = $user->getUsername();
                    } elseif (property_exists($user, 'username')) {
                        $userUsername = $user->username;
                    } else {
                        $userUsername = (string)$user;
                    }
                    if ($userUsername === $username) {
                        unset($users[$key]);
                        break;
                    }
                }
            } else {
                array_pop($users);
            }
        }

        return $users;
    }

    private function generateStatisticFromBooking(Booking $booking, $originalStatistics = null): Statistics
    {
        if ($originalStatistics) {
            $statistic = $originalStatistics;
        } else {
            $statistic = new Statistics();
        }

        $statistic->setDateStart($booking->getDateStart());
        $statistic->setDateEnd($booking->getDateEnd());
        $catalogueResource = $booking->getCatalogueResource();
        $statistic->setCatalog($this->getJson($catalogueResource->getId(), $catalogueResource->getTitle()));
        $resources = $booking->getResource()->getValues();
        $resourceJson = array_map(fn($resource) => $this->getJson($resource->getId(), $resource->getTitle(), false), $resources);
        $statistic->setResources(json_encode($resourceJson));
        $statistic->setBooking(json_encode(["id" => $booking->getId()]));
        $statistic->setService($this->getJson($catalogueResource->getService()->getId(), $catalogueResource->getService()->getTitle()));
        $statistic->setLocalization($catalogueResource->getLocalization()->getTitle());
        if($booking->getWorkflow()) {
            $statistic->setWorkflow($this->getJson($booking->getWorkflow()->getId(), $booking->getWorkflow()->getTitle()));
        }
        $statistic->setCustomField($this->parseBookingOptions($booking->getOptions()->getValues()));

        return $statistic;
    }

    private function clearData($remoteUserData)
    {
        $data = $remoteUserData;

        if (isset($data['roles']) && is_array($data['roles'])) {
            $data['roles'] = array_filter($data['roles'], function($role) {
                return $role !== 'ROLE_USER';
            });
            // Réindexer le tableau pour avoir des indices consécutifs
            $data['roles'] = array_values($data['roles']);
        }

        return $data;
    }

    private function parseUserData($users): string
    {
        $usersData = [];
        $usernames = [];
        foreach ($users as $user) {
            if (method_exists($user, 'getUsername')) {
                $username = $user->getUsername();
            } elseif (property_exists($user, 'username')) {
                $username = $user->username;
            } else {
                $username = (string)$user;
            }
            $usernames [] = $username;
        }
            try {
                $remoteUsers = $this->remoteService->getUsers(['query' => implode(',', $usernames), 'filters' => $this->remoteStatisticsUserFields]);
                $remoteUsers = json_decode($remoteUsers, true);
                foreach ($remoteUsers as $remoteUser) {
                    $remoteUserRole = $this->clearData($remoteUser);
                    $usersData[] = $remoteUserRole;
                }
            } catch (\Exception $e) {
                $usersData[]  = ["ROLE_USER"];
            }
        return json_encode($usersData);
    }

    private function parseBookingOptions($options): string
    {
        $bookingOptions = [];
        foreach ($options as $option) {
            $bookingOptions[] = $option->getTitle();
        }
        return json_encode($bookingOptions);
    }

    private function getJson($id, $title, $encode = true)
    {

        $json = [
            "id" => $id,
            "title" => $title
        ];
        if ($encode === false) {
            return $json;
        }
        return json_encode($json, JSON_UNESCAPED_UNICODE);
    }
}