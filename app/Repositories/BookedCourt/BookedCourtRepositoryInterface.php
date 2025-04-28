<?php

namespace App\Repositories\BookedCourt;
 
interface BookedCourtRepositoryInterface
{
    public function deleteBookedCourtsByVenueId($venueId);
} 