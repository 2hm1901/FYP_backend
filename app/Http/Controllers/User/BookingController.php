<?php

namespace App\Http\Controllers\User;

use App\Events\BookingStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameParticipant;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\BookedCourt;
use App\Models\Venue;
use App\Events\CourtCancelled;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            'payment_image' => 'nullable|string|regex:/^data:image\/[a-z]+;base64,/',
        ]);

        // Xử lý ảnh chuyển khoản nếu có
        if ($request->filled('payment_image')) {
            try {
                $paymentImageUrl = $this->savePaymentImage($request->payment_image);
                $data['payment_image'] = $paymentImageUrl;
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save payment image: ' . $e->getMessage(),
                ], 400);
            }
        }

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

            // Cập nhật status trong courts_booked
            $courtsBooked = $booking->courts_booked;
            $updated = false;

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

            $booking->courts_booked = $courtsBooked;
            $booking->save();

            // Mặc định gửi thông báo cho người đặt sân
            $userIds = [$booking->user_id];

            // Kiểm tra game liên quan đến booking
            $game = Game::find($id); // Giả định có trường booking_id
            if ($game) {
                // Nếu có game, lấy danh sách user_id từ GameParticipant
                $gameParticipants = GameParticipant::where('game_id', $game->id)->get();
                if ($gameParticipants->isNotEmpty()) {
                    $userIds = $gameParticipants->pluck('user_id')->unique()->toArray();
                    Log::info("Found participants for game {$game->id}: ", $userIds);
                } else {
                    Log::info("No participants found for game {$game->id}, using booking user_id");
                }
                // Xóa game nếu tồn tại
                $game->delete();
                Log::info("Deleted game with ID: {$game->id}");
            } else {
                Log::info("No game found for booking {$bookingId}, notifying booking owner only");
            }

            // Phát sự kiện CourtCancelled cho từng user_id
            foreach ($userIds as $userId) {
                $notification = Notification::create([
                    'user_id' => $userId,
                    'message' => "{$courtNumber} tại {$booking->venue_name} ({$startTime} - {$endTime}) đã bị hủy bởi chủ sân",
                ]);
                event(new CourtCancelled(
                    $userId,
                    $courtNumber,
                    $startTime,
                    $endTime,
                    $booking->venue_name,
                    $notification->id
                ));
            }

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
            Log::error('Cancel court failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    //API chấp nhận request thuê sân
    public function acceptBooking(Request $request)
    {
        try {
            $request->validate([
                'booking_id' => 'required|string', // _id từ MongoDB
            ]);

            $bookingId = $request->input('booking_id');
            $booking = BookedCourt::find($bookingId);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking không tồn tại'
                ], 404);
            }

            // Cập nhật status của tất cả courts_booked thành 'accepted'
            $courtsBooked = array_map(function ($court) {
                $court['status'] = 'accepted';
                return $court;
            }, $booking->courts_booked);

            $booking->courts_booked = $courtsBooked;
            $booking->save();

            // Gửi thông báo thời gian thực
            event(new BookingStatusUpdated(
                $booking->user_id,
                $booking->venue_name,
                $booking->booking_date,
                'accepted'
            ));

            return response()->json([
                'success' => true,
                'message' => 'Đã chấp nhận booking thành công',
                'data' => $booking
            ], 200);
        } catch (\Exception $e) {
            Log::error('Accept booking failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    //API từ chối request thuê sân
    public function declineBooking(Request $request)
    {
        try {
            $request->validate([
                'booking_id' => 'required|string',
            ]);

            $bookingId = $request->input('booking_id');
            $booking = BookedCourt::find($bookingId);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking không tồn tại'
                ], 404);
            }

            // Cập nhật status của tất cả courts_booked thành 'cancelled'
            $courtsBooked = array_map(function ($court) {
                $court['status'] = 'cancelled';
                return $court;
            }, $booking->courts_booked);

            $booking->courts_booked = $courtsBooked;
            $booking->save();

            // Gửi thông báo thời gian thực
            event(new BookingStatusUpdated(
                $booking->user_id,
                $booking->venue_name,
                $booking->booking_date,
                'cancelled'
            ));

            return response()->json([
                'success' => true,
                'message' => 'Đã từ chối booking thành công',
                'data' => $booking
            ], 200);
        } catch (\Exception $e) {
            Log::error('Decline booking failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    private function savePaymentImage($paymentImageBase64)
    {
        if (!preg_match('#^data:image/\w+;base64,#i', $paymentImageBase64)) {
            throw new \Exception('Invalid base64 image data');
        }

        $mime = explode(';', $paymentImageBase64)[0];
        $extension = explode('/', $mime)[1];
        $paymentImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $paymentImageBase64));

        if ($paymentImage === false) {
            throw new \Exception('Failed to decode base64 image');
        }

        $paymentImageFilename = uniqid() . '.' . $extension;
        $paymentImagePath = 'payment_images/' . $paymentImageFilename;
        Storage::disk('public')->put($paymentImagePath, $paymentImage);

        return $paymentImageFilename;
    }
}