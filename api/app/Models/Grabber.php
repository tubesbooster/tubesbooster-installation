<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grabber extends Model
{
    protected $table = 'grabbers';

    protected $fillable = [
        'platform',
        'status',
        'importType',
        'type',
        'duplicated',
        'data',
        'title_limit',
        'description_limit',
        'description',
        'queue',
        'new'
    ];
}