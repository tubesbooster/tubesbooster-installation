<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'status',
        'thumbnail',
        'seo_title',
        'seo_keywords',
        'seo_description',
        'title_slug'
    ];

    public function tags()
    {
        return $this->belongsToMany(ContentTag::class, 'content_tag_content_category', 'content_category_id', 'content_tag_id');
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
