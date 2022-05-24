<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'password' => 'required|min:8',
            'newPassword' => 'required|min:8',
            'repeatNewPassword' => 'required|same:newPassword|min:8'
        ];
    }

    public function messages() {
        return [
            'password.required' => 'Current password is required',
            'password.min' => 'Minimum length is 8 character',
            'newPassword.required' => 'New password is required',
            'newPassword.min' => 'Minimum length is 8 character',
            'repeatNewPassword.required' => 'Retype new password is required',
            'repeatNewPassword.same' => 'Retype password not match',
            'repeatNewPassword.min' => 'Minimum length is 8 character'
        ];
    }
}
