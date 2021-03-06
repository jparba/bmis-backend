<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\User;
use App\Resident;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;

class StoreResidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
           'firstname'=> 'required',
           'middlename'=> 'required',
           'lastname'=> 'required',
           'gender'=> 'required',
           'bdate'=> 'required',
           'pob'=> 'required',
           'bloodtype'=> 'required',
           'civilstatus'=> 'required',
           'religion'=> 'required',
           'occupation'=> 'required',
           'phone'=> 'required',
           'email'=> 'required|email',
           'cstreet'=> 'required',
           'ccity'=> 'required',
           'cprovince'=> 'required',
           'pstreet'=> 'required',
           'pcity'=> 'required',
           'pprovince'=> 'required',
        ];
    }

    public function messages() {
        return [
            'firstname.required'=> 'Firstname is required',
            'middlename.required'=> 'Middlename is required',
            'lastname.required'=> 'Lastname is required',
            'gender.required'=> 'Gender is required',
            'bdate.required'=> 'Birthdate is required',
            'pob.required'=> 'Place of birth is required',
            'bloodtype.required'=> 'Bloodtype is required',
            'civilstatus.required'=> 'Civil status is required',
            'religion.required'=> 'Religion is required',
            'occupation.required'=> 'Occupation is required',
            'phone.required'=> 'Phone is required',
            'email.required'=> 'Email is required',
            'email.email'=> 'Must be valid email',
            'cstreet.required'=> 'Current address street is required',
            'ccity.required'=> 'Current address city is required',
            'cprovince.required'=> 'Current address province is required',
            'pstreet.required'=> 'Pernament address street is required',
            'pcity.required'=> 'Pernament address city is required',
            'pprovince.required'=> 'Pernament address province is required',
        ];
    }

    public function withValidator($validator) {
        $resident =Resident::where('user_id', '!=' , Auth::id())
            ->where('email', $this->email)
            ->get();

        $user = User::where('id', '!=' , Auth::id())
            ->where('email', $this->email)
            ->get();

        if(count($resident) > 0 || count($user) > 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('email', 'Email already exist.');
            });
        }
    }
}
