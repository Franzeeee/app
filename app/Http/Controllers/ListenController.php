<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Listen;
use App\Models\Music;
use App\Models\User;

class ListenController extends Controller
{
    //

    public function record(Request $request)
    {
        $validated = $request->validate([
            'music_id' => 'required',
            'points' => 'required|int'
        ]);

        $listen = Listen::where('music_id', $validated['music_id'])->first();

        if (!$listen) {
            // If no record exists, create a new one
            $listen = new Listen();
            $listen->music_id = $validated['music_id'];
            $listen->points = $validated['points'];
        } else {
            // If a record exists, update the points
            $listen->increment('points', $validated['points']);
        }

        $listen->save();

        return response()->json(["Updated: " => $listen->points], 200);
    }
    
    public function topTen(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Get the artist_id of the user with the highest listen points
        $topArtistId = Listen::select('artist_id')
            ->groupBy('artist_id')
            ->orderByRaw('SUM(points) DESC')
            ->value('artist_id');

        if (!$topArtistId) {
            return response()->json(["message" => "No user found with listen points."], 404);
        }

        // Fetch the top 5 music posted by the top artist
        $topFiveMusic = Music::where('artist_id', $topArtistId)
            ->leftJoin('listens', 'music.id', '=', 'listens.music_id')
            ->select('music.*', \DB::raw('SUM(listens.points) as total_points'))
            ->groupBy('music.id')
            ->orderBy('total_points', 'DESC')
            ->take(5)
            ->get();

        return response()->json(["music" => $topFiveMusic], 200);
    }

}
