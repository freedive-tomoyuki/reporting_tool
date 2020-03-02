<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Http\Requests\CsvImportFormRequest;
use App\Product;
use App\Monthlysite;
use App\DailyDiff;
use App\Monthlydata;
use Illuminate\Http\Request;
//use App\Services\ImportCsvService;
use Illuminate\Support\Facades\Validator;
use Auth;
use DB;

class CsvImportController extends Controller
{
    protected $csv_service;

    // CSV配列
    protected $csv_array;

    // 戻り
    protected $return_top = '/admin/import';

	public $validateRules = [
					'date' 	=> 'required|date',
		            'imp' 	=> 'required|numeric',
		            'click' => 'required|numeric',
		            'cv' 	=> 'required|numeric',
		            'cvr' 	=> 'required|numeric',
		            'ctr' 	=> 'required|numeric',
		            'active' => 'required|numeric',
		            'partnership' => 'required|numeric',
		            'asp_id' => 'required|numeric',
		            'product_id' => 'required|numeric',
		            'price' => 'required|numeric',
		            'cost' 	=> 'required|numeric',
		            'cpa' 	=> 'required|numeric',
		            //'approval' 	=> 'required|numeric',
		            //'approval_price' 	=> 'required|numeric',
	    ];

	public $validateMessages = [
		        "date.required" => "年月の指定は必須項目です。",
		        "imp.required" => "インプレッション数の指定は必須項目です。",
		        "click.required" => "クリック数の指定は必須項目です。",
		        "cv.required" => "CV数は必須項目です。",
		        "active.required" => "アクティブ数の指定は必須項目です。",
		        "partnership.required" => "提携数の指定は必須項目です。",
		        "asp_id.required" => "ASPIDの指定は必須項目です。",
		        "product_id.required" => "案件IDの指定は必須項目です。",
		        "price.required" => "発生金額の指定は必須項目です。",
		        //"approval.required" => "承認件数の指定は必須項目です。",
		        //"approval_price.required" => "承認金額の指定は必須項目です。",
		        "integer" => "整数で入力してください。",
		        "imp.numeric" => "インプレッション数の指定は数値で入力してください。",
		        "click.numeric" => "クリック数の指定は数値で入力してください。",
		        "cv.numeric" => "CV数は数値で入力してください。",
		        "active.numeric" => "アクティブ数の指定は数値で入力してください。",
		        "partnership.numeric" => "提携数の指定は数値で入力してください。",
		        "asp_id.numeric" => "ASPIDの指定は数値で入力してください。",
		        "product_id.numeric" => "案件IDの指定は数値で入力してください。",
		        "price.numeric" => "発生金額の指定は数値で入力してください。",

		        "date" => "日付の形式で記載してください。"
		];

	public function  validateSiteRules($month = null){
		return [
					'date' 	=> [
						'required',
						'date',
						function($attribute, $value, $fail)use ($month) {
		                // 入力の取得
		                    if(date('Ym',strtotime($value)) != $month){
	                        	$fail('ご指定された年月と異なった日付が入っております');
	                        }
                        }
                    ],
		            'imp' 	=> 'required|numeric',
		            'click' => 'required|numeric',
		            'cv' 	=> 'required|numeric',
		            'cvr' 	=> 'required|numeric',
		            'ctr' 	=> 'required|numeric',
		            'site_name' => 'string',
		            'site_id' => 'string',
		            //'asp_id' => 'required|numeric',
		            'product_id' => 'required|numeric',
		            'price' => 'required|numeric',
		            'cost' 	=> 'required|numeric',
		            'cpa' 	=> 'required|numeric',
		            'approval' 	=> 'required|numeric',
		            'approval_price' 	=> 'required|numeric',
		            'approval_rate' 	=> 'required',
	    ];
	} 
	public $validateSiteMessages = [
		        "date.required" => "年月の指定は必須項目です。",
		        "imp.required" => "インプレッション数の指定は必須項目です。",
		        "click.required" => "クリック数の指定は必須項目です。",
		        "cv.required" => "CV数は必須項目です。",
		        "site_name.string" => "サイト名の形式が異なります。",
		        "site_id.string" => "サイトIDの形式が異なります。",
		        //"asp_id.required" => "アクティブ数の指定は必須項目です。",
		        "product_id.required" => "案件IDの指定は必須項目です。",
		        "price.required" => "発生金額の指定は必須項目です。",
		        "approval.required" => "承認件数の指定は必須項目です。",
		        "approval_price.required" => "承認金額の指定は必須項目です。",
		        
		        "imp.numeric" => "インプレッション数は数値で入力してください。",
		        "click.numeric" => "クリック数は数値で入力してください。",
		        "cv.numeric" => "CV数は数値で入力してください。",
		        "product_id.numeric" => "案件IDは数値で入力してください。",
		        "price.numeric" => "発生金額は数値で入力してください。",
		        "approval.numeric" => "承認件数は数値で入力してください。",
		        "approval_price.numeric" => "承認金額は数値で入力してください。",

		        "integer" => "整数で入力してください。",
		        //"numeric" => "数値で入力してください。",
		        "date" => "日付の形式で記載してください。"
		];

