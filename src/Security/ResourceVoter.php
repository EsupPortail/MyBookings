<?php

namespace App\Security;
use App\Entity\CatalogueResource;
use App\Entity\Resource;
use App\Entity\User;
use App\Repository\CatalogueResourceRepository;
use App\Repository\ResourceRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ResourceVoter extends Voter
{
    public function __construct(private ResourceRepository $resourceRepository)
    {
    }

    const VIEWRESOURCE = 'viewResource';
    const EDITRESOURCE = 'editResource';

    const BOOKRESOURCE = 'bookResource';
    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEWRESOURCE, self::EDITRESOURCE, self::BOOKRESOURCE])) {
            return false;
        }

        if($subject !== null && !$subject instanceof ResourceRepository) {
            $subject = $this->resourceRepository->find($subject);
            if($subject) {
                return true;
            } else {
                return false;
            }
        }

        // only vote on `Resource` objects
        if (!$subject instanceof ResourceRepository) {
            return false;
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

        // you know $subject is a Resource object, thanks to `supports()`
        /** @var Resource $resource */

        if($subject !== null && !$subject instanceof CatalogueResourceRepository) {
            $resource = $this->resourceRepository->find($subject);
        } else {
            $resource = $subject;
        }

        return match($attribute) {
            self::VIEWRESOURCE => $this->canView($resource, $user),
            self::EDITRESOURCE => $this->canEdit($resource, $user),
            self::BOOKRESOURCE => $this->canBook($resource, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canView(Resource $resource, User $user): bool
    {
        $service = $resource->getService();
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

    private function canEdit(Resource $resource, User $user): bool
    {
        $service = $resource->getService();
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

    private function canBook(Resource $resource, User $user): bool
    {
        $catalogue = $resource->getCatalogueResource();
        $service = $resource->getService();

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

        if($this->isUserAllowed($user, $catalogue)) {
            return true;
        }

        return false;
    }

    private function isUserAllowed($user, CatalogueResource $catalogueResource): bool
    {
        foreach ($catalogueResource->getProvisions() as $provision) {
            foreach ($provision->getGroups()->getValues() as $group) {
                if(in_array($user->getUsername(), json_decode($group->getUsers()))) {
                    return true;
                }
            }
        }
        return false;
    }

}