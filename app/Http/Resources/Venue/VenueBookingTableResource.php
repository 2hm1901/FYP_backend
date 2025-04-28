<?php

namespace App\Http\Resources\Venue;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CourtPrice;

class VenueBookingTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $courtPrices = CourtPrice::where("venue_id", $this->id)->get();
        
        return [
            'venue' => $this->resource,
            'courtPrices' => $courtPrices
        ];
    }
} 