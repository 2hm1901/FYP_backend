<?php

namespace App\Repositories\CourtPrice;

interface CourtPriceRepositoryInterface
{
    public function getCourtPriceByVenueId($venueId);
    public function createCourtPrice(array $data);
    public function updateCourtPrice($venueId, array $data);
    public function deleteCourtPriceByVenueId($venueId);
} 