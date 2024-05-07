<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtistRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', // user_id is also fillable, as it's needed to associate the request with a user
        'requested_artist_name',
        'email'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
