<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class StoreProduct extends FormRequest
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
            'name' => 'required|string',
            'asp_id' => 'required',
            'product' => 'required',
            'loginid' => 'required',
            'password' => 'required',
        ];
    }
    public function attributes()
    {
        return [
            'name' => '案件名',
            'asp_id' => 'ASP',
            'product' => '広告主名（案件名）',
            'loginid' => 'ログインID',
            'password' => 'パスワード',
        ]; 
    }
    /**
     * 定義済みバリデーションルールのエラーメッセージ取得
     *
     * @return array
     */
/*    public function messages()
    {
        return [

            'name.required' => '案件名が入力されていません。',
            'asp_id.required' => 'ASPが選択されておりません。',
            'product.required' => '案件名が選択されておりません。',
            'loginid.required' => 'ASPのログインIDが入力されておりません。',
            'password.required' => 'ASPのログインパスワードが入力されておりません。',
        ];
    }
    */
}
