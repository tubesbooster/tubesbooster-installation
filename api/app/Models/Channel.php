<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $table = 'channels';

    protected $fillable = [
        'banner_wide',
        'banner_square',
        'logo',
        'cover',
        'title',
        'short_description',
        'description',
        'status',
        'referral_link',
        'show_user',
        'title_slug',
        'referral_label'
    ];

    public function categories()
    {
        return $this->belongsToMany(ContentCategory::class);
    }

    public function tags()
    {
        return $this->belongsToMany(ContentTag::class);
    }

    public function models()
    {
        return $this->belongsToMany(SiteModel::class, "channel_model", "channel_id", "model_id");
    }

    public function videos()
    {
        return $this->belongsToMany(Video::class, "video_channel", "channel_id", "video_id");
    }

    public function albums()
    {
        return $this->belongsToMany(Album::class, "album_channel", "channel_id", "album_id");
    }

    public function views()
    {
        return $this->hasMany(ChannelView::class);
    }
}