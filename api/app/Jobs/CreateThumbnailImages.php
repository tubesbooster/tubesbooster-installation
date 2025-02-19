<?php

namespace App\Jobs;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateThumbnailImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filename;
    public $slug;

    /**
     * Create a new job instance.
     *
     * @param string $filename
     * @param string $slug
     */
    public function __construct($filename, $slug)
    {
        $this->filename = $filename;
        $this->slug = $slug;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //Create animated thumbnail
        $ffmpeg = FFMpeg::create();
        $ffprobe = FFProbe::create();
        $videoMeta = $ffprobe
            ->streams(public_path("videos/{$this->filename}"))
            ->videos()
            ->first();         
        $videoFile = $ffmpeg->openAdvanced([public_path("videos/".$this->filename)]);
        try {
            $videoFile = $ffmpeg->open(public_path('videos')."/".$this->filename);
            if (!file_exists(public_path('videos/thumbs')."/".$this->slug.".jpg")) {
                $videoFile->frame(TimeCode::fromSeconds(20))
                          ->save(public_path('videos/thumbs')."/".$this->slug.".jpg");
            }
        } catch (\Exception $e) {
            Log::error("Failed to generate JPG thumbnail for ".$this->slug.": " . $e->getMessage(), [
                'file' => $this->filename,
                'slug' => $this->slug,
            ]);
        }
    }
}