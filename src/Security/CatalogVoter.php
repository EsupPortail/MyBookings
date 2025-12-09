<?php

namespace App\Security;
use App\Entity\CatalogueResource;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\CatalogueResourceRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CatalogVoter extends Voter
{
    public function __construct(private CatalogueResourceRepository $catalogueResourceRepository)
    {
    }

    const VIEWCATALOG = 'viewCatalog';
    const EDITCATALOG = 'editCatalog';

    const ALLOWED_USER_VIEW = 'canAllowedUserView';
    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEWCATALOG, self::EDITCATALOG, self::ALLOWED_USER_VIEW])) {
            return false;
        }

        if($subject !== null && !$subject instanceof CatalogueResourceRepository) {
            $subject = $this->catalogueResourceRepository->find($subject);
            if($subject) {
                return true;
            } else {
                return false;
            }
        }

        // only vote on `Catalog` objects
        if (!$subject instanceof CatalogueResourceRepository) {
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

        // you know $subject is a CatalogueRessource object, thanks to `supports()`
        /** @var CatalogueResource $catalogueRessource */

        if($subject !== null && !$subject instanceof CatalogueResourceRepository) {
            $catalog = $this->catalogueResourceRepository->find($subject);
        } else {
            $catalog = $subject;
        }

        return match($attribute) {
            self::VIEWCATALOG => $this->canView($catalog, $user),
            self::EDITCATALOG => $this->canEdit($catalog, $user),
            self::ALLOWED_USER_VIEW => $this->canAllowedUserView($catalog, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canAllowedUserView(CatalogueResource $catalogueResource, User $user): bool
    {
        $service = $catalogueResource->getService();

        //Cheching if the user is an administrator
        if($this->isUserAdminRessourcerie($user, $service) || $this->isUserAdmin($user)) {
            return true;
        }

        //Checking if the user is a moderator or an admin of the site
        foreach($user->getAcls() as $acl) {
            if($acl->getService()->getId() === $service->getId()) {
                if($acl->getType() === 'ROLE_ADMINSITE' || $acl->getType() === 'ROLE_MODERATOR') {
                    return true;
                }
            }
        }

        //Checking if the user is allowed to view the catalogue
        if($this->isUserAllowed($user, $catalogueResource)) {

            return true;
        }

        return false;
    }

    private function canView(CatalogueResource $catalogueResource, User $user): bool
    {
        $service = $catalogueResource->getService();

        if($this->isUserAdminRessourcerie($user, $service) || $this->isUserAdmin($user)) {
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

    private function canEdit(CatalogueResource $catalogueResource, User $user): bool
    {
        $service = $catalogueResource->getService();

        if($this->isUserAdminRessourcerie($user, $service) || $this->isUserAdmin($user)) {
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


    private function isUserAdminRessourcerie($user, $service): bool
    {
        if(in_array("ROLE_ADMIN_RESSOURCERIE", $user->getRoles()) && $service->getType() === 'Ressourcerie') {
            return true;
        }
        return false;
    }

    private function isUserAdmin($user): bool
    {
        if(in_array("ROLE_ADMIN", $user->getRoles())) {
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