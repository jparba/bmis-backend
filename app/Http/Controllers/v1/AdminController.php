<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\User;
use App\Resident;
use App\Application;
use App\Official;
use App\Announcement;
use App\Http\Requests\StoreNewResident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Hashids\Hashids;

class AdminController extends Controller {

    public function residentList() {
        $resident = Resident::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($resident, 200);
    }

    public function singleResident(Request $request, $id) {
        $resident = Resident::where('id', $id)->first();
        return response()->json($resident, 200);
    }

    public function newResident(StoreNewResident $request) {
        $hashids_authID = new Hashids('', 10);

        try {
            $caddress = $request->cpurok.'/'.$request->cstreet.'/'.$request->ccity.'/'.$request->cprovince;
            $paddress = $request->ppurok.'/'.$request->pstreet.'/'.$request->pcity.'/'.$request->pprovince;
            $data = [
                'user_id'=> $request->user_id,
                'brgy_id'=> $this->brgyID(),
                'firstname'=> $request->firstname,
                'middlename'=> $request->middlename,
                'lastname'=> $request->lastname,
                'phone'=> $request->phone,
                'email'=> $request->email,
                'dob'=> $request->bdate,
                'pob'=> $request->pob,
                'gender'=> $request->gender,
                'weight'=> $request->weight,
                'height'=> $request->height,
                'religion'=> $request->religion,
                'blood_type'=> $request->bloodtype,
                'occupation'=> $request->occupation,
                'civil_status'=> $request->civilstatus,
                'spouse_name'=> $request->spouse,
                'current_address'=> $caddress,
                'pernament_address'=> $paddress,
                'tin'=> $request->tin,
                'pagibig'=> $request->pagibig,
                'sss'=> $request->sss,
                'philhealth'=> $request->philhealth
            ];
            $latestRecord =  Resident::latest()->first();
            $id = !empty($latestRecord)? $latestRecord->id + 1 : 1;
            if($request->hasFile('file')) {
                $hashAuthID = $hashids_authID->encode($id);

                $file = $request->file('file');
                $folder = public_path('accounts/'.$hashAuthID);
                $filename = $request->file('file')->getClientOriginalName();
                $path = $folder.'/'.$filename;

                if (!File::exists($folder)) { File::makeDirectory($folder, 0775, true, true); }

                $incrementFilename = $this->incrementFilename($path, $id);

                // save the file to directory
                $file->storeAs('accounts/'.$hashAuthID, $incrementFilename['filename']);
                $data['pic'] = $incrementFilename['path'];
            }

            Resident::create($data);

            return response()->json(['success' => true, 'message' => 'Resident added successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Adding resident failed']);
        }
    }

    public function updateResident(StoreNewResident $request) {
        $hashids_authID = new Hashids('', 10);
        try {
            $caddress = $request->cpurok.'/'.$request->cstreet.'/'.$request->ccity.'/'.$request->cprovince;
            $paddress = $request->ppurok.'/'.$request->pstreet.'/'.$request->pcity.'/'.$request->pprovince;
            $data = [
                'user_id'=> $request->user_id,
                'brgy_id'=> $request->brgyID,
                'firstname'=> $request->firstname,
                'middlename'=> $request->middlename,
                'lastname'=> $request->lastname,
                'phone'=> $request->phone,
                'email'=> $request->email,
                'dob'=> $request->bdate,
                'pob'=> $request->pob,
                'gender'=> $request->gender,
                'weight'=> $request->weight,
                'height'=> $request->height,
                'religion'=> $request->religion,
                'blood_type'=> $request->bloodtype,
                'occupation'=> $request->occupation,
                'civil_status'=> $request->civilstatus,
                'spouse_name'=> $request->spouse,
                'current_address'=> $caddress,
                'pernament_address'=> $paddress,
                'tin'=> $request->tin,
                'pagibig'=> $request->pagibig,
                'sss'=> $request->sss,
                'philhealth'=> $request->philhealth,
                'pic'=> $request->pic
            ];

            if($request->hasFile('file')) {
                $hashAuthID = $hashids_authID->encode($request->id);

                $file = $request->file('file');
                $folder = public_path('accounts/');
                $filename = $request->file('file')->getClientOriginalName();
                $path = $folder.'/'.$filename;

                if (!File::exists($folder)) { File::makeDirectory($folder, 0775, true, true); }

                $incrementFilename = $this->incrementFilename($path, $request->id);

                // save the file to directory
                $file->storeAs('accounts/', $incrementFilename['filename']);
                $data['pic'] = $incrementFilename['path'];
            }

            Resident::where('id', $request->id)->update($data);

            if(!empty($request->user_id)) {
                User::where('id', $request->user_id)
                       ->update([
                           'firstname' => $request->firstname,
                           'middlename' => $request->middlename,
                           'lastname' => $request->lastname,
                           'email' => $request->email,
                           'phone' => $request->phone,
                           'pic' => $request->pic
                        ]);
            }

            return response()->json(['success' => true, 'message' => 'Resident updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Updating resident failed']);
        }
    }

