<?php

namespace App\Repositories\Venue;

use App\Models\Venue;
use App\Models\BookedCourt;

class VenueRepository implements VenueRepositoryInterface
{
    /**
     * Lấy danh sách venue của một owner
     */
    public function getVenuesByOwnerId($ownerId)
    {
        return Venue::where('owner_id', $ownerId)->get();
    }

    /**
     * Lấy venue theo id
     */
    public function getVenueById($id)
    {
        return Venue::find($id);
    }

    /**
     * Lấy tất cả venue
     */
    public function getAllVenues()
    {
        return Venue::all();
    }

    /**
     * Tạo venue mới
     */
    public function createVenue(array $data)
    {
        return Venue::create($data);
    }

    /**
     * Cập nhật venue
     */
    public function updateVenue($id, array $data)
    {
        $venue = Venue::findOrFail($id);
        $venue->update($data);
        return $venue;
    }

    /**
     * Xoá venue
     */
    public function deleteVenue($id)
    {
        $venue = Venue::findOrFail($id);
        
        // Xóa tất cả các liên kết liên quan
        $venue->reviews()->delete();
        
        // Xóa sân
        return $venue->delete();
    }

    /**
     * Lấy tất cả venue kèm thông tin owner và reviews
     */
    public function getAllVenuesWithOwnerAndReviews()
    {
        return Venue::with(['owner', 'reviews'])->get();
    }

    /**
     * Lấy venue kèm thông tin owner và bank account
     */
    public function getVenueWithOwner($id)
    {
        return Venue::where('id', $id)
            ->with(['owner', 'owner.bankAccount'])
            ->first();
    }
} 