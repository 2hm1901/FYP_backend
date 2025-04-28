<?php

namespace App\Services\Venue;

use App\Repositories\Venue\VenueRepositoryInterface;
use App\Repositories\CourtPrice\CourtPriceRepositoryInterface;
use App\Repositories\BookedCourt\BookedCourtRepositoryInterface;

class VenueService implements VenueServiceInterface
{
    protected $venueRepository;
    protected $courtPriceRepository;
    protected $bookedCourtRepository;

    public function __construct(
        VenueRepositoryInterface $venueRepository,
        CourtPriceRepositoryInterface $courtPriceRepository,
        BookedCourtRepositoryInterface $bookedCourtRepository
    ) {
        $this->venueRepository = $venueRepository;
        $this->courtPriceRepository = $courtPriceRepository;
        $this->bookedCourtRepository = $bookedCourtRepository;
    }

    /**
     * Lấy tất cả venue
     */
    public function getAllVenues()
    {
        return $this->venueRepository->getAllVenues();
    }

    /**
     * Lấy danh sách venue của một owner
     */
    public function getVenuesByOwnerId($ownerId)
    {
        return $this->venueRepository->getVenuesByOwnerId($ownerId);
    }

    /**
     * Lấy venue theo id
     */
    public function getVenueById($id)
    {
        return $this->venueRepository->getVenueById($id);
    }

    /**
     * Lấy booking table của một venue
     */
    public function getBookingTable($id)
    {
        $venue = $this->venueRepository->getVenueById($id);
        if (!$venue) {
            return null;
        }
        
        return $venue;
    }

    /**
     * Tạo venue mới
     */
    public function createVenue(array $data)
    {
        // Tạo venue
        $venueData = [
            'owner_id' => $data['owner_id'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'location' => $data['location'],
            'court_count' => $data['court_count'],
            'open_time' => $data['open_time'],
            'close_time' => $data['close_time'],
        ];
        
        $venue = $this->venueRepository->createVenue($venueData);

        // Tạo court price
        $courtPriceData = [
            'venue_id' => $venue->id,
            'price_slots' => $data['price_slots'],
        ];
        
        $courtPrice = $this->courtPriceRepository->createCourtPrice($courtPriceData);

        return $venue;
    }

    /**
     * Cập nhật venue
     */
    public function updateVenue($id, array $data)
    {
        // Dữ liệu venue
        $venueData = array_filter($data, function ($key) {
            return !in_array($key, ['price_slots']);
        }, ARRAY_FILTER_USE_KEY);
        
        // Cập nhật venue
        $venue = $this->venueRepository->updateVenue($id, $venueData);

        // Cập nhật court price nếu có
        if (isset($data['price_slots'])) {
            $courtPrice = $this->courtPriceRepository->getCourtPriceByVenueId($venue->id);
            
            if ($courtPrice) {
                $this->courtPriceRepository->updateCourtPrice($venue->id, [
                    'price_slots' => array_map(function ($slot) {
                        return [
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'price' => (int) $slot['price'],
                        ];
                    }, $data['price_slots']),
                ]);
            } else {
                $this->courtPriceRepository->createCourtPrice([
                    'venue_id' => $venue->id,
                    'price_slots' => array_map(function ($slot) {
                        return [
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'price' => (int) $slot['price'],
                        ];
                    }, $data['price_slots']),
                ]);
            }
        }

        return $venue;
    }

    /**
     * Xoá venue
     */
    public function deleteVenue($id)
    {
        // Xoá court prices
        $this->courtPriceRepository->deleteCourtPriceByVenueId($id);
        
        // Xoá booked courts
        $this->bookedCourtRepository->deleteBookedCourtsByVenueId($id);
        
        // Xoá venue
        return $this->venueRepository->deleteVenue($id);
    }

    /**
     * Lấy tất cả venue kèm thông tin rating
     */
    public function getAllVenuesWithRatings()
    {
        return $this->venueRepository->getAllVenuesWithOwnerAndReviews();
    }
} 