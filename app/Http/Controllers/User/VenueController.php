<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\CourtPrice;
use App\Models\BookedCourt;

class VenueController extends Controller
{
    //API to get all the venues
    public function getVenueList()
    {
        $venues = Venue::all();
        return response()->json($venues);
    }

    //API to get the venues of the owner
    public function getMyVenues(Request $request)
    {
        {
            $userId = $request->query('user_id');
            $venues = Venue::where('owner_id', $userId)->get();
            $response = $venues->map(function ($venue) {
                $courtPrice = CourtPrice::where('venue_id', $venue->id)->first();
                return [
                    'id' => $venue->id,
                    'owner_id' => $venue->owner_id,
                    'name' => $venue->name,
                    'phone' => $venue->phone,
                    'location' => $venue->location,
                    'court_count' => $venue->court_count,
                    'open_time' => $venue->open_time,
                    'close_time' => $venue->close_time,
                    'created_at' => $venue->created_at,
                    'updated_at' => $venue->updated_at,
                    'courtPrices' => $courtPrice ? [
                        [
                            'court_id' => $venue->id,
                            'price_slots' => $courtPrice->price_slots,
                            'id' => $courtPrice->_id,
                        ]
                    ] : [],
                ];
            });
            return response()->json($response, 200);
        }
    }
    //API to get the venue detail
    public function getVenueDetail($id)
    {
        $venues = Venue::find($id);
        return response()->json($venues);
    }
    //API to show the booking table
    public function getBookingTable($id)
    {
        $venues = Venue::find($id);
        $courtPrices = CourtPrice::where("venue_id", (int) $id)->get();
        return response()->json([
            'venue' => $venues,
            'courtPrices' => $courtPrices
        ]);
    }
    //API to create a new venue
    public function createNewVenue(Request $request)
    {
        // Validation dữ liệu từ form
        $validated = $request->validate([
            'owner_id' => 'required|exists:users,id',
            'name' => 'required|string|max:100',
            'phone' => 'required|string|size:10',
            'location' => 'required|string',
            'court_count' => 'required|integer|min:1',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
            'price_slots' => 'required|array|min:1',
            'price_slots.*.start_time' => 'required|date_format:H:i',
            'price_slots.*.end_time' => 'required|date_format:H:i|after:price_slots.*.start_time',
            'price_slots.*.price' => 'required|integer|min:1',
        ]);

        // Lưu vào bảng venues (SQL)
        $venue = Venue::create([
            'owner_id' => $validated['owner_id'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'location' => $validated['location'],
            'court_count' => $validated['court_count'],
            'open_time' => $validated['open_time'],
            'close_time' => $validated['close_time'],
        ]);

        // Lưu price_slots vào collection court_prices (MongoDB)
        $courtPrice = CourtPrice::create([
            'venue_id' => $venue->id,
            'price_slots' => $validated['price_slots'],
        ]);

        // Kiểm tra xem $courtPrice có dữ liệu không
        if (!$courtPrice) {
            return response()->json(['error' => 'Không thể tạo CourtPrice'], 500);
        }

        // Gộp dữ liệu theo cấu trúc mong muốn
        $responseData = [
            'id' => $venue->id,
            'owner_id' => $venue->owner_id,
            'name' => $venue->name,
            'phone' => $venue->phone,
            'location' => $venue->location,
            'court_count' => $venue->court_count,
            'open_time' => $venue->open_time,
            'close_time' => $venue->close_time,
            'created_at' => $venue->created_at,
            'updated_at' => $venue->updated_at,
            'courtPrices' => [
                [
                    'court_id' => $venue->id, 
                    'price_slots' => $courtPrice->price_slots, 
                    'id' => $courtPrice->_id, 
                ]
            ],
        ];

        // Trả về response gộp
        return response()->json($responseData, 201);
    }
    // Chỉnh sửa venue
    public function updateVenue(Request $request, $id)
    {
        $venue = Venue::findOrFail($id);

        $validated = $request->validate([
            'owner_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|size:10',
            'location' => 'sometimes|string',
            'court_count' => 'sometimes|integer|min:1',
            'open_time' => 'sometimes|date_format:H:i',
            'close_time' => 'sometimes|date_format:H:i|after:open_time',
            'price_slots' => 'sometimes|array|min:1',
            'price_slots.*.start_time' => 'required_with:price_slots|date_format:H:i',
            'price_slots.*.end_time' => 'required_with:price_slots|date_format:H:i|after:price_slots.*.start_time',
            'price_slots.*.price' => 'required_with:price_slots|integer|min:1',
        ]);

        // Cập nhật thông tin venue (SQL)
        $venue->update(array_filter($validated, function ($key) {
            return $key !== 'price_slots'; // Loại bỏ price_slots khỏi dữ liệu venue
        }, ARRAY_FILTER_USE_KEY));

        // Cập nhật CourtPrice (MongoDB) nếu có price_slots
        if (isset($validated['price_slots'])) {
            $courtPrice = CourtPrice::where('venue_id', $venue->id)->first();
            if ($courtPrice) {
                $courtPrice->update([
                    'price_slots' => array_map(function ($slot) {
                        return [
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'price' => (int) $slot['price'],
                        ];
                    }, $validated['price_slots']),
                ]);
            } else {
                // Nếu chưa có CourtPrice, tạo mới
                $courtPrice = CourtPrice::create([
                    'venue_id' => $venue->id,
                    'price_slots' => array_map(function ($slot) {
                        return [
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'price' => (int) $slot['price'],
                        ];
                    }, $validated['price_slots']),
                ]);
            }
        } else {
            $courtPrice = CourtPrice::where('venue_id', $venue->id)->first();
        }

        // Gộp dữ liệu trả về
        $responseData = [
            'id' => $venue->id,
            'owner_id' => $venue->owner_id,
            'name' => $venue->name,
            'phone' => $venue->phone,
            'location' => $venue->location,
            'court_count' => $venue->court_count,
            'open_time' => $venue->open_time,
            'close_time' => $venue->close_time,
            'created_at' => $venue->created_at,
            'updated_at' => $venue->updated_at,
            'courtPrices' => $courtPrice ? [
                [
                    'court_id' => $venue->id,
                    'price_slots' => $courtPrice->price_slots,
                    'id' => $courtPrice->_id,
                ],
            ] : [],
        ];

        return response()->json($responseData, 200);
    }

    // Xóa venue
    public function deleteVenue($id)
    {
        try {
            $venue = Venue::findOrFail($id);
            
            // Xóa tất cả các liên kết liên quan
            $venue->reviews()->delete();
            CourtPrice::where('venue_id', $venue->id)->delete();
            BookedCourt::where('venue_id', $venue->id)->delete();
            
            // Xóa sân
            $venue->delete();

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

    public function getAllVenues()
    {
        try {
            $venues = Venue::with(['owner', 'reviews'])->get();

            $venuesWithRatings = $venues->map(function($venue) {
                $reviews = $venue->reviews;
                $averageRating = $reviews->avg('rating');
                
                return [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'location' => $venue->location,
                    'owner' => $venue->owner ? [
                        'id' => $venue->owner->id,
                        'username' => $venue->owner->username,
                        'email' => $venue->owner->email
                    ] : null,
                    'created_at' => $venue->created_at,
                    'average_rating' => round($averageRating, 1),
                    'total_reviews' => $reviews->count()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $venuesWithRatings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

}
