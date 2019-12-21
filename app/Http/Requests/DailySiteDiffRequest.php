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
            'media_id.*' => 'required|numeric',
            'site_name.*' => 'required',
            'product.*' => 'required|numeric',
            'imp.*' => 'required|numeric',
            'ctr.*' => 'required|numeric',
            'click.*' => 'required|numeric',
            'cvr.*' => 'required|numeric',
            'cv.*' => 'required|numeric',
            'cost.*' => 'nullable|numeric',
            'price.*' => 'nullable|numeric',
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
            'price.*' => 'FD単価'
            
        ];
    }  
}