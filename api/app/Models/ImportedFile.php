<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportedFile extends Model
{
    use HasFactory;
    
    protected $fillable = [
        "slug",
        "url"
    ];

    public function files()
    {
        return $this->belongsTo(ProgressImport::class);
    }
}
