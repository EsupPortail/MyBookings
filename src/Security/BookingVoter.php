<?php

namespace App\Security;
use ApiPlatform\Doctrine\Orm\Paginator;
use App\Entity\Booking;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\ServiceRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BookingVoter extends Voter
{


    public function __construct(private BookingRepository $bookingRepository)
    {
    }
    const MODERATEBOOKINGS = 'moderateBookings';
    const VIEWBOOKINGS = 'viewBookings';
    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::MODERATEBOOKINGS, self::VIEWBOOKINGS])) {
            return false;
        }

        if($subject !== null && !$subject instanceof Booking) {

            if($subject instanceof Paginator) {
                return true;
            }

            $subject = $this->bookingRepository->find($subject);
            if($subject) {
                return true;
            } else {
                return false;
            }
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
        /** @var Booking $booking */

        if($subject !== null && !$subject instanceof Service && !$subject instanceof Paginator) {
            $booking = $this->bookingRepository->find($subject);
        } else {
            $booking = $subject;
        }

        return match($attribute) {
            self::MODERATEBOOKINGS => $this->canModerateBookings($user),
            self::VIEWBOOKINGS => $this->canViewBooking($booking, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canViewBooking(Booking $booking, User $user): bool
    {
        $users = $booking->getUser()->getValues();
        foreach($users as $bookingUser) {
            if($bookingUser->getId() === $user->getId()) {
                return true;
            }
        }

        return false;
    }

    private function canModerateBookings(User $user): bool
    {
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