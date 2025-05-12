<?php

namespace App\Http\Controllers\User;

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
use App\Services\Booking\BookingServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
            $data = $request->validated();
            
            $booking = $this->bookingService->bookCourt($data);

            return response()->json([
                'message' => 'Booking successful',
                'data' => new BookingResource($booking)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Book court failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API để huỷ sân
     */
    public function cancelCourt(CancelCourtRequest $request)
    {
        try {
            $id = $request->input('id');
            
            $booking = $this->bookingService->cancelCourtByCompositeId($id);

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
            
            $booking = $this->bookingService->acceptBooking($bookingId);

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
            
            $booking = $this->bookingService->declineBooking($bookingId);

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
}