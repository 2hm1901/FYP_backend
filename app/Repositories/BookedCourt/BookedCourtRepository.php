<?php

namespace App\Repositories\BookedCourt;

use App\Models\BookedCourt;

class BookedCourtRepository implements BookedCourtRepositoryInterface
{
    /**
     * Xoá các sân đã đặt theo venue id
     */
    public function deleteBookedCourtsByVenueId($venueId)
    {
        return BookedCourt::where('venue_id', $venueId)->delete();
    }
} 