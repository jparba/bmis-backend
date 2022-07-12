<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\User;
use App\Resident;
use App\Application;
use App\Official;
use App\Announcement;
use App\Http\Requests\StoreNewResident;
use App\Traits\Itexmo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Hashids\Hashids;

class AdminController extends Controller {
    // use Itexmo;

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

        // try {
            $caddress = $request->cpurok.'/'.$request->cstreet.'/'.$request->ccity.'/'.$request->cprovince;
            $paddress = $request->ppurok.'/'.$request->pstreet.'/'.$request->pcity.'/'.$request->pprovince;
            $data = [
                // 'user_id'=> $request->user_id,
                'brgy_id'=> $this->brgyID(),
                'firstname'=> $request->firstname,
                'middlename'=> $request->middlename,
                'lastname'=> $request->lastname,
                'name_extension'=> $request->name_extension,
                'phone'=> $request->phone,
                'email'=> $request->email,
                'dob'=> $request->bdate,
                'age'=> is_nan($request->age) || $request->age < 0? 0 : $request->age,
                'pob'=> $request->pob,
                'gender'=> $request->gender,
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
        // } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Adding resident failed']);
        // }
    }

    public function updateResident(StoreNewResident $request) {
        $hashids_authID = new Hashids('', 10);
        // try {
            $caddress = $request->cpurok.'/'.$request->cstreet.'/'.$request->ccity.'/'.$request->cprovince;
            $paddress = $request->ppurok.'/'.$request->pstreet.'/'.$request->pcity.'/'.$request->pprovince;
            $data = [
                'user_id'=> $request->user_id,
                'brgy_id'=> $request->brgyID,
                'firstname'=> $request->firstname,
                'middlename'=> $request->middlename,
                'lastname'=> $request->lastname,
                'name_extension'=> $request->name_extension,
                'phone'=> $request->phone,
                'email'=> $request->email,
                'dob'=> $request->bdate,
                'age'=> is_nan((int)$request->age) || (int)$request->age < 0? 0 : (int)$request->age,
                'pob'=> $request->pob,
                'gender'=> $request->gender,
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

                $incrementFilename = $this->incrementFilename($path, '');

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
                       'pic' => $request->hasFile('file')? $data['pic'] : $request->pic
                    ]);
            }

            return response()->json(['success' => true, 'data' => $data, 'message' => 'Resident updated successfully']);
        // } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Updating resident failed']);
        // }
    }

    public function userList() {
        $user = User::with('resident')->where('role', 'user')->orderBy('created_at', 'desc')->get();
        return response()->json($user, 200);
    }

    public function getAllRequest() {
        $request =  Application::with('user.resident')->orderBy('created_at', 'desc')->get();
        return response()->json($request, 200);
    }

    public function updateStatusRequest(Request $request) {
        Application::where('id', $request->id)->update(['status' => $request->status]);
        $user = Application::with('user')->where('id', $request->id)->first();
        $status = $request->status == 3 ? 'request was rejected' : 'request was approved';
        $smsapi = $this->sendSMS($user['user']['phone'], 'Hello '.$user['user']['firstname'].' '.$user['user']['lastname'].' your '.$status);
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
                $smsapi = $this->sendSMS($user[0]['phone'], 'Hello '.$user[0]['firstname'].' '.$user[0]['lastname'].' your BMIS account was '.$mStatus['success'][$request->status]).'.';
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
        // try {
            $data = [
                'firstname'=> $request->firstname,
                'middlename'=> $request->middlename,
                'lastname'=> $request->lastname,
                'name_extension'=> $request->name_extension,
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
        // } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Adding official failed']);
        // }
    }

    public function updateOfficial(Request $request) {
        $hashids_authID = new Hashids('', 10);
        try {
            $data = [
                'firstname'=> $request->firstname,
                'middlename'=> $request->middlename,
                'lastname'=> $request->lastname,
                'name_extension'=> $request->name_extension,
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
                    $smsapi = $this->sendSMS($user['phone'], $data['sms']);
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
        try {
            Announcement::where('id', $request->id)->update([
               'hidden' => $request->value == '1' ? 0 : 1
           ]);
            return response()->json(['success' => true, 'message' => $request->value == '1'? 'Unhide successfully' : 'Hidden successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $request->value == '1'? 'Unhide failed' : 'Hidden failed']);
        }
    }

    public function dashboardData() {
        $user = User::where(['status' => 1, 'role' => 'user'])->count();
        $request = Application::where('status', 1)->count();
        $resident = Resident::all()->count();
        $vaccinated = Resident::where('vaccinated', 1)->count();
        $announcement = Announcement::all()->count();
        $dashData = [
            'user' => $user? $user : 0,
            'request' => $request? $request : 0,
            'resident' => $resident? $resident : 0,
            'vaccinated' => $vaccinated? $vaccinated : 0,
            'announcement' => $announcement? $announcement : 0,
        ];

        $doughnutData[] = Resident::where('gender', 'Male')->count();
        $doughnutData[] = Resident::where('gender', 'Female')->count();
        $doughnutData[] = Resident::where('age', '>=', 60)->count();

        $puroks = ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6', 'Purok 7', 'Purok 8', 'Purok 9', 'Purok 10'];

        $purokData = [];
        foreach ($puroks as $k => $purok) {
            $pAll = Resident::where('current_address', 'LIKE', '%'.$purok.'%')->count();
            $pMale = Resident::where([
                ['current_address', 'LIKE', '%'.$purok.'%'],
                ['gender', '=', 'Male']])->count();
            $pFemale = Resident::where([
                ['current_address', 'LIKE', '%'.$purok.'%'],
                ['gender', '=', 'Female']])->count();
            $pSenior = Resident::where([
                ['current_address', 'LIKE', '%'.$purok.'%'],
                ['age', '>=', 60]])->count();
            $purokData['resident'][$k] = $pAll;
            $purokData['male'][$k] = $pMale;
            $purokData['female'][$k] = $pFemale;
            $purokData['senior'][$k] = $pSenior;
        }
        return response()->json(['success' => true, 'data' => $dashData, 'pdata' => $purokData, 'doughnutData' => $doughnutData]);
    }

    public function sendingSms(Request $request) {
        $smsContent = $request->content;
        $phoneNumbers = $request->phoneNumbers;

        $status = json_decode($this->getServerStatus(), true);

        if($status['result']['APIStatus'] == 'ONLINE') {
            foreach ($phoneNumbers as $key => $value) {
                $smsapi = $this->sendSMS($value, $smsContent);
            }
            return response()->json(['success' => true]);
        }else{
            return response()->json(['success' => false]);
        }

    }

    public function displayOutgoing(Request $request) {
        $list = $this->listOutgoingSMS();
        return $list;
    }

    public function residentListFilter(Request $request) {

            $resident = Resident::with('user')
                ->whereNotNull('email')
                ->where(function($query) use ($request) {
                    if($request->gender) {
                        $query->whereIn('gender', $request->gender);
                    }
                })
                ->where(function($query) use ($request) {
                    if($request->status) {
                        $query->whereIn('civil_status', $request->status);
                    }
                })
                ->where(function($query) use ($request) {
                    if($request->bloodType) {
                        $query->whereIn('blood_type', $request->bloodType);
                    }
                })
                ->where(function($query) use ($request) {
                    if($request->religion) {
                        $query->whereIn('religion', $request->religion);
                    }
                })
                ->where(function($query) use ($request) {
                    if($request->purok) {
                        foreach($request->purok as $keyword) {
                            $query->orWhere('current_address', 'LIKE', '%'.$keyword.'%');
                        }
                    }
                })
                ->where(function($query) use ($request) {
                    if($request->ageRange) {
                        $query->whereBetween('age', $request->ageRange);
                    }
                })->orderBy('created_at', 'desc')->get();
            $total = count($resident);
            $male = 0;
            $female = 0;

            foreach ($resident as $key => $value) {
                if($value['gender'] == 'Male') { $male = $male + 1;
                }else{ $female = $female + 1; }
            }
            return response()->json(['resident' => $resident, 'male' => $male, 'female' => $female, 'total' => $total]);
    }

    public function requestListFilter(Request $request) {
            if(count($request->dates) == 1) {
                return response()->json(['success' => false]);
            }
            $requestList = Application::with('user')
                ->whereNotNull('user_id')
                ->where(function($query) use ($request) {
                    if($request->name) {
                        $query->where('firstname', 'LIKE', '%'.$request->name.'%');
                    }
                })
                ->where(function($query) use ($request) {
                    if($request->type) {
                        $query->whereIn('type', $request->type);
                    }
                })
                ->where(function($query) use ($request) {
                    if($request->status) {
                        $query->whereIn('status', $request->status);
                    }
                })
                ->where(function($query) use ($request) {
                    if($request->dates) {
                        $query->whereBetween('created_at', $request->dates);
                    }
                })->orderBy('created_at', 'desc')->get();
            return response()->json(['success' => true, 'request' => $requestList, 'total' => count($requestList)]);
    }

    public function residentSearch(Request $request) {
        $resident = Resident::where('firstname', 'LIKE', '%'.$request->name.'%')->orWhere('phone', 'LIKE', '%'.$request->name.'%')->orderBy('created_at', 'desc')->get();
        return response()->json($resident, 200);
    }

}
