<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\User;
use App\Resident;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Hashids\Hashids;

class UserController extends Controller
{
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
                $folder = public_path('accounts/');
                $filename = $request->file('file')->getClientOriginalName();
                $path = $folder.'/'.$filename;

                if (!File::exists($folder)) { File::makeDirectory($folder, 0775, true, true); }

                $incrementFilename = $this->incrementFilename($path, '');

                // save the file to directory
                $file->storeAs('accounts/', $incrementFilename['filename']);
                $data['pic'] = $incrementFilename['path'];
            }

            User::create($data);

            return response()->json(['success' => true]);
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
            User::where('id', $id)
                   ->update([
                       'firstname' => $request->firstname,
                       'middlename' => $request->middlename,
                       'lastname' => $request->lastname,
                       'email' => $request->email,
                       'phone' => $request->phone,
                    ]);
            $user = User::find($id);
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

    public function updatePhoto(Request $request, $id) {

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

                    $incrementFilename = $this->incrementFilename($path, Auth::id());

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
                        'valid_id' => $incrementFilename['path']
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
