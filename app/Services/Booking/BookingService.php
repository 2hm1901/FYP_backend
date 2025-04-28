<?php

namespace App\Services\Booking;

use App\Repositories\Booking\BookingRepositoryInterface;
use App\Repositories\Game\GameRepositoryInterface;
use App\Repositories\GameParticipant\GameParticipantRepositoryInterface;
use App\Repositories\Notification\NotificationRepositoryInterface;
use App\Repositories\Venue\VenueRepositoryInterface;
use Illuminate\Support\Str;

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

        foreach ($booking->courts_booked as $key => $court) {
            $booking->courts_booked[$key]['status'] = 'accepted';
        }

        $this->bookingRepository->update($booking);

        // Tạo thông báo cho người đặt sân
        $notificationData = [
            'user_id' => $booking->user_id,
            'message' => "Yêu cầu đặt sân của bạn tại {$booking->venue_name} đã được chấp nhận",
            'is_read' => false,
        ];

        $this->notificationRepository->create($notificationData);

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

        foreach ($booking->courts_booked as $key => $court) {
            $booking->courts_booked[$key]['status'] = 'declined';
        }

        $this->bookingRepository->update($booking);

        // Xoá game nếu có
        $games = $this->gameRepository->findById($bookingId);
        if ($games) {
            // Xoá người tham gia
            $participants = $this->gameParticipantRepository->getParticipantsByGameId($bookingId);
            foreach ($participants as $participant) {
                $this->gameRepository->delete($participant);
            }

            // Xoá game
            $this->gameRepository->delete($games);
        }

        // Tạo thông báo cho người đặt sân
        $notificationData = [
            'user_id' => $booking->user_id,
            'message' => "Yêu cầu đặt sân của bạn tại {$booking->venue_name} đã bị từ chối",
            'is_read' => false,
        ];

        $this->notificationRepository->create($notificationData);

        return $booking;
    }
} 