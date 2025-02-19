<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrabberItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grabber_id',
        'url',
        'message',
        'status'
    ];
}
