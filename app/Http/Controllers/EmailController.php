<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Mail\NewMail;

class EmailController extends Controller
{

    public function verifyEmail(Request $request, $id)
    {

        $decryptedData = Crypt::decryptString($id);
        [$decryptedId, $decryptedDate] = unserialize($decryptedData);
        $decryptedDate = Carbon::parse($decryptedDate);

        if($decryptedDate->diffInMinutes(now()) <= 5){
            // Find the user by ID
            $user = User::find($decryptedId);

            if (!$user) {
                return "User Not Found";
            }

            if($user->email_verified_at != null){
                return redirect('http://localhost:5173/');
            }

            // Update the user's verified_at column
            $user->email_verified_at = now();
            $user->status = 'active';
            $user->save();

            return redirect('http://localhost:5173/');
        }else{
            return 'Verification Link Expired. You can request a new link at the home page.';
        }
    }

    public function resendEmail($id){
        $user = User::findOrFail($id);
        
        if($user->email_verified_at !== null){
            return false;
        }

        $createdAt = Carbon::now();
        $encryptedData =  Crypt::encryptString(serialize([$id, $createdAt]));
        $verificationUrl = '/api/verify/' . $encryptedData;

        Mail::queue(new NewMail($user, $verificationUrl));

    }
}
