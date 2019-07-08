<?php
/**
 * CSVインポートクラス
 */
namespace App\Services\FileImport;

class ImportCsvService
{

    // パースしたCSVを入れる配列
    protected $csv_array = [];

    /**
     * CSV配列を返す
     */
    public function getCsvArray($request)
    {
        $this->uploadCsvToArray($request);
        return $this->csv_array;
    }

    /**
     * リクエストからCSVファイルをもらってパースして配列へ
     */
    private function uploadCsvToArray($request)
    {
        // リクエストより
        $file = $request->file('csv_file');

        // 読み込みます
        $csv = new \SplFileObject($file->getRealPath());
        $csv->setFlags(
            \SplFileObject::READ_CSV     |
            \SplFileObject::READ_AHEAD   |
            \SplFileObject::SKIP_EMPTY   |
            \SplFileObject::DROP_NEW_LINE
        );

        foreach($csv as $record) {
            // ループして $csv_array へ入れる
             $csv_array[]['name'] = $row[0];
             $csv_array[]['address'] = $row[1];
             $csv_array[]['tel'] = $row[2];
             
        }
    }

    /**
     * 以下、バリデーションの設定
     */
    public function validationRules()
    {
        return [
            'name'     => 'required|max:200',
            'address'  => 'max:500',
            'tel'      => 'max:20',
            
        ];
    }

    public function validationMessages()
    {
        return [

        ];
    }

    public function validationAttributes()
    {
        return [
            'name'     => '名前',
            'address'  => '住所',
            'tel'      => '電話番号',
            
        ];
    }

}