<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlbumLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'album_id',
        'ip_address',
        'type',
    ];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }
}
