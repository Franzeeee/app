<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Music;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Album;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Genre;
use Illuminate\Support\Facades\Storage;
use App\Models\Listen;

class MusicController extends Controller
{

    public function uploadMusic(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'id' => 'required',
            'albumCover' => 'required',
            'albumTitle' => 'required',
            'tracksInfo' => 'required|array', // Ensure tracksInfo is an array
            'tracksInfo.*.fileName' => 'required|string', // Ensure each track has a name
            'tracksInfo.*.genres' => 'required|array', // Ensure each track has a genre
            'tracksInfo.*.genres.*' => 'string', // Ensure each genre is a string
            'tracks.*' => 'file|mimes:mp3m,wav', // Validate each track file as mp3
        ]);

        $artistId = $request->id;

        // Get the album cover from the request
        $albumCover = $request->file('albumCover');

        // Generate a unique name for the album cover
        $albumCoverFileName = time() . '.' . $albumCover->getClientOriginalExtension();

        // Define the destination directory
        $destinationPath = public_path('storage/album_covers');

        // Move the album cover to the destination directory with the generated filename
        $albumCover->move($destinationPath, $albumCoverFileName);

        // Save the album details to the database
        $album = new Album();
        $album->title = $request->input('albumTitle');
        $album->description = $request->input('albumDescription', ''); // albumDescription is optional
        $album->artist = $artistId; // Assuming there's a default artist ID
        $album->cover_image = url('/storage/album_covers/' . $albumCoverFileName); // Get the public URL of the stored file
        $album->save(); // Move the track file to the desired directory

        $albumId = $album->id;


        // Get the tracks from the request
        $tracks = $request->file();

        $iterator = 0;

        // Save each track to the database
        foreach ($tracks as $key => $trackFile) {
            // Generate a unique name for the track file
            $randomString = Str::random(10); // Generate a random string
            $timestamp = time(); // Get the current timestamp
            $trackFileName = "{$timestamp}_{$randomString}.{$trackFile->extension()}";

            $info = $request->tracksInfo[$iterator];
            $fileNameFromInfo = $info['fileName'];


            // Move the track file to the desired directory
            $trackFile->move(public_path('music'), $trackFileName);

            // Save the track details to the database
            $music = new Music();
            $music->title = $fileNameFromInfo; // You can set the title to the filename for simplicity, or extract metadata if available
            $music->album_id = $album->id; // Assuming you have an album ID
            $music->file_name = url('/music/' . $trackFileName); // Get the file URL using the Storage facade
            $music->duration = $info['duration'];
            $music->artist_id = $artistId; // Assuming there's a default artist ID
            $music->save();

            $listen = Listen::create([
                'music_id' => $music->id,
                'points' => 0
            ]);


            // Attach genres to the music track
            foreach ($info['genres'] as $genreName) {
                $genre = Genre::firstOrCreate(['name' => $genreName]);
                $music->genres()->attach($genre); // Corrected method call
            }

            $iterator++;
            if ($iterator >= count($request->input('tracksInfo'))) {
                break;
            }
        }

        return response()->json(['message' => "Album Uploaded Successfully", 'albumId' => $albumId], 200);
    }

    public function getAllMusic()
    {
        // Retrieve all music records from the database
        $music = Music::all();

        // Loop through each music record and replace the artist ID with the user's name
        foreach ($music as $song) {
            $user = User::find($song->artist);
            if ($user) {
                $song->artist = $user->name;
            } else {
                // If user is not found, set artist to null or any default value
                $song->artist = null; // You can replace null with any default value
            }
        }

        // Return the modified music data as JSON response
        return response()->json($music, 200);
    }

    public function getAlbumWithMusic($albumId)
    {
        // Retrieve the album with its related music by its ID, limiting the related music to 10
        $album = Album::with(['music' => function ($query) {
            $query->take(10); // Limit the number of related music tracks to 10
        }])->findOrFail($albumId);

        return response()->json($album);
    }


    public function fetchMusic(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'album_id' => 'required|exists:albums,id', // Ensure the provided album ID exists in the albums table
        ]);

        // Retrieve the album cover URL based on the provided album ID
        $album = Album::findOrFail($request->album_id);
        $albumCover = $album->cover_image;

        // Retrieve all music records that belong to the provided album ID
        $music = Music::where('album_id', $request->album_id)->get();

        foreach ($music as $song) {
            // Retrieve the artist name from the users table based on the artist_id
            $artist = User::find($song->artist_id);
            // Add the artist name to the music record
            $song->artist = $artist ? $artist->name : "Unknown"; // Assign artist's name or "Unknown" if not found
            // Add the album cover URL to the music record
            $song->album_cover = $albumCover;
        }

        // Return the music records along with the album cover URL as JSON response
        return response()->json($music, 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $music = Music::find($request->id);

        if (!$music) {
            return response()->json(["message" => "Music not found"], 404);
        }

        $music->delete(); // Soft delete the music

        return response()->json(["message" => "Music Deleted Successfully!"], 200);
    }

    public function search(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        // Fetch music data based on the search query
        $searchQuery = $validated['name'];
        $music = Music::select('music.*', 'users.name as artist_name')
            ->leftJoin('users', 'music.artist_id', '=', 'users.id')
            ->where('title', 'like', "%$searchQuery%")
            ->get();

        return response()->json($music);
    }

    public function fetchTopMusic(Request $request)
    {
        // Validate the user id
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Fetch all the music related to the user id
        $music = Music::where('artist_id', $validated['user_id'])->get();

        // Fetch points for each music from the listens table
        $musicWithPoints = $music->map(function ($item) {
            $points = Listen::where('music_id', $item->id)->sum('points');
            $item['points'] = $points;
            return $item;
        });

        // Sort the music by points in descending order
        $sortedMusic = $musicWithPoints->sortByDesc('points');

        // Get the top 5 music tracks
        $topMusic = $sortedMusic->take(5);

        // Add album_name key based on album_id
        $topMusic = $topMusic->map(function ($item) {
            $album = Album::find($item->album_id);
            $item['album_name'] = $album ? $album->title : null;
            return $item;
        });

        // Return the top 5 music tracks as a response
        return response()->json($topMusic, 200);
    }

    public function fetchArtistMusic(Request $request)
    {
        $artist = User::find(1);

        if ($artist) {
            // Fetch all the music tracks for the artist
            $music = $artist->music()->get();

            // You can iterate over $music to access each music track
            foreach ($music as $track) {
                echo $track->title; // Accessing the title of each music track
            }
        } else {
            echo "Artist not found";
        }
    }
}
