<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'file',
        'slug',
        'status',
        'type',
        'date_scheduled',
        'source',
        'views',
        'likes',
        'duration',
        'embed',
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

    public function channels()
    {
        return $this->belongsToMany(Channel::class, "video_channel");
    }

    public function models()
    {
        return $this->belongsToMany(SiteModel::class, "model_video", "video_id", "model_id");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function views()
    {
        return $this->hasMany(VideoView::class);
    }

    public function likes()
    {
        return $this->hasMany(VideoLike::class, 'video_id');
    }
}
