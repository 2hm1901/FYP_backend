<?php

namespace App\Services\Booking;

interface BookingServiceInterface
{
    public function getBookings($userId, $statuses = []);
    public function getRequests($ownerId);
    public function getBookedCourtList($ownerId, $statuses = []);
    public function getBookedCourt($venueId, $bookingDate = null);
    public function bookCourt(array $data);
    public function cancelCourt($id);
    public function acceptBooking($bookingId);
    public function declineBooking($bookingId);
} 