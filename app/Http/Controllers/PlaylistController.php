<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Playlist;
use App\Models\User;
use App\Models\Album;
use App\Models\Music;
use Illuminate\Support\Facades\DB;

class PlaylistController extends Controller
{


    public function fetchUserPlaylist(Request $request)
    {
        // Retrieve the user instance
        $user = User::find($request->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Fetch the first two playlists associated with the user, ordered by creation date
        $firstTwoPlaylists = $user->playlists()->latest()->get();

        if ($firstTwoPlaylists->isEmpty()) {
            return response()->json(['message' => 'No playlists found for this user'], 404);
        }

        return response()->json(['playlists' => $firstTwoPlaylists], 200);
    }



    public function create(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'name' => 'required',
            'user_id' => 'required',
            'music_ids' => 'array' // Ensure music_ids is an array
        ]);

        try {
            // Create a new playlist instance with the validated data
            $playlist = new Playlist();
            $playlist->name = $validated['name'];

            // Save the playlist to the database
            $playlist->save();

            // Get the authenticated user's ID
            $user = User::find($validated['user_id']);

            // Associate the playlist with the user
            $user->playlists()->attach($playlist->id);

            // Attach music IDs to the playlist
            $playlist->music()->attach($validated['music_ids']);

            // Return a success response
            return response()->json(['message' => 'Playlist created successfully', 'playlist' => $playlist], 201);
        } catch (\Exception $e) {
            // If an error occurs, return an error response
            return response()->json(['message' => 'Failed to create playlist', 'error' => $e->getMessage()], 500);
        }
    }

    public function getLatestPlaylist(Request $request)
    {
        // Retrieve the user instance
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Fetch the first two playlists associated with the user, ordered by creation date
        $firstTwoPlaylists = $user->playlists()->latest()->take(2)->get();

        if ($firstTwoPlaylists->isEmpty()) {
            return response()->json(['message' => 'No playlists found for this user'], 404);
        }

        return response()->json(['first_two_playlists' => $firstTwoPlaylists], 200);
    }

    public function fetchMusic(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'id' => 'required|exists:playlists,id',
        ]);

        try {
            // Retrieve the playlist with its associated music, including softdeleted records
            $playlist = Playlist::with(['music' => function ($query) {
                $query->withTrashed();
            }])->find($request->id);

            if (!$playlist) {
                return response()->json(['message' => 'Playlist not found'], 404);
            }

            // Extract music data from the playlist
            $music = $playlist->music->map(function ($musicItem) {
                // Find the album details based on the album_id foreign key
                $album = Album::find($musicItem->album_id);
                $user = User::find($musicItem->artist_id);
                // Add the album name to the music item
                $musicItem->album_name = $album ? $album->title : null;
                $musicItem->album_cover = $album ? $album->cover_image : null;

                // Add the artist name to the music item
                $musicItem->artist_name = $user ? $user->name : null;
                return $musicItem;
            });

            return response()->json([
                'playlist' => $playlist,
                'music' => $music,
            ], 200);
        } catch (\Exception $e) {
            // If an error occurs, return an error response
            return response()->json(['message' => 'Failed to fetch music', 'error' => $e->getMessage()], 500);
        }
    }

    public function streamMusic(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'file_name' => 'required|string|exists:music,file_name',
            'range' => 'required|string',
        ]);
        $fileName = '1712901890_ziL9J4TB0v.mp3';
        try {
            // Get the requested audio file's path
            $filePath = storage_path('app/public/music/' . $fileName);

            // Open the audio file for reading
            $file = fopen($filePath, 'rb');
            if (!$file) {
                return response()->json(['message' => 'Failed to open file'], 500);
            }

            // Parse the byte range
            preg_match('/bytes=(\d+)-(\d+)?/', $request->range, $matches);
            $start = $matches[1];
            $end = isset($matches[2]) ? $matches[2] : filesize($filePath) - 1;

            // Set headers for byte range response
            header('HTTP/1.1 206 Partial Content');
            header('Accept-Ranges: bytes');
            header('Content-Length: ' . ($end - $start + 1));
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . filesize($filePath));

            // Seek to the requested byte range
            fseek($file, $start);

            // Stream the audio file
            while (!feof($file) && ($pos = ftell($file)) <= $end) {
                echo fread($file, min(8192, $end - $pos + 1));
                flush();
            }

            // Close the file
            fclose($file);
        } catch (\Exception $e) {
            // If an error occurs, return an error response
            return response()->json(['message' => 'Failed to stream music', 'error' => $e->getMessage()], 500);
        }
    }


    public function addToPlaylist(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'music_id' => 'required|exists:music,id',
            'playlist_ids' => 'required|array',
        ]);

        try {
            // Retrieve the music instance
            $music = Music::find($validated['music_id']);

            // Check if the music exists
            if (!$music) {
                return response()->json(['message' => 'Music not found'], 404);
            }

            // Attach music to each playlist
            foreach ($validated['playlist_ids'] as $playlistId) {
                $playlist = Playlist::find($playlistId);

                // Check if the playlist exists
                if (!$playlist) {
                    return response()->json(['message' => 'Playlist not found'], 404);
                }

                // Attach the music to the playlist
                $playlist->music()->attach($music->id);
            }

            return response()->json(['message' => 'Music added to playlists successfully'], 200);
        } catch (\Exception $e) {
            // If an error occurs, return an error response
            return response()->json(['message' => 'Failed to add music to playlists', 'error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            // Find the playlist by ID
            $playlist = Playlist::find($request->id);

            // Check if the playlist exists
            if (!$playlist) {
                return response()->json(['message' => 'Playlist not found'], 404);
            }

            // Delete the playlist
            $playlist->delete();

            return response()->json(['message' => 'Playlist deleted successfully'], 200);
        } catch (\Exception $e) {
            // If an error occurs, return an error response
            return response()->json(['message' => 'Failed to delete playlist', 'error' => $e->getMessage()], 500);
        }
    }

    public function edit(Request $request)
    {

        try {
            // Find the playlist by ID
            $playlist = Playlist::find($request->id);

            // Check if the playlist exists
            if (!$playlist) {
                return response()->json(['message' => 'Playlist not found'], 404);
            }

            // Validate the incoming request data
            $validated = $request->validate([
                'name' => 'required|string|max:255', // Define your validation rules here
            ]);

            // Update the playlist name
            $playlist->name = $validated['name'];
            $playlist->save();

            return response()->json(['message' => 'Playlist name updated successfully'], 200);
        } catch (\Exception $e) {
            // If an error occurs, return an error response
            return response()->json(['message' => 'Failed to update playlist name', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteTrack(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required'
        ]);

        $id = $validated['id'];

        DB::table('playlist_music')->where('music_id', $id)->delete();

        return response()->json(['message' => "Track deleted successfully"], 200);
    }
}
