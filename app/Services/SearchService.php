<?php
/**
 * CSVインポートクラス
 */
namespace App\Services;

class CalculationService
{

    /**
    　CPA算出用の関数
    */
    public function cpa($cv ,$price ,$asp){
      $calData = array();
      /*
        A8の場合の算出
      */
      if( $asp == 1 ){
        //$asp_fee = ($price * 1.2 * 1.1) * 1.1 ;
        $asp_fee = ($price*1.1)+($price*1.1*0.3);//FDグロス
        $total = $asp_fee * 1.1 * 1.2;
      }
      /*
        それ以外のASPの場合の算出
      */
      else{
        //$asp_fee = ($price * 1.3 * 1.1) ;
        $asp_fee = $price ;//グロス
        $total = $asp_fee * 1.3;//FDグロス
      }

      $calData['cpa'] = round(($total == 0 || $cv == 0 )? 0 : $total / $cv);
      $calData['cost'] = $total;

      return json_encode($calData);

    }




}