<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class DailySiteDiffRequest extends FormRequest
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
            'media_id.*' => 'required',
            'site_name.*' => 'required',
            'product.*' => 'required|numeric',
            'imp.*' => 'required|numeric',
            'ctr.*' => 'required|numeric',
            'click.*' => 'required|numeric',
            'cvr.*' => 'required|numeric',
            'cv.*' => 'required|numeric',
            'cost.*' => 'nullable|numeric',
            'price.*' => 'nullable|numeric',
            'approval.*' => 'nullable|numeric',
            'approval_price.*' => 'nullable|numeric',
            'approval_rate.*' => 'nullable|numeric',
        ];
    }
    public function attributes()
    {
        return [
            'date.*' => '対象日',
            'asp.*' => 'ASP',
            'site_name.*' => 'サイト名',
            'media_id.*' => 'メディアID',
            'product.*' => '案件',
            'imp.*' => 'インプレッション',
            'ctr.*' => 'CTR',
            'click.*' => 'クリック',
            'cvr.*' => 'CVR',
            'cv.*' => 'CV',
            'cost.*' => 'ASP単価',
            'price.*' => 'FD単価',
            'approval.*' => '承認件数',
            'approval_price.*' => '承認金額',
            'approval_rate.*' => '承認率',
            
        ];
    }  
}