	public function index()
	{
		$user = Auth::user();
		$products = Product::all();
		return view('admin.csv',compact('products','user'));
	}
    public function cpa($cv ,$price,$asp){
      $calData = array();
      $price = intval($price);
	/*
	  A8の場合の算出
	*/
      if( $asp == 1 ){
        //$asp_fee = ($price * 1.2 * 1.1) * 1.1 ;
      	$asp_fee = ($price*1.1)+($price*1.1*0.3);//FDグロス
      	$total = $price * 1.1 * 1.2;
      }
	/*
	  それ以外のASPの場合の算出
	*/
      else{
        //$asp_fee = ($price * 1.3 * 1.1) ;
        $asp_fee = $price ;//グロス
        $total = ($price * 1.3);//FDグロス
      }
        //$total = $asp_fee * 1.2 ;

        $calData['cpa'] = round(($total == 0 || $cv == 0 )? 0 : $total / $cv);
        $calData['cost'] = $total;
        
      return json_encode($calData);
    }

    //日次＋月次　アップロード
	public function store_daily(CsvImportFormRequest $request)
	{
		$products = Product::all();
		
		

	    // setlocaleを設定
	    setlocale(LC_ALL, 'ja_JP.UTF-8');

	    // アップロードしたファイルを取得
	    // 'csv_file' はCSVファイルインポート画面の inputタグのname属性
	    $uploaded_file = $request->file('csv_file');

	    // アップロードしたファイルの絶対パスを取得
	    $file_path = $request->file('csv_file')->path($uploaded_file);

	    $file = new \SplFileObject($file_path);
	    $file->setFlags(
	    	\SplFileObject::READ_CSV|
	    	\SplFileObject::READ_AHEAD|
	    	\SplFileObject::SKIP_EMPTY|
	    	\SplFileObject::DROP_NEW_LINE
	    );

	    $array = [];
	    $array_monthly = [];
	    $row_count = 1;

	    foreach ($file as $row)
	    {
	    	if ($row === [null]) continue; 

	        // 1行目のヘッダーは取り込まない
	        if ($row_count > 1)
	        {
	        	//Log::debug($row);
	            $date 		= mb_convert_encoding($row[0], 'UTF-8', 'SJIS');
	            $product_id = mb_convert_encoding($row[1], 'UTF-8', 'SJIS');
	            $asp_id 	= mb_convert_encoding($row[2], 'UTF-8', 'SJIS');
	            $imp 		= preg_replace('/[^0-9]/','',mb_convert_encoding($row[3], 'UTF-8', 'SJIS'));
	            $click 		= preg_replace('/[^0-9]/','',mb_convert_encoding($row[4], 'UTF-8', 'SJIS'));
	            $cv 		= preg_replace('/[^0-9]/','',mb_convert_encoding($row[5], 'UTF-8', 'SJIS'));
	            $active 	= mb_convert_encoding($row[6], 'UTF-8', 'SJIS');
	            $partnership = mb_convert_encoding($row[7], 'UTF-8', 'SJIS');
	            $price 		= mb_convert_encoding($row[8], 'UTF-8', 'SJIS');
	            $approval 	= mb_convert_encoding($row[9], 'UTF-8', 'SJIS');
	            $approval_price = mb_convert_encoding($row[10], 'UTF-8', 'SJIS');

	            $calData = json_decode( json_encode( json_decode( $this->cpa( $cv ,$price ,$asp_id ) ) ), True );
                    
                $data[ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                $data[ 'cost' ] = $calData[ 'cost' ];
                \Log::debug($click);
                \Log::debug($imp);
                $ctr = (($click == false)||($imp == false))? 0 : ($click/$imp) * 100 ;
                $cvr = (($cv == false)||($click == false))? 0 : ($cv/$click) * 100 ;


	            $csvimport_monthly_array = [
		            'date' 	=> $date, 
		            'imp' 	=> $imp,
		            'click' => $click,
		            'cv' 	=> $cv,
		            'cvr' 	=> $ctr,
		            'ctr' 	=> $cvr,
		            'active' => $active,
		            'partnership' => $partnership,
		            'asp_id' => $asp_id,
		            'product_id' => $product_id,
		            'price' => $price,
		            'cost' 	=> $data['cost'],
		            'cpa' 	=> $data[ 'cpa' ],
		            'approval' 	=> $approval,
		            'approval_price' 	=> $approval_price,
	            ];
	            $csvimport_daily_array = [
		            'date' 	=> $date, 
		            'imp' 	=> $imp,
		            'click' => $click,
		            'cv' 	=> $cv,
		            'cvr' 	=> $ctr,
		            'ctr' 	=> $cvr,
		            'active' => $active,
		            'partnership' => $partnership,
		            'asp_id' => $asp_id,
		            'product_id' => $product_id,
		            'price' => $price,
		            'cost' 	=> $data['cost'],
		            'cpa' 	=> $data[ 'cpa' ],
	            ];

	            //　バリデーション処理
				$validator = Validator::make($csvimport_daily_array,
						        	$this->validateRules,
						            $this->validateMessages
						        );
		        
		        if ($validator->fails()) {
		           return redirect('admin/csv/import')->withErrors($validator)->withInput();
		        }

	            array_push($array, $csvimport_daily_array);
	            array_push($array_monthly, $csvimport_monthly_array);
	            
	        }
	    	$row_count++;
	    
	    }
		
		//配列宣言
	    	foreach ($array_monthly as $data) {
	        	$date_key = $data['date'];
	        	$product_key = $data['product_id'];
	        	$date_array = date('Ym', strtotime($date_key));

	        	$month_array[$date_array][$product_key] = array();
	
	        	$month_array[$date_array][$product_key]['imp'] = 0;
	        	$month_array[$date_array][$product_key]['click'] = 0;
	        	$month_array[$date_array][$product_key]['cv'] = 0;
	        	$month_array[$date_array][$product_key]['active'] = 0;
	        	$month_array[$date_array][$product_key]['partnership'] = 0;
	        	$month_array[$date_array][$product_key]['cost'] = 0;
	        	$month_array[$date_array][$product_key]['price'] = 0;
	        	$month_array[$date_array][$product_key]['approval'] = 0;
	        	$month_array[$date_array][$product_key]['approval_price'] = 0;
	        	$month_array[$date_array][$product_key]['date'] = '';
	    	}

	    	//配列をまるっとインポート(バルクインサート)
	        foreach ($array_monthly as $data) {
	        	//$end_of_date = 0;
	        	//$date_key = $data['date'];
	        	$date_key = date('Ym', strtotime($data['date']));

	        	$product_key = $data['product_id'];

				//日次（月末）
				if($month_array[$date_key][$product_key]['date'] == '' || strtotime($data['date']) > strtotime($month_array[$date_key][$product_key]['date']) ){
					$max_date_of_month = date('Y-m-d', strtotime($data['date']));
	        		$month_array[$date_key][$product_key]['date'] = $max_date_of_month;
				}
	        	//echo "product".$data['product_id']."<br>";
	        	//echo "asp".$data['asp_id']."<br>";
				
				//アクティブ数
					$month_array[$date_key][$product_key]['active'] = ($month_array[$date_key][$product_key]['active'] < $data['active'])? $data['active'] : $month_array[$date_key][$product_key]['active'];
				//提携数
					$month_array[$date_key][$product_key]['partnership'] = ($month_array[$date_key][$product_key]['partnership'] < $data['partnership'])? $data['partnership'] : $month_array[$date_key][$product_key]['partnership'];
				//ASP　ID
					$month_array[$date_key][$product_key]['asp_id'] = $data['asp_id'];
				//案件　ID
					$month_array[$date_key][$product_key]['product_id'] = $data['product_id'];

	        	
	        	//if($date_key == ){ //対象案件（同じASP） ｘ 同月
	        	//インプレッション
	        		$month_array[$date_key][$product_key]['imp'] += $data['imp'];
				//クリック
					$month_array[$date_key][$product_key]['click'] += $data['click'];
				//CV
					$month_array[$date_key][$product_key]['cv'] += $data['cv'];
				//管理画面表示の価格
	        		$month_array[$date_key][$product_key]['cost'] += $data['cost'];
				//グロス（ASPフィー込）
	        		$month_array[$date_key][$product_key]['price'] += $data['price'];
				//承認件数
					$month_array[$date_key][$product_key]['approval'] += $data['approval'];
				//承認金額
					$month_array[$date_key][$product_key]['approval_price'] += $data['approval_price'];

				//CVR
/*					$month_array[$date_key][$product_key]['cvr'] =
						($month_array[$date_key][$product_key]['click'] == 0 
							|| $month_array[$date_key][$product_key]['cv'] == 0 )? 0 :
								($month_array[$date_key][$product_key]['cv'] / $month_array[$date_key][$product_key]['click']) * 100 ;

				//CTR
					$month_array[$date_key][$product_key]['ctr'] =
						($month_array[$date_key][$product_key]['click'] == 0 
							|| $month_array[$date_key][$product_key]['imp'] == 0 )? 0 :
								($month_array[$date_key][$product_key]['click'] / $month_array[$date_key][$product_key]['imp']) * 100 ;

				//CPA
					$month_array[$date_key][$product_key]['cpa'] = 
						($month_array[$date_key][$product_key]['price'] == 0 
							|| $month_array[$date_key][$product_key]['cv'] == 0 )? 0 :
								$month_array[$date_key][$product_key]['price'] / $month_array[$date_key][$product_key]['cv'] ;
*/

					
			}

			//月次データ
			$push_cv = array();
			$push_approval = array();
			//var_dump($month_array);
			foreach($month_array as $a ){
				//Monthlydata::insert($array_1);

				foreach( $a as $d ){
				//CVR
					$d['cvr'] = ($d['click'] == 0 || $d['cv'] == 0 )? 0 : ($d['cv'] / $d['click']) * 100 ;

				//CTR
					$d['ctr'] =($d['click'] == 0 || $d['imp'] == 0 )? 0 : ($d['click'] / $d['imp']) * 100 ;

				//CPA
					$d['cpa'] = ($d['price'] == 0 || $d['cv'] == 0 )? 0 : $d['price'] / $d['cv'] ;
					//echo "<pre>";
					//echo $d['date'];
					array_push($push_cv			,$d['cv']);
					array_push($push_approval	,$d['approval']);
					
					//var_dump($push_cv);
					//var_dump($push_approval);
					
					if(count($push_cv) > 2) 		array_shift($push_cv);
					if(count($push_approval) > 2) 	array_shift($push_approval);
					
					$tree_month_cv = array_sum($push_cv);
					$tree_month_approval = array_sum($push_approval);

				//承認率
					//$d['approval_rate'] = ($d['cv'] == 0 || $d['approval'] == 0 )? 0 : ( $d['approval'] / $d['cv'] )* 100;
					$d['approval_rate'] = ($tree_month_cv == 0 || $tree_month_approval == 0 )? 0 : ( $tree_month_approval / $tree_month_cv )* 100;
					//echo "approval_rate=".$d['approval_rate'];
					//echo "</pre>";

					$date = DB::table('monthlydatas')
				    ->updateOrInsert(
				        ['product_id' => $d['product_id'] , 'date' => $d['date'],'asp_id' => $d['asp_id'] ],
				        ['imp' => $d['imp'],'click' => $d['click'],'cv' => $d['cv'],'active' => $d['active'],'partnership' => $d['cost'],'cost' => $d['cost'],'price' => $d['price'],'approval_price' => $d['approval_price'],'approval' => $d['approval'],'approval_rate' => $d['approval_rate'],'cvr' => $d['cvr'],'ctr' => $d['ctr'],'cpa' => $d['cpa'], 'created_at' =>  \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()]
				    );
			    }
			}


	    //追加した配列の数を数える
	    $array_count = count($array);
	    //もし配列の数が500未満なら
	    if ($array_count < 200){
	    	//var_dump($array);
			foreach( $array as $d ){
			//CVR
				$d['cvr'] = ($d['click'] == 0 || $d['cv'] == 0 )? 0 : ($d['cv'] / $d['click']) * 100 ;

			//CTR
				$d['ctr'] =($d['click'] == 0 || $d['imp'] == 0 )? 0 : ($d['click'] / $d['imp']) * 100 ;

			//CPA
				$d['cpa'] = ($d['price'] == 0 || $d['cv'] == 0 )? 0 : $d['price'] / $d['cv'] ;

				DB::table('daily_diffs')
				    ->updateOrInsert(
			        ['product_id' => $d['product_id'] , 'date' => $d['date'] ,'asp_id' => $d['asp_id']],
			        ['imp' => $d['imp'],'click' => $d['click'],'cv' => $d['cv'],'active' => $d['active'],'partnership' => $d['partnership'],'cost' => $d['cost'],'price' => $d['price'],'cvr' => $d['cvr'],'ctr' => $d['ctr'],'cpa' => $d['cpa'], 'created_at' =>  \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()]
				    );
			}

			//日次データ
			/*DailyDiff::insert(
	            $array
	        );
	        */
	    } else {
        
	        //追加した配列が500以上なら、array_chunkで500ずつ分割する
	        $array_partial = array_chunk($array, 200); //配列分割
	   
	        //分割した数を数えて
	        $array_partial_count = count($array_partial); //配列の数
	        $month_array = array();
	           
        	//分割した数の分だけインポートを繰り替えす
	        for ($i = 0; $i <= $array_partial_count - 1; $i++){
	            //CSVimport::insert($array_partial[$i]);
	            /*echo "<pre>";
	            var_dump($array_partial[$i]);
	            echo "</pre>";*/
				foreach( $array_partial[$i] as $d ){
	            /*echo "<pre>D";
	            var_dump($d);
	            echo "</pre>";*/
				//CVR
					$d['cvr'] = ($d['click'] == 0 || $d['cv'] == 0 )? 0 : ($d['cv'] / $d['click']) * 100 ;

				//CTR
					$d['ctr'] =($d['click'] == 0 || $d['imp'] == 0 )? 0 : ($d['click'] / $d['imp']) * 100 ;

				//CPA
					$d['cpa'] = ($d['price'] == 0 || $d['cv'] == 0 )? 0 : $d['price'] / $d['cv'] ;
					DB::table('daily_diffs')
					    ->updateOrInsert(
				        ['product_id' => $d['product_id'] , 'date' => $d['date'],'asp_id' => $d['asp_id'] ],
				        ['imp' => $d['imp'],'click' => $d['click'],'cv' => $d['cv'],'active' => $d['active'],'partnership' => $d['partnership'],'cost' => $d['cost'],'price' => $d['price'],'cvr' => $d['cvr'],'ctr' => $d['ctr'],'cpa' => $d['cpa'], 'created_at' =>  \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()]
					    );
				}
				//日毎の成果
				/*DailyDiff::insert(
	                $array_partial[$i]
	            );*/
			
	        }
	        
	    }

        return redirect('admin/csv/import', 303);
	    

	}
    //日次＋月次　アップロード
	public function store_month_site(CsvImportFormRequest $request)
	{
		$products = Product::all();

		//var_dump($request->month);
		$month = str_replace('-','',$request->month);

	    // setlocaleを設定
	    setlocale(LC_ALL, 'ja_JP.UTF-8');

	    // アップロードしたファイルを取得
	    // 'csv_file' はCSVファイルインポート画面の inputタグのname属性
	    $uploaded_file = $request->file('csv_file');

	    // アップロードしたファイルの絶対パスを取得
	    $file_path = $request->file('csv_file')->path($uploaded_file);

	    $file = new \SplFileObject($file_path);
	    $file->setFlags(
	    	\SplFileObject::READ_CSV|
	    	\SplFileObject::READ_AHEAD|
	    	\SplFileObject::SKIP_EMPTY|
	    	\SplFileObject::DROP_NEW_LINE
	    );

	    $array = [];
	    $array_monthly = [];
	    $row_count = 1;

	    foreach ($file as $row)
	    {
	    	if ($row === [null]) continue; 

	        // 1行目のヘッダーは取り込まない
	        if ($row_count > 1)
	        {
	        	//Log::debug($row);
	            $date 		= mb_convert_encoding($row[0], 'UTF-8', 'SJIS');
	            $product_id = mb_convert_encoding($row[1], 'UTF-8', 'SJIS');
	            $asp_id 	= mb_convert_encoding($row[2], 'UTF-8', 'SJIS');
	            $imp 		= mb_convert_encoding($row[3], 'UTF-8', 'SJIS');
	            $click 		= mb_convert_encoding($row[4], 'UTF-8', 'SJIS');
	            $cv 		= mb_convert_encoding($row[5], 'UTF-8', 'SJIS');
	            $media_id 	= mb_convert_encoding($row[6], 'UTF-8', 'SJIS');
	            $site_name 	= mb_convert_encoding($row[7], 'UTF-8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS');
	            $price 		= mb_convert_encoding($row[8], 'UTF-8', 'SJIS');
	            $approval 	= mb_convert_encoding($row[9], 'UTF-8', 'SJIS');
	            $approval_price = mb_convert_encoding($row[10], 'UTF-8', 'SJIS');

	            $imp = intval($imp);
	            $click = intval($click);
	            $cv = intval($cv);

	            $calData = json_decode( json_encode( json_decode( $this->cpa( $cv ,$price ,$asp_id ) ) ), True );
                    
                $data[ 'cpa' ]  = $calData[ 'cpa' ]; //CPA
                $data[ 'cost' ] = $calData[ 'cost' ];
				\Log::debug("run");
                $ctr = (($click == 0)||($imp == 0))? 0 : round(($click/$imp) * 100,2) ;
                $cvr = (($cv == 0)||($click == 0))? 0 : round(($cv/$click) * 100,2) ;
                $approval_rate =(($approval == 0)||($cv == 0))? 0 : round(($approval/$cv) * 100,2) ;

	            $csvimport_site_array = [
		            'date' 	=> $date, 
		            'imp' 	=> $imp,
		            'click' => $click,
		            'cv' 	=> $cv,
		            'cvr' 	=> $ctr,
		            'ctr' 	=> $cvr,
		            'media_id' => $media_id,
		            'site_name' => $site_name,
		            //'asp_id' => $asp_id,
		            'product_id' => $product_id,
		            'price' => $price,
		            'cost' 	=> $data['cost'],
		            'cpa' 	=> $data[ 'cpa' ],
		            'approval' 	=> $approval,
		            'approval_price' 	=> $approval_price,
		            'approval_rate' 	=> $approval_rate ,
	            ];

	            //　バリデーション処理
				$validator = Validator::make($csvimport_site_array,
						        	$this->validateSiteRules($month),
						            $this->validateSiteMessages
						        );
		        
		        if ($validator->fails()) {
		           return redirect('admin/csv/import')->withErrors($validator)->withInput();
		        }

	            array_push($array, $csvimport_site_array);
	            //array_push($array_monthly, $csvimport_monthly_array);
	            
	        }
	    	$row_count++;
	    
		}
		//return redirect('admin/csv/import', 303);
/*	    echo "test";
	    echo "<pre>";
	    var_dump($array);
	    echo "</pre>";*/
		\Log::debug("完了１");
		$monthlysites_table = $month.'_monthlysites';
	    //追加した配列の数を数える
	    $array_count = count($array);
	    //もし配列の数が500未満なら
	    if ($array_count < 500){
			//\Log::debug("完了2");
			foreach( $array as $d ){

				$d['cvr'] = ($d['click'] == 0 || $d['cv'] == 0 )? 0 : ($d['cv'] / $d['click']) * 100 ;

				//CTR
				$d['ctr'] =($d['click'] == 0 || $d['imp'] == 0 )? 0 : ($d['click'] / $d['imp']) * 100 ;

				//CPA
				$d['cpa'] = ($d['price'] == 0 || $d['cv'] == 0 )? 0 : $d['price'] / $d['cv'] ;

				//承認率
				$d['approval_rate'] = ($d['cv'] == 0 || $d['approval']  == 0 )? 0 : ( $d['approval'] / $d['cv'] )* 100;

				DB::table($monthlysites_table)
				    ->updateOrInsert(
			        ['product_id' => $d['product_id'] , 'date' => $d['date'],'media_id' => $d['media_id'] ],
			        ['imp' => $d['imp'],'click' => $d['click'],'cv' => $d['cv'],'approval' => $d['approval'],'approval_price' => $d['approval_price'],'approval_rate' => $d['approval_rate'],'site_name' => $d['site_name'],'cost' => $d['cost'],'price' => $d['price'],'cvr' => $d['cvr'],'ctr' => $d['ctr'],'cpa' => $d['cpa'], 'created_at' =>  \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()]
					);
			}
			
	    } else {
        
	        //追加した配列が500以上なら、array_chunkで500ずつ分割する
	        $array_partial = array_chunk($array, 500); //配列分割
	   
	        //分割した数を数えて
	        $array_partial_count = count($array_partial); //配列の数
	        $month_array = array();
			
        	//分割した数の分だけインポートを繰り替えす
	        for ($i = 0; $i <= $array_partial_count - 1; $i++){
	            //CSVimport::insert($array_partial[$i]);
				foreach( $array_partial[$i] as $d ){

					
					$d['cvr'] = ($d['click'] == 0 || $d['cv'] == 0 )? 0 : ($d['cv'] / $d['click']) * 100 ;

					//CTR
					$d['ctr'] =($d['click'] == 0 || $d['imp'] == 0 )? 0 : ($d['click'] / $d['imp']) * 100 ;

					//CPA
					$d['cpa'] = ($d['price'] == 0 || $d['cv'] == 0 )? 0 : $d['price'] / $d['cv'] ;

					//承認率
					$d['approval_rate'] = ($d['cv'] == 0 || $d['approval']  == 0 )? 0 : ( $d['approval'] / $d['cv'] )* 100;

					DB::table($monthlysites_table)
					    ->updateOrInsert(
				        ['product_id' => $d['product_id'] , 'date' => $d['date'],'media_id' => $d['media_id'] ],
				        ['imp' => $d['imp'],'click' => $d['click'],'cv' => $d['cv'],'approval' => $d['approval'],'approval_price' => $d['approval_price'],'approval_rate' => $d['approval_rate'],'site_name' => $d['site_name'],'cost' => $d['cost'],'price' => $d['price'],'cvr' => $d['cvr'],'ctr' => $d['ctr'],'cpa' => $d['cpa'], 'created_at' =>  \Carbon\Carbon::now(),'updated_at' => \Carbon\Carbon::now()]
						);
						
		        }
				
			}
			
			return redirect('admin/csv/import', 303);
		}

	}
}
