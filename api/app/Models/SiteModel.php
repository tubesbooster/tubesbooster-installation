<?php

namespace App\Models;

  

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class SiteModel extends Model
{
    use HasFactory;
    protected $table = "models";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'stage_name',
        'birth_date',
        'city',
        'country',
        'gender',
        'height',
        'weight',
        'measurements',
        'hair_color',
        'eye_color',
        'ethnicity',
        'site_name',
        'url',
        'biography',
        'orientation',
        'interests',
        'thumbnail',
        'cover',
        'social_media',
        'relationship',
        'tattoos',
        'piercings',
        'career_status',
        'career_start',
        'career_end',
        'status',
        'title_slug'
    ];

    public function videos()
    {
        return $this->belongsToMany(Video::class, "model_video", "model_id", "video_id");
    }

    public function albums()
    {
        return $this->belongsToMany(Album::class, "album_model", "model_id", "album_id");
    }

    public function channels()
    {
        return $this->belongsToMany(Channel::class, "channel_model", "model_id", "channel_id");
    }
}
