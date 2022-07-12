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
        $hashids_authID = new Hashids('', 10);
        $hashAuthID = $hashids_authID->encode($id);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if(file_exists($path)) {
            $x = 1;
            $new_path = $path;

            while (file_exists($new_path)) {
                $newfilename = $filename . '-' . $x . '.' .$extension;
                $new_path = $hashAuthID.'/'.$newfilename;
                $x++;
            }
            // save the file to directory
            return [
                'path' => $new_path,
                'filename' => $newfilename,
            ];
        }else{
            return [
                'path' => $hashAuthID.'/'.$filename.'.'.$extension,
                'filename' => $filename.'.'.$extension,
            ];
        }
    }

    /*public function sendsms($number, $message) {
        $ch = curl_init();
        $itexmo = array('1' => $number, '2' => $message, '3' => env('SMSAPIKEY'), 'passwd' => env('SMSAPIPWD'));
        curl_setopt($ch, CURLOPT_URL, "https://www.itexmo.com/php_api/api.php");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($itexmo));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
        curl_close($ch);
    }*/

    protected $API_CODE = "ST-APRIL506287_XU9RG";
    protected $API_PASS = "c7s1el31[t";
    protected $API_BASE = "https://www.itexmo.com/php_api/";
    protected $API_ENDPOINTS = array(
        'SEND_SMS' => 'api.php',
        'GET_INFO' => 'apicode_info.php',
        'LIST_OUTGOING' => 'display_outgoing.php',
        'SERVER_STATUS' => 'serverstatus.php',
        'CLEAR_OUTGOING' => 'delete_outgoing_all.php'
    );
    protected $API_ENDPOINTS_METHODS = array(
        'SEND_SMS' => 'POST',
        'GET_INFO' => 'GET',
        'LIST_OUTGOING' => 'GET',
        'SERVER_STATUS' => 'GET',
        'CLEAR_OUTGOING' => 'GET'
    );
    protected $ITEXMO_STATUS_CODES = array(
        "1" =>   "Invalid Number",
        "2" =>   "Number Prefix not supported. Please contact us so we can add.",
        "3" =>   "Invalid ApiCode.",
        "4" =>   "Maximum Message per day reached. This will be reset every 12MN.",
        "5" =>   "Maximum allowed characters for message reached.",
        "6" =>   "System OFFLINE.",
        "7" =>   "Expired ApiCode.",
        "8" =>   "iTexMo Error. Please try again later.",
        "9" =>   "Invalid Function Parameters.",
        "10" =>  "Recipient's number is blocked due to FLOODING, message was ignored.",
        "11" =>  "Recipient's number is blocked temporarily due to HARD sending (after 3 retries of sending and message still failed to send) and the message was ignored. Try again after an hour.",
        "12" =>  "Invalid request. You can't set message priorities on non corporate apicodes.",
        "13" =>  "Invalid or Not Registered Custom Sender ID.",
        "14" =>  "Invalid preferred server number.",
        "15" =>  "IP Filtering enabled - Invalid IP.",
        "16" =>  "Authentication error. Contact support at support@itexmo.com",
        "17" =>  "Telco Error. Contact Support support@itexmo.com",
        "18" =>  "Message Filtering Enabled. Contact Support support@itexmo.com",
        "19" =>  "Account suspended. Contact Support support@itexmo.com",
        "0" =>   "Success! Message is now on queue and will be sent soon."
    );

    private function __getRequest($url){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function __postRequest($url, $payload){
        // create curl handle
        $ch = curl_init();
        // set curl options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // execute curl handle
        $result = curl_exec ($ch);

        // close curl handle
        curl_close ($ch);

        return $result;
    }

    function createRequest($endpoint, $payload){
        if(array_key_exists($endpoint, $this->API_ENDPOINTS)){
            $url = $this->API_BASE . $this->API_ENDPOINTS[$endpoint];
            $requestType = $this->API_ENDPOINTS_METHODS[$endpoint];
            if($requestType === "POST"){
                return $this->__postRequest($url, $payload);
            } else {
                return $this->__getRequest($url . '?' . http_build_query($payload));
            }
        }
    }

    /**
     *  getStatusMessage - Allows you to map status code returned by itexmo to human readable message
     */
    function getStatusMessage($status_code){
        if(array_key_exists($status_code, $this->ITEXMO_STATUS_CODES)){
            // return $this->ITEXMO_STATUS_CODES[$status_code];
            return $status_code;
        } else {
            return $status_code;
        }
    }

    /**
     *  sendSMS - sends sms and returns message based on itexmo status codes
     */
    function sendSMS($number, $message){
        $dMessage = $message. "\r\n\r\n Sent from bmis.tech";
        $payload = array('1' => $number, '2' => $dMessage, '3' => env('SMSAPIKEY'), 'passwd' => env('SMSAPIPWD'));
        return $this->getStatusMessage($this->createRequest("SEND_SMS", $payload));
    }

    /**
     * getInfo - get you itexmo api info. Returns a json string
     */
    function getInfo(){
        return $this->createRequest('GET_INFO', array('apicode' => $this->API_CODE));
    }

    function listOutgoingSMS($sort='desc'){
        return $this->createRequest('LIST_OUTGOING', array('apicode' => $this->API_CODE, 'sortby' => $sort));
    }

    function clearOutgoingSMS(){
        return $this->createRequest('CLEAR_OUTGOING', array('apicode' => $this->API_CODE));
    }

    function getServerStatus(){
        return $this->createRequest('SERVER_STATUS', array('apicode' => $this->API_CODE));
    }
}
