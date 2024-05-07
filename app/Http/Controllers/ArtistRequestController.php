<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ArtistRequest;
use App\Models\User;

class ArtistRequestController extends Controller
{

    public function index()
    {
        // Fetch all the user with user type of artist and the listener that sent an artist request to admin
        $users = User::whereIn('user_type', ['artist'])
            ->orWhere(function ($query) {
                $query->where('user_type', 'listener')
                    ->whereExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('artist_requests')
                            ->whereRaw('artist_requests.user_id = users.id');
                    });
            })
            ->get();
            
        // Transform the status to "Pending Request" for listeners
        $users->transform(function ($user) {
            if ($user->user_type === 'listener') {
                $user->status = 'Pending Request';
            }else{
                $user->status = "Active";
            }
            $user->genre = "Not Stated";
            return $user;
        });

        return response()->json($users);
    }

    public function create(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $id = $request->id;

        try{
             // Find the user on the db using the id to fetch the email
            $user = User::findOrFail($id);

            $email = $user->email;
            $name = $user->name;

            $artistRequest = ArtistRequest::create([
                'user_id' => $id,
                'requested_artist_name' => $name,
                'email' => $email,
            ]);

            // You can return a response if needed
            return response()->json(['message' => 'Request sent successfully'], 200);
        } catch(ModelNotFoundException $exception){
            return response()->json(['message' => "User Not Found"], 200);
        }
    
    }

    public function showStatus(Request $request)
    {
        // Validate the request id
        $validatedData = $request->validate([
            'id' => 'required',
        ]);
    
        // Extract the id from the validated data
        $id = $validatedData['id'];
    
        // Attempt to find the record by id
        $record = ArtistRequest::where('user_id', $id)->first();

        if($record !== null){
            return response()->json(['found' => true]);
        }else {
            return response()->json(['found' => false]);
        }
        
    }

    public function approve(Request $request)
    {
        $id = $request->id;

        ArtistRequest::where('user_id', $id)->delete();

        User::where('id', $id)->update(['user_type' => 'artist']);

        return response()->json(['message' => "Request Approved"], 200);
    }

    public function reject(Request $request)
    {
        $id = $request->id;

        ArtistRequest::where('user_id', $id)->delete();

        return response()->json(['message' => "Request Rejected Successfully"], 200);
    }

}
