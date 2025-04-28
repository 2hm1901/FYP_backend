<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class UserListResource extends JsonResource
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
            'username' => $this->username,
            'email' => $this->email,
            'user_type' => $this->user_type,
            'created_at' => $this->created_at,
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $reviews->count()
        ];
    }
} 