<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected $table = 'playlists';
    protected $fillable = ['name'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_playlist');
    }
    public function music()
    {
        return $this->belongsToMany(Music::class, 'playlist_music', 'playlist_id', 'music_id');
    }
    
}
