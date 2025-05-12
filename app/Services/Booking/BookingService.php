<?php

namespace App\Services\Booking;

use App\Repositories\Booking\BookingRepositoryInterface;
use App\Repositories\Game\GameRepositoryInterface;
use App\Repositories\GameParticipant\GameParticipantRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\Venue\VenueRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Events\CourtCancelled;
use App\Events\BookingStatusUpdated;

class BookingService implements BookingServiceInterface
{
    protected $bookingRepository;
    protected $venueRepository;
    protected $gameRepository;
    protected $gameParticipantRepository;
    protected $notificationRepository;

    public function __construct(
        BookingRepositoryInterface $bookingRepository,
        VenueRepositoryInterface $venueRepository,
        GameRepositoryInterface $gameRepository,
        GameParticipantRepositoryInterface $gameParticipantRepository,
        NotificationRepositoryInterface $notificationRepository
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->venueRepository = $venueRepository;
        $this->gameRepository = $gameRepository;
        $this->gameParticipantRepository = $gameParticipantRepository;
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Lấy danh sách booking của một user
     */
    public function getBookings($userId, $statuses = [])
    {
        return $this->bookingRepository->getBookingsByUserId($userId, $statuses);
    }

    /**
     * Lấy danh sách yêu cầu thuê sân của owner
     */
    public function getRequests($ownerId)
    {
        $venues = $this->venueRepository->getVenuesByOwnerId($ownerId);
        $venueIds = $venues->pluck('id')->toArray();

        return $this->bookingRepository->getRequestsByVenueIds($venueIds);
    }

    /**
     * Lấy danh sách sân đã được đặt của owner
     */
    public function getBookedCourtList($ownerId, $statuses = [])
    {
        $venues = $this->venueRepository->getVenuesByOwnerId($ownerId);
        $venueIds = $venues->pluck('id')->toArray();

        return $this->bookingRepository->getBookedCourtsByVenueIds($venueIds, $statuses);
    }

    /**
     * Lấy danh sách sân đã được đặt của một venue
     */
    public function getBookedCourt($venueId, $bookingDate = null)
    {
        return $this->bookingRepository->getBookedCourtsByVenueId($venueId, $bookingDate);
    }

    /**
     * Đặt sân
     */
    public function bookCourt(array $data)
    {
        // Xử lý ảnh thanh toán nếu có
        if (isset($data['payment_image']) && strpos($data['payment_image'], 'data:image') === 0) {
            $data['payment_image'] = $this->savePaymentImage($data['payment_image']);
        }

        // Tạo booking mới
        $bookingData = [
            '_id' => (string) Str::uuid(),
            'user_id' => $data['user_id'],
            'venue_id' => $data['venue_id'],
            'venue_name' => $data['venue_name'],
            'venue_location' => $data['venue_location'],
            'renter_name' => $data['renter_name'],
            'renter_email' => $data['renter_email'],
            'renter_phone' => $data['renter_phone'],
            'courts_booked' => $data['courts_booked'],
            'total_price' => $data['total_price'],
            'booking_date' => $data['booking_date'],
            'note' => $data['note'] ?? null,
            'payment_image' => $data['payment_image'] ?? null,
        ];

        $booking = $this->bookingRepository->create($bookingData);

        // Lấy owner_id từ venue
        $venue = $this->venueRepository->getVenueById($data['venue_id']);
        if ($venue) {
            // Tạo thông báo cho chủ sân
            $notificationData = [
                'user_id' => $venue->owner_id,
                'message' => "Một yêu cầu đặt sân mới từ {$data['renter_name']} tại {$data['venue_name']}",
                'is_read' => false,
            ];

            $this->notificationRepository->create($notificationData);
        }

        return $booking;
    }

    /**
     * Huỷ sân từ ID phức hợp (bookingId-courtNumber-startTime-endTime)
     */
    public function cancelCourtByCompositeId($compositeId)
    {
        // Phân tích ID phức hợp
        $parts = explode('-', $compositeId);
        if (count($parts) !== 4) {
            throw new \Exception('ID không hợp lệ');
        }

        [$bookingId, $courtNumber, $startTime, $endTime] = $parts;

        // Tìm booking
        $booking = $this->bookingRepository->findById($bookingId);
        if (!$booking) {
            throw new \Exception('Booking không tồn tại');
        }

        // Cập nhật trạng thái sân
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
            throw new \Exception('Không tìm thấy sân để hủy');
        }

        // Cập nhật booking
        $booking->courts_booked = $courtsBooked;
        $this->bookingRepository->update($booking);

        // Xử lý game và thông báo
        $game = $this->gameRepository->findById($compositeId);
        $userIds = [$booking->user_id];

        if ($game) {
            // Lấy danh sách người tham gia
            $participants = $this->gameParticipantRepository->getParticipantsByGameId($game->id);
            if (!$participants->isEmpty()) {
                $userIds = $participants->pluck('user_id')->unique()->toArray();
                Log::info("Found participants for game {$game->id}: ", $userIds);
            }
            
            // Xóa game
            $this->gameRepository->delete($game);
            Log::info("Deleted game with ID: {$game->id}");
        }

        // Tạo thông báo và phát sự kiện
        foreach ($userIds as $userId) {
            $notification = $this->notificationRepository->create([
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

        return $booking;
    }

    /**
     * Huỷ sân
     */
    public function cancelCourt($id)
    {
        $booking = $this->bookingRepository->findById($id);

        if (!$booking) {
            throw new \Exception('Booking not found');
        }

        foreach ($booking->courts_booked as $key => $court) {
            $booking->courts_booked[$key]['status'] = 'cancelled';
        }

        $this->bookingRepository->update($booking);

        return $booking;
    }

    /**
     * Chấp nhận yêu cầu thuê sân
     */
    public function acceptBooking($bookingId)
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new \Exception('Booking not found');
        }

        // Cập nhật các sân có trạng thái awaiting sang accepted
        $updated = false;
        $courtsBooked = $booking->courts_booked;
        
        foreach ($courtsBooked as $key => $court) {
            if ($court['status'] === 'awaiting') {
                $courtsBooked[$key]['status'] = 'accepted';
                $updated = true;
            }
        }

        if (!$updated) {
            throw new \Exception('Không có sân nào cần được chấp nhận');
        }

        $booking->courts_booked = $courtsBooked;
        $this->bookingRepository->update($booking);

        // // Tạo thông báo cho người đặt sân
        // $notification = $this->notificationRepository->create([
        //     'user_id' => $booking->user_id,
        //     'message' => "Yêu cầu đặt sân của bạn tại {$booking->venue_name} đã được chấp nhận",
        //     'is_read' => false,
        // ]);

        // Phát sự kiện cập nhật trạng thái
        event(new BookingStatusUpdated(
            $booking->user_id,
            $booking->venue_name,
            $booking->booking_date,
            'accepted'
        ));

        return $booking;
    }

    /**
     * Từ chối yêu cầu thuê sân
     */
    public function declineBooking($bookingId)
    {
        $booking = $this->bookingRepository->findById($bookingId);

        if (!$booking) {
            throw new \Exception('Booking not found');
        }

        // Cập nhật các sân có trạng thái awaiting sang declined
        $updated = false;
        $courtsBooked = $booking->courts_booked;
        
        foreach ($courtsBooked as $key => $court) {
            if ($court['status'] === 'awaiting') {
                $courtsBooked[$key]['status'] = 'declined';
                $updated = true;
            }
        }

        if (!$updated) {
            throw new \Exception('Không có sân nào cần từ chối');
        }

        $booking->courts_booked = $courtsBooked;
        $this->bookingRepository->update($booking);

        // Xoá game nếu có
        $game = $this->gameRepository->findById($bookingId);
        if ($game) {
            // Xoá người tham gia
            $this->gameParticipantRepository->deleteByGameId($game->id);
            
            // Xoá game
            $this->gameRepository->delete($game);
            Log::info("Deleted game with ID: {$game->id}");
        }

        // // Tạo thông báo cho người đặt sân
        // $notification = $this->notificationRepository->create([
        //     'user_id' => $booking->user_id,
        //     'message' => "Yêu cầu đặt sân của bạn tại {$booking->venue_name} đã bị từ chối",
        //     'is_read' => false,
        // ]);

        // Phát sự kiện cập nhật trạng thái
        event(new BookingStatusUpdated(
            $booking->user_id,
            $booking->venue_name,
            $booking->booking_date,
            'declined'
        ));

        return $booking;
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