<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;


class Mail
{

    public function __construct(
        #[Autowire('%emails%')]
        private array $parameters,
        #[Autowire('%email_global_pretype%')]
        private string $globalPretype,
        private CalendarService $calendarService,
        private Environment $twig,
        private RemoteService $remoteService
    )
    {}

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function sendMail (Booking $booking, String $transitionName, array $moderators): void
    {
        $actuator = false;
        $resources = $booking->getResource()->getValues();
        foreach ($resources as $resource) {
            if($resource->getActuatorProfile() !== null && $resource->getActuatorProfile() !== 'null') {
                $actuator = true;
            }
        }
        $ics = $this->calendarService->generateEvent($booking);
        $users = $booking->getUser()->getValues();
        foreach ($users as $key => $user) {
            if(in_array(strtolower($user->getEmail()), $moderators)) {
                unset($users[$key]);
            }
        }
        if($transitionName !== "start_booking" && $transitionName !== "end_booking" && sizeof($users) > 0) {
            $to = $booking->getUser()->getValues()[0]->getEmail();

            $context = [
                    'booking' => $booking->getId(),
                    'status' => $transitionName,
                    'category' => $booking->getCatalogueResource()->getType()->getTitle(),
                    'subCategory' => $booking->getCatalogueResource()->getSubType()->getTitle(),
                    'dateStart' => $booking->getDateStart(),
                    'dateEnd' => $booking->getDateEnd(),
                    'userComment' => $booking->getUserComment(),
                    'confirmComment' => $booking->getConfirmComment(),
                    'service' => $booking->getCatalogueResource()->getService()->getTitle(),
                    'resources' => $resources,
                    'users' => $users,
                    'title' => $booking->getTitle(),
                    'actuator' => $actuator
                ];
            $to = json_encode([$to]);

            $email['subject'] = $this->globalPretype.'Réservation du catalogue : '.$booking->getCatalogueResource()->getTitle();
            $cc = [];
            if (count($booking->getUser()->getValues())>1)
                {
                    for ($i=1;$i<count($booking->getUser()->getValues());$i++)
                    {
                        $cc[] = $booking->getUser()->getValues()[$i]->getEmail();
                    }
                }
            $email = $this->buildEmail("update",$to, $context, json_encode($cc));
            $files = [
                'ics' => fopen($ics, 'r')
            ];
            try {
                $this->remoteService->send('mailer/send', [], $email, 'POST', $files);
            } catch (\Exception $e) {
                throw new \Exception('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
            }
        }
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws RuntimeError
     * @throws ClientExceptionInterface
     * @throws LoaderError
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws SyntaxError
     * @throws ServerExceptionInterface
     * @throws \Exception
     */
    public function sendMailModeration (Booking $booking, String $transitionName, $to): void
    {
        $resources = $booking->getResource()->getValues();
        $context= [
                    'status' => $transitionName,
                    'category' => $booking->getCatalogueResource()->getType()->getTitle(),
                    'subCategory' => $booking->getCatalogueResource()->getSubType()->getTitle(),
                    'dateStart' => $booking->getDateStart(),
                    'dateEnd' => $booking->getDateEnd(),
                    'userComment' => $booking->getUserComment(),
                    'confirmComment' => $booking->getConfirmComment(),
                    'service' => $booking->getCatalogueResource()->getService()->getTitle(),
                    'resources' => $resources,
                    'users' => $booking->getUser()->getValues(),
                    'title' => $booking->getTitle(),
                    'idService' => $booking->getCatalogueResource()->getService()->getId(),
                    'catalog' => $booking->getCatalogueResource()->getId()
                ];
        $to = explode(';', $to);
        $email = $this->buildEmail("moderation",$to, $context);
        $subject = "Réservation du catalogue : ";
        if($transitionName === 'end_booking') {
            $subject = "Fin de réservation : ";
        }
        $email['subject'] = $this->globalPretype.$subject.$booking->getCatalogueResource()->getTitle();
        try {
            $this->remoteService->send('mailer/send', [], $email, 'POST');
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function sendMailRemoved (Booking $booking, string $transitionName, $comment = null): void
    {
        $actuator = false;
        $resources = $booking->getResource()->getValues();
        foreach ($resources as $resource) {
            if($resource->getActuatorProfile() !== null && $resource->getActuatorProfile() !== 'null') {
                $actuator = true;
            }
        }
        if($transitionName !== "start_booking" && $transitionName !== "end_booking") {

            $context = [
                    'status' => $transitionName,
                    'category' => $booking->getCatalogueResource()->getType()->getTitle(),
                    'subCategory' => $booking->getCatalogueResource()->getSubType()->getTitle(),
                    'dateStart' => $booking->getDateStart(),
                    'dateEnd' => $booking->getDateEnd(),
                    'userComment' => $booking->getUserComment(),
                    'confirmComment' => $booking->getConfirmComment(),
                    'service' => $booking->getCatalogueResource()->getService()->getTitle(),
                    'resources' => $resources,
                    'actuator' => $actuator,
                    'comment' => $comment
                ];

            $cc = [];
            if (count($booking->getUser()->getValues())>1)
            {
                for ($i=1;$i<count($booking->getUser()->getValues());$i++)
                {
                    $cc[] = $booking->getUser()->getValues()[$i]->getEmail();
                }
            }
            if(sizeof($booking->getUser()->getValues()) > 0) {
                $to = [$booking->getUser()->getValues()[0]->getEmail()];
                $email = $this->buildEmail("removed",$to, $context, $cc);
                try {
                    $this->remoteService->send('mailer/send', [], $email, 'POST');
                } catch (\Exception $e) {
                    throw new \Exception('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws RuntimeError
     * @throws ClientExceptionInterface
     * @throws LoaderError
     * @throws SyntaxError
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function sendMailRemovedByUser(Booking $booking, String $transitionName): void
    {
        $resources = $booking->getResource()->getValues();
        if ($transitionName !== "start_booking" && $transitionName !== "end_booking") {
            $to = [$booking->getUser()->getValues()[0]->getEmail()];
            $context = [
                'status' => $transitionName,
                'category' => $booking->getCatalogueResource()->getType()->getTitle(),
                'subCategory' => $booking->getCatalogueResource()->getSubType()->getTitle(),
                'dateStart' => $booking->getDateStart(),
                'dateEnd' => $booking->getDateEnd(),
                'userComment' => $booking->getUserComment(),
                'confirmComment' => $booking->getConfirmComment(),
                'service' => $booking->getCatalogueResource()->getService()->getTitle(),
                'resources' => $resources,
            ];

            $cc = [];
            if (count($booking->getUser()->getValues()) > 1) {
                for ($i = 1; $i < count($booking->getUser()->getValues()); $i++) {
                    $cc[] = $booking->getUser()->getValues()[$i]->getEmail();
                }
            }
            $email = $this->buildEmail('removedByUser', $to, $context, $cc);
            try {
                $this->remoteService->send('mailer/send', [], $email, 'POST');
            } catch (\Exception $e) {
                throw new \Exception('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
            }
        }
    }


    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function sendLoginLink(User $user, string $link): void
    {
        $email = $this->buildEmail("login_link",[$user->getEmail()], ['link' => $link]);
        try {
            $this->remoteService->send('mailer/send', [], $email, 'POST');
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEditBooking(Booking $booking): void
    {
        $users = $booking->getUser()->getValues();


        $context = [
            'users' => $users,
            'category' => $booking->getCatalogueResource()->getType()->getTitle(),
            'subCategory' => $booking->getCatalogueResource()->getSubType()->getTitle(),
            'dateStart' => $booking->getDateStart(),
            'dateEnd' => $booking->getDateEnd(),
            'userComment' => $booking->getUserComment(),
            'confirmComment' => $booking->getConfirmComment(),
            'service' => $booking->getCatalogueResource()->getService()->getTitle(),
            'resources' => $booking->getResource()->getValues(),
            'title' => $booking->getTitle(),
            'idService' => $booking->getCatalogueResource()->getService()->getId(),
            'catalog' => $booking->getCatalogueResource()->getId()
        ];

        $to = [];
        if(count($users) > 0) {
            foreach ($users as $user) {
                $to[] = $user->getEmail();
            }
            $email = $this->buildEmail('edit_booking', $to, $context);
            try {
                $this->remoteService->send('mailer/send', [], $email, 'POST');
            } catch (\Exception $e) {
                throw new \Exception('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
            }
        }
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    private function buildEmail(string $type, string|array $to, array $context, $cc = null): array
    {
        $params = $this->parameters[$type];
        $email = [];
        $email['subject'] = $this->globalPretype.$params['subject'];
        $email['from'] = $params['from'];
        $email['to'] = $to;
        if ($cc !== null) {
            $email['cc'] = $cc;
        }
        $email['body'] = $this->twig->render($params['template'], $context);

        return $email;
    }
}
