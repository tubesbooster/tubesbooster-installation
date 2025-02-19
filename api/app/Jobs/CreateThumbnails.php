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

class CreateThumbnails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filename;
    public $slug;
    public $deleteFile;

    /**
     * Create a new job instance.
     *
     * @param string $filename
     * @param string $slug
     */
    public function __construct($filename, $slug, $deleteFile = false)
    {
        $this->filename = $filename;
        $this->slug = $slug;
        $this->deleteFile = $deleteFile;
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

        $duration = $videoMeta->get('duration');
        $videoFile = $ffmpeg->openAdvanced([public_path("videos/{$this->filename}")]);

        // Apply custom filters to extract thumbnails
        $videoFile->filters()
            ->custom('[0:v]', 'trim=start='.($duration/5).':end='.(($duration/5) + 2).',setpts=PTS-STARTPTS', '[a]')
            ->custom('[0:v]', 'trim=start='.($duration/5*2).':end='.(($duration/5*2) + 2).',setpts=PTS-STARTPTS', '[b]')
            ->custom('[a][b]', 'concat', '[c]')
            ->custom('[0:v]', 'trim=start='.($duration/5*3).':end='.(($duration/5*3) + 2).',setpts=PTS-STARTPTS', '[d]')
            ->custom('[0:v]', 'trim=start='.($duration/5*4).':end='.(($duration/5*4) + 2).',setpts=PTS-STARTPTS', '[f]')
            ->custom('[d][f]', 'concat', '[g]')
            ->custom('[c][g]', 'concat', '[out1]')
            ->custom('[out1]', 'scale=320:240', '[out2]');

        // Save animated thumbnail
        if (file_exists(public_path("videos/thumbs/preview-{$this->slug}.mp4"))) {
            return;
        }
        $videoFile->map(['[out2]'], new X264(), public_path("videos/thumbs/preview-{$this->slug}.mp4"))->save();

        try {
            $videoFile = $ffmpeg->open(public_path('videos')."/{$this->filename}");
            if (!file_exists(public_path('videos/thumbs')."/{$this->slug}.jpg")) {
                $videoFile->frame(TimeCode::fromSeconds(15))
                          ->save(public_path('videos/thumbs')."/{$this->slug}.jpg");
            }
        } catch (\Exception $e) {
            Log::error("Failed to generate JPG thumbnail for {$this->slug}: " . $e->getMessage(), [
                'file' => $this->filename,
                'slug' => $this->slug,
            ]);
        }
    }
}