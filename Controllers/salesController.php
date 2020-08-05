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

class salesController extends Controller
{
    //public  $site_url = 'https://bloomandfresh.com';
    public $site_url = 'http://188.166.86.116';
    public $backend_url = 'http://188.166.86.116:3000';
    //public $backend_url = 'https://everybloom.com';

    /*
     * 3D onayı alınmadığı zaman banka sayfasından dönüş yapılan web wervis.
     * Bankada gerçekleşen hatanın kodu DB'ye kaydedilir. Kullanıcıya sabit bir mesaj gösterilmek üzere ödeme sayfasına ilgili satış numarası ile yönlendirilir.
     */
    public function errorCallback(\Illuminate\Http\Request $request)
    {
        DB::beginTransaction();
        try {
            $oid = $request->oid;
            $couponData = DB::table('marketing_acts_sales')->where('sales_id', $oid)->get();
            if (count($couponData) != 0) {
                MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                    'used' => '0',
                    'valid' => '1'
                ]);
                DB::table('marketing_acts_sales')->where('sales_id', $oid)->delete();
            }
            $errorMessage = 418;
            if( count(Sale::where('id' ,$oid)->where('payment_methods' , 'OK')->get()) == 0 ){
                Sale::where('id', $oid)->update([
                    'payment_methods' => $errorMessage
                ]);
            }

            DB::table('sales')->where('id', Request::get('sale_number'))->update([
                'IsTroyCard' => 0
            ]);

