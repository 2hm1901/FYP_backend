<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\BookedCourt;
use App\Models\Venue;
use App\Events\CourtCancelled;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    // API lấy danh sách các sân đã đặt của một user với status linh hoạt
    public function getBookings(Request $request)
    {
        $id = $request->user_id;
        $statuses = $request->query('status', []);

        $query = BookedCourt::where("user_id", (int) $id);

        if (!empty($statuses)) {
            // Lọc các booking có ít nhất một sân với status trong danh sách
            $query->where('courts_booked', 'elemMatch', ['status' => ['$in' => $statuses]]);
        }

        $bookings = $query->get();

        // Lọc chỉ giữ lại các sân có status khớp với $statuses trong courts_booked
        $filteredBookings = $bookings->map(function ($booking) use ($statuses) {
            if (!empty($statuses)) {
                $booking->courts_booked = array_filter($booking->courts_booked, function ($court) use ($statuses) {
                    return in_array($court['status'], $statuses);
                });
                // Đặt lại key của mảng để tránh lỗ hổng chỉ số
                $booking->courts_booked = array_values($booking->courts_booked);
            }
            return $booking;
        });

        return response()->json($filteredBookings);
    }
    // API lấy danh sách yêu cầu thuê sân dành cho chủ sân
    public function getRequests(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $id = $request->user_id;

            // Lấy danh sách venue thuộc owner
            $venues = Venue::where('owner_id', $id)->get();

            if ($venues->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No venues found for this owner',
                    'data' => []
                ], 200);
            }

            $venueIds = $venues->pluck('id')->toArray();

            // Lấy các booking có ít nhất một sân với status "awaiting"
            $bookings = BookedCourt::whereIn('venue_id', $venueIds)
                ->where('courts_booked', 'elemMatch', ['status' => 'awaiting'])
                ->get();

            // Lọc chỉ giữ lại các sân có status "awaiting" trong courts_booked
            $filteredBookings = $bookings->map(function ($booking) {
                $booking->courts_booked = array_filter($booking->courts_booked, function ($court) {
                    return $court['status'] === 'awaiting';
                });
                // Đặt lại key của mảng để tránh lỗ hổng chỉ số
                $booking->courts_booked = array_values($booking->courts_booked);
                return $booking;
            });

            // Trả về response
            return response()->json([
                'success' => true,
                'message' => 'Requests retrieved successfully',
                'data' => $filteredBookings
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
    // API lấy danh sách sân đã được thuê dành cho chủ sân với status linh hoạt
    public function getBookedCourtList(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);

            $ownerId = $request->user_id;
            $statuses = $request->query('status', []); // Lấy statuses từ query string

            // Lấy danh sách venue thuộc owner
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

            $venueIds = $venues->pluck('id')->toArray();

            // Lấy các booking có ít nhất một sân với status trong $statuses (nếu có)
            $bookedCourtsQuery = BookedCourt::whereIn('venue_id', $venueIds);

            if (!empty($statuses)) {
                $bookedCourtsQuery->where('courts_booked', 'elemMatch', ['status' => ['$in' => $statuses]]);
            }

            $bookedCourts = $bookedCourtsQuery->get();

            // Lọc chỉ giữ lại các sân có status khớp với $statuses trong courts_booked
            $filteredBookedCourts = $bookedCourts->map(function ($booking) use ($statuses) {
                if (!empty($statuses)) {
                    $booking->courts_booked = array_filter($booking->courts_booked, function ($court) use ($statuses) {
                        return in_array($court['status'], $statuses);
                    });
                    $booking->courts_booked = array_values($booking->courts_booked);
                }
                return $booking;
            });

            return response()->json([
                'success' => true,
                'message' => 'Booked courts retrieved successfully',
                'data' => $filteredBookedCourts
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
    // API để lấy các sân đã được đặt của một sân với status linh hoạt
    public function getBookedCourt(Request $request, $id)
    {
        $selectedDate = $request->query('booking_date');

        $query = BookedCourt::where('venue_id', (int) $id);

        if ($selectedDate) {
            $query->where('booking_date', $selectedDate);
        }

        // Lọc các booking có ít nhất một sân với status "awaiting" hoặc "accepted"
        $query->where('courts_booked', 'elemMatch', [
            'status' => ['$in' => ['awaiting', 'accepted']]
        ]);

        $bookings = $query->get();

        // Lọc chỉ giữ lại các sân có status "awaiting" hoặc "accepted" trong courts_booked
        $filteredBookings = $bookings->map(function ($booking) {
            $booking->courts_booked = array_filter($booking->courts_booked, function ($court) {
                return in_array($court['status'], ['awaiting', 'accepted']);
            });
            // Đặt lại key của mảng để tránh lỗ hổng chỉ số
            $booking->courts_booked = array_values($booking->courts_booked);
            return $booking;
        });

        return response()->json($filteredBookings);
    }
    // API để đặt sân
    public function bookCourt(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'venue_id' => 'required|integer',
            'venue_name' => 'required|string',
            'venue_location' => 'required|string',
            'renter_name' => 'required|string',
            'renter_email' => 'required|email',
            'renter_phone' => 'required|string',
            'courts_booked' => 'required|array',
            'courts_booked.*.court_number' => 'required|string',
            'courts_booked.*.start_time' => 'required|string',
            'courts_booked.*.end_time' => 'required|string',
            'courts_booked.*.price' => 'required|integer',
            'courts_booked.*.status' => 'required|string|in:awaiting,accepted,cancelled',
            'total_price' => 'required|integer',
            'booking_date' => 'required|string',
            'note' => 'string|nullable',
        ]);

        $bookedCourt = BookedCourt::create($data);

        return response()->json([
            'message' => 'Booking successful',
            'data' => $bookedCourt
        ], 201);
    }
    // API để huỷ sân
    public function cancelCourt(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|string',
            ]);

            $id = $request->input('id');
            // Phân tích id: bookingId-courtNumber-startTime-endTime
            $parts = explode('-', $id);
            if (count($parts) !== 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID không hợp lệ'
                ], 400);
            }

            [$bookingId, $courtNumber, $startTime, $endTime] = $parts;

            // Tìm booking theo _id
            $booking = BookedCourt::find($bookingId);
            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking không tồn tại'
                ], 404);
            }

            // Lấy mảng courts_booked ra để sửa đổi
            $courtsBooked = $booking->courts_booked;
            $updated = false;

            // Duyệt và cập nhật status
            foreach ($courtsBooked as $index => $court) {
                if (
                    $court['court_number'] === $courtNumber &&
                    $court['start_time'] === $startTime &&
                    $court['end_time'] === $endTime
                ) {
                    $courtsBooked[$index]['status'] = 'cancelled';
                    $updated = true;
                    break;
                }
            }

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy sân để hủy'
                ], 404);
            }

            // Gán lại mảng đã sửa đổi vào model
            $booking->courts_booked = $courtsBooked;
            $booking->save();

            // Lưu thông báo vào database
            $notification = Notification::create([
                'user_id' => $booking->user_id,
                'message' => "Sân {$courtNumber} tại {$booking->venue_name} ({$startTime} - {$endTime}) đã bị hủy bởi chủ sân",
            ]);
            // Phát sự kiện CourtCancelled
            event(new CourtCancelled(
                $booking->user_id,
                $courtNumber,
                $startTime,
                $endTime,
                $booking->venue_name,
                $notification->id
            ));

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy sân thành công',
                'data' => $booking
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}