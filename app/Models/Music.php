<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Genre;
use App\Models\Listen;


class Music extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'album',
        'music_file_name',
    ];

    public function artist()
    {
        return $this->belongsTo(User::class, 'artist');
    }
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'music_genre', 'music_id', 'genre_id');
    }
    
    // Define the many-to-many relationship with Playlist model
    public function playlists()
    {
        return $this->belongsToMany(Playlist::class);
    }

    public function listen()
    {
        return $this->hasOne(Listen::class);
    }
}
