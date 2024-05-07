<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\MusicController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\ArtistRequestController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\ListenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');


// API Endpoints for email verification
Route::get('/verify/{id}', [EmailController::class, 'verifyEmail']);
Route::get('/resend/{id}', [EmailController::class, 'resendEmail']);

// Artist Request
Route::post('/artist_request', [ArtistRequestController::class, 'create']);
Route::post('/artist-status', [ArtistRequestController::class, 'showStatus']);
Route::get('/fetch_requests', [ArtistRequestController::class, 'index']);
Route::get('/artist_requests/{id}', [ArtistRequestController::class, 'reject']);
Route::post('/approve_artist_requests', [ArtistRequestController::class, 'approve']);

//Verify if user is logged in
Route::post('verifyUser', [AuthController::class, 'verify']);

// Upload Music
Route::post('/music', [MusicController::class, 'uploadMusic']);
// Fetch all music
Route::get('/music', [MusicController::class, 'getAllMusic']);
Route::post('/fetchArtistMusic', [MusicController::class, 'fetchArtistMusic']);
// Music Search
Route::post('/music/search', [MusicController::class, 'search']);

Route::post('/fetchMusic', [MusicController::class, 'fetchMusic']);
Route::post('/music/delete', [MusicController::class, 'delete']);

// Album Controllers
Route::get('/album/all', [AlbumController::class, 'index']);
Route::post('/album/delete', [AlbumController::class, 'delete']);
Route::post('/album/update', [AlbumController::class, 'update']);
Route::get('/albums/{albumId}', [MusicController::class, 'getAlbumWithMusic']);

Route::get('/user_management/all', [UserManagementController::class, 'fetchUsers']);
Route::get('/user/artist', [UserManagementController::class, 'fetchArtist']);

// Playlist
Route::get('/playlists/{id}', [PlaylistController::class, 'fetchUserPlaylist']);
Route::post('/playlist/create', [PlaylistController::class, 'create']);
Route::post('/playlist/latestTwo', [PlaylistController::class, 'getLatestPlaylist']);
Route::post('/playlist/fetchMusic', [PlaylistController::class, 'fetchMusic']);
Route::post('/audio/stream', [PlaylistController::class, 'streamMusic']);
Route::post('/playlists/add-music', [PlaylistController::class, 'addToPlaylist']);
Route::post('/playlists/delete', [PlaylistController::class, 'delete']);
Route::post('/playlists/{id}/edit', [PlaylistController::class, 'edit']);
Route::post('/playlist/music/delete', [PlaylistController::class, 'deleteTrack']);

// Listen
Route::post('/listen/record', [ListenController::class, 'record']);
Route::post('/listen/top-ten', [MusicController::class, 'fetchTopMusic']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
