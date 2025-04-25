<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'phone',
        'location',
        'court_count',
        'open_time',
        'close_time',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewed_id')->where('reviewed_type', 'venue');
    }

    public function courtPrices()
    {
        return $this->hasMany(CourtPrice::class, 'venue_id');
    }

    public function bookedCourts()
    {
        return $this->hasMany(BookedCourt::class, 'venue_id');
    }
}


