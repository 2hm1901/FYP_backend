<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class BookedCourt extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'booked_court';

    protected $fillable = [
        'user_id',
        'court_id',
        'courts_booked',
        'total_price',
        'booking_date',
    ];
}
