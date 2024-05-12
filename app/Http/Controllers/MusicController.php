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
use Illuminate\Support\Facades\Http;

class MusicController extends Controller
{

    public function uploadMusic(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'id' => 'required',
            'albumCover' => 'required',
            'albumTitle' => 'required',
            'tracksInfo' => 'required|array',
            'tracksInfo.*.fileName' => 'required|string',
            'tracksInfo.*.genres' => 'required|array',
            'tracksInfo.*.genres.*' => 'string',
            'tracks.*' => 'file|mimes:mp3m,wav',
        ]);

        $artistId = $request->id;


        $albumCover = $request->file('albumCover');
        $albumCoverFileName = time() . '.' . $albumCover->getClientOriginalExtension();
        $destinationPath = public_path('storage/album_covers');
        $albumCover->move($destinationPath, $albumCoverFileName);

        $album = new Album();
        $album->title = $request->input('albumTitle');
        $album->description = $request->input('albumDescription', '');
        $album->artist = $artistId;
        $album->cover_image = url('/storage/album_covers/' . $albumCoverFileName);
        $album->save();
        $albumId = $album->id;


        $tracks = $request->file();

        $iterator = 0;

        foreach ($tracks as $key => $trackFile) {
            $randomString = Str::random(10);
            $timestamp = time();
            $trackFileName = "{$timestamp}_{$randomString}.{$trackFile->extension()}";

            $info = $request->tracksInfo[$iterator];
            $fileNameFromInfo = $info['fileName'];


            // Move the track file to the desired directory
            $trackFile->move(public_path('music'), $trackFileName);

            // Save the track details to the database
            $music = new Music();
            $music->title = $fileNameFromInfo;
            $music->album_id = $album->id;
            $music->file_name = url('/music/' . $trackFileName);
            $music->duration = $info['duration'];
            $music->artist_id = $artistId;
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
        $music = Music::all();

        foreach ($music as $song) {
            $user = User::find($song->artist);
            if ($user) {
                $song->artist = $user->name;
            } else {
                $song->artist = null;
            }
        }

        return response()->json($music, 200);
    }

    public function getAlbumWithMusic(Request $request, $albumId)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        $album = Album::with(['music' => function ($query) use ($perPage) {
            $query->paginate($perPage);
        }])->findOrFail($albumId);

        $paginator = $album->music()->paginate($perPage);

        $albumData = $album->toArray();
        $albumData['pagination'] = [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];

        return response()->json($albumData);
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
            $artist = User::find($song->artist_id);
            $song->artist = $artist ? $artist->name : "Unknown";
            $song->album_cover = $albumCover;
            $song->album_title = $album->title;
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

    public function fetchAudio($audio)
    {
        // URL of the original audio file
        $filePath  = 'music/' . $audio;

        // Check if the file exists
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Audio file not found'], 404);
        }

        // Read the file contents
        $fileContents = file_get_contents($filePath);

        // Set the response headers
        $headers = [
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => filesize($filePath),
        ];

        // Return the audio file as a response
        return response($fileContents, 200)->withHeaders($headers);
    }
}
