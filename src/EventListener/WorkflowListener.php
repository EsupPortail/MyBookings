<?php

namespace App\EventListener;

use App\Entity\Booking;
use App\Entity\CatalogueResource;
use App\Entity\WorkflowLog;
use App\Repository\AclRepository;
use Symfony\Component\Mime\Email;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use App\Repository\WorkflowLogRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WorkflowListener {

    public function __construct(
        private WorkflowLogRepository $workflowLogRepository,
        private BookingRepository $bookingRepository,
        private AclRepository $aclRepository,
        private UserRepository $userRepository,
        #[Autowire('%env(DOMAIN_NAME)%')] private string $domainName
    ){}

    #[AsTransitionListener(workflow: 'ressourcerie_effect', transition: 'start_request')]
    #[AsTransitionListener(workflow: 'ressourcerie_effect', transition: 'accept_request')]
    #[AsTransitionListener(workflow: 'ressourcerie_effect', transition: 'end_request')]
    public function log(TransitionEvent $event): void
    {   
        $workflowLog = new WorkflowLog();
        $booking = $event->getSubject();
        $workflowLog->setBooking($booking);
        $workflowLog->setDate(new \DateTime());
        $workflowLog->setStatusTarget($event->getTransition()->getName());
        $this->workflowLogRepository->add($workflowLog, true);
    }

    #[AsTransitionListener(workflow: 'ressourcerie_effect', transition: 'remove_request')]
    public function logOnRemove(TransitionEvent $event): void
    {   
        $action = $event->getContext()['action'] ?? 'supprimée'; 
        $workflowLog = new WorkflowLog();
        $workflowLog->setDate(new \DateTime());
        $workflowLog->setStatusTarget($event->getTransition()->getName());
        $workflowLog->setComment('La demande "' . $event->getSubject()->getCatalogueResource()->getTitle() . '" a été ' . $action . ' par ' . $event->getContext()['user']);
        $this->workflowLogRepository->add($workflowLog, true);
    }

    #[AsTransitionListener(workflow: 'ressourcerie_effect', transition: 'start_request')]
    public function requestedEffectMail(TransitionEvent $event): void
    {
        $booking = $event->getSubject();
        $serviceId = (int) $booking->getTitle();

        $this->sendMail('[Ressourcerie] Demande de bien',
        '<p>Une nouvelle demande de bien a été réalisée. <a href="' . $this->domainName . '/ressourcerie/administration/site/' . $serviceId . '/bookings">Consulter la liste des demandes en attente de modération.</a></p>',
            ...$this->getModeratorsEmailsByBooking($booking, false),
        );

        $this->sendMail('[Ressourcerie] Demande de bien',
            '<p>Votre demande de bien a été envoyée. Vous recevrez un email lorsque votre demande sera validée.</p>',
            $booking->getUser()[0]->getEmail(),
        );
        
    }

    #[AsTransitionListener(workflow: 'ressourcerie_effect', transition: 'accept_request')]
    public function acceptedEffectMail(TransitionEvent $event): void
    {
        $booking = $event->getSubject();
        $serviceId = (int) $booking->getTitle();


        $this->sendMail('[Ressourcerie] Nouvelle demande de bien',
            '<p>Une nouvelle demande de bien a été réalisée. <a href="' . $this->domainName . '/ressourcerie/administration/site/' . $serviceId . '/bookings">Consulter la liste des demandes en attente de modération.</a></p>',
            'support.sit@uca.fr',
            ...$this->getModeratorsEmailsByBooking($booking)
        );

    }

    #[AsTransitionListener(workflow: 'ressourcerie_effect', transition: 'refuse_request')]
    public function refusedEffectMail(TransitionEvent $event): void
    {
        $booking = $event->getSubject();
        $this->sendMail('[Ressourcerie] Demande de bien refusée',
            '<p>Une demande de bien a été refusée.</p>',
            $booking->getUser()[0]->getEmail()
        );
    }

    #[AsTransitionListener(workflow: 'ressourcerie_effect', transition: 'end_request')]
    public function deliveredEffectMail(TransitionEvent $event): void
    {
        $booking = $event->getSubject();
        $this->sendMail('[Ressourcerie] Demande de bien',
            '<p>Une demande de bien a été réalisée.</p>',
            $booking->getUser()[0]->getEmail()
        );
    }

    #[AsTransitionListener(workflow: 'ressourcerie_effect', transition: 'remove_request')]
    public function removedEffectMail(TransitionEvent $event): void
    {
        $booking = $event->getSubject();
        $this->sendMail('[Ressourcerie] Demande de bien supprimée',
            '<p>Une demande de bien a été supprimée.</p>',
            $booking->getUser()[0]->getEmail(),
        );
    }

    #[AsTransitionListener(workflow: 'ressourcerie_catalog', transition: 'new_submission')]
    public function newSubmission(TransitionEvent $event): void
    {
        $catalog = $event->getSubject();
        $serviceId = (int) $catalog->getService()->getId();
        $username = $event->getContext()['user'] ?? 'anonyme';

        $this->sendMail('[Ressourcerie] Dépôt de bien',
            '<p>Un nouveau bien a été déposé. <a href="' . $this->domainName . '/ressourcerie/administration/site/' . $serviceId . '/catalogue">Consulter la liste des dépôts.</a></p>',
            ...$this->getModeratorsEmailByCatalog($catalog)
        );


        // Mail for the submitter
        $user = $this->userRepository->findOneBy(['username' => $username]);
        if ($user) {
            $emailSubmitter = (new Email())
                ->from('noreply-ressourcerie@uca.fr')
                ->to($user->getEmail())
                ->subject('[Ressourcerie] Dépôt de bien')
                ->html('<p>Votre dépôt de bien a été enregistré.</p>')
            ;
            $this->mail->send($emailSubmitter);
        }

        // Log the action
        $workflowLog = new WorkflowLog();
        $workflowLog->setDate(new \DateTime());
        $workflowLog->setStatusTarget($event->getTransition()->getName());
        $json = json_encode(['username' => $username, 'catalogId' => $catalog->getId()]);
        $workflowLog->setComment($json);
        $this->workflowLogRepository->add($workflowLog, true);
    }

    #[AsTransitionListener(workflow: 'ressourcerie_catalog', transition: 'new_catalog')]
    #[AsTransitionListener(workflow: 'ressourcerie_catalog', transition: 'validate_submission')]
    public function newCatalog(TransitionEvent $event): void
    {
        $catalog = $event->getSubject();
        $username = $event->getContext()['user'] ?? 'anonyme';
        // Mail for SIT
        // Get all emails from userRepository with ADMIN_RESSOURCERIE environment variable (this an array of usernames separated by comma)
        $adminEmails = [];
        $adminUsernames = json_decode($_ENV['ADMIN_RESSOURCERIE'] ?? '[]');

        $admins = $this->userRepository->findBy(['username' => $adminUsernames]);
        foreach ($admins as $admin) {
            $adminEmails[] = $admin->getEmail();
        }
        if (!empty($adminEmails)) {

            $this->sendMail('[Ressourcerie] Dépôt de bien',
                '<p>Un nouveau bien a été déposé. <a href="' . $this->domainName . '/ressourcerie/sit/deposits">Consulter la liste des dépôts.</a></p>',
                ...$adminEmails
            );
        }


        // Mail for the submitter
        $user = $this->userRepository->findOneBy(['username' => $username]);
        if ($user) {
            $this->sendMail('[Ressourcerie] Dépôt de bien',
                '<p>Votre dépôt de bien a été enregistré.</p>',
                $user->getEmail()
            );
        }

        // Log the action
        $workflowLog = new WorkflowLog();
        $workflowLog->setDate(new \DateTime());
        $workflowLog->setStatusTarget($event->getTransition()->getName());
        $json = json_encode(['username' => $username, 'catalogId' => $catalog->getId()]);
        $workflowLog->setComment($json);
        $this->workflowLogRepository->add($workflowLog, true);
    }

    #[AsTransitionListener(workflow: 'ressourcerie_catalog', transition: 'sit_accept_submission')]
    public function sitAcceptSubmissionMail(TransitionEvent $event): void
    {
        $catalog = $event->getSubject();
        $serviceId = (int) $catalog->getService()->getTitle();
        $workflowLog = $this->workflowLogRepository->findByTransitionAndCatalogId('new_catalog', $catalog->getId());
        $userEmail = null;
        if(isset($workflowLog[0])) {
            $json = json_decode($workflowLog[0]->getComment(), true);
            $username = $json['username'] ?? 'anonyme';
            $user = $this->userRepository->findOneBy(['username' => $username]);
            $userEmail = $user ? $user->getEmail() : null;
        }

        $this->sendMail('[Ressourcerie] Dépôt de bien approuvé',
            '<p>Votre dépôt de bien <b>'.$catalog->getTitle().'</b> a été approuvé par le support SIT.',
            $userEmail,
            ...$this->getModeratorsEmailByCatalog($catalog));
    }


    private function sendMail(string $subject, string $body, $to = null, $cc = null): void
    {
        // Mail to JIRA
        $email = (new Email())
            ->from('noreply-ressourcerie@uca.fr')
            ->subject($subject)
            ->html($body)
        ;

        if($to) {
            $email->to($to);
        }

        if($cc) {
            $email->cc($cc);
        }

        if(!$to && !$cc) {
            // If no recipient, do not send the email
            return;
        }
        $this->mail->send($email);
    }


    private function getModeratorsEmailsByBooking(Booking $booking, $source = true, $destination = true): array
    {
        $destinationServiceId = $destination ? (int) $booking->getTitle() : null;
        $sourceServiceId = $source ? (int) $booking->getCatalogueResource()->getService()->getId() : null;

        return $this->getModerators($sourceServiceId, $destinationServiceId);
    }

    private function getModeratorsEmailByCatalog(CatalogueResource $catalog): array
    {
        $sourceServiceId = (int) $catalog->getService()->getId();
        return $this->getModerators($sourceServiceId);
    }

    private function getModerators($sourceServiceId, $destinationServiceId = null): array
    {
        // Retrieve moderators from ACLs
        $moderators = [];

        $acls = $this->aclRepository->findBy(['service' => [$destinationServiceId ?: null, $sourceServiceId?: null]]);
        foreach ($acls as $acl) {
            $moderators[] = $acl->getUser()->getEmail();
        }

        return $moderators;
    }


}