    public function userList() {
        $user = User::where('role', 'user')->orderBy('created_at', 'desc')->get();
        return response()->json($user, 200);
    }

    public function getAllRequest() {
        $request =  Application::with('user.resident')->orderBy('created_at', 'desc')->get();
        return response()->json($request, 200);
    }

    public function updateStatusRequest(Request $request) {
        Application::where('id', $request->id)->update(['status' => $request->status]);
        $data = [
            'index' => $request->index,
            'status' => $request->status,
            'message' => $request->status == 3 ? 'Request rejected' : 'Request approved',
        ];
        return response()->json($data, 200);
    }

    public function updatePurposeRequest(Request $request) {
        Application::where('id', $request->purposeID)->update(['purpose' => $request->purposeTxt]);
        $data = [
            'message' => 'Request purpose updated',
        ];
        return response()->json($data, 200);
    }

    public function getSingleUser(Request $request, $id) {
        $user = User::with('resident')->where('id', $id)->get();
        return response()->json($user[0], 200);
    }

    public function verify(Request $request) {
        $mStatus = [
            'success' => [1 => 'verified', 2 => 'declined', 3 => 'block', 4 => 'set to reupload ID'],
            'error' => [1 => 'Verifying', 2 => 'Declining', 3 => 'Blocking', 4 => 'Setting to reupload ID']
        ];

        try {
            $user = User::where('id', (int)$request->id)->get();
            DB::transaction(function () use($request, &$user, &$mStatus) {
                User::where('id', (int)$request->id)->update(['status' => $request->status]);
                $smsapi = $this->sendsms($user[0]['phone'], 'Hello '.$user[0]['firstname'].' '.$user[0]['lastname'].' your BMIS account was '.$mStatus['success'][$request->status]).'.';
            });

            return response()->json(['success' => true, 'message' => 'User '.$mStatus['success'][$request->status].' successfully']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $mStatus['error'][$request->status].' user failed']);
        }
    }

    public function getBrgyCapt() {
        $official = Official::where('position', 'Barangay Captain')->first();
        return response()->json($official, 200);
    }

    public function officialList() {
        $official = Official::orderBy('created_at', 'desc')->get();
        return response()->json($official, 200);
    }

    public function singleOfficial(Request $request, $id) {
        $official = Official::where('id', $id)->first();
        return response()->json($official, 200);
    }

    public function newOfficial(Request $request) {
        $hashids_authID = new Hashids('', 10);
        try {
            $data = [
                'firstname'=> $request->firstname,
                'middlename'=> $request->middlename,
                'lastname'=> $request->lastname,
                'gender'=> $request->gender,
                'birthdate'=> $request->bdate,
                'position'=> $request->position,
                'phone'=> $request->phone,
                'email'=> $request->email,
            ];

            $latestRecord =  Official::latest()->first();
            $id = !empty($latestRecord)? $latestRecord->id + 1 : 1;
            if($request->hasFile('file')) {
                $hashAuthID = $hashids_authID->encode($id);

                $file = $request->file('file');
                $folder = public_path('officials/'.$hashAuthID);
                $filename = $request->file('file')->getClientOriginalName();
                $path = $folder.'/'.$filename;

                if (!File::exists($folder)) { File::makeDirectory($folder, 0775, true, true); }

                $incrementFilename = $this->incrementFilename($path, $id);

                // save the file to directory
                $file->storeAs('officials/'.$hashAuthID, $incrementFilename['filename']);
                $data['photo'] = $incrementFilename['path'];
            }

            if($request->hasFile('signatureFile')) {
                $hashAuthID = $hashids_authID->encode($id);

                $signatureFile = $request->file('signatureFile');
                $signaturefolder = public_path('officials/'.$hashAuthID);
                $signaturefilename = $request->file('signatureFile')->getClientOriginalName();
                $signaturepath = $signaturefolder.'/'.$signaturefilename;

                if (!File::exists($signaturefolder)) { File::makeDirectory($signaturefolder, 0775, true, true); }

                $signatureIncrementFilename = $this->incrementFilename($signaturepath, $id);

                // save the file to directory
                $signatureFile->storeAs('officials/'.$hashAuthID, $signatureIncrementFilename['filename']);
                $data['signature'] = $signatureIncrementFilename['path'];
            }

            Official::create($data);

            return response()->json(['success' => true, 'message' => 'Official added successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Adding official failed']);
        }
    }

    public function updateOfficial(Request $request) {
        $hashids_authID = new Hashids('', 10);
        try {
            $data = [
                'firstname'=> $request->firstname,
                'middlename'=> $request->middlename,
                'lastname'=> $request->lastname,
                'gender'=> $request->gender,
                'birthdate'=> $request->bdate,
                'phone'=> $request->phone,
                'position'=> $request->position,
                'email'=> $request->email,
                'photo'=> $request->photo,
                'signature'=> $request->signature
            ];

            if($request->hasFile('file')) {
                $hashAuthID = $hashids_authID->encode($request->id);

                $file = $request->file('file');
                $folder = public_path('officials/'.$hashAuthID);
                $filename = $request->file('file')->getClientOriginalName();
                $path = $folder.'/'.$filename;

                if (!File::exists($folder)) { File::makeDirectory($folder, 0775, true, true); }

                $incrementFilename = $this->incrementFilename($path, $request->id);

                // save the file to directory
                $file->storeAs('officials/'.$hashAuthID, $incrementFilename['filename']);
                $data['photo'] = $incrementFilename['path'];
            }

            if($request->hasFile('signatureFile')) {
                $hashAuthID = $hashids_authID->encode($request->id);

                $signatureFile = $request->file('signatureFile');
                $signatureFolder = public_path('officials/'.$hashAuthID);
                $signatureFilename = $request->file('signatureFile')->getClientOriginalName();
                $signaturePath = $signatureFolder.'/'.$signatureFilename;

                if (!File::exists($signatureFolder)) { File::makeDirectory($signatureFolder, 0775, true, true); }

                $incrementFilename = $this->incrementFilename($signaturePath, $request->id);

                // save the file to directory
                $signatureFile->storeAs('officials/'.$hashAuthID, $incrementFilename['filename']);
                $data['signature'] = $incrementFilename['path'];
            }

            Official::where('id', $request->id)->update($data);

            return response()->json(['success' => true, 'message' => 'Official updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Updating official failed']);
        }
    }

    public function eventAnnouncementList() {
        $announcement = Announcement::orderBy('created_at', 'desc')->get();

        $newannouncement = [];
        foreach ($announcement as $key => $value) {
            $newannouncement[] = $value;
            $newannouncement[$key]['content'] = strip_tags($value['content']);
        }

        return response()->json($announcement, 200);
    }

    public function newEventAnnouncement(Request $request) {
        try {
            $data = [
                'title'=> $request->title,
                'type'=> $request->type,
                'content'=> $request->content,
                'sms'=> $request->smscontent
            ];


            $users = User::where(['status' => 1, 'role' => 'user'])->get();
            $smsapi = '';
            DB::transaction(function () use($request, &$data, &$users, &$smsapi) {
                Announcement::create($data);

                foreach ($users as $user) {
                    $smsapi = $this->sendsms($user['phone'], $data['sms']);
                }
            });

            return response()->json(['success' => true, 'smsapi' => $smsapi, 'message' => $request->type == 1 ? 'Event added successfully' : 'Announcement added successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $request->type == 1 ? 'Adding event failed' : 'Adding announcement failed']);
        }
    }

    public function singleEA(Request $request, $id) {
        $ea = Announcement::where('id', $id)->first();
        return response()->json($ea, 200);
    }

    public function updateEventAnnouncement(Request $request) {
        try {
            $data = [
                'title'=> $request->title,
                'type'=> $request->type,
                'content'=> $request->content,
            ];

            Announcement::where('id', $request->id)->update($data);

            return response()->json(['success' => true, 'message' => $request->type == 1 ? 'Event updated successfully' : 'Announcement updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $request->type == 1 ? 'Updating event failed' : 'Updating nnouncement failed']);
        }
    }

    public function deleteAnnouncement(Request $request) {
        try {
            Announcement::where('id', $request->id)->delete();
            return response()->json(['success' => true, 'message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Deleting failed']);
        }
    }

    public function hideUnhideAnnouncement(Request $request) {
        // try {
            Announcement::where('id', $request->id)->update([
               'hidden' => $request->value == '1' ? 0 : 1
           ]);
            return response()->json(['success' => true, 'message' => $request->value == '1'? 'Unhide successfully' : 'Hidden successfully']);
        // } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $request->value == '1'? 'Unhide failed' : 'Hidden failed']);
        // }
    }

    public function dashboardData() {
        $user = User::where(['status' => 1, 'role' => 'user'])->count();
        $request = Application::all()->count();
        $resident = Resident::all()->count();
        $announcement = Announcement::all()->count();
        $dashData = [
            'user' => $user? $user : 0,
            'request' => $request? $request : 0,
            'resident' => $resident? $resident : 0,
            'announcement' => $announcement? $announcement : 0,
        ];
        return response()->json(['success' => true, 'data' => $dashData]);
    }

}
