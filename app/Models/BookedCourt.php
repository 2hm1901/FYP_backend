<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class BookedCourt extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'booked_court';

    protected $fillable = [
        'user_id',
        'venue_id',
        'venue_name',
        'venue_location',
        'renter_name',
        'renter_email',
        'renter_phone',
        'courts_booked',
        'total_price',
        'booking_date',
        'note',
    ];
}