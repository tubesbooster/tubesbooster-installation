<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;
use App\Models\Channel;
use App\Models\ContentCategory;
use App\Models\ContentTag;
use App\Models\Grabber;
use App\Models\SiteModel as Model;
use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getPerformance() {
        $loadAverage = sys_getloadavg();
        $cpuUsage = shell_exec("top -b -n 1 | grep 'Cpu(s)' | awk '{print $2 + $4}'");
        $cpuUsage = str_replace("\n", "%", $cpuUsage);
        $memoryInfo = shell_exec('free -h');
        $memoryInfoArray = preg_split('/\s+/', trim($memoryInfo));
        $memoryUsage = [
            'total' => $memoryInfoArray[7],
            'used' => $memoryInfoArray[8],
            'free' => $memoryInfoArray[9],
            'shared' => $memoryInfoArray[10],
            'cache' => $memoryInfoArray[11]
        ];
        $diskSpace = shell_exec('df -h');
        $diskSpaceArray = explode("\n", trim($diskSpace));
        $diskSpaceInfo = [];
        foreach ($diskSpaceArray as $line) {
            $columns = preg_split('/\s+/', $line);
            $diskSpaceInfo[] = [
                'filesystem' => $columns[0],
                'size' => $columns[1],
                'used' => $columns[2],
                'available' => $columns[3],
                'use_percentage' => $columns[4],
                'mounted_on' => $columns[5],
            ];
        }
        $queueCount = Grabber::sum('queue');
        $currentDateTime = Carbon::now();
        $twentyFourHoursAgo = $currentDateTime->subHours(24);
        $weekAgo = $currentDateTime->subHours(24*7);

        $request = new Request([
            "limit" => 3,
            "column" => "id",
            "order" => "desc"
        ]);
        $recent3albums = AlbumController::index($request)->original["data"];

        return response()->json([
            "workload" => $loadAverage,
            "cpuUsage" => $cpuUsage,
            "memoryUsage" => $memoryUsage,
            "diskSpace" => $diskSpaceInfo,
            "queueCount" => $queueCount,
            "php_version" => PHP_VERSION,
            "local_time" => date('d.m.Y h:i'),
            "ip_address" => $_SERVER['REMOTE_ADDR'],
            "username" => "Admin",
            "domain" => $_SERVER['HTTP_HOST'],
            "version" => "0.1",
            "videos_active" => Video::where('status', 1)->count(),
            "videos_inactive" => Video::where('status', 2)->count(),
            "videos_24h" => Video::where('created_at', '>=', $twentyFourHoursAgo)->count(),
            "albums_active" => Album::where('status', 1)->count(),
            "albums_inactive" => Album::where('status', 2)->count(),
            "models_active" => Model::count(),
            "models_inactive" => 0,
            "channels_active" => Channel::where('status', 1)->count(),
            "channels_inactive" => Channel::where('status', 2)->count(),
            "categories_active" => ContentCategory::where('status', 1)->count(),
            "categories_inactive" => ContentCategory::where('status', 2)->count(),
            "tags_active" => ContentTag::count(),
            "tags_inactive" => 0,
            "users_24h" => User::where('created_at', '>=', $twentyFourHoursAgo)->count(),
            "users_total" => User::count(),
            "users_7d" => User::where('created_at', '>=', $weekAgo)->count(),
            "albums_24h" => Album::where('created_at', '>=', $twentyFourHoursAgo)->count(),
            "videos" => Video::orderBy("id", "desc")->with('user')->limit(3)->get(),
            "albums" => $recent3albums,
            "users" => User::orderBy("id", "desc")->limit(6)->get(),
        ]);
    }

    public function tempLogin(Request $request){
        if($request->password == "7hKp2DgFvR"){
            return "true";
        }
        else {
            return "false";
        }
    }

    public function deleteAsset(Request $request){
        unlink(public_path('assets/'.$request->file.'.png'));
        return "ok";
    }
}