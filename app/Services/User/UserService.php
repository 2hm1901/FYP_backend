<?php

namespace App\Services\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Venue\VenueRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;

class UserService implements UserServiceInterface
{
    protected $userRepository;
    protected $venueRepository;
    protected $notificationRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        VenueRepositoryInterface $venueRepository,
        NotificationRepositoryInterface $notificationRepository
    ) {
        $this->userRepository = $userRepository;
        $this->venueRepository = $venueRepository;
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Lấy thông tin chủ sân theo venue id
     */
    public function getOwnerInfoByVenueId($venueId)
    {
        $venue = $this->venueRepository->getVenueWithOwner($venueId);
        
        if (!$venue) {
            throw new \Exception('Venue not found');
        }
        
        if (!$venue->owner) {
            throw new \Exception('Owner not found for this venue');
        }
        
        return $venue->owner;
    }

    /**
     * Lấy thông tin user theo id
     */
    public function getUserById($userId)
    {
        $user = $this->userRepository->getUserById($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        return $user;
    }

    /**
     * Lấy tất cả users kèm thông tin ratings
     */
    public function getAllUsersWithRatings()
    {
        return $this->userRepository->getAllUsersWithReviews();
    }

    /**
     * Xoá user
     */
    public function deleteUser($userId)
    {
        // Xoá thông báo
        $this->notificationRepository->deleteNotificationsByUserId($userId);
        
        // Xoá user
        return $this->userRepository->deleteUser($userId);
    }
} 