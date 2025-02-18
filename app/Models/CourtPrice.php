<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CourtPrice extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'court_price';

}
