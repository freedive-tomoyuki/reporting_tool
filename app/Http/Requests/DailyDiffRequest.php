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
                'imp0' => 'required|numeric',
                'ctr0' => 'required|numeric',
                'click0' => 'required|numeric',
                'cvr0' => 'required|numeric',
                'cv0' => 'required|numeric',
                'active0' => 'required|numeric',
                'partner0' => 'required|numeric',
                'cost0' => 'required|numeric',
                'price0' => 'required|numeric',
        ];
    }

}