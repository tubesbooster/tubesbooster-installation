<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'ip_address',
        'type',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
