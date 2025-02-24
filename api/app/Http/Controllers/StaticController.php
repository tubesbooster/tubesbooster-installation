<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaticController extends Controller {
    public function showStatic(Request $request){
        $url = $request->url;
        $protocol = 'https';
        $domain = $_SERVER['HTTP_HOST'];
        $fullUrl = $protocol . '://' . $domain . $url;
        $settingsController = new SettingsController;
        $settings = $settingsController->indexFrontend()->original;
        //dd($settings);
        $verificationCode = "";
        if(isset($settings["verificationCode"])){
            $verificationCode = $settings["verificationCode"];    
        }

        $description = $settings["siteDescription"];
        $title = $settings["siteTitle"];
        
        $homeRequest = (new FrontendController)->home();
        $home = json_decode($homeRequest->getContent());

        $videosBeingWatchedNowArray = [];
        if(isset($home->videosBeingWatchedNow)){
            foreach($home->videosBeingWatchedNow as $key => $value){
                if(isset($value->title_slug) && isset($value->title)){
                    $videosBeingWatchedNowArray[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value->title_slug.'">'.$value->title.'</a></li>';  
                }
            }
        }
        $videosBeingWatchedNow = implode("", $videosBeingWatchedNowArray);

        $videosArray = [];
        if(isset($home->videosNew)){
            foreach($home->videosNew as $key => $value){
                if(isset($value->title_slug) && isset($value->title)){
                    $videosArray[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value->title_slug.'">'.$value->title.'</a></li>';  
                }
            }
        }
        $videos = implode("", $videosArray);

        $albumsArray = [];
        if(isset($home->albums)){
            foreach($home->albums as $key => $value){
                if(isset($value->title_slug) && isset($value->title)){
                    $albumsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/gallery/'.$value->title_slug.'">'.$value->title.'</a></li>';  
                }
            }
        }
        $albums = implode("", $albumsArray);

        $modelsArray = [];
        if(isset($home->models)){
            foreach($home->models as $key => $value){
                if(isset($value->title_slug) && isset($value->stage_name)){
                    $modelsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/model/'.$value->title_slug.'">'.$value->stage_name.'</a></li>';  
                }
            }
        }
        $models = implode("", $modelsArray);
        
        $descriptionLong = '
            <!-- Description Section -->
            <section>
                <h2>About ' . $title . '</h2>
                <p>' . $description . '</p>
            </section>
            <!-- Videos Being Watched Now Section -->
            <section>
                <h2>Being Watched Now</h2>
                <p>' . $videosBeingWatchedNow . '</p>
            </section>
            <!-- Videos Recently Published Section -->
            <section>
                <h2>Recently Published Videos</h2>
                <p>' . $videosBeingWatchedNow . '</p>
            </section>
            <!-- Galleries Section -->
            <section>
                <h2>Galleries</h2>
                <p>' . $albums . '</p>
            </section>
            <!-- Models Section -->
            <section>
                <h2>Models</h2>
                <p>' . $models . '</p>
            </section>
        ';

        $imageUrl = asset('assets/file_header_logo.png');
        $contentType = 'website';
        $type = 'ItemList';
        $uploadDate = '2025-01-01';
        $schemaScript = '';
        $keywords = $settings["siteKeywords"];

        if (preg_match('#^/video/(.+)$#', $url, $matches)) {
            $contentType = 'video/other';
            $type = 'VideoObject';
            $videoSlug = $matches[1];
            $videoController = new VideoController;
            $video = $videoController->show($videoSlug, new Request)->original;
            $title = $video["name"].' - Video';
            $uploadDate = date('Y-m-d', strtotime($video["datePublished"]));
            $duration = UtilsController::convertDurationToISO8601($video["length"]);

            $categories = [];
            if(isset($video["categoriesNew"])){
                foreach($video["categoriesNew"] as $category){
                    $categories[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/'.$category["title_slug"].'">'.$category["name"].'</a></li>';        
                }
            }
            $categories = implode("", $categories);

            $tags = [];
            if(isset($video["tagsNew"])){
                foreach($video["tagsNew"] as $tag){
                    $tags[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/tag/'.$tag["title_slug"].'">'.$tag["name"].'</a></li>';        
                }
            }
            $tags = implode("", $tags);

            $models = [];
            if(isset($video["models"])){
                foreach($video["models"] as $model){
                    $models[] = '<li><a href="'.$protocol . '://' . $domain.'/model/'.$model["title_slug"].'">'.$model["name"].'</a></li>';        
                }
            }
            $models = implode("", $models);

            $channels = [];
            if(isset($video["channel"])){
                $channels[] = '<li><a href="'.$protocol . '://' . $domain.'/channel/'.$video["channel"]["title_slug"].'">'.$video["channel"]["title"].'</a></li>';        
            }
            $channels = implode("", $channels);

            $related = [];
            foreach($video["related"] as $key => $value){
                $related[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value["title_slug"].'">'.$value['name'].'</a></li>';
            }
            $related = implode('', $related);

            $videoEmbed = '';
            $file = str_replace('.mp4', '-240p.mp4', asset('videos/'.$video['file']));
            if(file_exists(str_replace('.mp4', '-240p.mp4', public_path('videos/'.$video['file'])))){
                $videoEmbed = '<video height="240" alt="'.$title.'" src="'.$file.'" controls></video>';    
            }

            if(isset($video["thumbnail"])){
                $imageUrl = asset('videos/thumbs/'.$video["thumbnail"]);
            }

            $description = $video["description"];
            $descriptionLong = '
                <!-- Video File Section -->
                <section>
                    <h2>Video</h2>
                    '.$videoEmbed.'
                </section>
                <!-- Video Description Section -->
                <section>
                    <p>'.$video["description"].'</p>
                <section>
                    <h2>Categories</h2>
                    <ul>'.$categories.'</ul>
                </section>
                <!-- Tags Section -->
                <section>
                    <h2>Tags</h2>
                    <ul>'.$tags.'</ul>
                </section>
                <!-- Models Section -->
                <section>
                    <h2>Models</h2>
                    <ul>'.$models.'</ul>
                </section>
                <!-- Channels Section -->
                <section>
                    <h2>Channel</h2>
                    <ul>'.$channels.'</ul>
                </section>
                <!-- Related Videos Section -->
                <section>
                    <h2>Related videos</h2>
                    <ul>'.$related.'</ul>
                </section>
            ';

            $schemaScript = '
                <script type="application/ld+json">
                    {
                        "@context": "https://schema.org",
                        "@type": "'.$type.'",
                        "name": "'.$title.'",
                        "description": "'.$description.'",
                        "thumbnailUrl": "'.$imageUrl.'",
                        "uploadDate": "'.$uploadDate.'",
                        "duration": "'.$duration.'",
                        "contentUrl": "'.$file.'",
                        "publisher": {
                            "@type": "Organization",
                            "name": "'.$settings["siteTitle"].'",
                            "url": "'.$protocol . '://' . $domain.'"
                        }
                    }
                </script>
            ';
        }

        if (preg_match('#^/gallery/(.+)$#', $url, $matches)) {
            $albumSlug = $matches[1];
            $albumController = new AlbumController;
            $album = $albumController->show($albumSlug, new Request)->original;
            $title = $album["name"].' - Gallery';
            $categories = [];
            if(isset($album["categories"])){
                foreach($album["categories"] as $category){
                    $categories[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/'.$category["title_slug"].'">'.$category["name"].'</a></li>'; 
                }
            }
            $categories = implode("", $categories);

            $tags = [];
            if(isset($album["tags"])){
                foreach($album["tags"] as $tag){
                    $tags[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/tag/'.$tag["title_slug"].'">'.$tag["name"].'</a></li>';    
                }
            }
            $tags = implode("", $tags);

            $models = [];
            if(isset($album["models"])){
                foreach($album["models"] as $model){
                    $models[] = '<li><a href="'.$protocol . '://' . $domain.'/model/'.$model["title_slug"].'">'.$model["name"].'</a></li>';  
                }
            }
            $models = implode("", $models);

            $channels = [];
            if(isset($album["channel"])){
                $channels[] = '<li><a href="'.$protocol . '://' . $domain.'/channel/'.$album["channel"]["title_slug"].'">'.$album["channel"]["title"].'</a></li>';   
            }
            $channels = implode("", $channels);

            $imageUrl = !empty($album["current_featured"]) ? $album["current_featured"] : $album["photos"][0];
            
            $description = $album["description"];
            $descriptionLong = 
                $album["description"].'
                <!-- Categories Section -->
                <section>
                    <h2>Categories</h2>
                    <ul>'.$categories.'</ul>
                </section>
                <!-- Tags Section -->
                <section>
                    <h2>Tags</h2>
                    <ul>'.$tags.'</ul>
                </section>
                <!-- Models Section -->
                <section>
                    <h2>Models</h2>
                    <ul>'.$models.'</ul>
                </section>
                <!-- Channels Section -->
                <section>
                    <h2>Channel</h2>
                    <ul>'.$channels.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/channel/(.+)$#', $url, $matches)) {
            $channelSlug = $matches[1];
            $channelController = new ChannelController;
            $channel = $channelController->show($channelSlug, new Request)->original;
            $title = $channel["title"].' - Channel';
            
            $categories = [];
            if(isset($channel["categoriesNew"])){
                foreach($channel["categoriesNew"] as $category){
                    $categories[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/'.$category["title_slug"].'">'.$category["name"].'</a></li>'; 
                }
            }
            $categories = implode("", $categories);

            $tags = [];
            if(isset($channel["tagsNew"])){
                foreach($channel["tagsNew"] as $tag){
                    $tags[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/tag/'.$tag["title_slug"].'">'.$tag["name"].'</a></li>';    
                }
            }
            $tags = implode("", $tags);
            $models = [];
            if(isset($channel["models"])){
                foreach($channel["models"] as $model){
                    $models[] = '<li><a href="'.$protocol . '://' . $domain.'/model/'.$model["title_slug"].'">'.$model["name"].'</a></li>';  
                }
            }
            $models = implode("", $models);

            $featured = [];
            if(isset($channel["featured"])){
                foreach($channel["featured"] as $key => $value){
                    $featured[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value["title_slug"].'">'.$value["title"].'</a></li>';  
                }
            }
            $featured = implode("", $featured);

            $imageUrl = $channel["current_logo"];
            
            $description = $channel["description"];
            $descriptionLong = '
                <!-- Channels Description Section -->
                <section>
                    <p>'.$channel["description"].'</p>
                </section
                <!-- Channels Categories Section -->
                <section>
                    <h2>Categories</h2>
                    <ul>'.$categories.'</ul>
                </section>
                <!-- Channels Tags Section -->
                <section>
                    <h2>Tags</h2>
                    <ul>'.$tags.'</ul>
                </section>
                <!-- Channels Models Section -->
                <section>
                    <h2>Models</h2>
                    <ul>'.$models.'</ul>
                </section>
                <!-- Channels Featured Videos Section -->
                <section>
                    <h2>Featured Videos</h2>
                    <ul>'.$featured.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/model/(.+)$#', $url, $matches)) {
            $modelSlug = $matches[1];
            $modelController = new ModelController;
            $model = $modelController->show($modelSlug, new Request)->original;
            $title = $model["name"].' - Model';
            
            $categories = [];
            if(isset($model["categoriesNew"])){
                foreach($model["categoriesNew"] as $category){
                    $categories[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/'.$category["title_slug"].'">'.$category["name"].'</a></li>'; 
                }
            }
            $categories = implode("", $categories);

            $tags = [];
            if(isset($model["tagsNew"])){
                foreach($model["tagsNew"] as $tag){
                    $tags[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/tag/'.$tag["title_slug"].'">'.$tag["name"].'</a></li>';    
                }
            }
            $tags = implode("", $tags);
            $models = [];
            if(isset($model["models"])){
                foreach($model["models"] as $model){
                    $models[] = '<li><a href="'.$protocol . '://' . $domain.'/model/'.$model["title_slug"].'">'.$model["name"].'</a></li>';  
                }
            }
            $models = implode("", $models);

            $featured = [];
            if(isset($model["featuredVideos"])){
                foreach($model["featuredVideos"] as $key => $value){
                    $featured[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value["title_slug"].'">'.$value["title"].'</a></li>';  
                }
            }
            $featured = implode("", $featured);

            $related = [];
            if(isset($model["relatedModels"])){
                foreach($model["relatedModels"] as $key => $value){
                    $related[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value["title_slug"].'">'.$value["stage_name"].'</a></li>';  
                }
            }
            $related = implode("", $related);

            $imageUrl = $model["current_thumbnail"];
            
            $description = $model["biography"];
            $name = $model["name"];

            $descriptionLong = '
                <!-- '.$name.'\' Description Section -->
                <section>
                    <h2>About '.$name.'</h2>
                    <p>'.$description.'</p>
                </section>
                <!-- '.$name.'\' Videos Section -->
                <section>
                    <h2>'.$name.'\'s Videos</h2>
                    <ul>'.$featured.'</ul>
                </section>
                <!-- '.$name.'\' Videos Section -->
                <section>
                    <h2>Models Related To '.$name.'</h2>
                    <ul>'.$related.'</ul>
                </section>
            ';
        }

        if (
            preg_match('#^/videos/(.+)$#', $url, $matches) &&
            !preg_match('#^/videos/tag/(.+)$#', $url, $void) &&
            !preg_match('#^/videos/all$#', $url, $void) &&
            !preg_match('#^/videos/all/page/(.+)$#', $url, $void) &&
            !preg_match('#^/videos/newest-videos$#', $url, $void) &&
            !preg_match('#^/videos/most-watched$#', $url, $void) &&
            !preg_match('#^/videos/top-rated$#', $url, $void)
        ) {
            $categorySlug = $matches[1];
            $categoryController = new ContentCategoryController;
            $category = $categoryController->show($categorySlug, new Request)->original;
            
            $request = new Request([
                'category_id' => $category["id"],
                'column' => 'id',
                'order' => 'desc',
                'limit' => 10
            ]);

            $videoController = new VideoController;
            $videos = $videoController->index($request);
            $videos = json_decode($videos->getContent());
            
            $albums = (new AlbumController)->index($request);
            $albums = json_decode($albums->getContent());
            
            $channels = (new ChannelController)->index($request);
            $channels = json_decode($channels->getContent());

            $name = $category["seo_title"] ?  $category["seo_title"] : $category["name"];
            $title = $name.' - Videos Category';
            
            if(!empty($category["seo_keywords"])){
                $keywords = $category["seo_keywords"];
            }

            $tags = [];
            if(isset($category["tags"])){
                foreach($category["tags"] as $key => $value){
                    $tags[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/tag/'.$value["title_slug"].'">'.$value["name"].'</a></li>';  
                }
            }
            $tags = implode("", $tags);

            $videosArray = [];
            if(isset($videos)){
                foreach($videos->data as $key => $value){
                    if(isset($value->title_slug) && isset($value->title)){
                        $videosArray[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value->title_slug.'">'.$value->title.'</a></li>';  
                    }
                }
            }
            $videosArray = implode("", $videosArray);

            $albumsArray = [];
            if(isset($albums)){
                foreach($albums->data as $key => $value){
                    if(isset($value->title_slug) && isset($value->title)){
                        $albumsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/gallery/'.$value->title_slug.'">'.$value->title.'</a></li>';  
                    }
                }
            }
            $albumsArray = implode("", $albumsArray);

            $channelsArray = [];
            if(isset($channels)){
                foreach($channels->data as $key => $value){
                    if(isset($value->title_slug) && isset($value->title)){
                        $channelsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/channel/'.$value->title_slug.'">'.$value->title.'</a></li>';  
                    }
                }
            }
            $channelsArray = implode("", $channelsArray);

            $imageUrl = $category["current_thumbnail"];
            
            $description = $category["seo_description"] ?  $category["seo_description"] : $category["description"];

            $descriptionLong = '
                <!-- '.$name.' Description Section -->
                <section>
                    <h2>About '.$name.'</h2>
                    <p>'.$description.'</p>
                </section>
                <!-- '.$name.' Tags Section -->
                <section>
                    <h2>'.$name.' Tags</h2>
                    <ul>'.$tags.'</ul>
                </section>
                <!-- '.$name.' Videos Section -->
                <section>
                    <h2>'.$name.' Videos</h2>
                    <ul>'.$videosArray.'</ul>
                </section>
                <!-- '.$name.' Galleries Section -->
                <section>
                    <h2>'.$name.' Galleries</h2>
                    <ul>'.$albumsArray.'</ul>
                </section>
                <section>
                    <h2>'.$name.' Channels</h2>
                    <ul>'.$channelsArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/videos/tag/(.+)$#', $url, $matches)) {
            $tagSlug = $matches[1];
            $tagController = new ContentTagController;
            $tag = $tagController->show($tagSlug, new Request)->original;
            
            $request = new Request([
                'tag_id' => $tag["id"],
                'column' => 'id',
                'order' => 'desc',
                'limit' => 10
            ]);

            $videoController = new VideoController;
            $videos = $videoController->index($request);
            $videos = json_decode($videos->getContent());
            
            $albums = (new AlbumController)->index($request);
            $albums = json_decode($albums->getContent());

            $name = ucwords($tag["seo_title"] ?  $tag["seo_title"] : $tag["name"]);
            $title = $name.' - Video Tag';
            
            if(!empty($tag["seo_keywords"])){
                $keywords = $tag["seo_keywords"];
            }

            $videosArray = [];
            if(isset($videos)){
                foreach($videos->data as $key => $value){
                    if(isset($value->title_slug) && isset($value->title)){
                        $videosArray[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value->title_slug.'">'.$value->title.'</a></li>';  
                    }
                }
            }
            $videosArray = implode("", $videosArray);

            $albumsArray = [];
            if(isset($albums)){
                foreach($albums->data as $key => $value){
                    if(isset($value->title_slug) && isset($value->title)){
                        $albumsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/gallery/'.$value->title_slug.'">'.$value->title.'</a></li>';  
                    }
                }
            }
            $albumsArray = implode("", $albumsArray);

            $description = $tag["seo_description"] ?  $tag["seo_description"] : $tag["description"];

            $descriptionLong = '
                <!-- '.$name.' Description Section -->
                <section>
                    <h2>About '.$name.'</h2>
                    <p>'.$description.'</p>
                </section>
                <!-- '.$name.' Videos Section -->
                <section>
                    <h2>'.$name.' Videos</h2>
                    <ul>'.$videosArray.'</ul>
                </section>
                <!-- '.$name.' Galleries Section -->
                <section>
                    <h2>'.$name.' Galleries</h2>
                    <ul>'.$albumsArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/search/(.+)$#', $url, $matches)) {
            $searchQuery = $matches[1];
            $request = new Request([
                'name' => $searchQuery
            ]);
            $searchController = new SearchController;
            $search = (new SearchController)->search($request)->original;
            $name = ucwords($searchQuery);
            
            if(isset($search["videos"]["data"][0])){
                $imageUrl = asset($search["videos"]["data"][0]['thumb']);
            }

            $videosArray = [];
            $videosNamesArray = [];
            foreach($search["videos"]["data"] as $key => $value){
                $videosArray[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value['title_slug'].'">'.$value['title'].'</a></li>';      
                $videosNamesArray[] = $value['title'];
            }
            $videosArray = implode("", $videosArray);
            $videosNamesArray = implode(", ", $videosNamesArray);

            $albumsArray = [];
            foreach($search["albums"]["data"] as $key => $value){
                $albumsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/gallery/'.$value['title_slug'].'">'.$value['title'].'</a></li>';      
            }
            $albumsArray = implode("", $albumsArray);

            $description = 'Watch videos: ' . $videosNamesArray;
            $title = $title . ' - Search For ' . $name . '';

            $descriptionLong = '
                <!-- Search For '.$name.' Videos Section -->
                <section>
                    <h2>'.$name.' Videos</h2>
                    <ul>'.$videosArray.'</ul>
                </section>
                <!-- Search For '.$name.' Galleries Section -->
                <section>
                    <h2>'.$name.' Galleries</h2>
                    <ul>'.$albumsArray.'</ul>
                </section>
            ';
        }

        if (
            preg_match('#^/videos$#', $url, $matches) ||
            preg_match('#^/videos/all$#', $url, $matches) ||
            preg_match('#^/videos/all/page/(.+)$#', $url, $matches)
        ) {
            $request = new Request([
                'column' => 'date_scheduled',
                'order' => 'desc',
                'limit' => 24,
                'disableAds' => true
            ]);
            $videos = (new VideoController)->index($request);
            $videos = json_decode($videos->getContent());
            
            if(isset($videos->data[0])){
                $imageUrl = explode('?', asset($videos->data[0]->thumb))[0];
            }

            $videosArray = [];
            $videosNamesArray = [];
            foreach($videos->data as $key => $value){
                $videosArray[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value->title_slug.'">'.$value->title.'</a></li>';      
                $videosNamesArray[] = $value->title;
            }
            $videosArray = implode("", $videosArray);
            $videosNamesArray = implode(", ", $videosNamesArray);

            $description = 'Watch videos: ' . $videosNamesArray;
            $title = 'Videos - ' . $settings["siteTitle"];

            $descriptionLong = '
                <!-- Description Section -->
                <section>
                    <p>' . $description . '</p>
                </section>
                <!-- Videos Section -->
                <section>
                    <h2>Videos</h2>
                    <ul>'.$videosArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/videos/newest-videos$#', $url, $matches)) {
            $request = new Request([
                'column' => 'date_scheduled',
                'order' => 'desc',
                'limit' => 24,
                'disableAds' => true
            ]);
            $videos = (new VideoController)->index($request);
            $videos = json_decode($videos->getContent());
            
            if(isset($videos->data[0])){
                $imageUrl = explode('?', asset($videos->data[0]->thumb))[0];
            }

            $videosArray = [];
            $videosNamesArray = [];
            foreach($videos->data as $key => $value){
                $videosArray[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value->title_slug.'">'.$value->title.'</a></li>';      
                $videosNamesArray[] = $value->title;
            }
            $videosArray = implode("", $videosArray);
            $videosNamesArray = implode(", ", $videosNamesArray);

            $description = 'Watch newest videos: ' . $videosNamesArray;
            $title = 'Newest Videos - ' . $settings["siteTitle"];

            $descriptionLong = '
                <!-- Description Section -->
                <section>
                    <p>' . $description . '</p>
                </section>
                <!-- Videos Section -->
                <section>
                    <h2>Newest Videos</h2>
                    <ul>'.$videosArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/videos/top-rated$#', $url, $matches)) {
            $request = new Request([
                'column' => 'likes',
                'order' => 'desc',
                'limit' => 24,
                'disableAds' => true
            ]);
            $videos = (new VideoController)->index($request);
            $videos = json_decode($videos->getContent());
            
            if(isset($videos->data[0])){
                $imageUrl = explode('?', asset($videos->data[0]->thumb))[0];
            }

            $videosArray = [];
            $videosNamesArray = [];
            foreach($videos->data as $key => $value){
                $videosArray[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value->title_slug.'">'.$value->title.'</a></li>';      
                $videosNamesArray[] = $value->title;
            }
            $videosArray = implode("", $videosArray);
            $videosNamesArray = implode(", ", $videosNamesArray);

            $description = 'Watch top rated videos: ' . $videosNamesArray;
            $title = 'Top Rated Videos - ' . $settings["siteTitle"];

            $descriptionLong = '
                <!-- Description Section -->
                <section>
                    <p>' . $description . '</p>
                </section>
                <!-- Videos Section -->
                <section>
                    <h2>Top Rated Videos</h2>
                    <ul>'.$videosArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/videos/most-watched$#', $url, $matches)) {
            $request = new Request([
                'column' => 'views',
                'order' => 'desc',
                'limit' => 24,
                'disableAds' => true
            ]);
            $videos = (new VideoController)->index($request);
            $videos = json_decode($videos->getContent());
            
            if(isset($videos->data[0])){
                $imageUrl = explode('?', asset($videos->data[0]->thumb))[0];
            }

            $videosArray = [];
            $videosNamesArray = [];
            foreach($videos->data as $key => $value){
                $videosArray[] = '<li><a href="'.$protocol . '://' . $domain.'/video/'.$value->title_slug.'">'.$value->title.'</a></li>';      
                $videosNamesArray[] = $value->title;
            }
            $videosArray = implode("", $videosArray);
            $videosNamesArray = implode(", ", $videosNamesArray);

            $description = 'Watch most watched videos: ' . $videosNamesArray;
            $title = 'Most Watched Videos - ' . $settings["siteTitle"];

            $descriptionLong = '
                <!-- Description Section -->
                <section>
                    <p>' . $description . '</p>
                </section>
                <!-- Videos Section -->
                <section>
                    <h2>Most Watched Videos</h2>
                    <ul>'.$videosArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/albums$#', $url, $matches) || preg_match('#^/albums(.+)$#', $url, $matches)) {
            $request = new Request([
                'column' => 'views',
                'order' => 'desc',
                'limit' => 24,
                'disableAds' => true
            ]);
            $albums = (new AlbumController)->index($request);
            $albums = json_decode($albums->getContent());
            
            if(isset($albums->data[0])){
                $imageUrl = explode('?', asset($albums->data[0]->thumb))[0];
            }

            $albumsArray = [];
            $albumsNamesArray = [];
            foreach($albums->data as $key => $value){
                $albumsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/gallery/'.$value->title_slug.'">'.$value->name.'</a></li>';      
                $albumsNamesArray[] = $value->name;
            }
            $albumsArray = implode("", $albumsArray);
            $albumsNamesArray = implode(", ", $albumsNamesArray);

            $description = 'Browse galleries: ' . $albumsNamesArray;
            $title = 'Galleries - ' . $settings["siteTitle"];

            $descriptionLong = '
                <!-- Description Section -->
                <section>
                    <p>' . $description . '</p>
                </section>
                <!-- Galleries Section -->
                <section>
                    <h2>Galleries</h2>
                    <ul>'.$albumsArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/channels$#', $url, $matches) || preg_match('#^/channels(.+)$#', $url, $matches)) {
            $request = new Request([
                'column' => 'views',
                'order' => 'desc',
                'limit' => 24,
                'disableAds' => true
            ]);
            $channels = (new ChannelController)->index($request);
            $channels = json_decode($channels->getContent());
            
            if(isset($channels->data[0])){
                $imageUrl = explode('?', asset($channels->data[0]->cover))[0];
            }

            $channelsArray = [];
            $channelsNamesArray = [];
            foreach($channels->data as $key => $value){
                $channelsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/channel/'.$value->title_slug.'">'.$value->title.'</a></li>';      
                $channelsNamesArray[] = $value->title;
            }
            $channelsArray = implode("", $channelsArray);
            $channelsNamesArray = implode(", ", $channelsNamesArray);

            $description = 'Browse channels: ' . $channelsNamesArray;
            $title = 'Channels - ' . $settings["siteTitle"];

            $descriptionLong = '
                <!-- Description Section -->
                <section>
                    <p>' . $description . '</p>
                </section>
                <!-- Channels Section -->
                <section>
                    <h2>Channels</h2>
                    <ul>'.$channelsArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/models$#', $url, $matches) || preg_match('#^/models(.+)$#', $url, $matches)) {
            $request = new Request([
                'column' => 'videos',
                'order' => 'desc',
                'limit' => 24,
                'disableAds' => true
            ]);
            $models = (new ModelController)->index($request);
            $models = json_decode($models->getContent());
            
            if(isset($models->data[0])){
                $imageUrl = explode('?', asset($models->data[0]->nameAvatar))[0];
            }

            $modelsArray = [];
            $modelsNamesArray = [];
            foreach($models->data as $key => $value){
                $modelsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/model/'.$value->title_slug.'">'.$value->stage_name.'</a></li>';      
                $modelsNamesArray[] = $value->stage_name;
            }
            $modelsArray = implode("", $modelsArray);
            $modelsNamesArray = implode(", ", $modelsNamesArray);

            $description = 'Browse models: ' . $modelsNamesArray;
            $title = 'Models - ' . $settings["siteTitle"];

            $descriptionLong = '
                <!-- Description Section -->
                <section>
                    <p>' . $description . '</p>
                </section>
                <!-- Models Section -->
                <section>
                    <h2>Models</h2>
                    <ul>'.$modelsArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/categories$#', $url, $matches) || preg_match('#^/categories(.+)$#', $url, $matches)) {
            $request = new Request([
                'column' => 'videos_count',
                'order' => 'desc',
                'limit' => 24,
                'disableAds' => true
            ]);
            $categories = (new ContentCategoryController)->indexPagination($request);
            $categories = json_decode($categories->getContent());
            
            if(isset($categories->data[0])){
                $imageUrl = explode('?', asset($categories->data[0]->thumbnail))[0];
            }

            $categoriesArray = [];
            $categoriesNamesArray = [];
            foreach($categories->data as $key => $value){
                $categoriesArray[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/'.$value->title_slug.'">'.ucwords($value->name).'</a></li>';      
                $categoriesNamesArray[] = ucwords($value->name);
            }
            $categoriesArray = implode("", $categoriesArray);
            $categoriesNamesArray = implode(", ", $categoriesNamesArray);

            $description = 'Browse categories: ' . $categoriesNamesArray;
            $title = 'Categories - ' . $settings["siteTitle"];

            $descriptionLong = '
                <!-- Description Section -->
                <section>
                    <p>' . $description . '</p>
                </section>
                <!-- Categories Section -->
                <section>
                    <h2>Categories</h2>
                    <ul>'.$categoriesArray.'</ul>
                </section>
            ';
        }

        if (preg_match('#^/tags$#', $url, $matches) || preg_match('#^/tags(.+)$#', $url, $matches)) {
            $request = new Request([
                'column' => 'videos_count',
                'order' => 'desc',
                'limit' => 24,
                'disableAds' => true
            ]);
            $tags = (new ContentTagController)->indexAdmin($request);
            $tags = json_decode($tags->getContent());

            $tagsArray = [];
            $tagsNamesArray = [];
            foreach($tags->data as $key => $value){
                $tagsArray[] = '<li><a href="'.$protocol . '://' . $domain.'/videos/tag'.$value->title_slug.'">'.ucwords($value->name).'</a></li>';      
                $tagsNamesArray[] = ucwords($value->name);
            }
            $tagsArray = implode("", $tagsArray);
            $tagsNamesArray = implode(", ", $tagsNamesArray);

            $description = 'Browse tags: ' . $tagsNamesArray;
            $title = 'Tags - ' . $settings["siteTitle"];

            $descriptionLong = '
                <!-- Description Section -->
                <section>
                    <p>' . $description . '</p>
                </section>
                <!-- Tags Section -->
                <section>
                    <h2>Tags</h2>
                    <ul>'.$tagsArray.'</ul>
                </section>
            ';
        }

        $image = '<img src="'.$imageUrl.'" alt="'.$title.'" width="640" />';   

        $htmlContent = '
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    '.$verificationCode.'
                    <meta name="tagline" content="'.$settings["siteTagline"].'">
                    <meta name="keywords" content="'.$keywords.'">
                    <meta name="description" content="'.$description.'">
                    <meta property="og:title" content="'.$title.'">
                    <meta property="og:description" content="'.$description.'">
                    <meta property="og:image" content="'.$imageUrl.'">
                    <meta property="og:url" content="'.$fullUrl.'">
                    <meta property="og:type" content="'.$contentType.'">
                    <meta name="twitter:card" content="summary_large_image" />
                    <meta name="twitter:title" content="'.$title.'" />
                    <meta name="twitter:description" content="'.$description.'" />
                    <meta name="twitter:image" content="'.$imageUrl.'" />
                    <link rel="canonical" href="'.$fullUrl.'">
                    <link rel="icon" href="' . asset('assets/file_favicon.png') . '" type="image/png">
                    <link rel="sitemap" type="application/xml" title="Sitemap" href="' . asset('sitemap.xml') . '">
                    <title>'.$title.'</title>
                    '.$schemaScript.'
                </head>
                <body>
                    <!-- Main Heading -->    
                    <h1>'.$title.'</h1>
                    <p>'.$descriptionLong.'<p>
                    <h2>Preview image</h2>
                    '.$image.'
                </body>
            </html>
        ';
    
        return response($htmlContent)->header('Content-Type', 'text/html');
    }
}