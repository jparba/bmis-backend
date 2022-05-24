<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
           'firstname' => 'required',
           'middlename' => 'required',
           'lastname' => 'required',
           'email' => 'required|email|unique:users,email,'.$this->route('id'),
           'phone' => 'required',
        ];
    }

    public function messages() {
        return [
            'firstname.required' => 'Firstname is required.',
            'middlename.required' => 'Middlename is required.',
            'lastname.required' => 'Lastname is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Must be valid e-mail.',
            'email.unique' => 'Email already exist.',
            'phone.required' => 'Phone No. is required.',
        ];
    }
}
