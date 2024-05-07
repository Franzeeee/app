<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;


class UserController extends Controller
{
    public function fetchArtist()
    {
        // Fetch all users where user_type is 'artist'
        $artists = User::where('user_type', 'artist')->get();

        // Return the fetched artists as a JSON response
        return response()->json(['artists' => $artists], 200);
    }
}
