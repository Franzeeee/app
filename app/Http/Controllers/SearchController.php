<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Album;
use App\Models\Music;
use App\Models\Playlist;

class SearchController extends Controller
{
    public function search($search)
    {
        // Validate the search query
        request()->validate([
            'query' => 'equired|string|min:1',
        ]);

        // Get the search query from the request
        $query = $search;

        // Search for users
        $users = User::where('name', 'like', "%$query%")
            ->whereIn('user_type', ['artist', 'admin'])
            ->get();

        // Search for albums
        $albums = Album::where('title', 'like', "%$query%")
            ->orWhere('artist', 'like', "%$query%")
            ->get();

        // Search for music
        $music = Music::where('title', 'like', "%$query%")
            ->orWhereHas('album', function ($albumQuery) use ($query) {
                $albumQuery->where('title', 'like', "%$query%");
            })
            ->orWhereHas('artist', function ($artistQuery) use ($query) {
                $artistQuery->where('name', 'like', "%$query%");
            })
            ->get();

        // Return the search results as JSON
        return response()->json([
            'users' => $users,
            'albums' => $albums,
            'music' => $music,
        ]);
    }
}
