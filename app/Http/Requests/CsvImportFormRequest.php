<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class CsvImportFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'csv_file' => [
                'required',
                'max:40960', // php.iniのupload_max_filesizeとpost_max_sizeを考慮する必要があるので注意
                'file',
                'mimes:csv,txt', // mimesの都合上text/csvなのでtxtも許可が必要
                'mimetypes:text/plain',
            ],
           //'month' => 'required'
        ];
    }
}