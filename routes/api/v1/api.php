<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\AuthController;
use App\Http\Controllers\v1\UserController;
use App\Http\Controllers\v1\ResidentController;
use App\Http\Controllers\v1\ApplicationController;
use App\Http\Controllers\v1\AdminController;
use App\Http\Controllers\v1\PublicController;
use Illuminate\Support\Facades\Hash;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/getOfficialList', [PublicController::class, 'getOfficialList']);
Route::get('/getEventAnnouncementList', [PublicController::class, 'getEventAnnouncementList']);
Route::post('/EAsingle/{id}', [PublicController::class, 'EAsingle']);

Route::middleware('auth:sanctum')->group( function() {
    Route::post('/updateAccount/{id}', [UserController::class, 'update']);
    Route::post('/changePassword/{id}', [UserController::class, 'changePassword']);
    Route::post('/user/{id}', [UserController::class, 'show']);
    Route::post('/updatePhoto/{id}', [UserController::class, 'updatePhoto']);
    Route::post('/reuploadID', [UserController::class, 'reuploadID']);

    Route::post('/resident', [ResidentController::class, 'store']);
    Route::post('/resident/{id}', [ResidentController::class, 'update']);

    Route::get('/request', [ApplicationController::class, 'index']);
    Route::post('/request', [ApplicationController::class, 'store']);
    Route::patch('/request/{id}', [ApplicationController::class, 'update']);

    /*admin route*/
    Route::get('/getBrgyCapt', [AdminController::class, 'getBrgyCapt']);
    Route::get('/dashboardData', [AdminController::class, 'dashboardData']);

    Route::get('/residentList', [AdminController::class, 'residentList']);
    Route::post('/singleResident/{id}', [AdminController::class, 'singleResident']);
    Route::post('/newResident', [AdminController::class, 'newResident']);
    Route::post('/updateResident', [AdminController::class, 'updateResident']);

    Route::get('/requestAllList', [AdminController::class, 'getAllRequest']);
    Route::post('/updatePurposeRequest', [AdminController::class, 'updatePurposeRequest']);
    Route::post('/updateStatusRequest', [AdminController::class, 'updateStatusRequest']);

    Route::get('/userList', [AdminController::class, 'userList']);
    Route::post('/getSingleUser/{id}', [AdminController::class, 'getSingleUser']);
    Route::post('/verify', [AdminController::class, 'verify']);

    Route::get('/officialList', [AdminController::class, 'officialList']);
    Route::post('/singleOfficial/{id}', [AdminController::class, 'singleOfficial']);
    Route::post('/newOfficial', [AdminController::class, 'newOfficial']);
    Route::post('/updateOfficial', [AdminController::class, 'updateOfficial']);

    Route::get('/eventAnnouncementList', [AdminController::class, 'eventAnnouncementList']);
    Route::post('/newEventAnnouncement', [AdminController::class, 'newEventAnnouncement']);
    Route::post('/updateEventAnnouncement', [AdminController::class, 'updateEventAnnouncement']);
    Route::post('/singleEA/{id}', [AdminController::class, 'singleEA']);
    Route::post('/deleteAnnouncement', [AdminController::class, 'deleteAnnouncement']);
    Route::post('/hideUnhideAnnouncement', [AdminController::class, 'hideUnhideAnnouncement']);
});