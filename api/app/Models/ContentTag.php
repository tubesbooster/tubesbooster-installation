<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'title_slug',
        'slug',
        'description',
        'status',
        'seo_title',
        'seo_keywords',
        'seo_description'
    ];

    public function categories()
    {
        return $this->belongsToMany(ContentCategory::class, 'content_tag_content_category', 'content_tag_id', 'content_category_id');
    }

    public function videos()
    {
        return $this->belongsToMany(Video::class);
    }

    public function albums()
    {
        return $this->belongsToMany(Album::class);
    }

    public function channels()
    {
        return $this->belongsToMany(Channel::class);
    }
}
