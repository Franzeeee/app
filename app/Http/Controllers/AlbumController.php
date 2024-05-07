<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;

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
}
