<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ConvertVideoResolution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;
    protected $slug;

    /**
     * Create a new job instance.
     *
     * @param int $id
     * @param string $slug
     * @return void
     */
    public function __construct($id, $slug)
    {
        $this->id = $id;
        $this->slug = $slug;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $videoPath = base_path("public/videos/{$this->id}-{$this->slug}.mp4");

        if (!file_exists($videoPath)) {
            \Log::error("Video file not found: $videoPath");
            return;
        }

        $ffprobeCommand = ["ffprobe", "-v", "error", "-select_streams", "v:0", "-show_entries", "stream=width,height", "-of", "csv=p=0", $videoPath];
        $process = new Process($ffprobeCommand);
        
        try {
            $process->mustRun();
            list($width, $height) = explode(',', trim($process->getOutput()));
            $height = (int)$height;
        } catch (ProcessFailedException $exception) {
            \Log::error("Error executing ffprobe: {$exception->getMessage()}");
            return;
        }

        // Define resolutions to convert to
        $resolutions = [
            '240p' => [426, 240],
            '480p' => [854, 480],
            '720p' => [1280, 720],
            '1080p' => [1920, 1080],
            '4k' => [3840, 2160],
            '8k' => [7680, 4320],
        ];

        foreach ($resolutions as $label => [$scaleWidth, $scaleHeight]) {
            // Only convert if the original height is greater than or equal to the target height
            if ($height > $scaleHeight) {
                $outputPath = base_path("public/videos/{$this->id}-{$this->slug}-{$label}.mp4");

                if (!file_exists($outputPath)) {
                    $ffmpegCommand = ["ffmpeg", "-i", $videoPath, "-vf", "scale={$scaleWidth}:{$scaleHeight}", "-c:a", "copy", $outputPath];
                    $process = new Process($ffmpegCommand);
                    $process->setTimeout(14400);
                    try {
                        $process->mustRun();
                        \Log::info("Successfully converted video to {$label}: $outputPath");
                    } catch (ProcessFailedException $exception) {
                        \Log::error("FFmpeg failed at {$label} conversion: {$exception->getMessage()}");
                    }
                }
            }
        }
    }
}