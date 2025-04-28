<?php

namespace App\Repositories\Booking;

use App\Models\BookedCourt;

class BookingRepository implements BookingRepositoryInterface
{
    /**
     * Lấy danh sách booking của một user
     */
    public function getBookingsByUserId($userId, $statuses = [])
    {
        $query = BookedCourt::where("user_id", (int) $userId);

        if (!empty($statuses)) {
            $query->where('courts_booked', 'elemMatch', ['status' => ['$in' => $statuses]]);
        }

        return $query->get();
    }

    /**
     * Lấy danh sách yêu cầu thuê sân theo danh sách venue
     */
    public function getRequestsByVenueIds($venueIds)
    {
        return BookedCourt::whereIn('venue_id', $venueIds)
            ->where('courts_booked', 'elemMatch', ['status' => 'awaiting'])
            ->get();
    }

    /**
     * Lấy danh sách sân đã được đặt theo danh sách venue
     */
    public function getBookedCourtsByVenueIds($venueIds, $statuses = [])
    {
        $query = BookedCourt::whereIn('venue_id', $venueIds);

        if (!empty($statuses)) {
            $query->where('courts_booked', 'elemMatch', ['status' => ['$in' => $statuses]]);
        }

        return $query->get();
    }

    /**
     * Lấy danh sách sân đã được đặt của một venue
     */
    public function getBookedCourtsByVenueId($venueId, $bookingDate = null)
    {
        $query = BookedCourt::where('venue_id', (int) $venueId);

        if ($bookingDate) {
            $query->where('booking_date', $bookingDate);
        }

        $query->where('courts_booked', 'elemMatch', [
            'status' => ['$in' => ['awaiting', 'accepted']]
        ]);

        return $query->get();
    }

    /**
     * Tạo booking mới
     */
    public function create(array $data)
    {
        return BookedCourt::create($data);
    }

    /**
     * Tìm booking theo id
     */
    public function findById($id)
    {
        return BookedCourt::find($id);
    }

    /**
     * Cập nhật booking
     */
    public function update($booking)
    {
        return $booking->save();
    }
} 