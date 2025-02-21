<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Venue;
use App\Models\CourtPrice;

class VenueController extends Controller
{
    //API to get all the venues
    public function getVenueList()
    {
        $venues = Venue::all();
        return response()->json($venues);
    }
    //API to get the venue detail
    public function getVenueDetail($id){
        $venues = Venue::find($id);
        return response()->json($venues);
    }
    //API to show the booking table
    public function getBookingTable($id){
        $venues = Venue::find($id);
        $courtPrices = CourtPrice::where("court_id", (int)$id)->get();
        return response()->json([
            'venue' => $venues, 
            'courtPrices' => $courtPrices
        ]);
    }

}
