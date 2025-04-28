<?php

namespace App\Repositories\Booking;

interface BookingRepositoryInterface
{
    public function getBookingsByUserId($userId, $statuses = []);
    public function getRequestsByVenueIds($venueIds);
    public function getBookedCourtsByVenueIds($venueIds, $statuses = []);
    public function getBookedCourtsByVenueId($venueId, $bookingDate = null);
    public function create(array $data);
    public function findById($id);
    public function update($booking);
} 