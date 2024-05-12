<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Music;
use App\Models\Listen;

class UserController extends Controller
{
    public function fetchArtist()
    {
        // Fetch all users where user_type is 'artist'
        $artists = User::where('user_type', 'artist')->get();

        // Return the fetched artists as a JSON response
        return response()->json(['artists' => $artists], 200);
    }

    public function index()
    {
        try {
            // Query to calculate the total ranking of users based on the points from listens
            $rankings = User::select('users.id as user_id', 'users.name as user_name')
                ->join('music', 'users.id', '=', 'music.artist_id')
                ->join('listens', 'music.id', '=', 'listens.music_id')
                ->groupBy('users.id')
                ->selectRaw('users.id as user_id, users.name as user_name, SUM(listens.points) as total_points')
                ->orderByDesc('total_points')
                ->get();

            return response()->json(['rankings' => $rankings], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchUserRanking($userId)
    {
        try {
            // Get the total ranking based on the points from listens
            $userRanking = User::selectRaw('users.id as user_id, users.name as user_name, SUM(listens.points) as total_points')
                ->join('music', 'users.id', '=', 'music.artist_id')
                ->join('listens', 'music.id', '=', 'listens.music_id')
                ->where('users.id', $userId)
                ->groupBy('users.id')
                ->first();

            if (!$userRanking) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Get the user's ranking position
            $rankingPosition = User::selectRaw('COUNT(*) + 1 as ranking_position')
                ->fromSub(function ($query) use ($userRanking) {
                    $query->from('users')
                        ->join('music', 'users.id', '=', 'music.artist_id')
                        ->join('listens', 'music.id', '=', 'listens.music_id')
                        ->selectRaw('users.id')
                        ->groupBy('users.id')
                        ->havingRaw('SUM(listens.points) > ?', [$userRanking->total_points]);
                }, 'sub')
                ->get();

            $rank = $rankingPosition->isEmpty() ? 1 : $rankingPosition->first()->ranking_position;

            return response()->json(['user_ranking' => $userRanking, 'rank' => $rank], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchUser($id)
    {
        return User::find($id);
    }

    public function updateUserName(Request $request, $userId)
    {
        // Validate the request data if necessary
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            // Find the user by ID
            $user = User::findOrFail($userId);

            // Update the user's name
            $user->name = $request->name;
            $user->save();

            return response()->json(['message' => 'User name updated successfully'], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur
            return response()->json(['message' => 'Failed to update user name', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateProfileImage(Request $request, $userId)
    {
        // Validate the request data
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Maximum size of 2MB
        ]);

        // Get the image from the request
        $image = $request->file('image');

        // Generate a unique name for the image
        $imageName = time() . '.' . $image->getClientOriginalExtension();

        // Define the destination directory
        $destinationPath = public_path('storage/profile_images');

        // Move the image to the destination directory with the generated filename
        $image->move($destinationPath, $imageName);

        // Update the user's profile image URL in the database
        $user = User::find($userId); // Assuming you're using authentication
        $user->profile_image = url('/storage/profile_images/' . $imageName); // Get the public URL of the stored file
        $user->save();

        // Optionally, you can return a response
        return response()->json(['message' => 'Profile image updated successfully', 'imageUrl' => $user->profile_image]);
    }
}
