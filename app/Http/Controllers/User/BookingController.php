<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookedCourt;

class BookingController extends Controller
{
    //API to get all bookings
    public function getBookings(Request $request){
        $id = $request->user_id;
        $statuses = $request->query('status', []);

        $query = BookedCourt::where("user_id", (int)$id);

        if (!empty($statuses)) {
            $query->whereIn('status', $statuses);
        }

        $bookings = $query->get();

        return response()->json($bookings);
    }

    //API to get booked slot
    public function getBookedCourt(Request $request, $id)
    {
        $selectedDate = $request->query('booking_date');

        $query = BookedCourt::where('venue_id', (int)$id);

        if ($selectedDate) {
            $query->where('booking_date', $selectedDate);
        }

        $bookings = $query->get();

        return response()->json($bookings);
    }
    public function bookCourt(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'venue_id' => 'required|integer',
            'venue_name'=> 'required|string',
            'venue_location'=> 'required|string',
            'renter_name'=> 'required|string',
            'renter_email' => 'required|email',
            'renter_phone' => 'required|string',
            'courts_booked' => 'required|array',
            'total_price' => 'required|integer',
            'booking_date' => 'required|string',
            'status' => 'required|string|in:awaiting,accepted,completed',
            'note' => 'string|nullable',
        ]);

        $bookedCourt = BookedCourt::create($data);

        return response()->json([
            'message' => 'Booking successful',
            'data' => $bookedCourt
        ], 201);
    }

}
