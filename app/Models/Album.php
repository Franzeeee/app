<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Album extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'cover_image', 'artist'];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function music()
    {
        return $this->hasMany(Music::class);
    }
}
