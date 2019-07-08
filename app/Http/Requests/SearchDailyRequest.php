<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class SearchDailyRequest extends FormRequest
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
                'searchdate_start' => 'required|date',
                'searchdate_end' => [
                    'required',
                    'date',
                    function($attribute, $value, $fail) {
                        // 入力の取得
                        $input_data = $this->all();
                        // 条件に合致しなかったら失敗にする
                        $s_date = strtotime($input_data['searchdate_start']);
                        $e_date = strtotime($input_data['searchdate_end']);
                        //$fail($this->time_diff($s_date,$e_date));
                        if ($this->time_diff($s_date,$e_date) > 365) {
                                $fail('1年以内で入力してください');
                        }
                        if($input_data['searchdate_start'] < '2018-07-01'||$input_data['searchdate_end'] < '2018-07-01'){
                                $fail('2018年7月1日以前のデータは参照できません');
                        }
                        if($input_data['searchdate_start'] > $input_data['searchdate_end'] ){
                                $fail('ご指定いただいた範囲のデータは取得できません');
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
            'searchdate_start.required' => '日付を入力してください',
            'searchdate_end.required' => '日付を入力してください',
            'product.required' => '案件を選択してください',
        ];
    }
    function time_diff($time_from, $time_to) 
    {
        // 日時差を秒数で取得
        $dif = $time_to - $time_from;
        // 時間単位の差
        $dif_time = date("H:i:s", $dif);
        // 日付単位の差
        $dif_days = (strtotime(date("Y-m-d", $dif)) - strtotime("1970-01-01")) / 86400;
        return "{$dif_days}";
    }
}