<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class SearchYearlyRequest extends FormRequest
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
                'product' => 'required|integer',
        ];
    }
    /**
     * 定義済みバリデーションルールのエラーメッセージ取得
     *
     * @return array
     */
    public function messages()
    {
        return [
            'product.required' => '企業を指定してください',

        ];
    }
}