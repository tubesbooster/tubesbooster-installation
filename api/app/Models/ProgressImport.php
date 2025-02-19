<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgressImport extends Model
{
    use HasFactory;

    protected $table = 'progress_import';

    public function files()
    {
        return $this->hasMany(ImportedFile::class);
    }
}
