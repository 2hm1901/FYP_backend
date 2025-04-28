<?php

namespace App\Http\Controllers\User;

use App\Events\BookingStatusUpdated;
use App\Events\CourtCancelled;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AcceptBookingRequest;
use App\Http\Requests\Booking\BookCourtRequest;
use App\Http\Requests\Booking\CancelCourtRequest;
use App\Http\Requests\Booking\DeclineBookingRequest;
use App\Http\Requests\Booking\GetBookedCourtListRequest;
use App\Http\Requests\Booking\GetBookingsRequest;
use App\Http\Requests\Booking\GetRequestsRequest;
use App\Http\Resources\Booking\BookingCollection;
use App\Http\Resources\Booking\BookingResource;
use App\Models\Game;
use App\Models\GameParticipant;
use App\Models\Notification;
use App\Services\Booking\BookingServiceInterface;
use Illuminate\Http\Request;
use App\Models\BookedCourt;
use App\Models\Venue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingServiceInterface $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * API lấy danh sách các sân đã đặt của một user với status linh hoạt
     */
    public function getBookings(GetBookingsRequest $request)
    {
        $userId = $request->user_id;
        $statuses = $request->query('status', []);

        $bookings = $this->bookingService->getBookings($userId, $statuses);

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

        return response()->json(new BookingCollection($filteredBookings));
    }

    /**
     * API lấy danh sách yêu cầu thuê sân dành cho chủ sân
     */
    public function getRequests(GetRequestsRequest $request)
    {
        try {
            $ownerId = $request->user_id;
            $bookings = $this->bookingService->getRequests($ownerId);

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
                'data' => new BookingCollection($filteredBookings)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API lấy danh sách sân đã được thuê dành cho chủ sân với status linh hoạt
     */
    public function getBookedCourtList(GetBookedCourtListRequest $request)
    {
        try {
            $ownerId = $request->user_id;
            $statuses = $request->query('status', []); 

            $bookedCourts = $this->bookingService->getBookedCourtList($ownerId, $statuses);

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
                'data' => new BookingCollection($filteredBookedCourts)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving booked courts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API để lấy các sân đã được đặt của một sân với status linh hoạt
     */
    public function getBookedCourt(Request $request, $id)
    {
        $selectedDate = $request->query('booking_date');
        $bookings = $this->bookingService->getBookedCourt($id, $selectedDate);

        // Lọc chỉ giữ lại các sân có status "awaiting" hoặc "accepted" trong courts_booked
        $filteredBookings = $bookings->map(function ($booking) {
            $booking->courts_booked = array_filter($booking->courts_booked, function ($court) {
                return in_array($court['status'], ['awaiting', 'accepted']);
            });
            // Đặt lại key của mảng để tránh lỗ hổng chỉ số
            $booking->courts_booked = array_values($booking->courts_booked);
            return $booking;
        });

        return response()->json(new BookingCollection($filteredBookings));
    }

    /**
     * API để đặt sân
     */
    public function bookCourt(BookCourtRequest $request)
    {
        $data = $request->validated();

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

        $booking = $this->bookingService->bookCourt($data);

        return response()->json([
            'message' => 'Booking successful',
            'data' => new BookingResource($booking)
        ], 201);
    }

    /**
     * API để huỷ sân
     */
    public function cancelCourt(CancelCourtRequest $request)
    {
        try {
            $id = $request->input('id');
            
            try {
                $booking = $this->bookingService->cancelCourt($id);
                
            // Phân tích id: bookingId-courtNumber-startTime-endTime
            $parts = explode('-', $id);
            if (count($parts) !== 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID không hợp lệ'
                ], 400);
            }

            [$bookingId, $courtNumber, $startTime, $endTime] = $parts;

            // Mặc định gửi thông báo cho người đặt sân
            $userIds = [$booking->user_id];

            // Kiểm tra game liên quan đến booking
                $game = Game::find($id); 
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
                    'data' => new BookingResource($booking)
            ], 200);
            } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                    'message' => $e->getMessage()
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Cancel court failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API chấp nhận request thuê sân
     */
    public function acceptBooking(AcceptBookingRequest $request)
    {
        try {
            $bookingId = $request->input('booking_id');
            
            try {
                $booking = $this->bookingService->acceptBooking($bookingId);

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
                    'data' => new BookingResource($booking)
            ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Accept booking failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API từ chối request thuê sân
     */
    public function declineBooking(DeclineBookingRequest $request)
    {
        try {
            $bookingId = $request->input('booking_id');
            
            try {
                $booking = $this->bookingService->declineBooking($bookingId);

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
                    'data' => new BookingResource($booking)
            ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Decline booking failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lưu ảnh chuyển khoản
     * 
     * @param string $paymentImageBase64
     * @return string
     * @throws \Exception
     */
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