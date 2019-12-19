<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class DailyDiffRequest extends FormRequest
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
            'date.*' => 'required|date',
            'asp.*' => 'required|numeric',
            'product.*' => 'required|numeric',
            'imp.*' => 'required|numeric',
            'ctr.*' => 'required|numeric',
            'click.*' => 'required|numeric',
            'cvr.*' => 'required|numeric',
            'cv.*' => 'required|numeric',
            'active.*' => 'required|numeric',
            'partner.*' => 'required|numeric',
            'cost.*' => 'nullable|numeric',
            'price.*' => 'nullable|numeric',
        ];
    }
    public function attributes()
    {
        return [
            'date.*' => '対象日',
            'asp.*' => 'ASP',
            'product.*' => '案件',
            'imp.*' => 'インプレッション',
            'ctr.*' => 'CTR',
            'click.*' => 'クリック',
            'cvr.*' => 'CVR',
            'cv.*' => 'CV',
            'active.*' => 'アクティブ数',
            'partner.*' => '提携数',
            'cost.*' => 'ASP単価',
            'price.*' => 'FD単価'
            
        ];
    }  
}