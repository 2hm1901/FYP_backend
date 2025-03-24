<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Game extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'venue_id',
        'creator_id',
        'game_date',
        'court_number',
        'start_time',
        'end_time',
        'max_players',
        'current_players',
        'skill_level_required',
        'is_active',
    ];

    public function court()
    {
        return $this->belongsTo(Venue::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function participants()
    {
        return $this->hasMany(GameParticipant::class);
    }
}
