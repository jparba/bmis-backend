<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\User;
use App\Resident;
use App\PasswordReset;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Hashids\Hashids;

class UserController extends Controller {
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $validated = $request->validate([
            'email' => 'email|unique:users',
        ]);


        try {

            $data = [
                'firstname' => $request->firstname,
                'middlename' => $request->middlename,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                // 'brgy_id' => $request->idNumber
            ];
            if($request->hasFile('file')) {

                $file = $request->file('file');
                $folder = public_path('validID/');
                $filename = $request->file('file')->getClientOriginalName();
                $path = $folder.'/'.$filename;

                if (!File::exists($folder)) { File::makeDirectory($folder, 0775, true, true); }

                $incrementFilename = $this->incrementFilename($path, '');

                // save the file to directory
                $file->storeAs('validID/', $incrementFilename['filename']);
                $data['valid_ID'] = $incrementFilename['path'];
            }

            if($request->base64Image) {
                // remove the part that we don't need from the provided image and decode it
                $imgdata = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->base64Image));
                $filename = $request->firstname.''.$request->lastname.'base64.png';

                $filepath = "validID/".$filename;

                // Save the image in a defined path
                file_put_contents($filepath, $imgdata);
                $data['valid_ID'] = '/'.$filename;
            }

            $user = User::create($data);

            return response()->json(['success' => true, 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error encountered']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        $user = User::with('resident')->where('id', $id)->get();
        return response()->jsoN($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreUserRequest $request, $id) {
        try {

            $data = [
               'firstname' => $request->firstname,
               'middlename' => $request->middlename,
               'lastname' => $request->lastname,
               'email' => $request->email,
               'phone' => $request->phone,
            ];

            User::where('id', $id)->update($data);
            Resident::where('user_id', Auth::id())->update($data);

            $user = User::with('resident')->find($id);
            return response()->json(['success' => true, 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    public function changePassword(ChangePasswordRequest $request, $id) {
        $user = User::find($id);
        if(!Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password entered incorrect']);
        }
        try {
            User::where('id', $id)
                   ->update([
                       'password' => Hash::make($request->newPassword),
                    ]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false]);
        }
    }

    public function generateOTP(Request $request) {
        $user = User::with('resident')->where([
            ['email', $request->email],
            ['role', 'user'],
        ])->first();


        if($user) {
            $digits = 5; /*5 digits*/
            $otp = rand(pow(10, $digits-1), pow(10, $digits)-1);
            $data = [ 'token' => $otp ];
            $resident = PasswordReset::updateOrCreate(
                ['email' => $request->email],
                $data
            );

            $smsContent = 'Your OTP is: '.$otp;
            $status = json_decode($this->getServerStatus(), true);

            if($status['result']['APIStatus'] == 'ONLINE') {
                $smsapi = $this->sendSMS($user['phone'], $smsContent);
                return response()->json(['success' => true]);
            }else{
                return response()->json(['success' => false, 'message' => 'SMS not available. Please try again later!']);
            }

            return response()->json(['success' => true]);
        }else{
            return response()->json(['success' => false, 'message' => 'Email not registered!']);
        }
    }

    public function confirmOTP(Request $request) {
        $otp = PasswordReset::where([
            ['email', $request->email],
            ['token', $request->otp],
        ])->first();

        if($otp) {
            return response()->json(['success' => true]);
        }else{
            return response()->json(['success' => false, 'message' => 'OTP entered is invalid!']);
        }
    }

    public function resetPassword(Request $request) {
        $user = User::where('email', $request->email);
        if($user) {
            User::where('email', $request->email)
                   ->update([
                       'password' => Hash::make($request->newPassword),
                    ]);
            return response()->json(['success' => true]);
        }else{
            return response()->json(['success' => false]);
        }
    }

    public function updatePhoto(Request $request, $id) {

        if($request->base64Image) {

            try{
                $data = [];
                DB::transaction(function () use($request, $id, &$data) {

                    // $file = $request->file('file');
                    $user = User::where('id', Auth::id())->first();
                    $folder = public_path('accounts/');
                    // $filename = $request->file('file')->getClientOriginalName();
                    // $path = $folder.'/'.$filename;

                    if (!File::exists($folder)) {
                        File::makeDirectory($folder, 0775, true, true);
                    }

                    // $incrementFilename = $this->incrementFilename($path, '');

                    // save the file to directory
                    // $file->storeAs('accounts/', $incrementFilename['filename']);

                    // remove the part that we don't need from the provided image and decode it
                    $imgdata = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->base64Image));
                    $filename = $user['firstname'].''.$user['lastname'].''.md5(uniqid(rand(), true)).'.png';

                    $filepath = "accounts/".$filename;

                    // Save the image in a defined path
                    file_put_contents($filepath, $imgdata);
                    $filename = '/'.$filename;

                    User::where('id', Auth::id())->update(['pic' => $filename]);
                    Resident::where('user_id', Auth::id())->update(['pic' => $filename]);

                    $data['user'] = User::with('resident')->where('id', $id)->get();
                    $data['success'] = true;
                    $data['message'] = 'Changing photo successfully';

                });
                return response()->json($data, 200);
            }catch(\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Changing photo failed']);
            }
        }

        if($request->hasFile('file')) {

            try{
                $data = [];
                DB::transaction(function () use($request, $id, &$data) {

                    $file = $request->file('file');
                    $folder = public_path('accounts/');
                    $filename = $request->file('file')->getClientOriginalName();
                    $path = $folder.'/'.$filename;

                    if (!File::exists($folder)) {
                        File::makeDirectory($folder, 0775, true, true);
                    }

                    $incrementFilename = $this->incrementFilename($path, '');

                    // save the file to directory
                    $file->storeAs('accounts/', $incrementFilename['filename']);
                    User::where('id', Auth::id())->update(['pic' => $incrementFilename['path']]);
                    Resident::where('user_id', Auth::id())->update(['pic' => $incrementFilename['path']]);

                    $data['user'] = User::with('resident')->where('id', $id)->get();
                    $data['success'] = true;
                    $data['message'] = 'Changing photo successfully';

                });
                return response()->json($data, 200);
            }catch(\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Changing photo failed']);
            }
        }
    }

    public function reuploadID(Request $request) {

        if($request->hasFile('file')) {

            $file = $request->file('file');
            $folder = public_path('validID/');
            $filename = $request->file('file')->getClientOriginalName();
            $path = $folder.'/'.$filename;

            if (!File::exists($folder)) { File::makeDirectory($folder, 0775, true, true); }

            $incrementFilename = $this->incrementFilename($path, '');

            // save the file to directory
            $file->storeAs('validID/', $incrementFilename['filename']);

            try{
                User::where('id', Auth::id())->update([
                        'status' => 0,
                        'valid_ID' => $incrementFilename['path']
                    ]
                );
                return response()->json(['success' => true, 'message' => 'Valid ID uploaded successfully']);
            }catch(\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Upload valid ID failed']);
            }
        }

        if($request->base64Image) {
            // remove the part that we don't need from the provided image and decode it
            $imgdata = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->base64Image));
            $filename = md5(uniqid(rand(), true)).'base64.png';

            $filepath = "validID/".$filename;

            // Save the image in a defined path
            file_put_contents($filepath, $imgdata);
            $filename = '/'.$filename;

            try{
                User::where('id', Auth::id())->update([
                        'status' => 0,
                        'valid_ID' => $filename
                    ]
                );
                return response()->json(['success' => true, 'message' => 'Valid ID uploaded successfully']);
            }catch(\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Upload valid ID failed']);
            }
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
    }
}
