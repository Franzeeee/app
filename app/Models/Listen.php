<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Music;

class Listen extends Model
{
    use HasFactory;

    protected $fillable = [
        'points',
        'music_id'
    ];

    public function music()
    {
        return $this->belongsTo(Music::class);
    }
}
