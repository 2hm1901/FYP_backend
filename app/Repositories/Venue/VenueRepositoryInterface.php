<?php

namespace App\Repositories\Venue;

interface VenueRepositoryInterface
{
    public function getVenuesByOwnerId($ownerId);
    public function getVenueById($id);
    public function getAllVenues();
    public function createVenue(array $data);
    public function updateVenue($id, array $data);
    public function deleteVenue($id);
    public function getAllVenuesWithOwnerAndReviews();
    public function getVenueWithOwner($id);
} 