<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venue\CreateVenueRequest;
use App\Http\Requests\Venue\GetMyVenuesRequest;
use App\Http\Requests\Venue\UpdateVenueRequest;
use App\Http\Resources\Venue\VenueBookingTableResource;
use App\Http\Resources\Venue\VenueCollection;
use App\Http\Resources\Venue\VenueListResource;
use App\Http\Resources\Venue\VenueResource;
use App\Services\Venue\VenueServiceInterface;
use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\CourtPrice;
use App\Models\BookedCourt;

/**
 * @OA\Tag(
 *     name="Venue",
 *     description="Quản lý thông tin sân badminton"
 * )
 */
class VenueController extends Controller
{
    protected $venueService;

    public function __construct(VenueServiceInterface $venueService)
    {
        $this->venueService = $venueService;
    }

    /**
     * API to get all the venues
     * 
     * @OA\Get(
     *     path="/api/getAllVenue",
     *     summary="Lấy danh sách tất cả sân",
     *     description="API lấy danh sách tất cả sân badminton",
     *     operationId="getVenueList",
     *     tags={"Venue"},
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
    public function getVenueList()
    {
        $venues = $this->venueService->getAllVenues();
        return response()->json(new VenueCollection($venues));
    }

    /**
     * API to get the venues of the owner
     * 
     * @OA\Get(
     *     path="/api/getMyVenues",
     *     summary="Lấy danh sách sân của chủ sân",
     *     description="API lấy danh sách sân badminton của chủ sân",
     *     operationId="getMyVenues",
     *     tags={"Venue"},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID của chủ sân",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy danh sách thành công",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
     */
    public function getMyVenues(GetMyVenuesRequest $request)
    {
        $userId = $request->query('user_id');
        $venues = $this->venueService->getVenuesByOwnerId($userId);
        
        return response()->json(VenueResource::collection($venues), 200);
    }

    /**
     * API to get the venue detail
     * 
     * @OA\Get(
     *     path="/api/getVenueDetail/{id}",
     *     summary="Lấy thông tin chi tiết sân",
     *     description="API lấy thông tin chi tiết sân badminton theo ID",
     *     operationId="getVenueDetail",
     *     tags={"Venue"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID của sân",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lấy thông tin thành công",
     *         @OA\JsonContent(
     *             type="object"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Không tìm thấy sân"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Lỗi server"
     *     )
     * )
     */
    public function getVenueDetail($id)
    {
        $venue = $this->venueService->getVenueById($id);
        
        if (!$venue) {
            return response()->json(['error' => 'Venue not found'], 404);
        }
        
        return response()->json(new VenueResource($venue));
    }

    /**
     * API to show the booking table
     */
    public function getBookingTable($id)
    {
        $venue = $this->venueService->getBookingTable($id);
        
        if (!$venue) {
            return response()->json(['error' => 'Venue not found'], 404);
        }

        return response()->json(new VenueBookingTableResource($venue));
    }

    /**
     * API to create a new venue
     */
    public function createNewVenue(CreateVenueRequest $request)
    {
        $validated = $request->validated();
        
        $venue = $this->venueService->createVenue($validated);
        
        return response()->json(new VenueResource($venue), 201);
    }

    /**
     * API to update a venue
     */
    public function updateVenue(UpdateVenueRequest $request, $id)
    {
        $validated = $request->validated();
        
        $venue = $this->venueService->updateVenue($id, $validated);

        return response()->json(new VenueResource($venue), 200);
    }

    /**
     * API to delete a venue
     */
    public function deleteVenue($id)
    {
        try {
            $this->venueService->deleteVenue($id);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa sân thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API to get all venues with ratings
     */
    public function getAllVenues()
    {
        try {
            $venues = $this->venueService->getAllVenuesWithRatings();

            return response()->json([
                'success' => true,
                'data' => VenueListResource::collection($venues)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
