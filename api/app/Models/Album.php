<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'status',
        'type',
        'views',
        'source',
        'url',
        'description',
        'date_scheduled',
        'featured',
        'user_id',
        'title_slug'
    ];

    public function categories()
    {
        return $this->belongsToMany(ContentCategory::class);
    }

    public function tags()
    {
        return $this->belongsToMany(ContentTag::class);
    }

    public function photos()
    {
        return $this->belongsToMany(Photo::class);
    }

    public function channels()
    {
        return $this->belongsToMany(Channel::class);
    }

    public function models()
    {
        return $this->belongsToMany(SiteModel::class, "album_model", "album_id", "model_id");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function views()
    {
        return $this->hasMany(AlbumView::class);
    }

    public function likes()
    {
        return $this->hasMany(AlbumLike::class, 'album_id');
    }
}
