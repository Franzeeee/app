<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Album;
use App\Models\Music;
use App\Models\Listen;
use App\Models\Genre;
use Illuminate\Support\Str;

class MusicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define the artist ID
        $artistId = 1; // Assuming there's a default artist ID
        $track = new \Illuminate\Http\File(public_path('music/1711065146_N1JIAor4U1.mp3'));
        $albumCover = new \Illuminate\Http\File(public_path('img/Cassette-logo.png'));

        $albumCoverFileName = time() . '.' . pathinfo($albumCover->getFilename(), PATHINFO_EXTENSION);

        // Define the destination directory
        $destinationPath = public_path('storage/album_covers');

        // Move the album cover to the destination directory with the generated filename
        \Storage::copy($albumCover->getPathname(), $destinationPath . '/' . $albumCoverFileName);
        


        // Create the album
        $album = Album::create([
            'title' => 'Your Album Title',
            'description' => 'Your Album Description',
            'artist' => $artistId,
            'cover_image' => url('/storage/album_covers/' . $albumCoverFileName),
            // Add other album details as needed
        ]);

        // Define the track details
        $trackDuration = '5:00'; // Example duration
        $genres = ['Rock', 'Pop']; // Example genres

        // Generate 10,000 records for the same album
        for ($i = 1; $i <= 10000; $i++) {
            $randomString = Str::random(10); // Generate a random string
            $timestamp = time(); // Get the current timestamp
            $trackFileName = time() . '.' . pathinfo($track->getFilename(), PATHINFO_EXTENSION); // Example file name


        // Move the track file to the desired directory
        \Storage::copy($track->getPathname(), public_path('music') . '/' . $trackFileName);

            // Save the track details to the database
            $music = Music::create([
                'title' => 'Track ' . $i,
                'album_id' => $album->id,
                'file_name' => url('music/' . $trackFileName),
                'duration' => $trackDuration,
                'artist_id' => $artistId,
                // Add other track details as needed
            ]);

            // Create listen records
            Listen::create([
                'music_id' => $music->id,
                'points' => 0
            ]);

            // Attach genres to the music track
            foreach ($genres as $genreName) {
                $genre = Genre::firstOrCreate(['name' => $genreName]);
                $music->genres()->attach($genre);
            }
        }
    }
}
