<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_id',
        'reviewer_name',
        'reviewed_id',
        'reviewed_type', // 'user' hoặc 'venue'
        'rating',
        'comment',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewedUser()
    {
        return $this->belongsTo(User::class, 'reviewed_id');
    }

    public function reviewedVenue()
    {
        return $this->belongsTo(Venue::class, 'reviewed_id');
    }
}

