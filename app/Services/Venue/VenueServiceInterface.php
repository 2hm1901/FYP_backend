<?php

namespace App\Services\Venue;

interface VenueServiceInterface
{
    public function getAllVenues();
    public function getVenuesByOwnerId($ownerId);
    public function getVenueById($id);
    public function getBookingTable($id);
    public function createVenue(array $data);
    public function updateVenue($id, array $data);
    public function deleteVenue($id);
    public function getAllVenuesWithRatings();
} 