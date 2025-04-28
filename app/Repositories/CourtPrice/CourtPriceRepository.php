<?php

namespace App\Repositories\CourtPrice;

use App\Models\CourtPrice;

class CourtPriceRepository implements CourtPriceRepositoryInterface
{
    /**
     * Lấy court price theo venue id
     */
    public function getCourtPriceByVenueId($venueId)
    {
        return CourtPrice::where('venue_id', $venueId)->first();
    }

    /**
     * Tạo court price mới
     */
    public function createCourtPrice(array $data)
    {
        return CourtPrice::create($data);
    }

    /**
     * Cập nhật court price
     */
    public function updateCourtPrice($venueId, array $data)
    {
        $courtPrice = $this->getCourtPriceByVenueId($venueId);
        
        if ($courtPrice) {
            $courtPrice->update($data);
            return $courtPrice;
        }
        
        return null;
    }

    /**
     * Xoá court price theo venue id
     */
    public function deleteCourtPriceByVenueId($venueId)
    {
        return CourtPrice::where('venue_id', $venueId)->delete();
    }
} 