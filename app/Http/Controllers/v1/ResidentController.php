<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\User;
use App\Resident;
use App\Http\Requests\StoreResidentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ResidentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreResidentRequest $request) {
        try{
            $caddress = $request->cpurok.'/'.$request->cstreet.'/'.$request->ccity.'/'.$request->cprovince;
            $paddress = $request->ppurok.'/'.$request->pstreet.'/'.$request->pcity.'/'.$request->pprovince;
            $data = [
                'user_id' => Auth::id(),
                'firstname' => $request->firstname,
                'middlename' => $request->middlename,
                'lastname' => $request->lastname,
                'phone' => $request->phone,
                'email' => $request->email,
                'dob' => $request->bdate,
                'pob' => $request->pob,
                'gender' => $request->gender,
                'weight'=> $request->weight,
                'height'=> $request->height,
                'religion' => $request->religion,
                'blood_type' => $request->bloodtype,
                'occupation' => $request->occupation,
                'civil_status' => $request->civilstatus,
                'spouse_name' => $request->spouse,
                'current_address' => $caddress,
                'pernament_address' => $paddress,
                'tin'=> $request->tin,
                'pagibig'=> $request->pagibig,
                'sss'=> $request->sss,
                'philhealth'=> $request->philhealth
            ];

            $resident = Resident::where('user_id', Auth::id())->first();
            if(empty($resident)) {
                $data['brgy_id'] = $this->brgyID();
            }

            DB::transaction(function () use($request, &$data) {
                $resident = Resident::updateOrCreate(
                    ['user_id' => Auth::id()],
                    $data
                );

                User::where('id', Auth::id())
                    ->update([
                       'firstname' => $request->firstname,
                       'middlename' => $request->middlename,
                       'lastname' => $request->lastname,
                       'email' => $request->email,
                       'phone' => $request->phone,
                       'civil_status' => $request->civilstatus
                    ]);


                $user = User::with('resident')->where('id', Auth::id())->get();
                $data['user'] = $user;
                $data['success'] = true;
                $data['message'] = 'Details save successfully';
            });

            return response()->json($data, 200);

        }catch(\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Saving details failed']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
