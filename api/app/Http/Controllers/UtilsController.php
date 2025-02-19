<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UtilsController extends Controller {
    public static function convertNullStringsToNull($data, $boolConvertDisabled = false)
    {
        // Iterate through each item in the array
        foreach ($data as $key => $value) {
            // If the value is an array, recursively convert
            if (is_array($value)) {
                $data[$key] = self::convertNullStringsToNull($value);
            } else {
                // If the value is the string "null", replace it with null
                if ($value === 'null') {
                    $data[$key] = null;
                }
                if ($value === 'true' && !$boolConvertDisabled) {
                    $data[$key] = true;
                }
                if ($value === 'false' && !$boolConvertDisabled) {
                    $data[$key] = false;
                }
            }
        }

        return $data;
    }

    public static function convertNullToZero($data)
    {
        // Iterate through each item in the array
        foreach ($data as $key => $value) {
            // If the value is an array, recursively convert
            if (is_array($value)) {
                $data[$key] = self::convertNullStringsToNull($value);
            } else {
                // If the value is the string null, replace it with 0
                if ($value === null) {
                    $data[$key] = 0;
                }
            }
        }

        return $data;
    }

    public static function createSlug($string, $table, $slugName = "slug", $id = 0)
    {
        $originalSlug = Str::slug($string);
        $slug = $originalSlug;
        $count = 2;

        while (DB::table($table)->where($slugName, $slug)->where("id", '!=', $id)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    public function php(){
        return phpinfo();
    }

    public static function convertDurationToISO8601($duration) {
        // Split the duration into hours, minutes, and seconds
        list($hours, $minutes, $seconds) = explode(':', $duration);
    
        // Initialize the ISO 8601 duration string
        $isoDuration = 'PT';
    
        // Add hours, minutes, and seconds if they exist
        if ((int)$hours > 0) {
            $isoDuration .= (int)$hours . 'H';
        }
        if ((int)$minutes > 0) {
            $isoDuration .= (int)$minutes . 'M';
        }
        if ((int)$seconds > 0) {
            $isoDuration .= (int)$seconds . 'S';
        }
    
        return $isoDuration;
    }
}