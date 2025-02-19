<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Album;
use App\Models\Channel;
use App\Models\SiteModel;
use App\Models\ContentCategory;
use App\Models\ContentTag;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function generate()
    {
        $frontendBaseUrl = env('FRONTEND_URL', '');

        $sitemap = Sitemap::create()
            ->add(Url::create("{$frontendBaseUrl}/")->setPriority(1.0)->setChangeFrequency('daily'))
            ->add(Url::create("{$frontendBaseUrl}/videos")->setPriority(0.8)->setChangeFrequency('daily'))
            ->add(Url::create("{$frontendBaseUrl}/galleries")->setPriority(0.7)->setChangeFrequency('daily'))
            ->add(Url::create("{$frontendBaseUrl}/channels")->setPriority(0.8)->setChangeFrequency('daily'))
            ->add(Url::create("{$frontendBaseUrl}/models")->setPriority(0.7)->setChangeFrequency('daily'))
            ->add(Url::create("{$frontendBaseUrl}/categories")->setPriority(0.8)->setChangeFrequency('daily'))
            ->add(Url::create("{$frontendBaseUrl}/tags")->setPriority(0.7)->setChangeFrequency('daily'));

        $videos = Video::all();
        foreach ($videos as $video) {
            $sitemap->add(
                Url::create("{$frontendBaseUrl}/video/{$video->title_slug}")
                    ->setPriority(0.9)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($video->updated_at)
            );
        }
        $albums = Album::all();
        foreach ($albums as $album) {
            $sitemap->add(
                Url::create("{$frontendBaseUrl}/gallery/{$album->title_slug}")
                    ->setPriority(0.8)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($album->updated_at)
            );
        }
        $channels = Channel::all();
        foreach ($channels as $channel) {
            $sitemap->add(
                Url::create("{$frontendBaseUrl}/channel/{$channel->title_slug}")
                    ->setPriority(0.9)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($channel->updated_at)
            );
        }
        $models = SiteModel::all();
        foreach ($models as $model) {
            $sitemap->add(
                Url::create("{$frontendBaseUrl}/model/{$model->title_slug}")
                    ->setPriority(0.8)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($model->updated_at)
            );
        }
        $categories = ContentCategory::all();
        foreach ($categories as $category) {
            $sitemap->add(
                Url::create("{$frontendBaseUrl}/videos/{$category->title_slug}")
                    ->setPriority(0.9)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($category->updated_at)
            );
        }
        $tags = ContentTag::all();
        foreach ($tags as $tag) {
            $sitemap->add(
                Url::create("{$frontendBaseUrl}/videos/tag/{$tag->title_slug}")
                    ->setPriority(0.8)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($tag->updated_at)
            );
        }

        return $sitemap->toResponse(request());
    }
}
