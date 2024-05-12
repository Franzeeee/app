<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;
use App\Models\Listen;

class AlbumController extends Controller
{
    public function index()
    {
        $albums = Album::all();

        return $albums;
    }

    public function update(Request $request)
    {
        // Validate the request data
        $request->validate([
            'id' => 'required',
            'name' => 'required|string|max:255',
        ]);

        // Find the album by id
        $album = Album::find($request->id);

        if (!$album) {
            return response()->json(['message' => 'Album not found'], 404);
        }

        // Update the album with the new data
        $album->update([
            'title' => $request->name,
        ]);

        return response()->json(['message' => 'Album saved successfully', 'album' => $album], 200);
    }

    public function delete(Request $request)
    {
        // Extract the album ID from the request
        $id = $request->id;

        // Find the album by id
        $album = Album::find($id);

        if (!$album) {
            return response()->json(['message' => 'Album not found'], 404);
        }

        // Delete related musics
        $album->music()->delete();

        // Delete the album
        $album->delete();

        return response()->json(['message' => 'Album and its related records deleted successfully'], 200);
    }

    public function totalAlbum($id)
    {
        $albums = Album::where('artist', $id)->withTrashed()->get();

        if (!$albums) {
            return 0;
        }

        $totalAlbum = $albums->count();

        return $totalAlbum;
    }

    public function fetchArtistAlbum($id)
    {
        $albums = Album::select('albums.*', 'listens.points')
            ->where('albums.artist', $id)
            ->leftJoin('listens', 'albums.id', '=', 'listens.music_id')
            ->withTrashed()
            ->get();

        return $albums;
    }
}
