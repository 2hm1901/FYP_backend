<?php

namespace App\Http\Resources\Venue;

use Illuminate\Http\Resources\Json\JsonResource;

class VenueListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $reviews = $this->reviews;
        $averageRating = $reviews->isNotEmpty() ? $reviews->avg('rating') : 0;
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'owner' => $this->owner ? [
                'id' => $this->owner->id,
                'username' => $this->owner->username,
                'email' => $this->owner->email
            ] : null,
            'created_at' => $this->created_at,
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $reviews->count()
        ];
    }
} 