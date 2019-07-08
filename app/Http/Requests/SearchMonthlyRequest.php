<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class SearchMonthlyRequest extends FormRequest
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
                'month' => [
                    'required',
                    function($attribute, $value, $fail) {
                        // 入力の取得
                        $input_data = $this->all();

                        if($input_data['month'] < '2018-07'){
                                $fail('2018年6月以前のデータは参照できません');
                        }
                    }
                ],
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
            'month.required' => '検索月を指定してください',
            'product.required' => '案件を選択してください',
        ];
    }

}