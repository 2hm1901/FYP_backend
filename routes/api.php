<?php

use App\Http\Controllers\User\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\VenueController;
use App\Http\Controllers\User\GameController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\BankAccountController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\User\ReviewController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Auth APIs
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

//User APIs
Route::get('/venue-owner', [UserController::class,'getOwnerInfo']);
Route::get('/getUser', [UserController::class, 'getUser']);
Route::post('/users/add-points', [GameController::class, 'addPoints'])->middleware('auth:sanctum');

//Bank Account APIs
Route::get('/bank-account/{userId}', [BankAccountController::class, 'getUserBankAccount'])->middleware('auth:sanctum');

//Forgot Password APIs
Route::post('/forgot-password', [ForgotPasswordController::class, 'forgot']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

//Venue APIs
Route::get("/getAllVenue", [VenueController::class,"getVenueList"]);
Route::get("/getVenueDetail/{id}", [VenueController::class,"getVenueDetail"]);
Route::get("/getBookingTable/{id}", [VenueController::class,"getBookingTable"]);
Route::get("/getMyVenues", [VenueController::class,"getMyVenues"]);
Route::post("/createNewVenue", [VenueController::class,"createNewVenue"]);
Route::put('/venues/{id}', [VenueController::class, 'updateVenue']);
Route::delete('/venues/{id}', [VenueController::class, 'deleteVenue']);

//Game APIs
Route::get("/getAllGame", [GameController::class,"getAllGame"]);
Route::get("/getGames", [GameController::class,"getGameList"]);
Route::get("/getParticipatingGames/{userId}", [GameController::class,"getParticipatingGames"]);
Route::get("/getGameDetail/{id}", [GameController::class,"getGameDetail"]);
Route::post("/createGame", [GameController::class,"createGame"]);
Route::get("/getGameStatus", [GameController::class,"getGameStatus"]);
Route::put("/updateGame", [GameController::class,"updateGame"]);
Route::delete("/cancelGame", [GameController::class,"cancelGame"]);
Route::post("/requestJoinGame", [GameController::class,"requestJoinGame"]);
Route::post("/acceptJoinRequest", [GameController::class,"acceptJoinRequest"]);
Route::post("/rejectJoinRequest", [GameController::class,"rejectJoinRequest"]);
Route::get("/getJoinRequests/{gameId}", [GameController::class,"getJoinRequests"]);
Route::post("/kickPlayer", [GameController::class,"kickPlayer"]);

//Booking APIs
Route::get("/getBookings", [BookingController::class,"getBookings"]);
Route::get('/getBookedCourts/{id}', [BookingController::class, 'getBookedCourt']);
Route::post('/bookCourt', [BookingController::class, 'bookCourt']);
Route::get('/getBookedCourtList', [BookingController::class,'getBookedCourtList']);
Route::get('/getRequests', [BookingController::class,'getRequests']);
Route::put('/cancelCourt', [BookingController::class, 'cancelCourt']);
Route::post('/bookings/accept', [BookingController::class, 'acceptBooking'])->middleware('auth:sanctum');
Route::post('/bookings/decline', [BookingController::class, 'declineBooking'])->middleware('auth:sanctum');

//Notification APIs
Route::get('/notifications', [NotificationController::class, 'getNotifications']);
Route::post('/notifications/mark-read', [NotificationController::class, 'markNotificationAsRead'])->middleware('auth:sanctum');

//Profile APIs
Route::middleware('auth:sanctum')->group(function () {
    Route::put('updateProfile', [ProfileController::class, 'update']);
});

//Get image
// routes/api.php
Route::get('/avatar/{filename}', function ($filename) {
    $path = storage_path('app/public/avatars/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});

//Get QR code image
Route::get('/qr_codes/{filename}', function ($filename) {
    $path = storage_path('app/public/qr_codes/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});

//Get payment image
Route::get('/payment_images/{filename}', function ($filename) {
    $path = storage_path('app/public/payment_images/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});

//Review APIs
Route::post('/reviews', [ReviewController::class, 'createReview'])->middleware('auth:sanctum');
Route::get('/reviews', [ReviewController::class, 'getReviews']);

//Admin APIs
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/users', [UserController::class, 'getAllUsers']);
    Route::delete('/admin/users/{id}', [UserController::class, 'deleteUser']);
    Route::get('/admin/venues', [VenueController::class, 'getAllVenues']);
    Route::delete('/admin/venues/{id}', [VenueController::class, 'deleteVenue']);
});