            logEventController::logErrorToDB('3DerrorCallback', $request->mdstatus, $request->mdstatus, 'WS', $oid);
            DB::commit();
            $tempLangId = Sale::where('id', $oid)->select('lang_id')->get()[0]->lang_id;
            if ($tempLangId == 'en') {
                return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . $oid);
            } else {
                return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . $oid);
            }
        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('3DerrorCallbackException', $e->getCode(), $e->getMessage(), 'WS', '');
            if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) == 0 )
            Sale::where('id', Request::get('sale_number'))->update([
                'payment_methods' => 400
            ]);
            $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
            if ($tempLangId == 'en') {
                return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
            } else {
                return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
            }
        }
    }

    /*
     * 3D'den onay alındığında bankadan dönüş yapılan web servis.
     * Burada mdstatus 0 ise satış işlemi gerçekleştirilir.
     * Mdstatus 0 dan farklı bir değer olduğunda DB'ye loglanır. Link ödeme sayfasına satış numarası verilerek yönlendirilir.
     * Satışın başarılı olma durumunda gerekli mailler gönderilir.
     * Başarılı için kullanıcı satış özeti sayfasına yönlendirilir.
     */

    public function transactionWithout(\Illuminate\Http\Request $request)
    {
        DB::beginTransaction();
        $tempMoneyTaken = false;
        $tempCouponUsed = false;
        $paymentWithoutMoney = false;
        $tempLeftMoneyFromCoupon = 0.0;

        try {
            if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) > 0 ){
                DB::commit();
                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-details?orderId=' . Request::get('sale_number'));
                }
                else {
                    return redirect()->away($this->site_url . '/satis-ozet?orderId=' . Request::get('sale_number'));
                }
            }
            //$tempFiyongo = false;
            //$paymentWithoutMoney = false;

            $tempCheckFlowerStatus = AdminPanelController::checkFlower(Request::get('sale_number'));

            if($tempCheckFlowerStatus == false){
                if( Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->count() == 0 ){
                    Sale::where('id', Request::get('sale_number'))->update([
                        'payment_methods' => 430
                    ]);
                }
                logEventController::logErrorToDB('completeSale', 'Geçmiş tarihli veya kapatılmış çiçek', 'Geçmiş tarihli veya kapatılmış çiçek', 'WS', '');
                DB::commit();
                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
                } else {
                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                }
            }

            $client = new \GuzzleHttp\Client();
            if (Request::input('access_token')) {
                $useCoupon = false;
                $userId = Request::input('user_id');
                $customerId = Customer::where('user_id', $userId)->get()[0]->id;
                $now = Carbon::now();
                $productId = DB::table('sales_products')->where('sales_id', Request::get('sale_number'))->get()[0]->products_id;
                $productInfo = Product::where('id', $productId)->get();
                $priceWithDiscount = $productInfo[0]->price;
                $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);
                //$priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);

                if( $productInfo[0]->product_type == 2 ){
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100);
                }
                else{
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                }

                $tempPrice = $priceWithDiscount;
                $priceWithDiscount = number_format($priceWithDiscount, 2);
                parse_str($priceWithDiscount);
                parse_str($tempPrice);
                $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);
                $tempPrice = str_replace('.', ',', $tempPrice);
                if (Request::input('coupon_id')) {
                    MarketingAct::where('expiredDate', '<', $now)->update([
                        'valid' => '0'
                    ]);
                    $couponList = DB::table('customers')
                        ->join('customers_marketing_acts', 'customers.id', '=', 'customers_marketing_acts.customers_id')
                        ->join('marketing_acts', 'customers_marketing_acts.marketing_acts_id', '=', 'marketing_acts.id')
                        ->where('customers.id', $customerId)
                        ->where('used', '0')
                        ->where('valid', '1')
                        ->where('active', '1')
                        ->where('marketing_acts.id', Request::input('coupon_id'))
                        ->select('marketing_acts.id', 'marketing_acts.name', 'marketing_acts.type', 'marketing_acts.value', 'marketing_acts.description', 'marketing_acts.long_term', 'marketing_acts.product_coupon')->get();

                    if (count($couponList) > 0) {
                        if ($couponList[0]->product_coupon != null && $couponList[0]->product_coupon != 0) {
                            if (DB::table('productList_coupon')->where('coupon_id', $couponList[0]->product_coupon)->where('product_id', $productId)->count() == 0) {
                                if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) == 0 )
                                Sale::where('id', Request::get('sale_number'))->update([
                                    'payment_methods' => 419
                                ]);
                                logEventController::logErrorToDB('completeSale', 'Çiçek ve kupon eşleşmesi hatalı', 'Çiçek ve kupon eşleşmesi hatalı', 'WS', '');
                                DB::commit();
                                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                                if ($tempLangId == 'en') {
                                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
                                } else {
                                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                                }
                            }
                        }
                        $useCoupon = true;
                        $priceWithDiscount = str_replace(',', '.', $tempPrice);
                        if ($couponList[0]->type == 2) {
                            $priceWithDiscount = floatval($priceWithDiscount) * (100 - floatval($couponList[0]->value)) / 100;

                            if( $productInfo[0]->product_type == 3 || $productInfo[0]->product_type == 2 ){
                                Sale::where('id', Request::get('sale_number'))->update([
                                    'payment_methods' => 419
                                ]);
                                logEventController::logErrorToDB('completeSale', 'Ürün-kupon eşleşmesi hatalı', 'Ürün-kupon eşleşmesi hatalı', 'WS', '');
                                DB::commit();
                                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                                if ($tempLangId == 'en') {
                                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
                                } else {
                                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                                }
                            }

                        } else {
                            $priceWithDiscount = $productInfo[0]->price;
                            $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);

                            if( $productInfo[0]->product_type == 2 ){
                                $priceWithDiscount = floatval( ( floatval($priceWithDiscount) - floatval($couponList[0]->value) )* 108 / 100);
                            }
                            else{
                                $priceWithDiscount = floatval(  ( floatval($priceWithDiscount)  - floatval($couponList[0]->value) )* 118 / 100);
                            }

                            if($priceWithDiscount <= 0){
                                $paymentWithoutMoney = true;
                                $tempLeftMoneyFromCoupon = -1*$priceWithDiscount;
                                $priceWithDiscount = 0.0;
                            }
                            $tempCouponUsed = true;
                            //$priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                            //$tempPrice = $priceWithDiscount;
                            //$priceWithDiscount = number_format($priceWithDiscount, 2);
                            //parse_str($priceWithDiscount);
                            //parse_str($tempPrice);
                            //$priceWithDiscount = str_replace('.', ',', $priceWithDiscount);
                            //$tempPrice = str_replace('.', ',', $tempPrice);
                            //dd($priceWithDiscount);
                        }
                    }
                    else {
                        if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) == 0 )
                        Sale::where('id', Request::get('sale_number'))->update([
                            'payment_methods' => 409
                        ]);
                        logEventController::logErrorToDB('completeSale', 'Hatalı kupon girildi.', 'Hatalı kupon girildi.', 'WS', '');
                        DB::commit();
                        $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                        if ($tempLangId == 'en') {
                            return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
                        } else {
                            return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                        }
                    }
                }

                if ($useCoupon) {
                    $priceWithDiscount = number_format($priceWithDiscount, 2);
                    $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);
                    Sale::where('id', Request::get('sale_number'))->update([
                        'sum_total' => $priceWithDiscount
                    ]);
                    DB::table('marketing_acts_sales')
                        ->insert([
                            'sales_id' => Request::get('sale_number'),
                            'marketing_acts_id' => Request::input('coupon_id')
                        ]);
                }
            }

            if (Request::input('access_token')) {
                $price = $priceWithDiscount;
                Sale::where('id', Request::get('sale_number'))->update([
                    'sum_total' => $priceWithDiscount
                ]);
            } else {
                $productId = DB::table('sales_products')->where('sales_id', Request::get('sale_number'))->get()[0]->products_id;
                $productInfo = Product::where('id', $productId)->get();
                $priceWithDiscount = $productInfo[0]->price;
                $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);

                if( $productInfo[0]->product_type == 2 ){
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100);
                }
                else{
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                }
                
                //$priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                $priceWithDiscount = number_format($priceWithDiscount, 2);
                parse_str($priceWithDiscount);
                $price = str_replace('.', ',', $priceWithDiscount);
            }

            if(Request::input('cross_sell') > 0){
                $tempCrossProduct = DB::table('cross_sell_products')->where('id', Request::input('cross_sell'))->get()[0];
                $tempCrossSellPrice = floatval(str_replace(',', '.', $tempCrossProduct->price));
                $tempCrossSellDiscount = 0;
                $tempCrossSellTax = number_format(floatval($tempCrossSellPrice / 100.0 * 8.0), 2);
                if( $tempLeftMoneyFromCoupon > 0 ){
                    $tempCrossSellPrice = number_format($tempCrossSellPrice * 108 / 100 - $tempLeftMoneyFromCoupon, 2);
                }
                else{
                    $tempCrossSellPrice = number_format(floatval($tempCrossSellPrice * 108 / 100), 2);
                }

                if( $tempCrossSellPrice < 0 ){
                    $tempCrossSellDiscount = $tempCrossProduct->price;
                    $paymentWithoutMoney = true;
                    $tempCrossSellPrice = 0.0;
                }
                else{
                    $tempCrossSellDiscount = $tempLeftMoneyFromCoupon;
                    $paymentWithoutMoney = false;
                }

                //$tempCrossSellTax = number_format(floatval($tempCrossSellPrice / 100.0 * 8.0), 2);
                //$tempCrossSellTotal = $tempCrossSellPrice + $tempCrossSellTax;
                $tempCrossSellTotal = $tempCrossSellPrice;
                $price = floatval(str_replace(',', '.', $price)) + $tempCrossSellTotal;
                $price = str_replace('.', ',', $price);
                DB::table('cross_sell')->where('sales_id' , Request::get('sale_number'))->delete();
                DB::table('cross_sell')->insert([
                    'sales_id' => Request::get('sale_number'),
                    'product_id' => Request::input('cross_sell'),
                    'product_price' => $tempCrossProduct->price,
                    'discount' => $tempCrossSellDiscount,
                    'tax' => str_replace('.', ',', $tempCrossSellTax),
                    'total_price' => str_replace('.', ',', $tempCrossSellTotal)
                ]);
            }
            else{
                DB::table('cross_sell')->where('sales_id' , Request::get('sale_number'))->delete();
            }

            $tempPrice = $price;

            $price = str_replace(',', '.', $price);
            $price = floatval($price) * 100.00;
            parse_str($price);
            $tempArray = explode(".", $price);
            $price = $tempArray[0];
            $client = new \GuzzleHttp\Client();
            $strTerminalID = "10025261";
            $strTerminalID_ = "010025261";
            $strStoreKey = "696632333569663233356966323335696632333569663233";
            $strProvisionPassword = "Hakan1234";
            $cardNumber = str_replace('-', '', Request::input('card_no'));

            $SecurityData = strtoupper(sha1($strProvisionPassword . $strTerminalID_));
            $HashData = strtoupper(sha1(Request::get('sale_number') . $strTerminalID . $cardNumber . $price . $SecurityData));

            if($paymentWithoutMoney){
                $tempMoneyTaken = true;
                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'created_at' => Carbon::now()
                ]);

                $mailData = DB::table('sales')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.id', '=', Request::get('sale_number'))
                    ->select('delivery_locations.district','delivery_locations.city_id', 'sales.id', 'sales.sum_total', 'customer_contacts.surname as contact_surname', 'customer_contacts.name as contact_name', 'deliveries.wanted_delivery_limit',
                        'deliveries.created_at', 'deliveries.wanted_delivery_date', 'deliveries.products', 'sales.receiver_address as address', 'sales_products.products_id',
                        'sales.sender_name as name', 'sales.sender_surname as surname', 'sales.sender_mobile as mobile', 'sales.paymentAmount', 'IsTroyCard')
                    ->get()[0];

                if( $mailData->IsTroyCard ){
                    $mailData->paymentAmount = floatval($mailData->paymentAmount)/100;

                    parse_str($mailData->paymentAmount);
                    $mailData->sum_total = str_replace('.', ',', $mailData->paymentAmount);
                }

                $created = new Carbon($mailData->wanted_delivery_limit);

                if( $mailData->city_id == 2 ){
                    $tempCityMail = '06-Ankara';
                }
                else if( $mailData->city_id == 1 ){
                    $tempCityMail = '34-İstanbul';
                }
                else{
                    $tempCityMail = 'KARGO';
                }

                setlocale(LC_TIME, "");
                setlocale(LC_ALL, 'tr_TR.utf8');
                $mailDate = new Carbon($mailData->wanted_delivery_limit);
                $mailDate = $mailDate->formatLocalized('%A %d %B');

                if(Request::input('cross_sell') > 0){
                    $extraProduct = $tempCrossProduct->name;
                }
                else
                    $extraProduct = '';

                \MandrillMail::messages()->sendTemplate('siparisuyari', null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => 'Sipariş verildi.',
                    'subject' => 'Sipariş - ' . $tempCityMail . ' ' . $mailDate,
                    'from_email' => 'siparis@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => 'siparis@bloomandfresh.com',
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
                            'content' => $extraProduct
                        )
                    )
                ));

                Sale::where('id', Request::get('sale_number'))->update([
                    'payment_methods' => 'OK'
                ]);

                $couponId = str_random(8);
                $uniqueFlagTester = true;
                while ($uniqueFlagTester) {
                    if (count(MarketingAct::where('publish_id', $couponId)->get()) == 0) {
                        $uniqueFlagTester = false;
                    } else {
                        $couponId = str_random(8);
                    }
                }
                $couponId = strtoupper($couponId);
                MarketingAct::create(
                    [
                        'publish_id' => $couponId,
                        'name' => '10% Şanslı Kişi İndirimi',
                        'description' => 'Size çiçek siparişi verildiği için kazandığınız indirim.',
                        'type' => 2,
                        'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/10_indirim.png',
                        'value' => '10',
                        'active' => false,
                        'valid' => 1,
                        'expiredDate' => Carbon::now()->addDay(180),
                        'used' => false,
                        'administrator_id' => 1
                    ]
                );

                Sale::where('id', Request::get('sale_number'))->update([
                    'delivery_notification' => $couponId,
                    'payment_type' => 'COUPON'
                ]);
                $couponData = DB::table('marketing_acts_sales')->where('sales_id', Request::get('sale_number'))->get();
                if (count($couponData) != 0) {
                    $tempCoupon = MarketingAct::where('id', $couponData[0]->marketing_acts_id)->get()[0];
                    if ($tempCoupon->long_term) {

                    } else {
                        MarketingAct::where('id', $tempCoupon->id)->update([
                            'used' => '1',
                            'valid' => '0'
                        ]);
                    }
                }

                DB::commit();
                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-details?orderId=' . Request::get('sale_number'));
                } else {
                    return redirect()->away($this->site_url . '/satis-ozet?orderId=' . Request::get('sale_number'));
                }
            }

            /*if(
                substr($cardNumber, 0, 6) == '979217' ||
                substr($cardNumber, 0, 6) == '979280' ||
                substr($cardNumber, 0, 6) == '979210' ||
                substr($cardNumber, 0, 6) == '979212' ||
                substr($cardNumber, 0, 6) == '979244' ||
                substr($cardNumber, 0, 6) == '650052' ||
                substr($cardNumber, 0, 6) == '650170' ||
                substr($cardNumber, 0, 6) == '979209' ||
                substr($cardNumber, 0, 6) == '979223' ||
                substr($cardNumber, 0, 6) == '979206' ||
                substr($cardNumber, 0, 6) == '979207' ||
                substr($cardNumber, 0, 6) == '979208' ||
                substr($cardNumber, 0, 6) == '979236' ||
                substr($cardNumber, 0, 6) == '979204' ||
                substr($cardNumber, 0, 6) == '650082' ||
                substr($cardNumber, 0, 6) == '650092' ||
                substr($cardNumber, 0, 6) == '650173' ||
                substr($cardNumber, 0, 6) == '650456' ||
                substr($cardNumber, 0, 6) == '650987' ||
                substr($cardNumber, 0, 6) == '979233' ||
                substr($cardNumber, 0, 6) == '657366' ||
                substr($cardNumber, 0, 6) == '657998' ||
                substr($cardNumber, 0, 6) == '650161' ||
                substr($cardNumber, 0, 6) == '979215' ||
                substr($cardNumber, 0, 6) == '979241' ||
                substr($cardNumber, 0, 6) == '979242' ||
                substr($cardNumber, 0, 6) == '979202' ||
                substr($cardNumber, 0, 6) == '979203' ||
                substr($cardNumber, 0, 6) == '365770' ||
                substr($cardNumber, 0, 6) == '365771' ||
                substr($cardNumber, 0, 6) == '365772' ||
                substr($cardNumber, 0, 6) == '365773' ||
                substr($cardNumber, 0, 6) == '654997' ||
                substr($cardNumber, 0, 6) == '979240' ||
                substr($cardNumber, 0, 6) == '979213' ||
                substr($cardNumber, 0, 6) == '979227' ||
                substr($cardNumber, 0, 6) == '979216' ||
                substr($cardNumber, 0, 6) == '979218' ||
                substr($cardNumber, 0, 6) == '979235' ||
                substr($cardNumber, 0, 6) == '979248' ||
                substr($cardNumber, 0, 6) == '979277' ||
                substr($cardNumber, 0, 6) == '979254' ||
                substr($cardNumber, 0, 6) == '979278' ||
                substr($cardNumber, 0, 6) == '979249' ||
                substr($cardNumber, 0, 6) == '979243' ||
                substr($cardNumber, 0, 6) == '979250' ||
                substr($cardNumber, 0, 6) == '979266' ||
                substr($cardNumber, 0, 6) == '979260' ||
                substr($cardNumber, 0, 6) == '979261' ||
                substr($cardNumber, 0, 6) == '979262'
            ){

                $tempPrice = str_replace(',', '.', $tempPrice);

                if( floatval($tempPrice) > 100){

                    $tempPrice = floatval($tempPrice) - 30;

                    $tempPrice = floatval($tempPrice) * 100.00;
                    parse_str($tempPrice);
                    $tempArray = explode(".", $tempPrice);
                    $tempPrice = $tempArray[0];

                    $HashData = strtoupper(sha1(Request::get('sale_number') . $strTerminalID . $cardNumber . $tempPrice . $SecurityData));

                    $price = $tempPrice;

                    DB::table('sales')->where('id', Request::get('sale_number'))->update([
                        'IsTroyCard' => 1
                    ]);

                }
                else{
                    DB::table('sales')->where('id', Request::get('sale_number'))->update([
                        'IsTroyCard' => 0
                    ]);
                }

            }
            else{
                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'IsTroyCard' => 0
                ]);
            }*/

            if(IsBankSalesController::checkMaximum($cardNumber)){
                return IsBankSalesController::isBankPos(Request::get('sale_number'), $cardNumber, Request::input('card_year') . Request::input('card_month'), $price ,false, Request::input('card_cvv'), Request::input('cross_sell'));
            }

            $xml = "
            <GVPSRequest>
                <Mode>PROD</Mode>
                <Version>v0.01</Version>
                <Terminal>
                    <ProvUserID>PROVAUT</ProvUserID>
                    <HashData>" . $HashData . "</HashData>
                    <UserID>TMYTKLI</UserID>
                    <ID>10025261</ID>
                    <MerchantID>9384072</MerchantID>
                </Terminal>
                <Customer>
                    <IPAddress>" . Request::ip() . "</IPAddress>
                    <EmailAddress></EmailAddress>
                </Customer>
                <Card>
                    <Number>" . $cardNumber . "</Number>
                    <ExpireDate>" . Request::input('card_month') . Request::input('card_year') . "</ExpireDate>
                    <CVV2>" . Request::input('card_cvv') . "</CVV2>
                </Card>
                <Order>
                    <OrderID>" . Request::get('sale_number') . "</OrderID>
                    <GroupID></GroupID>
                    <AddressList>
                        <Address>
                            <Type>S</Type>
                            <Name></Name>
                            <LastName></LastName>
                            <Company></Company>
                            <Text></Text>
                            <District></District>
                            <City></City>
                            <PostalCode></PostalCode>
                            <Country></Country>
                            <PhoneNumber></PhoneNumber>
                        </Address>
                    </AddressList>
                </Order>
                <Transaction>
                    <Type>sales</Type>
                    <InstallmentCnt></InstallmentCnt>
                    <Amount>" . $price . "</Amount>
                    <CurrencyCode>949</CurrencyCode>
                    <CardholderPresentCode>0</CardholderPresentCode>
                    <MotoInd>N</MotoInd>
                    <Description></Description>
                    <OriginalRetrefNum></OriginalRetrefNum>
                </Transaction>
            </GVPSRequest>";
            $response = $client->post('https://sanalposprov.garanti.com.tr/VPServlet', ['body' => $xml]);
            if ($response->xml()->Transaction->Response->Code == "00") {
                $tempMoneyTaken = true;
                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'created_at' => Carbon::now()
                ]);

                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'paymentAmount' => $price
                ]);

                $mailData = DB::table('sales')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.id', '=', Request::get('sale_number'))
                    ->select('delivery_locations.district','delivery_locations.city_id', 'sales.id', 'sales.sum_total', 'customer_contacts.surname as contact_surname', 'customer_contacts.name as contact_name', 'deliveries.wanted_delivery_limit',
                        'deliveries.created_at', 'deliveries.wanted_delivery_date', 'deliveries.products', 'sales.receiver_address as address', 'sales_products.products_id', 'IsTroyCard', 'paymentAmount',
                        'sales.sender_name as name', 'sales.sender_surname as surname', 'sales.sender_mobile as mobile')
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

                if(Request::input('cross_sell') > 0){
                    $extraProduct = $tempCrossProduct->name;
                }
                else
                    $extraProduct = '';

                \MandrillMail::messages()->sendTemplate('siparisuyari', null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => 'Sipariş verildi.',
                    'subject' => 'Sipariş - ' . $tempCityMail . ' ' . $mailDate,
                    'from_email' => 'siparis@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => 'siparis@bloomandfresh.com',
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
                            'content' => $extraProduct
                        )
                    )
                ));

                Sale::where('id', Request::get('sale_number'))->update([
                    'payment_methods' => 'OK'
                ]);

                if($tempCouponUsed){
                    DB::table('sales')->where('id', Request::get('sale_number'))->update([
                        'payment_type' => 'COUPON'
                    ]);
                }
                else{
                    DB::table('sales')->where('id', Request::get('sale_number'))->update([
                        'payment_type' => 'POS'
                    ]);
                }

                $couponId = str_random(8);
                $uniqueFlagTester = true;
                while ($uniqueFlagTester) {
                    if (count(MarketingAct::where('publish_id', $couponId)->get()) == 0) {
                        $uniqueFlagTester = false;
                    } else {
                        $couponId = str_random(8);
                    }
                }
                $couponId = strtoupper($couponId);
                MarketingAct::create(
                    [
                        'publish_id' => $couponId,
                        'name' => '10% Şanslı Kişi İndirimi',
                        'description' => 'Size çiçek siparişi verildiği için kazandığınız indirim.',
                        'type' => 2,
                        'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/10_indirim.png',
                        'value' => '10',
                        'active' => false,
                        'valid' => 1,
                        'expiredDate' => Carbon::now()->addDay(180),
                        'used' => false,
                        'administrator_id' => 1
                    ]
                );

                Sale::where('id', Request::get('sale_number'))->update([
                    'delivery_notification' => $couponId
                ]);
                $couponData = DB::table('marketing_acts_sales')->where('sales_id', Request::get('sale_number'))->get();
                if (count($couponData) != 0) {
                    $tempCoupon = MarketingAct::where('id', $couponData[0]->marketing_acts_id)->get()[0];
                    if ($tempCoupon->long_term) {

                    } else {
                        MarketingAct::where('id', $tempCoupon->id)->update([
                            'used' => '1',
                            'valid' => '0'
                        ]);
                    }
                }

                generateDataController::callSetProductFromSaleId(Request::get('sale_number'));

                DB::commit();
                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-details?orderId=' . Request::get('sale_number'));
                } else {
                    return redirect()->away($this->site_url . '/satis-ozet?orderId=' . Request::get('sale_number'));
                }
            } else {

                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'IsTroyCard' => 0
                ]);

                $couponData = DB::table('marketing_acts_sales')->where('sales_id', Request::get('sale_number'))->get();
                if (count($couponData) != 0) {
                    MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                        'used' => '0',
                        'valid' => '1'
                    ]);
                    DB::table('marketing_acts_sales')->where('sales_id', Request::get('sale_number'))->delete();
                }
                if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) == 0 )
                Sale::where('id', Request::get('sale_number'))->update([
                    'payment_methods' => 418
                ]);
                logEventController::logErrorToDB('transactionWithout', $response->xml()->Transaction->Response->Code, $response->xml()->Transaction->Response->Code, 'WS', Request::get('sale_number'));
                DB::commit();
                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
                } else {
                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                }
            }
        } catch (\Exception $e) {
            DB::rollback();

            DB::table('sales')->where('id', Request::get('sale_number'))->update([
                'IsTroyCard' => 0
            ]);

            $couponData = DB::table('marketing_acts_sales')->where('sales_id', Request::get('sale_number'))->get();
            if (count($couponData) != 0) {
                MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                    'used' => '0',
                    'valid' => '1'
                ]);
                DB::table('marketing_acts_sales')->where('sales_id', Request::get('sale_number'))->delete();
            }
            logEventController::logErrorToDB('successCallbackExceptionError', $e->getCode(), $e->getMessage(), 'WS', Request::get('sale_number'));
            if ($tempMoneyTaken) {
                Sale::where('id', Request::get('sale_number'))->update([
                    'payment_methods' => 'OK'
                ]);
                return redirect()->away($this->site_url . '/satis-ozet?orderId=' . Request::get('sale_number'));
            }
            if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) == 0 )
            Sale::where('id', Request::get('sale_number'))->update([
                'payment_methods' => 418
            ]);

            return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));

        }
    }

    public function successCallback(\Illuminate\Http\Request $request)
    {
        DB::beginTransaction();
        $tempMoneyTaken = false;
        try {

            $strTerminalID = "10025261";
            $strTerminalID_ = "010025261";
            $strProvisionPassword = "Hakan1234";

            $client = new \GuzzleHttp\Client();
            $authcode = $request->cavv;
            $securityLevel = $request->eci;
            $txnId = $request->xid;
            $strMD = $request->md;
            $oid = $request->oid;
            $customeripaddress = $request->customeripaddress;
            $customeremailaddress = $request->customeremailaddress;
            $txnamount = $request->txnamount;
            $price = Sale::where('id', $oid)->get()[0]->sum_total;

            $tempCikolat = AdminPanelController::getCikolatData($oid);

            //if($tempCikolat){
            //    $price = floatval(str_replace(',', '.', $tempCikolat->total_price)) + floatval(str_replace(',', '.', $price));
            //}

            $tempCrossSell =  DB::table('cross_sell')->where('sales_id', $oid )->get();
            if( count($tempCrossSell) > 0 ){
                $price = str_replace(',', '.', $price);
                $price = floatval($price) +  floatval(str_replace(',', '.', $tempCrossSell[0]->total_price));
            }
            
            $price = str_replace(',', '.', $price);
            $price = floatval($price) * 100.00;
            parse_str($price);
            $tempArray = explode(".", $price);
            $price = $tempArray[0];

            $tempTotalPaymentValue = DB::table('sales')->where('id', $oid)->get();

            /*if ( $tempTotalPaymentValue[0]->paymentAmount != $txnamount ) {

                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'IsTroyCard' => 0
                ]);

                $couponData = DB::table('marketing_acts_sales')->where('sales_id', $oid)->get();
                if (count($couponData) != 0) {
                    MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                        'used' => '0',
                        'valid' => '1'
                    ]);
                    DB::table('marketing_acts_sales')->where('sales_id', $oid)->delete();
                }
                if( count(Sale::where('id' ,$oid)->where('payment_methods' , 'OK')->get()) == 0 )
                Sale::where('id', $oid)->update([
                    'payment_methods' => 418
                ]);
                logEventController::logErrorToDB('3DSuccessPricingError', 'priceError', 'priceError', 'WS', $oid);
                DB::commit();
                $tempLangId = Sale::where('id', $oid)->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . $oid);
                } else {
                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . $oid);
                }
            }*/

            if ($request->mdstatus == 0 || $request->mdstatus == 5 || $request->mdstatus == 7 || $request->mdstatus == 8) {
                $couponData = DB::table('marketing_acts_sales')->where('sales_id', $oid)->get();
                if (count($couponData) != 0) {
                    MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                        'used' => '0',
                        'valid' => '1'
                    ]);
                    DB::table('marketing_acts_sales')->where('sales_id', $oid)->delete();
                }
                if( count(Sale::where('id' ,$oid)->where('payment_methods' , 'OK')->get()) == 0 )
                Sale::where('id', $oid)->update([
                    'payment_methods' => 400
                ]);

                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'IsTroyCard' => 0
                ]);

                logEventController::logErrorToDB('successCallbackBeforeTransaction', $request->mdstatus, $request->mdstatus, 'WS', $oid);
                DB::commit();
                $tempLangId = Sale::where('id', $oid)->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . $oid);
                } else {
                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . $oid);
                }
            }
            $SecurityData = strtoupper(sha1($strProvisionPassword . $strTerminalID_));
            $HashData = strtoupper(sha1($oid . $strTerminalID . $txnamount . $SecurityData));
            $xml = "
            <GVPSRequest>
                <Mode>PROD</Mode>
                <Version>v0.01</Version>
                <ChannelCode></ChannelCode>
                <Terminal>
                    <ProvUserID>PROVAUT</ProvUserID>
                    <HashData>" . $HashData . "</HashData>
                    <UserID>TMYTKLI</UserID>
                    <ID>10025261</ID>
                    <MerchantID>9384072</MerchantID>
                </Terminal>
                <Customer>
                    <IPAddress>" . $customeripaddress . "</IPAddress>
                    <EmailAddress>" . $customeremailaddress . "</EmailAddress>
                </Customer>
                <Card>
                    <Number></Number>
                    <ExpireDate></ExpireDate>
                    <CVV2></CVV2>
                </Card>
                <Order>
                    <OrderID>" . $oid . "</OrderID>
                    <GroupID></GroupID>
                    <AddressList>
                        <Address>
                            <Type>S</Type>
                            <Name></Name>
                            <LastName></LastName>
                            <Company></Company>
                            <Text></Text>
                            <District></District>
                            <City></City>
                            <PostalCode></PostalCode>
                            <Country></Country>
                            <PhoneNumber></PhoneNumber>
                        </Address>
                    </AddressList>
                </Order>
                <Transaction>
                    <Type>sales</Type>
                    <InstallmentCnt></InstallmentCnt>
                    <Amount>" . $txnamount . "</Amount>
                    <CurrencyCode>949</CurrencyCode>
                    <CardholderPresentCode>13</CardholderPresentCode>
                    <MotoInd>N</MotoInd>
                    <Secure3D>
                        <AuthenticationCode>" . $authcode . "</AuthenticationCode>
                        <SecurityLevel>" . $securityLevel . "</SecurityLevel>
                        <TxnID>" . $txnId . "</TxnID>
                        <Md>" . $strMD . "</Md>
                    </Secure3D>
                </Transaction>
            </GVPSRequest>";
            $response = $client->post('https://sanalposprov.garanti.com.tr/VPServlet', ['body' => $xml]);
            if ($response->xml()->Transaction->Response->Code == "00") {
                $tempMoneyTaken = true;
                DB::table('sales')->where('id', $oid)->update([
                    'created_at' => Carbon::now()
                ]);

                $mailData = DB::table('sales')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.id', '=', $oid)
                    ->select('delivery_locations.district','delivery_locations.city_id', 'sales.id', 'sales.sum_total', 'customer_contacts.surname as contact_surname', 'customer_contacts.name as contact_name', 'deliveries.wanted_delivery_limit',
                        'deliveries.created_at', 'deliveries.wanted_delivery_date', 'deliveries.products', 'sales.receiver_address as address', 'sales_products.products_id',
                        'sales.sender_name as name', 'sales.sender_surname as surname', 'sales.sender_mobile as mobile', 'sales.paymentAmount', 'IsTroyCard')
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
                $extraProduct = '';
                if($tempCikolat){
                    $extraProduct = $tempCikolat->name;
                }

                if( $mailData->IsTroyCard ){
                    $mailData->paymentAmount = floatval($mailData->paymentAmount)/100;

                    parse_str($mailData->paymentAmount);
                    $mailData->sum_total = str_replace('.', ',', $mailData->paymentAmount);
                }

                \MandrillMail::messages()->sendTemplate('siparisuyari', null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => 'Sipariş verildi.',
                    'subject' => 'Sipariş - ' . $tempCityMail . ' ' . $mailDate,
                    'from_email' => 'siparis@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => 'siparis@bloomandfresh.com',
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
                            'content' => $extraProduct
                        )
                    )
                ));

                Sale::where('id', $oid)->update([
                    'payment_methods' => 'OK'
                ]);

                $couponId = str_random(8);
                $uniqueFlagTester = true;
                while ($uniqueFlagTester) {
                    if (count(MarketingAct::where('publish_id', $couponId)->get()) == 0) {
                        $uniqueFlagTester = false;
                    } else {
                        $couponId = str_random(8);
                    }
                }
                $couponId = strtoupper($couponId);
                MarketingAct::create(
                    [
                        'publish_id' => $couponId,
                        'name' => '10% Şanslı Kişi İndirimi',
                        'description' => 'Size çiçek siparişi verildiği için kazandığınız indirim.',
                        'type' => 2,
                        'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/10_indirim.png',
                        'value' => '10',
                        'active' => false,
                        'valid' => 1,
                        'expiredDate' => Carbon::now()->addDay(180),
                        'used' => false,
                        'administrator_id' => 1
                    ]
                );

                Sale::where('id', $oid)->update([
                    'delivery_notification' => $couponId
                ]);
                $couponData = DB::table('marketing_acts_sales')->where('sales_id', $oid)->get();
                if (count($couponData) != 0) {
                    $tempCoupon = MarketingAct::where('id', $couponData[0]->marketing_acts_id)->get()[0];
                    if ($tempCoupon->long_term) {

                    } else {
                        MarketingAct::where('id', $tempCoupon->id)->update([
                            'used' => '1',
                            'valid' => '0'
                        ]);
                    }
                }

                generateDataController::callSetProductFromSaleId($oid);

                DB::commit();
                $tempLangId = Sale::where('id', $oid)->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-details?orderId=' . $oid);
                } else {
                    return redirect()->away($this->site_url . '/satis-ozet?orderId=' . $oid);
                }
            } else {

                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'IsTroyCard' => 0
                ]);

                $couponData = DB::table('marketing_acts_sales')->where('sales_id', $oid)->get();
                if (count($couponData) != 0) {
                    MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                        'used' => '0',
                        'valid' => '1'
                    ]);
                    DB::table('marketing_acts_sales')->where('sales_id', $oid)->delete();
                }
                if( count(Sale::where('id' ,$oid)->where('payment_methods' , 'OK')->get()) == 0 )
                Sale::where('id', $oid)->update([
                    'payment_methods' => 418
                ]);
                logEventController::logErrorToDB('3DSuccessTransactionFailError', $response->xml()->Transaction->Response->Code, $response->xml()->Transaction->Response->Code, 'WS', $oid);
                DB::commit();
                $tempLangId = Sale::where('id', $oid)->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . $oid);
                } else {
                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . $oid);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            $couponData = DB::table('marketing_acts_sales')->where('sales_id', $oid)->get();
            if (count($couponData) != 0) {
                MarketingAct::where('id', $couponData[0]->marketing_acts_id)->update([
                    'used' => '0',
                    'valid' => '1'
                ]);
                DB::table('marketing_acts_sales')->where('sales_id', $oid)->delete();
            }
            logEventController::logErrorToDB('successCallbackExceptionError', $e->getCode(), $e->getMessage(), 'WS', $oid);

            if ($tempMoneyTaken) {
                Sale::where('id', $oid)->update([
                    'payment_methods' => 'OK'
                ]);
                return redirect()->away($this->site_url . '/satis-ozet?orderId=' . $oid);
            }

            DB::table('sales')->where('id', Request::get('sale_number'))->update([
                'IsTroyCard' => 0
            ]);

            if( count(Sale::where('id' ,$oid)->where('payment_methods' , 'OK')->get()) == 0 )
            Sale::where('id', $oid)->update([
                'payment_methods' => 418
            ]);

            return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . $oid);
        }
    }

    /*
     * Satış başarılı olduğunda yönlendirilen satış özeti sayfasının bilgilerinin karşılandığı web servis
     */
    public function getCompleteSaleInfo($salesId)
    {
        try {
            $tempData = DB::table('sales')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->where('sales.id', '=', $salesId)
                ->select('sales.ups', 'sales.id', 'sales.sender_email', 'sales.created_at', 'sales.sender_surname', 'sales.sender_name', 'sales.card_message', 'sales.sum_total', 'customer_contacts.surname as surname', 'customer_contacts.name as name', 'deliveries.wanted_delivery_limit as wanted_delivery_date_end ',
                    'deliveries.wanted_delivery_date', 'deliveries.products', 'sales.receiver_address as address', 'sales_products.products_id', 'sales.firstVisit', 'sales.IsTroyCard')
                ->get()[0];

            $now = Carbon::now();
            $now->addMinute(-15);

            $tempCikolat = AdminPanelController::getCikolatData($tempData->id);

            if($tempCikolat){
                //$tempData->sum_total = str_replace('.', ',', floatval(str_replace(',', '.', $tempData->sum_total)) + floatval(str_replace(',', '.', $tempCrossSell[0]->total_price)));
                $tempData->godiva_sum_total = $tempCikolat->total_price;
                $tempData->godiva_name = $tempCikolat->name;
                $tempData->godiva_desc = $tempCikolat->desc;
                $tempData->godiva_image = $tempCikolat->image;
            }
            else{
                $tempData->godiva_sum_total = "";
                $tempData->godiva_name = "";
                $tempData->godiva_desc = "";
                $tempData->godiva_image = "";
            }

            if( $tempData->IsTroyCard == 1 ){

                if($tempCikolat){
                    //$tempData->godiva_sum_total = str_replace('.', ',', number_format( (floatval(str_replace(',', '.', $tempCikolat->total_price )) - 30) , 2));
                    $tempData->sum_total = str_replace('.', ',', number_format( (floatval(str_replace(',', '.', $tempData->sum_total )) - 30) , 2));
                }
                else{
                    $tempData->sum_total = str_replace('.', ',', number_format( (floatval(str_replace(',', '.', $tempData->sum_total )) - 30) , 2));
                }

            }

            //if( $tempData->created_at < $now ){
            //    logEventController::logErrorToDB('getCompleteSaleInfo-tarihgecmis','','','WS',$salesId);
            //    return response()->json(["status" => -2, "description" => "Zaman aşımı"], 400);
            //}

            if ($tempData->firstVisit == 1) {
                logEventController::logErrorToDB('getCompleteSaleInfo-ikinci-gelis', '', '', 'WS', $salesId);
                return response()->json(["status" => -2, "description" => "Zaman aşımı"], 400);
            }

            //$tempData->name = $tempData->name . ' ' . $tempData->surname;

            DB::table('sales')->where('id', $salesId)->update([
                'firstVisit' => '1'
            ]);

            return response()->json(["status" => 1, "data" => $tempData], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getCompleteSaleInfo', $e->getCode(), $e->getMessage(), 'WS', $salesId);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    /*
     * Satış esnasında hata alındığı zaman gerekli satış bilgilerinin tekrar görüntülenmesi için gereken bilgilerin sağlandığı web servis
     */
    public function getFailSaleInfo($salesId)
    {
        DB::beginTransaction();
        try {
            $tempNumber = DB::table('INFORMATION_SCHEMA.TABLES')
                ->select('AUTO_INCREMENT')
                ->where('TABLE_SCHEMA', 'bloomNfresh')
                ->where('TABLE_NAME', 'sales')->get()[0]->AUTO_INCREMENT;
            $tempNumber = $tempNumber + 1;
            DB::statement("ALTER TABLE sales AUTO_INCREMENT = " . $tempNumber . ";");

            $now = Carbon::now();
            $now->addHour(-5);
            //if (DB::table('sales')->where('created_at', '>', $now)->where('payment_methods', '!=', 'OK')->where('id', $salesId)->count() == 0) {
            //    logEventController::logErrorToDB('getFailSaleInfo', '5h expired sale operation', '5h expired sale operation', 'WS', $salesId);
            //    return response()->json(["status" => -1, "description" => 400], 400);
            //}

            $coupon = DB::table('marketing_acts_sales')->where('sales_id', $salesId)->get();
            if (count($coupon) > 0) {
                MarketingAct::where('id', $coupon[0]->marketing_acts_id)->update([
                    'used' => '0',
                    'valid' => '1'
                ]);
                DB::table('marketing_acts_sales')->where('sales_id', $salesId)->delete();
            }

            $returnData = DB::table('sales')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.id', '=', $salesId)
                ->select('sales.sender_mobile as mobile', 'sales.sender_surname as surname', 'sales.sender_name as name', 'sales.sender_email as mail',
                    'sales.receiver_mobile as contact_mobile', 'sales.receiver_address as contact_address',
                    'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.delivery_locations_id as city_id', 'products.speciality' ,
                    'sales.sum_total as sum_total', 'sales.card_message as card_message', 'sales.receiver as customer_receiver_name', 'sales.sender as customer_sender_name',
                    'sales.customer_contact_id', 'sales.delivery_notification', 'sales.payment_methods', 'sales.id as sales_id', 'deliveries.wanted_delivery_limit as wanted_delivery_date_end',
                    'deliveries.wanted_delivery_date as wanted_delivery_date', 'deliveries.products as product_name', 'sales_products.products_id')
                ->get()[0];

            if( $returnData->payment_methods == 'OK' ){
                DB::table('sales')->where('id', $salesId )->update([
                    'firstVisit' => 0
                ]);
            }

            MarketingAct::where('publish_id', $returnData->delivery_notification)->delete();

            $tempCrossSell = DB::table('cross_sell')->where('sales_id', $returnData->sales_id)->get();

            if(count($tempCrossSell) > 0){
                $returnData->cross_sell_id = $tempCrossSell[0]->product_id;
            }
            else{
                $returnData->cross_sell_id = 0;
            }

            $tempErrorLog = DB::table('is_bank_log')->where('code', '!=', '0000')->where('sale_id', $salesId )->orderBy('created_at', 'DESC')->take(1)->get();
            $returnData->errorMessage = 'Satış gerçekleşmedi. Banka veya kart ile ilgili teknik bir sorun var. Lütfen tekrar dene. Ya da 0212 212 0 282’den bizimle iletişime geç.';
            if( count($tempErrorLog) > 0 ){
                $tempErrorCode = $tempErrorLog[0]->code;

                if( $tempErrorCode == '1050' || $tempErrorCode == '1051' || $tempErrorCode == '1052' || $tempErrorCode == '1054' || $tempErrorCode == '1084' ||
                    $tempErrorCode == '04' || $tempErrorCode == '05' || $tempErrorCode == '07' || $tempErrorCode == '33' || $tempErrorCode == '37' ||
                    $tempErrorCode == '54' || $tempErrorCode == '82' || $tempErrorCode == '6000' ){
                    $returnData->errorMessage = "Banka Mesajı: Girmiş olduğunuz kart bilgileri hatalı. Kontrol edip tekrar deneyiniz.";
                }
                else if( $tempErrorCode == '51' ){
                    $returnData->errorMessage = "Banka Mesajı: İşlem yapmaya çalıştığınız karta ait limit müsait değil.";
                }
                else if( $tempErrorCode == '0057' ){
                    $returnData->errorMessage = "Banka Mesajı: Kartınızın işlem izni yok. Lütfen bankanızla iletişime geçin.";
                }
                else if( $tempErrorCode == 'NNNN' ){
                    $returnData->errorMessage = "Banka Mesajı: Kartınız 3D ile işlem için kayıtlı değil.";
                }
                else if( $tempErrorCode == 'E' ){
                    $returnData->errorMessage = "Lütfen 3D kullanmadan tekrar deneyin.";
                }
                else if( $tempErrorCode == '93' ){
                    $returnData->errorMessage = "Banka Mesajı: Kartınız internet üzerinden alışverişe kapalıdır.";
                }
            }
            else{

                $tempErrorLog = DB::table('error_logs')
                    ->where('related_variable', '!=', '')
                    ->where('error_code', '!=', '')
                    ->where('related_variable', $salesId )
                    ->orderBy('created_at', 'DESC')
                    ->take(1)
                    ->get();

                if( count($tempErrorLog) > 0 ){
                    $tempErrorCode = $tempErrorLog[0]->error_code;

                    if( $tempErrorCode == '4'  || $tempErrorCode == '14' || $tempErrorCode == '18' || $tempErrorCode == '33' ||
                        $tempErrorCode == '34' || $tempErrorCode == '36' || $tempErrorCode == '37' || $tempErrorCode == '41' ||
                        $tempErrorCode == '43' || $tempErrorCode == '55' || $tempErrorCode == '56' || $tempErrorCode == '82' || $tempErrorCode == '12' ){
                        $returnData->errorMessage = "Banka Mesajı: Girmiş olduğunuz kart bilgileri hatalı. Kontrol edip tekrar deneyiniz.";
                    }
                    else if( $tempErrorCode == '16' || $tempErrorCode == '51' ){
                        $returnData->errorMessage = "Banka Mesajı: İşlem yapmaya çalıştığınız karta ait limit müsait değil.";
                    }
                }
            }

            $returnData->contact_name = $returnData->contact_name . ' ' . $returnData->contact_surname;
            $returnData->name = $returnData->name . ' ' . $returnData->surname;

            $returnData->sales_id = $tempNumber;
            DB::commit();
            return response()->json(["status" => 1, "data" => $returnData], 200);

        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('getFailSaleInfo', $e->getCode(), $e->getMessage(), 'WS', $salesId);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    /*
     * Fatura sayfasından satış bilgileri sayfasına geçerken kullanıcının bilgilerinin kaydedildiği metottur.
     * Burada satış işleminde kullanılacak sipariş numarası döndürülür.
     */
    public function saveDataBeforeSale(beforeSaleRequest $request)
    {
        try {
            $tempContactNameSurname = logEventController::splitNameSurname(Request::get('contact_name'));
            $tempNameSurname = logEventController::splitNameSurname(Request::get('name'));
            $created = new Carbon(Request::get('wanted_delivery_date'));
            $limitDate = new Carbon(Request::get('wanted_delivery_date_end'));
            $now = Carbon::now();
            $tempCreatedDate = new Carbon(Request::get('wanted_delivery_date'));
            if ($created->hour == 18) {
                if ($now > $created) {
                    DB::rollback();
                    logEventController::logErrorToDB('saveDataBeforeSale', 'geçmiş tarihli sipariş', 'geçmiş tarihli sipariş', 'WS', '');
                    return response()->json(["status" => -1, "description" => 408], 400);
                }
            } else {
                if ($tempCreatedDate->addMinute(75) < $now) {
                    DB::rollback();
                    logEventController::logErrorToDB('saveDataBeforeSale', 'geçmiş tarihli sipariş', 'geçmiş tarihli sipariş', 'WS', '');
                    return response()->json(["status" => -1, "description" => 408], 400);
                }
            }

            $productInfo = Product::where('id', Request::input('product_id'))->get();
            $priceWithDiscount = str_replace(',', '.', $productInfo[0]->price);
            //$priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);

            if( $productInfo[0]->product_type == 2 ){
                $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100);
            }
            else{
                $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
            }

            $priceWithDiscount = number_format($priceWithDiscount, 2);
            parse_str($priceWithDiscount);
            $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

            DB::beginTransaction();

            $customerId = Customer::create(['mobile' => Request::get('mobile'),
                'email' => Request::get('mail'),
                'name' => $tempNameSurname[0],
                'surname' => $tempNameSurname[1]
            ])->id;

            $contactId = CustomerContact::create([
                'mobile' => Request::get('contact_mobile'),
                'address' => Request::get('contact_address'),
                'name' => $tempContactNameSurname[0],
                'surname' => $tempContactNameSurname[1],
                'customer_id' => $customerId,
                'delivery_location_id' => Request::get('city_id'),
                'customer_list' => false
            ])->id;

            $tempCityLocation = DB::table('delivery_locations')->where('id', Request::get('city_id') )->get();
            $tempUps = 0;
            $tempCityName = 'İstanbul';

            $tempRelatedCityId = 0;
            if( count($tempCityLocation ) > 0 ){
                if( $tempCityLocation[0]->continent_id == 'Ups' ){
                    $tempUps = 1;
                    $tempRelatedCityId = DB::table('ups_locations')->where('delivery_location_id', $tempCityLocation[0]->id)->get()[0]->related_city_id;
                }
                $tempCityName = $tempCityLocation[0]->city;
            }

            $salesId = Sale::create([
                'receiver_mobile' => Request::get('contact_mobile'),
                'receiver_address' => Request::get('contact_address'),
                'sender_name' => $tempNameSurname[0],
                'sender_surname' => $tempNameSurname[1],
                //'sender_mobile' => Request::get('mobile'),
                'sender_email' => Request::get('mail'),
                'id' => Request::get('sale_number'),
                'sum_total' => $priceWithDiscount,
                'card_message' => Request::get('card_message'),
                'payment_methods' => Request::get('paymentType'),
                'receiver' => Request::get('customer_receiver_name'),
                'sender' => Request::get('customer_sender_name'),
                'customer_contact_id' => $contactId,
                'vendors_id' => 1,
                'shops_id' => Request::get('web_site_id'),
                'courier_id' => 1,
                'delivery_locations_id' => Request::get('city_id'),
                'browser' => Request::get('browser'),
                'device' => Request::get('device'),
                'sales_ip' => Request::ip(),
                'product_price' => $productInfo[0]->price,
                'lang_id' => Request::get('lang_id'),
                'ups' => $tempUps,
                'related_city_id' => $tempRelatedCityId
            ])->id;

            Delivery::create([
                'wanted_delivery_date' => Request::get('wanted_delivery_date'),
                'wanted_delivery_limit' => $limitDate,
                'status' => '1',
                'products' => Request::get('product_name'),
                'sales_id' => $salesId
            ]);

            //if (Request::get('billing_type') == 1) {
            Billing::create([
                'billing_name' => $tempNameSurname[0],
                'billing_surname' => $tempNameSurname[1],
                'billing_address' => '',
                'billing_send' => 0,
                'small_city' => DeliveryLocation::where('id', Request::get('city_id'))->get()[0]->district,
                'city' => $tempCityName,
                'sales_id' => $salesId,
                'billing_type' => 1,
                'userBilling' => 0
            ]);
            //} else {
            //    Billing::create([
            //        'company' => Request::get('company'),
            //        'billing_address' => Request::get('billing_address'),
            //        'tax_office' => Request::get('tax_office'),
            //        'tax_no' => Request::get('tax_no'),
            //        'billing_send' => Request::get('billing_send'),
            //        'billing_type' => 2,
            //        'sales_id' => $salesId
            //    ]);
            //}

            DB::table('sales_products')
                ->insert([
                    'sales_id' => $salesId,
                    'products_id' => Request::input('product_id')
                ]);
            DB::commit();
            return response()->json(["status" => 1, "sale_number" => $salesId], 200);
        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('saveDataBeforeSale', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    /*
     * Satış işleminin gerçekleştirilmeye çalışıldığı metottur.
     */
    public function completeSale()
    {
        DB::beginTransaction();
        try {
            if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) > 0 ){
                DB::commit();
                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-details?orderId=' . Request::get('sale_number'));
                }
                else {
                    return redirect()->away($this->site_url . '/satis-ozet?orderId=' . Request::get('sale_number'));
                }
            }
            //$tempFiyongo = false;
            $tempCoupon = false;
            $tempLeftMoneyFromCoupon = 0.0;

            $tempCheckFlowerStatus = AdminPanelController::checkFlower(Request::get('sale_number'));

            if($tempCheckFlowerStatus == false){
                if( Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->count() == 0 ){
                    Sale::where('id', Request::get('sale_number'))->update([
                        'payment_methods' => 430
                    ]);
                }
                logEventController::logErrorToDB('completeSale', 'Geçmiş tarihli veya kapatılmış çiçek', 'Geçmiş tarihli veya kapatılmış çiçek', 'WS', '');
                DB::commit();
                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                if ($tempLangId == 'en') {
                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
                } else {
                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                }
            }

            if (Request::input('access_token')) {

                $useCoupon = false;
                $userId = Request::input('user_id');
                $customerId = Customer::where('user_id', $userId)->get()[0]->id;
                $now = Carbon::now();
                $productId = DB::table('sales_products')->where('sales_id', Request::get('sale_number'))->get()[0]->products_id;
                $productInfo = Product::where('id', $productId)->get();
                $priceWithDiscount = $productInfo[0]->price;
                $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);

                if( $productInfo[0]->product_type == 2 ){
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100);
                }
                else{
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                }

                //$priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                $tempPrice = $priceWithDiscount;
                $priceWithDiscount = number_format($priceWithDiscount, 2);
                parse_str($priceWithDiscount);
                parse_str($tempPrice);
                $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);
                $tempPrice = str_replace('.', ',', $tempPrice);
                if (Request::input('coupon_id')) {
                    MarketingAct::where('expiredDate', '<', $now)->update([
                        'valid' => '0'
                    ]);
                    $couponList = DB::table('customers')
                        ->join('customers_marketing_acts', 'customers.id', '=', 'customers_marketing_acts.customers_id')
                        ->join('marketing_acts', 'customers_marketing_acts.marketing_acts_id', '=', 'marketing_acts.id')
                        ->where('customers.id', $customerId)
                        ->where('used', '0')
                        ->where('valid', '1')
                        ->where('active', '1')
                        ->where('marketing_acts.id', Request::input('coupon_id'))
                        ->select('marketing_acts.id', 'marketing_acts.name', 'marketing_acts.type', 'marketing_acts.value', 'marketing_acts.description', 'marketing_acts.long_term', 'marketing_acts.product_coupon')->get();

                    if (count($couponList) > 0) {
                        if ($couponList[0]->product_coupon != null && $couponList[0]->product_coupon != 0) {
                            if (DB::table('productList_coupon')->where('coupon_id', $couponList[0]->product_coupon)->where('product_id', $productId)->count() == 0) {
                                if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) == 0 )
                                Sale::where('id', Request::get('sale_number'))->update([
                                    'payment_methods' => 419
                                ]);
                                logEventController::logErrorToDB('completeSale', 'Çiçek ve kupon eşleşmesi hatalı', 'Çiçek ve kupon eşleşmesi hatalı', 'WS', '');
                                DB::commit();
                                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                                if ($tempLangId == 'en') {
                                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
                                } else {
                                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                                }
                            }
                        }
                        $useCoupon = true;
                        $priceWithDiscount = str_replace(',', '.', $tempPrice);
                        if ($couponList[0]->type == 2) {
                            $priceWithDiscount = floatval($priceWithDiscount) * (100 - floatval($couponList[0]->value)) / 100;

                            if( $productInfo[0]->product_type == 3 || $productInfo[0]->product_type == 2 ){
                                Sale::where('id', Request::get('sale_number'))->update([
                                    'payment_methods' => 419
                                ]);
                                logEventController::logErrorToDB('completeSale', 'Ürün-kupon eşleşmesi hatalı', 'Ürün-kupon eşleşmesi hatalı', 'WS', '');
                                DB::commit();
                                $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                                if ($tempLangId == 'en') {
                                    return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
                                } else {
                                    return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                                }
                            }

                        } else {

                            $priceWithDiscount = $productInfo[0]->price;
                            $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);

                            if( $productInfo[0]->product_type == 2 ){
                                $priceWithDiscount = floatval(( floatval($priceWithDiscount) - floatval($couponList[0]->value) )* 108 / 100);
                            }
                            else{
                                $priceWithDiscount = floatval(( floatval($priceWithDiscount)  - floatval($couponList[0]->value))* 118 / 100);
                            }

                            if($priceWithDiscount <= 0){
                                $tempLeftMoneyFromCoupon = -1*$priceWithDiscount;
                                $priceWithDiscount = 0.0;
                            }

                            $tempCoupon = true;

                            //$priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                        }
                    } else {
                        if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) == 0 )
                        Sale::where('id', Request::get('sale_number'))->update([
                            'payment_methods' => 409
                        ]);
                        logEventController::logErrorToDB('completeSale', 'Hatalı kupon girildi.', 'Hatalı kupon girildi.', 'WS', '');
                        DB::commit();
                        $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                        if ($tempLangId == 'en') {
                            return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' . Request::get('sale_number'));
                        } else {
                            return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                        }
                    }
                }

                if ($useCoupon) {
                    $priceWithDiscount = number_format($priceWithDiscount, 2);
                    $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);
                    Sale::where('id', Request::get('sale_number'))->update([
                        'sum_total' => $priceWithDiscount
                    ]);
                    DB::table('marketing_acts_sales')
                        ->insert([
                            'sales_id' => Request::get('sale_number'),
                            'marketing_acts_id' => Request::input('coupon_id')
                        ]);
                }
            }

            if (Request::input('access_token')) {
                $price = $priceWithDiscount;
                Sale::where('id', Request::get('sale_number'))->update([
                    'sum_total' => $priceWithDiscount
                ]);
            } else {
                $productId = DB::table('sales_products')->where('sales_id', Request::get('sale_number'))->get()[0]->products_id;
                $productInfo = Product::where('id', $productId)->get();
                $priceWithDiscount = $productInfo[0]->price;
                $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);

                if( $productInfo[0]->product_type == 2 ){
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100);
                }
                else{
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                }

                //$priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                $priceWithDiscount = number_format($priceWithDiscount, 2);
                parse_str($priceWithDiscount);
                $price = str_replace('.', ',', $priceWithDiscount);
            }
            if(Request::input('cross_sell') > 0){

                $tempCrossProduct = DB::table('cross_sell_products')->where('id', Request::input('cross_sell'))->get()[0];
                $tempCrossSellPrice = floatval(str_replace(',', '.', $tempCrossProduct->price));
                $tempCrossSellDiscount = 0;
                $tempCrossSellTax = number_format(floatval($tempCrossSellPrice / 100.0 * 8.0), 2);
                if( $tempLeftMoneyFromCoupon > 0 ){
                    $tempCrossSellPrice = number_format($tempCrossSellPrice * 108 / 100 - $tempLeftMoneyFromCoupon, 2);
                }
                else{
                    $tempCrossSellPrice = number_format(floatval($tempCrossSellPrice * 108 / 100), 2);
                }

                if( $tempCrossSellPrice < 0 ){
                    $tempCrossSellDiscount = $tempCrossProduct->price;
                    $paymentWithoutMoney = true;
                    $tempCrossSellPrice = 0.0;
                }
                else{
                    $tempCrossSellDiscount = $tempLeftMoneyFromCoupon;
                    $paymentWithoutMoney = false;
                }

                //$tempCrossSellTax = number_format(floatval($tempCrossSellPrice / 100.0 * 8.0), 2);
                //$tempCrossSellTotal = $tempCrossSellPrice + $tempCrossSellTax;
                $tempCrossSellTotal = $tempCrossSellPrice;
                $price = floatval(str_replace(',', '.', $price)) + $tempCrossSellTotal;
                $price = str_replace('.', ',', $price);
                DB::table('cross_sell')->where('sales_id' , Request::get('sale_number'))->delete();
                DB::table('cross_sell')->insert([
                    'sales_id' => Request::get('sale_number'),
                    'product_id' => Request::input('cross_sell'),
                    'product_price' => $tempCrossProduct->price,
                    'discount' => $tempCrossSellDiscount,
                    'tax' => str_replace('.', ',', $tempCrossSellTax),
                    'total_price' => str_replace('.', ',', $tempCrossSellTotal)
                ]);
            }
            else{
                DB::table('cross_sell')->where('sales_id' , Request::get('sale_number'))->delete();
            }

            $tempPrice = $price;

            $price = str_replace(',', '.', $price);
            $price = floatval($price) * 100.00;
            parse_str($price);
            $tempArray = explode(".", $price);
            $price = $tempArray[0];
            $client = new \GuzzleHttp\Client();
            $strType = "sales";
            $strAmount = "100";
            $strInstallmentCount = "";
            $strOrderID = Request::get('sale_number');
            $strTerminalID = "10025261";
            $strTerminalID_ = "010025261";
            $strStoreKey = "696632333569663233356966323335696632333569663233";
            $strProvisionPassword = "Hakan1234";
            $strSuccessURL = $this->backend_url . "/call-back-success";
            $strErrorURL = $this->backend_url . "/error-call-back";
            $SecurityData = strtoupper(sha1($strProvisionPassword . $strTerminalID_));
            $cardNumber = str_replace('-', '', Request::input('card_no'));

            /*if(
                substr($cardNumber, 0, 6) == '979217' ||
                substr($cardNumber, 0, 6) == '979280' ||
                substr($cardNumber, 0, 6) == '979210' ||
                substr($cardNumber, 0, 6) == '979212' ||
                substr($cardNumber, 0, 6) == '979244' ||
                substr($cardNumber, 0, 6) == '650052' ||
                substr($cardNumber, 0, 6) == '650170' ||
                substr($cardNumber, 0, 6) == '979209' ||
                substr($cardNumber, 0, 6) == '979223' ||
                substr($cardNumber, 0, 6) == '979206' ||
                substr($cardNumber, 0, 6) == '979207' ||
                substr($cardNumber, 0, 6) == '979208' ||
                substr($cardNumber, 0, 6) == '979236' ||
                substr($cardNumber, 0, 6) == '979204' ||
                substr($cardNumber, 0, 6) == '650082' ||
                substr($cardNumber, 0, 6) == '650092' ||
                substr($cardNumber, 0, 6) == '650173' ||
                substr($cardNumber, 0, 6) == '650456' ||
                substr($cardNumber, 0, 6) == '650987' ||
                substr($cardNumber, 0, 6) == '979233' ||
                substr($cardNumber, 0, 6) == '657366' ||
                substr($cardNumber, 0, 6) == '657998' ||
                substr($cardNumber, 0, 6) == '650161' ||
                substr($cardNumber, 0, 6) == '979215' ||
                substr($cardNumber, 0, 6) == '979241' ||
                substr($cardNumber, 0, 6) == '979242' ||
                substr($cardNumber, 0, 6) == '979202' ||
                substr($cardNumber, 0, 6) == '979203' ||
                substr($cardNumber, 0, 6) == '365770' ||
                substr($cardNumber, 0, 6) == '365771' ||
                substr($cardNumber, 0, 6) == '365772' ||
                substr($cardNumber, 0, 6) == '365773' ||
                substr($cardNumber, 0, 6) == '654997' ||
                substr($cardNumber, 0, 6) == '979240' ||
                substr($cardNumber, 0, 6) == '979213' ||
                substr($cardNumber, 0, 6) == '979227' ||
                substr($cardNumber, 0, 6) == '979216' ||
                substr($cardNumber, 0, 6) == '979218' ||
                substr($cardNumber, 0, 6) == '979235' ||
                substr($cardNumber, 0, 6) == '979248' ||
                substr($cardNumber, 0, 6) == '979277' ||
                substr($cardNumber, 0, 6) == '979254' ||
                substr($cardNumber, 0, 6) == '979278' ||
                substr($cardNumber, 0, 6) == '979249' ||
                substr($cardNumber, 0, 6) == '979243' ||
                substr($cardNumber, 0, 6) == '979250' ||
                substr($cardNumber, 0, 6) == '979266' ||
                substr($cardNumber, 0, 6) == '979260' ||
                substr($cardNumber, 0, 6) == '979261' ||
                substr($cardNumber, 0, 6) == '979262'
            ){

                $tempPrice = str_replace(',', '.', $tempPrice);

                if( floatval($tempPrice) > 100){

                    $tempPrice = floatval($tempPrice) - 30;

                    $tempPrice = floatval($tempPrice) * 100.00;
                    parse_str($tempPrice);
                    $tempArray = explode(".", $tempPrice);
                    $tempPrice = $tempArray[0];

                    $price = $tempPrice;

                    DB::table('sales')->where('id', Request::get('sale_number'))->update([
                        'IsTroyCard' => 1,
                        'paymentAmount' => $tempPrice
                    ]);

                }
                else{
                    DB::table('sales')->where('id', Request::get('sale_number'))->update([
                        'IsTroyCard' => 0
                    ]);
                }

            }
            else{
                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'IsTroyCard' => 0
                ]);
            }*/

            $HashData = strtoupper(sha1($strTerminalID . $strOrderID . $price . $strSuccessURL . $strErrorURL . $strType . $strInstallmentCount . $strStoreKey . $SecurityData));

            $data = [
                "txnmotoind" => "Y",
                "secure3dsecuritylevel" => "3D",
                "cardnumber" => $cardNumber,
                "refreshtime" => "60",
                "lang" => "tr",
                "cardexpiredatemonth" => Request::input('card_month'),
                "cardexpiredateyear" => Request::input('card_year'),
                "cardcvv2" => Request::input('card_cvv'),
                "cardholder" => Request::input('card_holder'),
                "mode" => "PROD",
                "version" => "v1.0",
                "txntype" => "sales",
                "txnamount" => $price,
                "txncurrencycode" => "949",
                "txninstallmentcount" => "",
                "terminaluserid" => "TMYTKLI",
                "orderid" => Request::get('sale_number'),
                "customeripaddress" => $_SERVER['REMOTE_ADDR'],
                "customeremailaddress" => Request::get('mail'),
                "terminalid" => "10025261",
                "terminalprovuserid" => "PROVAUT",
                "terminalid_" => "010025261",
                "terminalmerchantid" => "9384072",
                "successurl" => $strSuccessURL,
                "errorurl" => $strErrorURL,
                "securitydata" => $SecurityData,
                "secure3dhash" => $HashData,
                "companyname" => "Bloomandfresh.com"
            ];
            if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) == 0 )
            Sale::where('id', Request::get('sale_number'))->update([
                'payment_methods' => "Banka sayfasında.",
                'created_at' => Carbon::now()
            ]);
            Delivery::where('sales_id', Request::get('sale_number'))->update([
                'created_at' => Carbon::now()
            ]);

            if($tempCoupon){
                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'payment_type' => 'COUPON'
                ]);
            }
            else{
                DB::table('sales')->where('id', Request::get('sale_number'))->update([
                    'payment_type' => 'POS'
                ]);
            }

            DB::commit();
            if(IsBankSalesController::checkMaximum($cardNumber)){
                return IsBankSalesController::isBankPos(Request::get('sale_number'), $cardNumber, Request::input('card_year') . Request::input('card_month'), $price ,true, Request::input('card_cvv'), Request::input('cross_sell'));
            }
            return view('before3D', compact('data'));
        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('before3DSecure', $e->getCode(), $e->getMessage(), 'WS', '');
            if( count(Sale::where('id' ,Request::get('sale_number'))->where('payment_methods' , 'OK')->get()) == 0 )
            Sale::where('id', Request::get('sale_number'))->update([
                'payment_methods' => 400
            ]);

            return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));

        }
    }

}