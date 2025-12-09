<?php

namespace App\DataFixtures\Faker\Provider;
class ArrayProvider
{
    public static function getArray($users): bool|string
    {
        $usernameArray = [];
        foreach ($users as $user) {
            $usernameArray[] = $user->getUsername();
        }
        return json_encode($usernameArray);
    }

    public static function getBookingDuration(): float|int
    {
        return rand(1, 5)*30; // Random duration between 1 and 5 hours
    }

    public static function getServiceOfCatalogue($catalogue)
    {
        return $catalogue->getService();
    }
}