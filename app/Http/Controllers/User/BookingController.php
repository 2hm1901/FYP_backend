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

/**
 * @OA\Tag(
 *     name="Booking",
 *     description="Quản lý đặt sân badminton"
 * )
 */
class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingServiceInterface $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * API lấy danh sách các sân đã đặt của một user với status linh hoạt
     * 
     * @OA\Get(
     *     path="/api/getBookings",
     *     summary="Lấy danh sách sân đã đặt của người dùng",
     *     description="API lấy danh sách sân đã đặt của một user với status linh hoạt",
     *     operationId="getBookings",
     *     tags={"Booking"},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID của người dùng",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Trạng thái của booking (awaiting, accepted, declined, cancelled)",
     *         required=false,
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(type="string", enum={"awaiting", "accepted", "declined", "cancelled"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách thành công",
     *         @OA\JsonContent(
     *             type="object"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
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
     * 
     * @OA\Post(
     *     path="/api/bookCourt",
     *     summary="Đặt sân",
     *     description="API để đặt sân badminton",
     *     operationId="bookCourt",
     *     tags={"Booking"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"venue_id", "user_id", "booking_date", "courts"},
     *             @OA\Property(property="venue_id", type="integer", description="ID của sân"),
     *             @OA\Property(property="user_id", type="integer", description="ID của người đặt"),
     *             @OA\Property(property="booking_date", type="string", format="date", description="Ngày đặt (YYYY-MM-DD)"),
     *             @OA\Property(property="courts", type="array", description="Danh sách sân cần đặt",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="court_number", type="integer", description="Số sân"),
     *                     @OA\Property(property="start_time", type="string", format="time", description="Thời gian bắt đầu (HH:MM)"),
     *                     @OA\Property(property="end_time", type="string", format="time", description="Thời gian kết thúc (HH:MM)")
     *                 )
     *             ),
     *             @OA\Property(property="payment_image", type="string", description="Ảnh chuyển khoản dạng base64 (tuỳ chọn)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Đặt sân thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Booking successful"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dữ liệu không hợp lệ"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
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
            
            // Tìm booking theo _id
            $booking = BookedCourt::find($bookingId);
            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking không tồn tại'
                ], 404);
            }

            // Cập nhật tất cả các sân trong booking sang trạng thái accepted
            $courtsBooked = $booking->courts_booked;
            foreach ($courtsBooked as $index => $court) {
                // Chỉ cập nhật các court có status là awaiting
                if ($court['status'] === 'awaiting') {
                    $courtsBooked[$index]['status'] = 'accepted';
                }
            }

            // Lưu lại booking đã cập nhật
            $booking->courts_booked = $courtsBooked;
            $booking->save();

            // Tạo thông báo cho người đặt sân
            $notification = Notification::create([
                'user_id' => $booking->user_id,
                'message' => "Yêu cầu đặt sân của bạn tại {$booking->venue_name} đã được chấp nhận",
            ]);

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
            
            // Tìm booking theo _id
            $booking = BookedCourt::find($bookingId);
            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking không tồn tại'
                ], 404);
            }

            // Cập nhật tất cả các sân trong booking sang trạng thái declined
            $courtsBooked = $booking->courts_booked;
            foreach ($courtsBooked as $index => $court) {
                // Chỉ cập nhật các court có status là awaiting
                if ($court['status'] === 'awaiting') {
                    $courtsBooked[$index]['status'] = 'declined';
                }
            }

            // Lưu lại booking đã cập nhật
            $booking->courts_booked = $courtsBooked;
            $booking->save();

            // Kiểm tra game liên quan đến booking
            $game = Game::find($bookingId);
            if ($game) {
                // Xóa các participant
                GameParticipant::where('game_id', $game->id)->delete();
                
                // Xóa game
                $game->delete();
                Log::info("Deleted game with ID: {$game->id}");
            }

            // Tạo thông báo cho người đặt sân
            $notification = Notification::create([
                'user_id' => $booking->user_id,
                'message' => "Yêu cầu đặt sân của bạn tại {$booking->venue_name} đã bị từ chối",
            ]);

            // Gửi thông báo thời gian thực
            event(new BookingStatusUpdated(
                $booking->user_id,
                $booking->venue_name,
                $booking->booking_date,
                'declined'
            ));

            return response()->json([
                'success' => true,
                'message' => 'Đã từ chối booking thành công',
                'data' => new BookingResource($booking)
            ], 200);
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