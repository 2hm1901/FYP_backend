<?php

namespace App\Services\User;

interface UserServiceInterface
{
    public function getOwnerInfoByVenueId($venueId);
    public function getUserById($userId);
    public function getAllUsersWithRatings();
    public function deleteUser($userId);
} 