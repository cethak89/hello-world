<?php namespace App\Http\Controllers;

use Request;
use App\Models\ErrorLog;
use Session;
use Carbon\Carbon;

class logEventController extends Controller
{
    /*
     * Metotlar için gerekli validasyon sağlanmadığı zaman kullanılan metottur.
     * Hangi alanın eksik olduğu log metodu çağrılarak loglanırken client tarafa sabit bir mesaj yollanır.
     */

    public static function splitNameSurname($nameSurname){
        try{
            $tempNameSurname = explode(" " , $nameSurname);
            $tempCount = count($tempNameSurname);
            $tempName = '';
            while($tempCount != 0){
                if( $tempCount == count($tempNameSurname) ){
                    $tempSurname = $tempNameSurname[$tempCount - 1];
                }
                else{
                    $tempName = $tempNameSurname[$tempCount - 1] . " " . $tempName;
                }
                $tempCount --;
            }
            if($tempName == ''){
                $tempName = $tempSurname;
                $tempSurname = "";
            }


            return [$tempName , $tempSurname];
        }catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'splitNameSurname',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            // something went wrong whilst attempting to encode the token
            return response()->json(["status" => -1, "description" => 400], 400);
        }

    }

    public function failResponse()
    {
        foreach (Session::all() as $key => $value) {
            if ($key == 'errors') {
                $tempVar = $value;
            }
        }
        $tempArray = (object)[];
        $tempArray->list = $tempVar->all();
        $tempArray->status = -2;

        logEventController::logErrorToDB('allValidation','validation',$tempVar->all()[0],'WS','');
        return response()->json(["status" => -1, "description" => 400], 400);
    }

    /*
     * Client tarafında meydana gelen hataların loglanması için kullanılan web servis
     * Loglama fonksiyonu çağrılarak clientın device bilgileri, hatanın gerçekleştiği url, browser bilgisi, hata mesajı ve kodu log tablosuna kaydedilir.
     */
    public function logClientError(){
        $clientIp = 'client - ' . Request::ip() ;
        //logEventController::logErrorToDB(Request::get('method_name') ,Request::get('error_code'),Request::get('error_message'), $clientIp ,Request::get('url'));
        return response()->json(["status" => 1, "description" => "Hata loglandı."], 200);
    }
    /*
     * Hataları ErrorLog Tablosuna loglamak için kullandığımız metot
     * Error log tablosundaki alanlar client ve server için ortak olarak kullanıldığından aynı alana farklı özellikte bilgiler kaydedilebilir.
     */
    public static function logErrorToDB($methodName, $error_code, $error_message, $type, $related_variable){
        ErrorLog::create([
            'method_name' => $methodName,
            'error_code' => $error_code,
            'error_message' => $error_message,
            'type' => $type,
            'related_variable' => $related_variable
        ]);
    }


    public static function modifyEndDate($date){
        $tempDate = new Carbon($date);
        return $tempDate->addDay(1);
    }
}