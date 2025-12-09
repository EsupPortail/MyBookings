<?php

namespace App\Service;

use App\Entity\Booking;
use Eluceo\iCal\Domain\Enum\RoleType;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\ValueObject\Member;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Symfony\Component\HttpKernel\KernelInterface;

class CalendarService
{
    public function __construct(
        private KernelInterface $kernel,
        private BookingService $bookingService
    )
    {
    }

    public function generateCalendar(Array $bookings) {
        $arrayOfEvent = [];
        $dateToday = new \DateTime();
        $jourSemaine = $dateToday->format('N');
        $decalage = $jourSemaine - 1;

        $premierJourSemaine = $dateToday->sub(new \DateInterval("P{$decalage}D"));
        $premierJourSemaine->setTime(0, 0, 1);
        foreach ($bookings as $booking) {
            if($booking->getDateEnd() >= $premierJourSemaine) {
                $resource = $booking->getResource()->getValues()[0];
                $organizer = null;
                $author = $this->bookingService->getBookingAuthor($booking);
                if($author) {
                    $organizer = new Organizer(new EmailAddress($author->getEmail()), $author->getDisplayUserName());
                } else {
                    if(sizeof($booking->getUser()->getValues())> 0) {
                        $organizer = new Organizer(new EmailAddress($booking->getUser()->getValues()[0]->getEmail()), $booking->getUser()->getValues()[0]->getDisplayUserName());
                    }
                }
                $event = new Event();
                if($booking->getStatus() === 'pending') {
                    $title = 'En attente de validation';
                } else {
                    $title = $booking->getTitle() !== null ? $booking->getTitle(): 'Réservation';
                }
                $event
                    ->setSummary($title)
                    ->setOrganizer($organizer)
                    ->setLocation(new Location($resource->getTitle()))
                    ->setOccurrence(
                        new TimeSpan(new DateTime($booking->getDateStart(), true), new DateTime($booking->getDateEnd(), true))
                    );
                $arrayOfEvent[] = $event;
            }

        }
        // 2. Create Calendar domain entity
        $calendar = new Calendar($arrayOfEvent);

        // 3. Transform domain entity into an iCalendar component
        $componentFactory = new CalendarFactory();
        $calendarComponent = $componentFactory->createCalendar($calendar);
        return $calendarComponent;
    }

    public function generateEvent(Booking $booking) {
        $arrayOfEvent = [];
        $dateToday = new \DateTime();
        $jourSemaine = $dateToday->format('N');
        $decalage = $jourSemaine - 1;

        $premierJourSemaine = $dateToday->sub(new \DateInterval("P{$decalage}D"));
        $premierJourSemaine->setTime(0, 0, 1);
        if($booking->getDateEnd() >= $premierJourSemaine) {
            $event = new Event();
            $title = 'Réservation MyBookings';
            if($booking->getTitle() !== null)
                $title = $booking->getTitle();
            $event->setSummary($title)
                ->setOccurrence(
                    new TimeSpan(new DateTime($booking->getDateStart(), true), new DateTime($booking->getDateEnd(), true))
                )
                ->setLocation(new Location($booking->getResource()->getValues()[0]->getCatalogueResource()->getLocalization()->getTitle()));

            $description = 'Réservation des ressources : ';
            foreach($booking->getResource()->getValues() as $resource) {
                $description .= $resource->getTitle()."\n";
            }
            $event->setDescription($description);

            // Il y a pas mieux sur la doc..
            // ¯\_(ツ)_/¯
            foreach($booking->getUser()->getValues() as $user) {
                $attendee = new Attendee(new EmailAddress($user->getEmail()));

                $attendee->setRole(RoleType::REQ_PARTICIPANT());
                $attendee->addMember(new Member(new EmailAddress($user->getEmail())));

                $event->addAttendee($attendee);
            }

            $arrayOfEvent[] = $event;
        }
        // 2. Create Calendar domain entity
        $calendar = new Calendar($arrayOfEvent);

        // 3. Transform domain entity into an iCalendar component
        $componentFactory = new CalendarFactory();
        $calendarComponent = $componentFactory->createCalendar($calendar);

        $uploadsDir = $this->kernel->getProjectDir().'/var/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $file = $uploadsDir.'/event.ics';
        file_put_contents($file, (string) $calendarComponent);
        return $file;
    }

}