<?php namespace App\Http\Controllers;

use App\Http\Requests\beforeSaleRequest;
use App\Http\Requests\completeSaleRequest;
use App\Http\Requests\deleteContactListRequest;
use App\Http\Requests\saveDateBeforeSaleRequest;
use App\Http\Requests\testRequest;
use Carbon\Carbon;
use DB;
use Queue;
use Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\MarketingAct;
use App\Models\CustomerContact;
use App\Models\Sale;
use App\Models\Delivery;
use App\Models\Billing;
use App\Models\DeliveryLocation;
use App\Models\ErrorLog;
use App\Models\User;
use App\Models\UserGroup;
use Session;
use Authorizer;

class IsBankSalesController extends Controller
{
    //public  $site_url = 'https://bloomandfresh.com';
    public $site_url = 'http://188.166.86.116';
    public $backend_url = 'http://188.166.86.116:3000';
    //public $backend_url = 'https://everybloom.com';

    public static function isBankPos($sales_id, $pan, $expiredDate, $amount, $secure3d, $cvc, $extraProduct)
    {
        try {
            if (count(Sale::where('id', $sales_id)->where('payment_methods', 'OK')->get()) > 0)
                return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sales_id);
            $tempMoneyTaken = false;
            $tempType = '';
            $number = substr($pan, 0, 6);
            $numberFirstNumber = substr($pan, 0, 1);

            if( $numberFirstNumber == '4' ){
                $tempType = '100';
            }
            else if( $numberFirstNumber == '5' ){
                $tempType = '200';
            }
            else if( $numberFirstNumber == '3' ){
                $tempType = '300';
            }
            else{
                $tempType = '300';
            }

            /*if ($number == '450803' || $number == '454360' || $number == '454359' || $number == '454358' || $number == '418342' || $number == '418343' || $number == '401071' ||
                $number == '418344' || $number == '418345' || $number == '479610' || $number == '444676' || $number == '444677' || $number == '444678' || $number == '454314' ||
                $number == '469884' || $number == '404591' || $number == '483602')
            {
                $tempType = '100';
            } else if ( $number == '540667' || $number == '540668' || $number == '543771' || $number == '552096' || $number == '510152' || $number == '548237' || $number == '534981' ||
                $number == '542374' || $number == '589283' || $number == '530905' || $number == '523529' || $number == '553058' || $number == '535514' || $number == '547287')
            {
                $tempType = '200';
            }*/
            if ($secure3d) {
                $client = new \GuzzleHttp\Client();
                $randomEnrollment = str_random(10);
                $response = $client->post('https://mpi.vpos.isbank.com.tr/Enrollment.aspx', [
                    //$response = $client->post('http://sanalpos.innova.com.tr/ISBANK/MpiWeb/Enrollment.aspx', [
                    'body' => [
                        'MerchantId' => '661414755',
                        'MerchantPassword' => 's8I0bBvhlB',
                        'VerifyEnrollmentRequestId' => $sales_id . '-' . $randomEnrollment,
                        'Pan' => $pan,
                        'ExpiryDate' => $expiredDate,
                        'PurchaseAmount' => $amount,
                        'Currency' => '949',
                        'BrandName' => $tempType,
                        'SessionInfo' => $sales_id . '_' . $cvc,
                        'SuccessUrl' => 'https://everybloom.com/is-bank-return-url',
                        'FailureUrl' => 'https://everybloom.com/is-bank-return-fail'
                    ]
                ])->xml();

                if (count(Sale::where('id', $sales_id)->where('payment_methods', 'OK')->get()) > 0)
                    return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sales_id);

                if ($response->VERes->Status == 'Y') {
                    $data = [
                        'ACSUrl' => $response->VERes->ACSUrl,
                        'PAReq' => $response->VERes->PAReq,
                        'TermUrl' => $response->VERes->TermUrl,
                        'MD' => $response->VERes->MD
                    ];
                    DB::table('is_bank_log')->insert([
                        'sale_id' => $sales_id,
                        'transaction_id' => $sales_id . '-' . $randomEnrollment,
                        'code' => '0000',
                        'error_message' => 'Banka 3D Secure Page bulundu ve yönlendiriliyor.',
                        'log_location' => 'Routing 3D Secure Page'
                    ]);
                    Sale::where('id', $sales_id)->update([
                        'payment_methods' => "Banka sayfasında.",
                        'created_at' => Carbon::now()
                    ]);
                    DB::commit();
                    return view('before3DIsBank', compact('data'));
                } else {
                    if ($response->VERes->Status == 'N') {
                        DB::table('is_bank_log')->insert([
                            'sale_id' => $sales_id,
                            'transaction_id' => $sales_id . '-' . $randomEnrollment,
                            'code' => 'NNNN',
                            'error_message' => 'Kart 3D hizmete kayıtlı değil!',
                            'log_location' => 'Routing 3D Secure Page'
                        ]);
                    } else {
                        DB::table('is_bank_log')->insert([
                            'sale_id' => $sales_id,
                            'transaction_id' => $sales_id . '-' . $randomEnrollment,
                            'code' => $response->VERes->Status,
                            'error_message' => $response->ResultDetail->ErrorMessage,
                            'log_location' => 'Routing 3D Secure Page'
                        ]);
                    }
                    Sale::where('id', $sales_id)->update([
                        'payment_methods' => 418
                    ]);

                    DB::table('sales')->where('id', $sales_id )->update([
                        'IsTroyCard' => 0
                    ]);

                    $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->get();
                    if (count($couponData) != 0) {
                        MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                            'used' => '0',
                            'valid' => '1'
                        ]);
                        DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->delete();
                    }
                    DB::commit();
                    $tempLangId = Sale::where('id', $sales_id)->select('lang_id')->get()[0]->lang_id;
                    if ($tempLangId == 'en') {
                        return redirect()->away('http://188.166.86.116/order-flowers/payment?orderId=' . $sales_id);
                    } else {
                        return redirect()->away('http://188.166.86.116/satin-alma/odeme-bilgileri?orderId=' . $sales_id);
                    }
                }
            } else {
                $randomEnrollment = str_random(10);
                $transactionId = $sales_id . '-' . $randomEnrollment;
                if( ($amount % 100) < 10){
                    $tempAmount = '0' . (string)($amount % 100);
                }
                else{
                    $tempAmount = (string)($amount % 100);
                }
                $tempAmount = (int)($amount / 100) . '.' . $tempAmount;
                $tempExpiredDate = '20' . $expiredDate;
                $xml_data = 'prmstr=<?xml version="1.0" encoding="utf-8"?>
            <VposRequest>
                <MerchantId>661414755</MerchantId>
                <Password>s8I0bBvhlB</Password>
                <BankId>1</BankId>
                <TransactionType>Sale</TransactionType>
                <TransactionId>' . $transactionId . '</TransactionId>
                <CurrencyAmount>' . $tempAmount . '</CurrencyAmount>
                <CurrencyCode>949</CurrencyCode>
                <Pan>' . $pan . '</Pan>
                <Cvv>' . $cvc . '</Cvv>
                <Expiry>' . $tempExpiredDate . '</Expiry>
                <OrderId>' . $sales_id . '</OrderId>
            </VposRequest>';
                DB::table('is_bank_log')->insert([
                    'sale_id' => $sales_id,
                    'transaction_id' => $transactionId,
                    'code' => '0000',
                    'error_message' => '',
                    'log_location' => 'Before Without 3D Secure'
                ]);
                //$URL = "http://sanalpos.innova.com.tr/ISBANK/VposWeb/v3/Vposreq.aspx";
                $URL = "https://trx.vpos.isbank.com.tr/v3/Vposreq.aspx";

                $ch = curl_init($URL);
                curl_setopt($ch, CURLOPT_URL, $URL);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 59);
                $output = curl_exec($ch);
                $xml_response = new \SimpleXMLElement($output);
                curl_close($ch);
                //dd($xml_response);
                if ($xml_response->ResultCode == '0000') {

                    $tempMoneyTaken = true;

                    DB::table('sales')->where('id', $sales_id )->update([
                        'paymentAmount' => $amount
                    ]);

                    DB::table('is_bank_log')->insert([
                        'sale_id' => $sales_id,
                        'transaction_id' => $transactionId,
                        'code' => '0000',
                        'error_message' => '',
                        'log_location' => 'Sale Success Without 3D'
                    ]);
                    DB::table('sales')->where('id', $sales_id)->update([
                        'created_at' => Carbon::now()
                    ]);
                    $mailData = DB::table('sales')
                        ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                        ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                        ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                        ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                        ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                        ->where('sales.id', '=', $sales_id)
                        ->select('delivery_locations.district','delivery_locations.city_id', 'sales.id', 'sales.sum_total', 'customer_contacts.surname as contact_surname', 'customer_contacts.name as contact_name', 'deliveries.wanted_delivery_limit',
                            'deliveries.created_at', 'deliveries.wanted_delivery_date', 'deliveries.products', 'sales.receiver_address as address', 'sales_products.products_id', 'sales.paymentAmount', 'IsTroyCard',
                            'sales.sender_name as name', 'sales.sender_surname as surname', 'sales.sender_mobile as mobile')
                        ->get()[0];

                    if( $mailData->city_id == 2 ){
                        $tempCityMail = '06-Ankara';
                    }
                    else if( $mailData->city_id == 1 ){
                        $tempCityMail = '34-İstanbul';
                    }
                    else{
                        $tempCityMail = 'KARGO';
                    }

                    $created = new Carbon($mailData->wanted_delivery_limit);

                    setlocale(LC_TIME, "");
                    setlocale(LC_ALL, 'tr_TR.utf8');
                    $mailDate = new Carbon($mailData->wanted_delivery_limit);
                    $mailDate = $mailDate->formatLocalized('%A %d %B');

                    $extraProductName = "";
                    if($extraProduct > 0){
                        $extraProduct = DB::table('cross_sell_products')->where('id', $extraProduct)->get();
                        if(count($extraProduct) > 0){
                            $extraProductName = $extraProduct[0]->name;
                        }
                    }

                    if( $mailData->IsTroyCard ){
                        $mailData->paymentAmount = floatval($mailData->paymentAmount)/100;

                        parse_str($mailData->paymentAmount);
                        $mailData->sum_total = str_replace('.', ',', $mailData->paymentAmount);
                    }

                    /*$mailData->paymentAmount = floatval($mailData->paymentAmount)/100;

                    parse_str($mailData->paymentAmount);
                    $mailData->paymentAmount = str_replace('.', ',', $mailData->paymentAmount);*/

                    \MandrillMail::messages()->sendTemplate('siparisuyari', null, array(
                        'html' => '<p>Example HTML content</p>',
                        'text' => 'Sipariş verildi.',
                        'subject' => 'Sipariş - ' . $tempCityMail . ' ' . $mailDate,
                        'from_email' => 'siparis@bloomandfresh.com',
                        //'from_email' => 'teknik@bloomandfresh.com',
                        'from_name' => 'Bloom And Fresh',
                        'to' => array(
                            array(
                                'email' => 'siparis@bloomandfresh.com',
                                //'email' => 'teknik@bloomandfresh.com',
                                'type' => 'to'
                            )
                        ),
                        'merge' => true,
                        'merge_language' => 'mailchimp',
                        'global_merge_vars' => array(
                            array(
                                'name' => 'FNAME',
                                'content' => $mailData->name,
                            ), array(
                                'name' => 'SALEID',
                                'content' => $mailData->id,
                            ), array(
                                'name' => 'CNTCNAME',
                                'content' => $mailData->contact_name,
                            ), array(
                                'name' => 'CNTCLNAME',
                                'content' => $mailData->contact_surname,
                            ), array(
                                'name' => 'CNTTEL',
                                'content' => $mailData->mobile,
                            ), array(
                                'name' => 'CNTADD',
                                'content' => $mailData->address
                            ), array(
                                'name' => 'WANTEDDATE',
                                'content' => $mailData->wanted_delivery_date . " - " . $created->hour . ':' . '00' . ':' . "00"
                            ), array(
                                'name' => 'PRICE',
                                'content' => $mailData->sum_total
                            ), array(
                                'name' => 'DISTRICT',
                                'content' => $mailData->district
                            ), array(
                                'name' => 'PRNAME',
                                'content' => $mailData->products
                            ), array(
                                'name' => 'ORDERDATE',
                                'content' => $mailData->created_at
                            ), array(
                                'name' => 'LNAME',
                                'content' => $mailData->surname
                            ), array(
                                'name' => 'EXTRA',
                                'content' => $extraProductName
                            )
                        )
                    ));

                    Sale::where('id', $sales_id)->update([
                        'payment_methods' => 'OK'
                    ]);

                    $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->get();
                    if (count($couponData) != 0) {
                        $tempCoupon = MarketingAct::where('id', $couponData[0]->marketing_acts_id)->get()[0];
                        if ($tempCoupon->long_term) {

                        } else {
                            MarketingAct::where('id', $tempCoupon->id)->update([
                                'used' => '1',
                                'valid' => '0'
                            ]);

                            if( $tempCoupon->type == 1 ){
                                DB::table('sales')->where('id', $sales_id)->update([
                                    'payment_type' => 'COUPON'
                                ]);
                            }
                        }
                    }

                    generateDataController::callSetProductFromSaleId($sales_id);

                    DB::commit();
                    $tempLangId = Sale::where('id', $sales_id)->select('lang_id')->get()[0]->lang_id;
                    if ($tempLangId == 'en') {
                        return redirect()->away('http://188.166.86.116/order-details?orderId=' . $sales_id);
                    } else {
                        return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sales_id);
                    }
                } else {

                    if( $xml_response->ResultCode == '1061' ){
                        return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sales_id);
                    }

                    DB::table('sales')->where('id', $sales_id )->update([
                        'IsTroyCard' => 0
                    ]);

                    DB::table('is_bank_log')->insert([
                        'sale_id' => $sales_id,
                        'transaction_id' => $transactionId,
                        'code' => $xml_response->ResultCode,
                        'error_message' => $xml_response->ResultDetail,
                        'log_location' => 'Sale Fail Without 3D'
                    ]);
                    Sale::where('id', $sales_id)->update([
                        'payment_methods' => 418
                    ]);
                    $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->get();
                    if (count($couponData) != 0) {
                        MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                            'used' => '0',
                            'valid' => '1'
                        ]);
                        DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->delete();
                    }
                    DB::commit();
                    $tempLangId = Sale::where('id', $sales_id)->select('lang_id')->get()[0]->lang_id;
                    if ($tempLangId == 'en') {
                        return redirect()->away('http://188.166.86.116/order-flowers/payment?orderId=' . $sales_id);
                    } else {
                        return redirect()->away('http://188.166.86.116/satin-alma/odeme-bilgileri?orderId=' . $sales_id);
                    }
                }
            }
        } catch (\Exception $e) {
            DB::rollback();

            if ($tempMoneyTaken) {
                Sale::where('id', $sales_id)->update([
                    'payment_methods' => 'OK'
                ]);
                return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sales_id);
            }

            $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->get();
            if (count($couponData) != 0) {
                MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                    'used' => '0',
                    'valid' => '1'
                ]);
                DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->delete();
            }
            DB::table('is_bank_log')->insert([
                'sale_id' => $sales_id,
                'transaction_id' => 'Exception',
                'code' => 'Exception',
                'error_message' => $e->getMessage(),
                'log_location' => 'Sale Fail Before 3D Secure Or Before Sale Without 3D'
            ]);


            DB::table('sales')->where('id', $sales_id)->update([
                'IsTroyCard' => 0
            ]);

            if (count(Sale::where('id', $sales_id)->where('payment_methods', 'OK')->get()) == 0)
                Sale::where('id', $sales_id)->update([
                    'payment_methods' => 418
                ]);

            return redirect()->away('http://188.166.86.116/satin-alma/odeme-bilgileri?orderId=' . $sales_id);
        }
    }

    public static function checkMaximum($pan)
    {
        $tempType = '';
        //$expiredDate = '20' . $expiredDate;
        $number = substr($pan, 0, 6);

        $activePos = DB::table('pos_type')->where('active', 1)->get();

        if( count($activePos) > 0 ){
            if( $activePos[0]->id == 3 ){
                return false;
            }
            else if( $activePos[0]->id == 2 ){
                return true;
            }
            else if( $activePos[0]->id == 4 ){
                if ($number == '450803' || $number == '454360' || $number == '454359' || $number == '454358' || $number == '418342' || $number == '418343' || $number == '418344' ||
                    $number == '401071' || $number == '418345' || $number == '444676' || $number == '469884' || $number == '404591' ||$number == '479610' ||  $number == '444677' ||
                    $number == '444678' || $number == '454314' || $number == '483602'
                )
                {
                    $tempType = '100';
                }
                else if ($number == '540667' || $number == '540668' || $number == '543771' || $number == '552096' || $number == '548237' || $number == '523529' || $number == '547287'
                        || $number == '510152' || $number == '589283' || $number == '534981' || $number == '542374' || $number == '553058' || $number == '535514' || $number == '530905'
                )
                {
                    $tempType = '200';
                }

                if ($tempType == '') {
                    return false;
                } else {
                    return true;
                }
            }
        }

        if ($number == 374421 || $number == 374422 || $number == 374424 || $number == 374425 || $number == 374426 || $number == 374427 || $number == 375622 || $number == 375623 || $number == 375624 ||
            $number == 375625 || $number == 375626 || $number == 375627 || $number == 375628 || $number == 375629 || $number == 375631 || $number == 377137 || $number == 379369 || $number == 379370 ||
            $number == 379371 || $number == 401738 || $number == 403280 || $number == 403666 || $number == 404308 || $number == 405051 || $number == 405090 || $number == 409219 || $number == 410141 ||
            $number == 413836 || $number == 420556 || $number == 420557 || $number == 426886 || $number == 426887 || $number == 426888 || $number == 426889 || $number == 427314 || $number == 427315 ||
            $number == 428220 || $number == 428221 || $number == 432154 || $number == 448472 || $number == 461668 || $number == 462274 || $number == 467293 || $number == 467294 || $number == 467295 ||
            $number == 474151 || $number == 479660 || $number == 479661 || $number == 479662 || $number == 479682 || $number == 482489 || $number == 482490 || $number == 482491 || $number == 487074 ||
            $number == 487075 || $number == 489455 || $number == 489478 || $number == 490175 || $number == 492186 || $number == 492187 || $number == 492193 || $number == 493845 || $number == 514915 ||
            $number == 516943 || $number == 516961 || $number == 517040 || $number == 517041 || $number == 517042 || $number == 517048 || $number == 517049 || $number == 520097 || $number == 520922 ||
            $number == 520940 || $number == 520988 || $number == 521824 || $number == 521825 || $number == 522204 || $number == 524659 || $number == 526955 || $number == 528939 || $number == 528956 ||
            $number == 533169 || $number == 534261 || $number == 535429 || $number == 535488 || $number == 540036 || $number == 540037 || $number == 540118 || $number == 540669 || $number == 540709 ||
            $number == 541865 || $number == 542030 || $number == 543738 || $number == 544078 || $number == 544294 || $number == 548935 || $number == 553130 || $number == 554253 || $number == 554254 ||
            $number == 554960 || $number == 557023 || $number == 558699 || $number == 589318 || $number == 622403 || $number == 670606 || $number == 676255 || $number == 676283 || $number == 676651 ||
            $number == 676827 || $number == 979236 )
        {
            $tempType = '';
        }
        else{
            $numberFirstNumber = substr($pan, 0, 1);

            if( $numberFirstNumber == '4' ){
                $tempType = '100';
            }
            else if( $numberFirstNumber == '5' ){
                $tempType = '200';
            }
            else if( $numberFirstNumber == '3' ){
                $tempType = '300';
            }
        }

        /*if ($number == '450803' || $number == '454360' || $number == '454359' || $number == '454358' || $number == '418342' || $number == '418343' || $number == '418344' ||
            $number == '401071' || $number == '418345' || $number == '444676' || $number == '469884' || $number == '404591' ||$number == '479610' ||  $number == '444677' ||
            $number == '444678' || $number == '454314' || $number == '483602'
        ) {
            $tempType = '100';
        } else if ($number == '540667' || $number == '540668' || $number == '543771' || $number == '552096' || $number == '548237' || $number == '523529' || $number == '547287'
                || $number == '510152' || $number == '589283' || $number == '534981' || $number == '542374' || $number == '553058' || $number == '535514' || $number == '530905'
        ) {
            $tempType = '200';
        }*/
        //return $number;
        if ($tempType == '') {
            return false;
        } else {
            return true;
        }
    }

    public function isBank3DSuccess()
    {
        //dd(Request::all());
        DB::beginTransaction();
        try {
            $tempSessionInfo = Request::input('SessionInfo');
            $sale_id = explode("_", $tempSessionInfo)[0];

            if (count(Sale::where('id', $sale_id)->where('payment_methods', 'OK')->get()) > 0)
                return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sale_id);

            $randomEnrollment = str_random(10);
            $transactionId = $sale_id . '_' . $randomEnrollment;

            if( (Request::input('PurchAmount') % 100) < 10){
                $tempAmount = '0' . (string)(Request::input('PurchAmount') % 100);
            }
            else{
                $tempAmount = (string)(Request::input('PurchAmount') % 100);
            }
            $tempAmount = (int)(Request::input('PurchAmount') / 100) . '.' . $tempAmount;

            //$tempAmount = (int)(Request::input('PurchAmount') / 100) . '.' . (string)(Request::input('PurchAmount') % 100);
            $tempExpiredDate = '20' . Request::input('Expiry');


            if (Request::input('Status') == 'N') {
                DB::table('is_bank_log')->insert([
                    'sale_id' => $sale_id,
                    'transaction_id' => Request::input('VerifyEnrollmentRequestId'),
                    'code' => 'NNNN',
                    'error_message' => 'Doğrulama başarısız!',
                    'log_location' => 'After 3D Check Fail'
                ]);
                Sale::where('id', $sale_id)->update([
                    'payment_methods' => 418
                ]);

                DB::table('sales')->where('id', $sale_id )->update([
                    'IsTroyCard' => 0
                ]);

                $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sale_id)->get();
                if (count($couponData) != 0) {
                    MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                        'used' => '0',
                        'valid' => '1'
                    ]);
                    DB::table('marketing_acts_sales')->where('sales_id', $sale_id)->delete();
                }
                DB::commit();
                $tempLangId = Sale::where('id', $sale_id)->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away('http://188.166.86.116/order-flowers/payment?orderId=' . $sale_id);
                } else {
                    return redirect()->away('http://188.166.86.116/satin-alma/odeme-bilgileri?orderId=' . $sale_id);
                }
            }

            $xml_data = 'prmstr=<?xml version="1.0" encoding="utf-8"?>
            <VposRequest>
                <MerchantId>661414755</MerchantId>
                <Password>s8I0bBvhlB</Password>
                <BankId>1</BankId>
                <TransactionType>Sale</TransactionType>
                <TransactionId>' . $transactionId . '</TransactionId>
                <CurrencyAmount>' . $tempAmount . '</CurrencyAmount>
                <CurrencyCode>949</CurrencyCode>
                <Pan>' . Request::input('Pan') . '</Pan>
                <Cvv>' . explode("_", $tempSessionInfo)[1] . '</Cvv>
                <Expiry>' . $tempExpiredDate . '</Expiry>
                <OrderId>' . $sale_id . '</OrderId>
                <Eci>' . Request::input('Eci') . '</Eci>
                <Cavv>' . Request::input('Cavv') . '</Cavv>
                <Xid>' . Request::input('Xid') . '</Xid>
            </VposRequest>';
            DB::table('is_bank_log')->insert([
                'sale_id' => $sale_id,
                'transaction_id' => $transactionId,
                'code' => '0000',
                'error_message' => '',
                'log_location' => 'Before payment after 3D success'
            ]);
            //$URL = "http://sanalpos.innova.com.tr/ISBANK/VposWeb/v3/Vposreq.aspx";
            $URL =  "https://trx.vpos.isbank.com.tr/v3/Vposreq.aspx";
            $ch = curl_init($URL);
            curl_setopt($ch, CURLOPT_URL, $URL);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 59);
            $output = curl_exec($ch);
            $xml_response = new \SimpleXMLElement($output);
            curl_close($ch);
            if ($xml_response->ResultCode == '0000') {
                //dd($xml_response);
                $tempMoneyTaken = true;
                $sales_id = explode("_", $xml_response->TransactionId)[0];
                DB::table('is_bank_log')->insert([
                    'sale_id' => $sales_id,
                    'transaction_id' => $xml_response->TransactionId,
                    'code' => '0000',
                    'error_message' => '',
                    'log_location' => 'Sale Success With 3D Secure'
                ]);
                DB::table('sales')->where('id', $sales_id)->update([
                    'created_at' => Carbon::now()
                ]);
                $mailData = DB::table('sales')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.id', '=', $sales_id)
                    ->select('delivery_locations.district','delivery_locations.city_id', 'sales.id', 'sales.sum_total', 'customer_contacts.surname as contact_surname', 'customer_contacts.name as contact_name', 'deliveries.wanted_delivery_limit',
                        'deliveries.created_at', 'deliveries.wanted_delivery_date', 'deliveries.products', 'sales.receiver_address as address', 'sales_products.products_id',
                        'sales.sender_name as name', 'sales.sender_surname as surname', 'sales.sender_mobile as mobile', 'sales.paymentAmount', 'IsTroyCard')
                    ->get()[0];

                if( $mailData->IsTroyCard ){
                    $mailData->paymentAmount = floatval($mailData->paymentAmount)/100;

                    parse_str($mailData->paymentAmount);
                    $mailData->sum_total = str_replace('.', ',', $mailData->paymentAmount);
                }

                if( $mailData->city_id == 2 ){
                    $tempCityMail = '06-Ankara';
                }
                else if( $mailData->city_id == 1 ){
                    $tempCityMail = '34-İstanbul';
                }
                else{
                    $tempCityMail = 'KARGO';
                }

                $created = new Carbon($mailData->wanted_delivery_limit);

                setlocale(LC_TIME, "");
                setlocale(LC_ALL, 'tr_TR.utf8');
                $mailDate = new Carbon($mailData->wanted_delivery_limit);
                $mailDate = $mailDate->formatLocalized('%A %d %B');

                $tempCikolat = AdminPanelController::getCikolatData($sales_id);
                $extraProductName = "";
                if($tempCikolat){
                    $extraProductName = $tempCikolat->name;
                }

                \MandrillMail::messages()->sendTemplate('siparisuyari', null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => 'Sipariş verildi.',
                    'subject' => 'Sipariş - ' . $tempCityMail . ' ' . $mailDate,
                    'from_email' => 'siparis@bloomandfresh.com',
                    //'from_email' => 'teknik@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => 'siparis@bloomandfresh.com',
                            //'email' => 'teknik@bloomandfresh.com',
                            'type' => 'to'
                        )
                    ),
                    'merge' => true,
                    'merge_language' => 'mailchimp',
                    'global_merge_vars' => array(
                        array(
                            'name' => 'FNAME',
                            'content' => $mailData->name,
                        ), array(
                            'name' => 'SALEID',
                            'content' => $mailData->id,
                        ), array(
                            'name' => 'CNTCNAME',
                            'content' => $mailData->contact_name,
                        ), array(
                            'name' => 'CNTCLNAME',
                            'content' => $mailData->contact_surname,
                        ), array(
                            'name' => 'CNTTEL',
                            'content' => $mailData->mobile,
                        ), array(
                            'name' => 'CNTADD',
                            'content' => $mailData->address
                        ), array(
                            'name' => 'WANTEDDATE',
                            'content' => $mailData->wanted_delivery_date . " - " . $created->hour . ':' . '00' . ':' . "00"
                        ), array(
                            'name' => 'PRICE',
                            'content' => $mailData->sum_total
                        ), array(
                            'name' => 'DISTRICT',
                            'content' => $mailData->district
                        ), array(
                            'name' => 'PRNAME',
                            'content' => $mailData->products
                        ), array(
                            'name' => 'ORDERDATE',
                            'content' => $mailData->created_at
                        ), array(
                            'name' => 'LNAME',
                            'content' => $mailData->surname
                        ), array(
                            'name' => 'EXTRA',
                            'content' => $extraProductName
                        )
                    )
                ));

                Sale::where('id', $sales_id)->update([
                    'payment_methods' => 'OK'
                ]);

                $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->get();
                if (count($couponData) != 0) {
                    $tempCoupon = MarketingAct::where('id', $couponData[0]->marketing_acts_id)->get()[0];
                    if ($tempCoupon->long_term) {

                    } else {
                        MarketingAct::where('id', $tempCoupon->id)->update([
                            'used' => '1',
                            'valid' => '0'
                        ]);

                        if( $tempCoupon->type == 1 ){
                            DB::table('sales')->where('id', $sales_id)->update([
                                'payment_type' => 'COUPON'
                            ]);
                        }
                    }
                }

                generateDataController::callSetProductFromSaleId($sales_id);

                DB::commit();
                $tempLangId = Sale::where('id', $sales_id)->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away('http://188.166.86.116/order-details?orderId=' . $sales_id);
                } else {
                    return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sales_id);
                }
            } else {
                $sales_id = explode("_", $xml_response->TransactionId)[0];
                DB::table('is_bank_log')->insert([
                    'sale_id' => $sales_id,
                    'transaction_id' => $xml_response->TransactionId,
                    'code' => $xml_response->ResultCode,
                    'error_message' => $xml_response->ResultDetail,
                    'log_location' => 'Sale Fail After 3D Secure Check'
                ]);
                Sale::where('id', $sales_id)->update([
                    'payment_methods' => 418
                ]);

                DB::table('sales')->where('id', $sales_id )->update([
                    'IsTroyCard' => 0
                ]);

                $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->get();
                if (count($couponData) != 0) {
                    MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                        'used' => '0',
                        'valid' => '1'
                    ]);
                    DB::table('marketing_acts_sales')->where('sales_id', $sales_id)->delete();
                }
                DB::commit();

                return redirect()->away('http://188.166.86.116/satin-alma/odeme-bilgileri?orderId=' . $sales_id);

            }
        }
        catch (\Exception $e) {
            DB::rollback();

            if ($tempMoneyTaken) {
                Sale::where('id', $sale_id)->update([
                    'payment_methods' => 'OK'
                ]);
                return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sale_id);
            }

            $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sale_id)->get();
            if (count($couponData) != 0) {
                MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                    'used' => '0',
                    'valid' => '1'
                ]);
                DB::table('marketing_acts_sales')->where('sales_id', $sale_id)->delete();
            }
            DB::table('is_bank_log')->insert([
                'sale_id' => $sale_id,
                'transaction_id' => 'Exception',
                'code' => 'Exception',
                'error_message' => 'Exception',
                'log_location' => 'Sale Fail Before 3D Secure Or Before Sale Without 3D'
            ]);

            DB::table('sales')->where('id', $sale_id )->update([
                'IsTroyCard' => 0
            ]);

            if (count(Sale::where('id', $sale_id)->where('payment_methods', 'OK')->get()) == 0)
                Sale::where('id', $sale_id)->update([
                    'payment_methods' => 418
                ]);

            return redirect()->away('http://188.166.86.116/satin-alma/odeme-bilgileri?orderId=' . $sale_id);
        }
    }

    public function isBank3DFail()
    {
        DB::beginTransaction();
        try {
            $tempSessionInfo = Request::input('SessionInfo');
            $sale_id = explode("_", $tempSessionInfo)[0];

            if (count(Sale::where('id', $sale_id)->where('payment_methods', 'OK')->get()) > 0)
                return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sale_id);

            if( Request::input('ErrorCode') == '1061' ){
                return redirect()->away('http://188.166.86.116/satis-ozet?orderId=' . $sale_id);
            }

            DB::table('is_bank_log')->insert([
                'sale_id' => $sale_id,
                'transaction_id' => Request::input('VerifyEnrollmentRequestId'),
                'code' => Request::input('ErrorCode'),
                'error_message' => Request::input('ErrorMessage'),
                'log_location' => 'Sale Fail 3D secure check before payment'
            ]);
            Sale::where('id', $sale_id)->update([
                'payment_methods' => 418
            ]);

            Sale::where('id', $sale_id)->update([
                'IsTroyCard' => 0
            ]);

            $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sale_id)->get();
            if (count($couponData) != 0) {
                MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                    'used' => '0',
                    'valid' => '1'
                ]);
                DB::table('marketing_acts_sales')->where('sales_id', $sale_id)->delete();
            }
            DB::commit();
            $tempLangId = Sale::where('id', $sale_id)->select('lang_id')->get()[0]->lang_id;
            if ($tempLangId == 'en') {
                return redirect()->away('http://188.166.86.116/order-flowers/payment?orderId=' . $sale_id);
            } else {
                return redirect()->away('http://188.166.86.116/satin-alma/odeme-bilgileri?orderId=' . $sale_id);
            }
        } catch (\Exception $e) {
            DB::rollback();
            $couponData = DB::table('marketing_acts_sales')->where('sales_id', $sale_id)->get();
            if (count($couponData) != 0) {
                MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                    'used' => '0',
                    'valid' => '1'
                ]);
                DB::table('marketing_acts_sales')->where('sales_id', $sale_id)->delete();
            }
            DB::table('is_bank_log')->insert([
                'sale_id' => $sale_id,
                'transaction_id' => 'Exception',
                'code' => 'Exception',
                'error_message' => 'Exception',
                'log_location' => 'Sale Fail Before 3D Secure Or Before Sale Without 3D'
            ]);
            if (count(Sale::where('id', $sale_id)->where('payment_methods', 'OK')->get()) == 0)
                Sale::where('id', $sale_id)->update([
                    'payment_methods' => 418
                ]);

            Sale::where('id', $sale_id)->update([
                'IsTroyCard' => 0
            ]);

            return redirect()->away('http://188.166.86.116/satin-alma/odeme-bilgileri?orderId=' . $sale_id);

        }
        //return Request::input('VerifyEnrollmentRequestId');
    }

}