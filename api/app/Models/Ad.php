<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'type',
        'source',
        'file',
        'status',
        'views',
        'scheduled_from',
        'scheduled_to'
    ];

    public function categories()
    {
        return $this->belongsToMany(ContentCategory::class);
    }
}
