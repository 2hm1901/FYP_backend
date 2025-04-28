<?php

namespace App\Http\Resources\Venue;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CourtPrice;

class VenueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $courtPrice = CourtPrice::where('venue_id', $this->id)->first();
        
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'location' => $this->location,
            'court_count' => $this->court_count,
            'open_time' => $this->open_time,
            'close_time' => $this->close_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'courtPrices' => $courtPrice ? [
                [
                    'court_id' => $this->id,
                    'price_slots' => $courtPrice->price_slots,
                    'id' => $courtPrice->_id,
                ]
            ] : [],
        ];
    }
} 