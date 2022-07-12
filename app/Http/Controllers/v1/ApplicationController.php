<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Application;
use App\User;
use App\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Hashids\Hashids;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $resident = Resident::where('user_id', Auth::id())->first();
        if(empty($resident)) return response()->json(['hasDetail' => false]);
        $request = Application::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return response()->json(['hasDetail' => true, 'data' => $request]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        if($request->hasFile('file')) {
            $hashids_authID = new Hashids('', 10);
            $hashAuthID = $hashids_authID->encode(Auth::id());

            $file = $request->file('file');
            $folder = public_path('attachment/'.$hashAuthID);
            $filename = $request->file('file')->getClientOriginalName();
            $path = $folder.'/'.$filename;

            if (!File::exists($folder)) {
                File::makeDirectory($folder, 0775, true, true);
            }

            $incrementFilename = $this->incrementFilename($path, '');

            // save the file to directory
            $file->storeAs('attachment/'.$hashAuthID, $incrementFilename['filename']);
        }

        if($request->base64Image) {
            // remove the part that we don't need from the provided image and decode it
            $imgdata = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->base64Image));
            $filename='cedulaAttach_'.date('m-d-Y_hia').'base64.png';

            $filepath = "attachment/".$filename;

            // Save the image in a defined path
            file_put_contents($filepath, $imgdata);
            $attachmentFile = '/'.$filename;
        }

        $attachFile = $request->hasFile('file') ? $incrementFilename['path'] : ($request->base64Image? $attachmentFile : '');
        $drequest = Application::create([
            'user_id' => Auth::id(),
            'type' => (int)$request->type,
            'status' => 1,
            'attachment' => $attachFile,
            'purpose' => $request->purpose
        ]);

        $data = $drequest;
        $data['success'] = true;

        return response()->json($data, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        try {
            Application::where('id', (int)$id)
                   ->update([
                       'status' => 4,
                    ]);
            return response()->json(['success' => true, 'message' => 'Request cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Request cancelation failed']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
