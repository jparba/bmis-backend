<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Hashids\Hashids;
use App\Resident;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function brgyID() {
        $brgy_id = Resident::latest()->first();
        $brgy_id = !empty($brgy_id) ? $brgy_id->brgy_id : date('Y').'-0000';
        $brgy_idy = explode('-', $brgy_id);
        $brgy_id = str_pad((int)$brgy_idy[1] + 1, 4, '0', STR_PAD_LEFT);
        return $brgy_idy[0].'-'.$brgy_id;
    }

    public function incrementFilename($path, $id) {
        // $hashids_authID = new Hashids('', 10);
        // $hashAuthID = $hashids_authID->encode($id);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if(file_exists($path)) {
            $x = 1;
            $new_path = $path;

            while (file_exists($new_path)) {
                $newfilename = $filename . '-' . $x . '.' .$extension;
                $new_path = $newfilename;
                $x++;
            }
            // save the file to directory
            return [
                'path' => $new_path,
                'filename' => $newfilename,
            ];
        }else{
            return [
                'path' => $filename.'.'.$extension,
                'filename' => $filename.'.'.$extension,
            ];
        }
    }

    public function sendsms($number, $message) {
        $ch = curl_init();
        $itexmo = array('1' => $number, '2' => $message, '3' => env('SMSAPIKEY'), 'passwd' => env('SMSAPIPWD'));
        curl_setopt($ch, CURLOPT_URL, "https://www.itexmo.com/php_api/api.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($itexmo));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
        curl_close($ch);
    }
}
