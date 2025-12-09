<?php

namespace App\Security;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\ServiceRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ServiceVoter extends Voter
{


    public function __construct(private ServiceRepository $serviceRepository, #[Autowire('%env(PLATFORM_MODE)%')] private $platformMode)
    {
    }

    const VIEWSERVICE = 'viewService';
    const EDITSERVICE = 'editService';

    const ADMINSERVICE = 'adminService';
    const MODERATESERVICE = 'moderateService';
    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEWSERVICE, self::EDITSERVICE, self::ADMINSERVICE, self::MODERATESERVICE])) {
            return false;
        }
        if($subject !== null && !$subject instanceof Service) {
            $subject = $this->serviceRepository->find($subject);
            if($subject) {
                return true;
            } else {
                return false;
            }
        } elseif($attribute === self::MODERATESERVICE) {
            return true;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Service object, thanks to `supports()`
        /** @var Service $service */

        if($subject !== null && !$subject instanceof Service) {
            $service = $this->serviceRepository->find($subject);
        } else {
            $service = $subject;
        }

        return match($attribute) {
            self::VIEWSERVICE => $this->canView($service, $user),
            self::EDITSERVICE => $this->canEdit($service, $user),
            self::ADMINSERVICE => $this->canAdminService($user),
            self::MODERATESERVICE => $this->canModerateService($user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canView(Service $service, User $user): bool
    {
        if(in_array("ROLE_ADMIN", $user->getRoles())) {
            return true;
        }

        if(in_array("ROLE_ADMIN_RESSOURCERIE", $user->getRoles()) && $service->getType() === 'Ressourcerie') {
            return true;
        }

        foreach($user->getAcls() as $acl) {
            if($acl->getService()->getId() === $service->getId()) {
                if($acl->getType() === 'ROLE_ADMINSITE' || $acl->getType() === 'ROLE_MODERATOR') {
                    return true;
                }
            }
        }
        return false;
    }

    private function canEdit(Service $service, User $user): bool
    {
        if(in_array("ROLE_ADMIN", $user->getRoles())) {
            return true;
        }

        if(in_array("ROLE_ADMIN_RESSOURCERIE", $user->getRoles()) && $service->getType() === 'Ressourcerie') {
            return true;
        }

        foreach($user->getAcls() as $acl) {
            if($acl->getService()->getId() === $service->getId()) {
                if($acl->getType() === 'ROLE_ADMINSITE') {
                    return true;
                }
            }
        }
        return false;
    }

    private function canAdminService(User $user): bool
    {
        if(in_array("ROLE_ADMIN", $user->getRoles())) {
            return true;
        }
        foreach($user->getAcls() as $acl) {
            if($acl->getType() === 'ROLE_ADMINSITE') {
                return true;
            }
        }
        return false;
    }

    private function canModerateService(User $user): bool
    {
        if($this->platformMode === 'ressourcerie') {
            return true;
        }
        if(in_array("ROLE_ADMIN", $user->getRoles())) {
            return true;
        }
        foreach($user->getAcls() as $acl) {

            if($acl->getType() === 'ROLE_ADMINSITE' || $acl->getType() === 'ROLE_MODERATOR') {
                return true;
            }
        }
        return false;
    }

}