<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookedCourt;
use App\Models\Venue;

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
    //API lấy danh sách yêu cầu thuê sân dành cho chủ sân
    public function getRequests(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $id = $request->user_id;

            $venues = Venue::where('owner_id', $id)->get();

            if ($venues->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No venues found for this owner',
                    'data' => []
                ], 200);
            }

            $venueIds = $venues->pluck('id')->toArray();

            $requests = BookedCourt::whereIn('venue_id', $venueIds)
                ->where('status', 'awaiting')
                ->get();


            // Trả về response
            return response()->json([
                'success' => true,
                'message' => 'Requests retrieved successfully',
                'data' => $requests
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    //API lấy danh sách sân đã được thuê dành cho chủ sân
    public function getBookedCourtList(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'status' => 'nullable|array',
            ]);

            $ownerId = $request->user_id;
            $statuses = $request->query('status', []);

            // Lấy tất cả venues thuộc owner từ MySQL
            $venues = Venue::where('owner_id', $ownerId)->get();

            if ($venues->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No venues found for this owner',
                    'data' => [
                        'booked_courts' => []
                    ]
                ], 200);
            }

            // Lấy danh sách venue_ids
            $venueIds = $venues->pluck('id')->toArray();

            // Query BookedCourt từ MongoDB dựa trên venue_ids (không dùng with('venue'))
            $bookedCourtsQuery = BookedCourt::whereIn('venue_id', $venueIds);

            // Nếu có filter status thì thêm điều kiện
            if (!empty($statuses)) {
                $bookedCourtsQuery->whereIn('status', $statuses);
            }

            $bookedCourts = $bookedCourtsQuery->get();

            // Trả về response
            return response()->json([
                'success' => true,
                'message' => 'Booked courts retrieved successfully',
                'data' => $bookedCourts
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving booked courts',
                'error' => $e->getMessage()
            ], 500);
        }
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
