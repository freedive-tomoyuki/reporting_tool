<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class CheckerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'asp_id' => 'required',
            'loginid' => 'required',
            'password' => 'required',
        ];
    }
    public function attributes()
    {
        return [
            'asp_id' => 'ASP',
            'loginid' => 'ログインID',
            'password' => 'パスワード',
        ]; 
    }
}
