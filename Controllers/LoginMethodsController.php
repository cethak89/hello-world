<?php namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\deleteContactListRequest;
use App\Http\Requests\deleteContactRequest;
use App\Http\Requests\newsLetterRequest;
use App\Http\Requests\saveDateBeforeSaleRequest;
use App\Http\Requests\setContactListRequest;
use App\Http\Requests\updateUserRequest;
use App\Http\Requests\setPersonalBillingRequest;
use App\Http\Requests\setCompanyBillingRequest;
use App\Http\Requests\updateCompanyBillingRequest;
use App\Http\Requests\updatePersonalBillingRequest;
use App\Http\Requests\setReminderRequest;
use App\Http\Requests\updateReminderRequest;
use App\Http\Requests\userCompleteSaleRequest;
use App\Http\Requests\userSaveDateBeforeSaleRequest;
use Carbon\Carbon;
use DB;
use App\Models\Newsletter;
use Request;
use App\Models\DeliveryLocation;
use App\Models\MarketingAct;
use App\Models\Customer;
use App\Models\Image;
use App\Models\Product;
use App\Models\CustomerContact;
use App\Models\Sale;
use App\Models\Delivery;
use App\Models\Billing;
use App\Models\Shop;
use App\Models\ErrorLog;
use App\Models\User;
use App\Models\CustomerBilling;
use App\Models\Reminder;
use Session;
use Authorizer;

class LoginMethodsController extends Controller
{
    public  $site_url = 'https://bloomandfresh.com';
    //public  $site_url = 'http://188.166.86.116';
    //public $backend_url = 'http://188.166.86.116:3000';
    public $backend_url = 'https://everybloom.com';

    public function getCompanySalesAll(){
        $userId = \Authorizer::getResourceOwnerId();
        $tempUserInfo = DB::table('users')->where('id', $userId)->get()[0];

        $tempSales = DB::table('sales')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('users', 'customers.user_id', '=', 'users.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status' , '<>' ,  '4')
            ->where('users.company_info_id' , $tempUserInfo->company_info_id )
            ->select('sales.id', 'sales.created_at', 'sales.sender_name', 'sales.sender_surname', 'customer_contacts.name',
                'customer_contacts.surname', 'deliveries.products', 'sales.sum_total', 'deliveries.delivery_date', 'deliveries.picker')
            ->get();

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        foreach($tempSales as $sale){
            $sale->sum_total = (floatval($sale->sum_total) / 118) * 100;
            $sale->sum_total = number_format($sale->sum_total  , 2) ;
            $sale->sum_total = str_replace('.', '!', $sale->sum_total);
            $sale->sum_total = str_replace(',', '.', $sale->sum_total);
            $sale->sum_total = str_replace('!', ',', $sale->sum_total);
            $requestDate = new Carbon($sale->created_at);
            //$sale->created_at = $requestDate->formatLocalized('%d %b %Y');
            $sale->created_at = $requestDate->formatLocalized('%d %b %Y') . ' ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            if( $sale->delivery_date == '0000-00-00 00:00:00'){
                $sale->delivery_date = '------';
            }
            else{
                $requestDate = new Carbon($sale->delivery_date);
                $sale->delivery_date = $requestDate->formatLocalized('%d %b %Y') . ' ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            }
        }
        return response()->json(["status" => 1, "sales" => $tempSales], 200);
    }

    public function checkBurberryCoupon(){
        $userId = \Authorizer::getResourceOwnerId();
        $customerId = Customer::where('user_id', $userId)->get()[0]->id;

        $tempData = DB::table('marketing_acts')
            ->join('customers_marketing_acts', 'marketing_acts.id', '=', 'customers_marketing_acts.marketing_acts_id')
            ->where('customers_marketing_acts.customers_id' , $customerId )
            ->where('marketing_acts.valid' , 1 )
            ->where('marketing_acts.used' , 0 )
            ->where('marketing_acts.product_coupon' , Request::get('flower_id') )->get();

        if(count($tempData) == 0){
            return response()->json(["status" => 0], 400);
        }
        else
            return response()->json(["status" => 1], 200);
    }

    public function addBurberryCoupon(){
        $userId = \Authorizer::getResourceOwnerId();
        $customerId = Customer::where('user_id', $userId)->get()[0]->id;

        $couponList = MarketingAct::where('publish_id', Request::get('coupon_id'))
            ->where('used', '0')
            ->where('active', '0')
            ->where('valid', '1')
            ->where('product_coupon', Request::get('flower_id'))
            ->select('image_type', 'id', 'name', 'type', 'value', 'description')
            ->get();

        if (count($couponList) > 0) {
            MarketingAct::where('id', $couponList[0]->id)->update([
                'active' => 1
            ]);

            DB::table('customers_marketing_acts')->insert([
                'marketing_acts_id' => $couponList[0]->id,
                'customers_id' => $customerId
            ]);
            return response()->json(["status" => 1], 200);
        }
        else
            return response()->json(["status" => 0], 400);

    }

    public function addBillingInfo(){
        try{
            $userId = \Authorizer::getResourceOwnerId();
            $customerId = Customer::where('user_id', $userId)->get()[0]->id;
            $tempControl = DB::table('customers')
                ->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
                ->join('sales', 'customer_contacts.id', '=', 'sales.customer_contact_id')
                ->where('sales.id' , Request::get('sales_id') )
                ->where('customers.id' , $customerId )
                ->get();



            if(count($tempControl) > 0){
                $tempNameSurname = logEventController::splitNameSurname( Request::get('name') );
                if (Request::get('billing_type') == 1) {
                    DB::table('billings')->where('sales_id' ,  Request::get('sales_id') )->update([
                        'billing_name' => $tempNameSurname[0],
                        'billing_surname' => $tempNameSurname[1],
                        'billing_address' => Request::get('billing_address'),
                        'billing_send' => 1,
                        'small_city' => Request::get('small_city'),
                        'city' => Request::get('city'),
                        'sales_id' => Request::get('sales_id'),
                        'tc' => Request::get('tc'),
                        'billing_type' => 1,
                        'userBilling' => 1,
                        'tax_office' => "",
                        'tax_no' => "",
                        'company' => ""
                    ]);
                } else {
                    DB::table('billings')->where('sales_id' ,  Request::get('sales_id') )->update([
                        'company' => Request::get('company'),
                        'billing_address' => Request::get('billing_address'),
                        'tax_office' => Request::get('tax_office'),
                        'tax_no' => Request::get('tax_no'),
                        'billing_send' => 1,
                        'small_city' => Request::get('small_city'),
                        'city' => Request::get('city'),
                        'billing_type' => 2,
                        'sales_id' => Request::get('sales_id'),
                        'userBilling' => 1,
                        'billing_name' => "",
                        'billing_surname' => "",
                        'tc' => ""
                    ]);
                }

                if( DB::table('customer_billings')->where('customers_id', $customerId )->count() == 0 ){
                    if (Request::get('billing_type') == 1) {
                        DB::table('customer_billings')->insert([
                            'billing_name' =>  $tempNameSurname[0],
                            'billing_surname' => $tempNameSurname[1],
                            'personal_address' => Request::get('billing_address'),
                            'small_city' => Request::get('small_city'),
                            'city' => Request::get('city'),
                            'tc' => Request::get('tc'),
                            'billing_type' => 1,
                            'customers_id' => $customerId
                        ]);
                    }
                    else{
                        DB::table('customer_billings')->insert([
                            'company' => Request::get('company'),
                            'billing_address' => Request::get('billing_address'),
                            'tax_office' => Request::get('tax_office'),
                            'tax_no' => Request::get('tax_no'),
                            'billing_type' => 2,
                            'customers_id' => $customerId
                        ]);
                    }
                }

                return response()->json(["status" => 1, "description" => 200], 200);
            }
            else{
                return response()->json(["status" => -1, "description" => 400], 400);
            }

        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'addBillingInfo',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function updateMobileNumber(){
        try{
            $userId = \Authorizer::getResourceOwnerId();
            $customerId = Customer::where('user_id', $userId)->get()[0]->id;
            $tempControl = DB::table('customers')
                ->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
                ->join('sales', 'customer_contacts.id', '=', 'sales.customer_contact_id')
                ->where('sales.id' , Request::get('id') )
                ->where('customers.id' , $customerId )
                ->get();

            if(count($tempControl) > 0){
                Sale::where('id' , Request::get('id'))->update([
                    'sender_mobile' => Request::get('mobile')
                ]);

                Customer::where('id' , $customerId )->update([
                    'mobile' => Request::get('mobile')
                ]);
                return response()->json(["status" => 1, "description" => 200], 200);
            }
            else{
                return response()->json(["status" => -1, "description" => 400], 400);
            }
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'updateMobileNumber',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function logUserReceiverInfo()
    {
        $tempNameSurname = logEventController::splitNameSurname( Request::get('name') );

        try {
            $userId = \Authorizer::getResourceOwnerId();
            $customerId = Customer::where('user_id', $userId)->get()[0]->id;
            //$customerId = Request::get('id');
            DB::table('log_receiver')->insert([
                'register_ip' => Request::ip(),
                'customer_id' => $customerId,
                'name' => $tempNameSurname[0],
                'surname' => $tempNameSurname[1],
                'address' => Request::get('address'),
                'city' => Request::get('city'),
                'delivery_date' => Request::get('delivery_date'),
                'phone' => Request::get('phone'),
                'product_name' => Request::get('product_name')
            ]);
            return response()->json(["status" => 1, "description" => 201], 200);
        }
        catch (\Exception $e) {
                ErrorLog::create([
                    'method_name' => 'logUserReceiverInfo',
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                    'type' => 'WS'
                ]);
                // something went wrong whilst attempting to encode the token
                return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function setCoupon()
    {
        try {
            $userId = \Authorizer::getResourceOwnerId();
            $customerId = Customer::where('user_id', $userId)->get()[0]->id;
            $now = Carbon::now();

            //MarketingAct::where('expiredDate', '<', $now)->update([
            //    'valid' => '0'
            //]);

            $couponListOne = MarketingAct::where('publish_id', Request::get('coupon_id'))
                ->where('used', '0')
                //->where('active', '0')
                ->where('valid', '1')
                ->select('image_type', 'id', 'name', 'type', 'value', 'description')
                ->get();

            if( count($couponListOne) == 0 ){
                $tempCoupon = DB::table('daily_coupons')
                    ->where('code', Request::get('coupon_id') )
                    ->where('start_date', '<', $now)
                    ->where('end_date', '>', $now)
                    ->where('active', 1)
                    ->get();

                //dd($tempCoupon);

                if( count($tempCoupon) > 0 ){

                    if( DB::table('marketing_acts')->join('customers_marketing_acts', 'marketing_acts.id', '=' ,'customers_marketing_acts.marketing_acts_id')->where('customers_marketing_acts.customers_id', $customerId )->where('daily_coupon_id', $tempCoupon[0]->id )->count() > 0 ){

                        return response()->json(["status" => -1, "description" => 406], 400);
                    }

                    $tempImageTypes = '';
                    if( $tempCoupon[0]->type == 1 ){
                        $tempImageTypes = 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $tempCoupon[0]->value . 'TL.png';
                    }
                    else{
                        $tempImageTypes = 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $tempCoupon[0]->value . '_indirim.png';
                    }

                    $tempId = DB::table('marketing_acts')->insertGetId([
                        'long_term' => Null,
                        'publish_id' => str_random(20),
                        'name' => $tempCoupon[0]->name,
                        'type' => $tempCoupon[0]->type,
                        'value' => $tempCoupon[0]->value,
                        'used' => 0,
                        'active' => 0,
                        'image_type' => $tempImageTypes,
                        'valid' => 1,
                        'special_type' => 0,
                        'expiredDate' => $tempCoupon[0]->end_date,
                        'description' => $tempCoupon[0]->description,
                        'administrator_id' => 1,
                        'product_coupon' => Null,
                        'prime' => 0,
                        'daily_coupon_id' => $tempCoupon[0]->id
                    ]);

                    $couponListOne = MarketingAct::where('id', $tempId)
                        ->where('used', '0')
                        //->where('active', '0')
                        ->where('valid', '1')
                        ->select('image_type', 'id', 'name', 'type', 'value', 'description')
                        ->get();
                }
            }

            if (count($couponListOne) > 0) {

                if( DB::table('customers_marketing_acts')->where('marketing_acts_id', $couponListOne[0]->id)->where('customers_id', $customerId )->count() > 0 ){

                    return response()->json(["status" => -1, "description" => 406], 400);
                }

                MarketingAct::where('id', $couponListOne[0]->id)->update([
                    'active' => 1
                ]);

                DB::table('customers_marketing_acts')->insert([
                    'marketing_acts_id' => $couponListOne[0]->id,
                    'customers_id' => $customerId
                ]);

                //MarketingAct::where('publish_id', Request::get('coupon_id'))->update([
                //    'valid' => '',
                //    'used' => '1'
                //]);

                //Get

                $userId = \Authorizer::getResourceOwnerId();
                $customerId = Customer::where('user_id', $userId)->get()[0]->id;
                $now = Carbon::now();

                //MarketingAct::where('expiredDate', '<', $now)->update([
                //    'valid' => '0'
                //]);

                //get Prime List
                if(DB::table('users')->where('id', $userId)->get()[0]->prime > 0){
                    //Check For Friday
                    if($now->dayOfWeek == 5){
                        if(DB::table('marketing_acts')->where('prime' , 1)
                                ->join('customers_marketing_acts', 'customers_marketing_acts.marketing_acts_id', '=', 'marketing_acts.id')
                                ->where('expiredDate', '>', $now)->where('customers_marketing_acts.customers_id', $customerId)->count() == 0){

                            $publish_id = 'PRM' . str_random(6);
                            while(count(MarketingAct::where('publish_id' , $publish_id)->get()) != 0){
                                $publish_id = 'PRM' . str_random(6);
                            }
                            $couponValue = DB::table('primeValues')->get()[0];
                            $endOfDay = Carbon::now();
                            $endOfDay->endOfDay();
                            $publish_id =  strtoupper($publish_id);
                            $id = DB::table('marketing_acts')->insertGetId(
                                [
                                    'publish_id' => $publish_id,
                                    'name' => $couponValue->name,
                                    'type' => '2',
                                    'value' => $couponValue->friday_value,
                                    'valid' => 1,
                                    'expiredDate' => $endOfDay,
                                    'used' => 0,
                                    'long_term' => 1,
                                    'active' => 1,
                                    'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/15_indirim.png',
                                    'administrator_id' => '1',
                                    'description' => $couponValue->description,
                                    'prime' => 1
                                ]
                            );

                            DB::table('customers_marketing_acts')->insert([
                                'marketing_acts_id' => $id,
                                'customers_id' => $customerId
                            ]);
                        }
                    }

                    $startOfMonth = Carbon::now();
                    $startOfMonth->day(0);
                    $startOfMonth->hour(0);
                    $startOfMonth->minute(0);
                    $startOfMonth->second(0);

                    $tempCount = DB::table('users')
                        ->join('customers', 'users.id', '=', 'customers.user_id')
                        ->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
                        ->join('sales', 'customer_contacts.id', '=', 'sales.customer_contact_id')
                        ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                        ->orderBy('deliveries.wanted_delivery_date', 'desc')
                        ->where('users.id', '=', $userId)
                        ->where('sales.payment_methods', '=', 'OK')
                        ->where('sales.created_at', '>', $startOfMonth)
                        ->where('deliveries.status', '!=', '4')->count();

                    if(DB::table('marketing_acts')->where('prime' , 2)
                            ->join('customers_marketing_acts', 'customers_marketing_acts.marketing_acts_id', '=', 'marketing_acts.id')
                            ->where('expiredDate', '>', $now)->where('customers_marketing_acts.customers_id', $customerId)->count() == 0 && $tempCount == 0 )
                    {

                        $publish_id = 'PRM' . str_random(6);
                        while(count(MarketingAct::where('publish_id' , $publish_id)->get()) != 0){
                            $publish_id = 'PRM' . str_random(6);
                        }
                        $couponValue = DB::table('primeValues')->get()[0];
                        $endOfMonth = Carbon::now();
                        $endOfMonth->endOfMonth();
                        $publish_id =  strtoupper($publish_id);
                        $id = DB::table('marketing_acts')->insertGetId(
                            [
                                'publish_id' => $publish_id,
                                'name' => $couponValue->month_name,
                                'type' => '2',
                                'value' => $couponValue->month_value,
                                'valid' => 1,
                                'expiredDate' => $endOfMonth,
                                'used' => 0,
                                'long_term' => 0,
                                'active' => 1,
                                'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/15_indirim.png',
                                'administrator_id' => '1',
                                'description' => $couponValue->month_description,
                                'prime' => 2
                            ]
                        );

                        DB::table('customers_marketing_acts')->insert([
                            'marketing_acts_id' => $id,
                            'customers_id' => $customerId
                        ]);

                    }
                }

                $couponList = DB::table('customers')
                    ->join('customers_marketing_acts', 'customers.id', '=', 'customers_marketing_acts.customers_id')
                    ->join('marketing_acts', 'customers_marketing_acts.marketing_acts_id', '=', 'marketing_acts.id')
                    ->where('customers.id', $customerId)
                    ->where('used', '0')
                    ->where('valid', '1')
                    ->select('marketing_acts.description', 'marketing_acts.image_type', 'marketing_acts.id', 'marketing_acts.name', 'marketing_acts.type', 'marketing_acts.value', 'marketing_acts.description' , 'marketing_acts.product_coupon', 'marketing_acts.special_type')->get();

                foreach($couponList as $coupon){

                    if(Request::input('lang_id') != 'tr'){
                        $tempLandContent = DB::table('bnf_content')->where('content' ,  $coupon->description )->where('lang_id' , 'tr' )->get();
                        if(count($tempLandContent) > 0){
                            $tempDescription = DB::table('bnf_content')->where( 'id' , $tempLandContent[0]->id )->where('lang_id' , Request::input('lang_id') )->get();
                            if(count($tempDescription) > 0){
                                $coupon->description = $tempDescription[0]->content;
                            }
                        }

                        $tempLandContent = DB::table('bnf_content')->where('content' ,  $coupon->name )->where('lang_id' , 'tr' )->get();
                        if(count($tempLandContent) > 0){
                            $tempDescription = DB::table('bnf_content')->where( 'id' , $tempLandContent[0]->id )->where('lang_id' , Request::input('lang_id') )->get();
                            if(count($tempDescription) > 0){
                                $coupon->name = $tempDescription[0]->content;
                            }
                        }

                    }

                    if($coupon->product_coupon != null && $coupon->product_coupon != 0){
                        $coupon->flowers = DB::table('productList_coupon')
                            ->join('products', 'productList_coupon.product_id', '=', 'products.id')
                            ->select('products.id' , 'products.name' , 'productList_coupon.limit_start' , 'productList_coupon.limit_end')
                            ->where('coupon_id',$coupon->product_coupon)->get();
                    }
                }

            } else {
                return response()->json(["status" => -1, "description" => 406], 400);
            }
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'setCoupon',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            // something went wrong whilst attempting to encode the token
            return response()->json(["status" => -1, "description" => 406], 400);
        }
        return response()->json(["status" => 1, "coupon" => $couponListOne , "couponList" => $couponList  ], 200);
    }

    public function getCouponList()
    {
        try {
            $userId = \Authorizer::getResourceOwnerId();
            $customerId = Customer::where('user_id', $userId)->get()[0]->id;
            $now = Carbon::now();

            //MarketingAct::where('expiredDate', '<', $now)->update([
            //    'valid' => '0'
            //]);

            //get Prime List
            if(DB::table('users')->where('id', $userId)->get()[0]->prime > 0){
                //Check For Friday
                if($now->dayOfWeek == 5){
                    if(DB::table('marketing_acts')->where('prime' , 1)
                            ->join('customers_marketing_acts', 'customers_marketing_acts.marketing_acts_id', '=', 'marketing_acts.id')
                            ->where('expiredDate', '>', $now)->where('customers_marketing_acts.customers_id', $customerId)->count() == 0){

                        $publish_id = 'PRM' . str_random(6);
                        while(count(MarketingAct::where('publish_id' , $publish_id)->get()) != 0){
                            $publish_id = 'PRM' . str_random(6);
                        }
                        $couponValue = DB::table('primeValues')->get()[0];
                        $endOfDay = Carbon::now();
                        $endOfDay->endOfDay();
                        $publish_id =  strtoupper($publish_id);
                        $id = DB::table('marketing_acts')->insertGetId(
                            [
                                'publish_id' => $publish_id,
                                'name' => $couponValue->name,
                                'type' => '2',
                                'value' => $couponValue->friday_value,
                                'valid' => 1,
                                'expiredDate' => $endOfDay,
                                'used' => 0,
                                'long_term' => 1,
                                'active' => 1,
                                'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/15_indirim.png',
                                'administrator_id' => '1',
                                'description' => $couponValue->description,
                                'prime' => 1
                            ]
                        );

                        DB::table('customers_marketing_acts')->insert([
                            'marketing_acts_id' => $id,
                            'customers_id' => $customerId
                        ]);
                    }
                }

                $startOfMonth = Carbon::now();
                $startOfMonth->day(0);
                $startOfMonth->hour(0);
                $startOfMonth->minute(0);
                $startOfMonth->second(0);

                $tempCount = DB::table('users')
                    ->join('customers', 'users.id', '=', 'customers.user_id')
                    ->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
                    ->join('sales', 'customer_contacts.id', '=', 'sales.customer_contact_id')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->orderBy('deliveries.wanted_delivery_date', 'desc')
                    ->where('users.id', '=', $userId)
                    ->where('sales.payment_methods', '=', 'OK')
                    ->where('sales.created_at', '>', $startOfMonth)
                    ->where('deliveries.status', '!=', '4')->count();

                if(DB::table('marketing_acts')->where('prime' , 2)
                        ->join('customers_marketing_acts', 'customers_marketing_acts.marketing_acts_id', '=', 'marketing_acts.id')
                        ->where('expiredDate', '>', $now)->where('customers_marketing_acts.customers_id', $customerId)->count() == 0 && $tempCount == 0 )
                {

                    $publish_id = 'PRM' . str_random(6);
                    while(count(MarketingAct::where('publish_id' , $publish_id)->get()) != 0){
                        $publish_id = 'PRM' . str_random(6);
                    }
                    $couponValue = DB::table('primeValues')->get()[0];
                    $endOfMonth = Carbon::now();
                    $endOfMonth->endOfMonth();
                    $publish_id =  strtoupper($publish_id);
                    $id = DB::table('marketing_acts')->insertGetId(
                        [
                            'publish_id' => $publish_id,
                            'name' => $couponValue->month_name,
                            'type' => '2',
                            'value' => $couponValue->month_value,
                            'valid' => 1,
                            'expiredDate' => $endOfMonth,
                            'used' => 0,
                            'long_term' => 0,
                            'active' => 1,
                            'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/15_indirim.png',
                            'administrator_id' => '1',
                            'description' => $couponValue->month_description,
                            'prime' => 2
                        ]
                    );

                    DB::table('customers_marketing_acts')->insert([
                        'marketing_acts_id' => $id,
                        'customers_id' => $customerId
                    ]);

                }
            }

            $couponList = DB::table('customers')
                ->join('customers_marketing_acts', 'customers.id', '=', 'customers_marketing_acts.customers_id')
                ->join('marketing_acts', 'customers_marketing_acts.marketing_acts_id', '=', 'marketing_acts.id')
                ->where('customers.id', $customerId)
                ->where('used', '0')
                ->where('valid', '1')
                ->select('marketing_acts.description', 'marketing_acts.image_type', 'marketing_acts.id', 'marketing_acts.name', 'marketing_acts.type', 'marketing_acts.value', 'marketing_acts.description' , 'marketing_acts.product_coupon', 'marketing_acts.special_type')->get();

            foreach($couponList as $coupon){

                if(Request::input('lang_id') != 'tr'){
                    $tempLandContent = DB::table('bnf_content')->where('content' ,  $coupon->description )->where('lang_id' , 'tr' )->get();
                    if(count($tempLandContent) > 0){
                        $tempDescription = DB::table('bnf_content')->where( 'id' , $tempLandContent[0]->id )->where('lang_id' , Request::input('lang_id') )->get();
                        if(count($tempDescription) > 0){
                            $coupon->description = $tempDescription[0]->content;
                        }
                    }

                    $tempLandContent = DB::table('bnf_content')->where('content' ,  $coupon->name )->where('lang_id' , 'tr' )->get();
                    if(count($tempLandContent) > 0){
                        $tempDescription = DB::table('bnf_content')->where( 'id' , $tempLandContent[0]->id )->where('lang_id' , Request::input('lang_id') )->get();
                        if(count($tempDescription) > 0){
                            $coupon->name = $tempDescription[0]->content;
                        }
                    }

                }

                if($coupon->product_coupon != null && $coupon->product_coupon != 0){
                    $coupon->flowers = DB::table('productList_coupon')
                        ->join('products', 'productList_coupon.product_id', '=', 'products.id')
                        ->select('products.id' , 'products.name' , 'productList_coupon.limit_start' , 'productList_coupon.limit_end')
                        ->where('coupon_id',$coupon->product_coupon)->get();
                }
            }

        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'getCouponList',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            // something went wrong whilst attempting to encode the token
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return response()->json(["status" => 1, "coupon_list" => $couponList], 200);
    }

    public function updateContactList()
    {
        $userId = \Authorizer::getResourceOwnerId();
        $customerId = Customer::where('user_id', $userId)->get()[0]->id;
        try {
            $affectedRows = CustomerContact::where('id', '=', Request::input('contact_id'))->update([
                'mobile' => Request::get('contact_mobile'),
                'address' => Request::get('contact_address'),
                'name' => Request::get('contact_name'),
                'surname' => Request::get('contact_surname'),
                'email' => Request::get('contact_email'),
                'customer_id' => $customerId,
                'delivery_location_id' => Request::get('city_id')
            ]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'updateContactList',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return $affectedRows;
    }

    public function userChangePassword()
    {
        try {
            $userId = \Authorizer::getResourceOwnerId();
            $mail = User::where('id', $userId)->get()[0]->email;
            if (\Auth::validate(['email' => $mail, 'password' => Request::input('old_password')])) {
                User::where('email', $mail)->update([
                    'password' => bcrypt(Request::get('new_password'))
                ]);
            } else {
                return response()->json(["status" => -2, "description" => 407], 400);
            }
            return ["status" => 1, "description" => "Basarili"];

        } catch (\Exception $e) {
            DB::rollback();
            ErrorLog::create([
                'method_name' => 'userChangePassword',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }

    }

    public function saveDataBeforeSale()
    {
        try {
            $tempContactNameSurname = logEventController::splitNameSurname( Request::get('contact_name') );
            $tempNameSurname = logEventController::splitNameSurname( Request::get('contact_name') );
            $userId = \Authorizer::getResourceOwnerId();
            $created = new Carbon(Request::get('wanted_delivery_date'));
            $limitDate = new Carbon(Request::get('wanted_delivery_date_end'));
            $now = Carbon::now();

            $tempCreatedDate = new Carbon(Request::get('wanted_delivery_date'));
            if($created->hour == 18){
                if($now > $created ){
                    DB::rollback();
                    logEventController::logErrorToDB('saveDataBeforeSale','geçmiş tarihli sipariş','geçmiş tarihli sipariş','WS','');
                    return response()->json(["status" => -1, "description" => 408], 400);
                }
            }
            else{
                if($tempCreatedDate->addMinute(75)  < $now  ){
                    DB::rollback();
                    logEventController::logErrorToDB('saveDataBeforeSale','geçmiş tarihli sipariş','geçmiş tarihli sipariş','WS','');
                    return response()->json(["status" => -1, "description" => 408], 400);
                }
            }

            $productInfo = Product::where('id', Request::input('product_id'))->get();
            DB::beginTransaction();
            $customerId = Customer::where('user_id', $userId)->get()[0]->id;
            $tempCustomer = Customer::where('user_id', $userId)->get()[0];

            if(!Customer::where('id' , $customerId)->get()[0]->mobile){
                Customer::where('id' , $customerId)->update([
                    'mobile' => Request::get('mobile')
                ]);
            }

            $priceWithDiscount = $productInfo[0]->price;
            $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);

            if (Request::get('contact_id')) {
                $contactId = Request::get('contact_id');
            } else {
                $contactId = CustomerContact::create([
                    'mobile' => Request::get('contact_mobile'),
                    'address' => Request::get('contact_address'),
                    'name' => $tempContactNameSurname[0],
                    'surname' => $tempContactNameSurname[1],
                    'customer_id' => $customerId,
                    'delivery_location_id' => Request::get('city_id'),
                    'customer_list' => false
                ])->id;
            }
            $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
            $priceWithDiscount = number_format($priceWithDiscount, 2);
            parse_str($priceWithDiscount);
            $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

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
                'sender_name' => $tempCustomer->name,
                'sender_surname' => $tempCustomer->surname,
                'sender_mobile' => Customer::where('user_id', $userId)->get()[0]->mobile,
                'sender_email' => Request::get('mail'),
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
                    'billing_name' => $tempCustomer->name,
                    'billing_surname' => $tempCustomer->surname,
                    'billing_address' => '',
                    'billing_send' => 0,
                    'small_city' => DeliveryLocation::where('id' , Request::get('city_id') )->get()[0]->district,
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
            DB::table('log_receiver')->where('customer_id', $customerId)->delete();
            DB::commit();
            return response()->json(["status" => 1, "sale_number" => $salesId], 200);
        } catch (\Exception $e) {
            DB::rollback();
            ErrorLog::create([
                'method_name' => 'submitForm',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function completeSale(userCompleteSaleRequest $request)
    {
        try {
            $useCoupon= false;
            $userId = \Authorizer::getResourceOwnerId();
            $customerId = Customer::where('user_id', $userId)->get()[0]->id;
            $now = Carbon::now();
            $productInfo = Product::where('id', Request::input('product_id'))->get();
            $priceWithDiscount = $productInfo[0]->price;
            $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
            $priceWithDiscount = number_format($priceWithDiscount, 2);
            parse_str($priceWithDiscount);
            $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

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
                    ->select('marketing_acts.id', 'marketing_acts.name', 'marketing_acts.type', 'marketing_acts.value', 'marketing_acts.description')->get();
                if (count($couponList) > 0) {
                    MarketingAct::where('id', Request::input('coupon_id'))->update([
                        'used' => '1',
                        'valid' => '0'
                    ]);
                    $useCoupon = true;
                    if ($couponList[0]->type == 2) {
                        $priceWithDiscount = floatval($priceWithDiscount) * (100 - floatval($couponList[0]->value)) / 100;
                    } else {
                        $priceWithDiscount = floatval($priceWithDiscount) - floatval($couponList[0]->value);
                    }
                } else {
                    Sale::where('id', Request::get('sale_number'))->update([
                        'payment_methods' => 409
                    ]);
                    $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                    if($tempLangId == 'en'){
                        return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' .  Request::get('sale_number'));
                    }
                    else{
                        return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                    }
                }
            }
            if($useCoupon){
                Sale::where('id', Request::get('sale_number'))->update([
                    'sum_total' => $priceWithDiscount
                ]);
                DB::table('marketing_acts_sales')
                    ->insert([
                        'sales_id' => Request::get('sale_number'),
                        'marketing_acts_id' => Request::input('coupon_id')
                    ]);
            }
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
            $strErrorURL = $this->backend_url .  "/error-call-back";
            $SecurityData = strtoupper(sha1($strProvisionPassword . $strTerminalID_));
            $HashData = strtoupper(sha1($strTerminalID . $strOrderID . $strAmount . $strSuccessURL . $strErrorURL . $strType . $strInstallmentCount . $strStoreKey . $SecurityData));
            $data = [
                "txnmotoind" => "Y",
                "secure3dsecuritylevel" => "3D",
                "cardnumber" => Request::input('card_no'), //Request::input('card_no')
                "refreshtime" => "60",
                "lang" => "tr",
                "cardexpiredatemonth" => Request::input('card_month'),  //Request::input('card_month')
                "cardexpiredateyear" => Request::input('card_year'),   //Request::input('card_year')
                "cardcvv2" => Request::input('card_cvv'),            //Request::input('card_cvv')
                "cardholder" => Request::input('card_holder'),  //Request::input('card_holder')
                "mode" => "PROD",
                "version" => "v1.0",
                "txntype" => "sales",
                "txnamount" => "100",           //$priceWithDiscount
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
                //"strstoreKey" => "696632333569663233356966323335696632333569663233",
                //"provisionpassword" => "Hakan1234",
                "successurl" => $strSuccessURL,
                "errorurl" => $strErrorURL,
                "securitydata" => $SecurityData,
                "secure3dhash" => $HashData,
                "companyname" => "Bloomandfresh.com"
            ];

            $response = $client->post('https://sanalposprov.garanti.com.tr/servlet/gt3dengine', ['body' => $data]);
            return $response;
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'completeSale',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            Sale::where('id', Request::get('sale_number'))->update([
                'payment_methods' => 400
            ]);
            $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
            if($tempLangId == 'en'){
                return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' .  Request::get('sale_number'));
            }
            else{
                return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
            }
        }
    }

    public function userUpdateUserInfo(updateUserRequest $request)
    {
        $userId = \Authorizer::getResourceOwnerId();
        try {
            $tempNameSurname = logEventController::splitNameSurname( Request::get('name') );
            if (count(User::where('email', Request::get('email'))->where('id', '!=', $userId)->get()) > 0) {
                return response()->json(["status" => -2, "description" => 410], 400);
            }

            User::where('id', '=', $userId)->update([
                "email" => Request::get('email'),
                "name" => $tempNameSurname[0],
                "surname" => $tempNameSurname[1]
            ]);

            DB::table('users')->where('id', '=', $userId)->update([
                'sale_info' => Request::get('saleDetail')
            ]);

            Customer::where('user_id', '=', $userId)->update([
                'mobile' => Request::get('mobile'),
                "name" => $tempNameSurname[0],
                "surname" => $tempNameSurname[1]
            ]);

            if (Request::input('newsLetter')) {
                if (count(Newsletter::where('email', Request::get('email'))->get()) == 0) {
                    Newsletter::create([
                        'email' => Request::get('email')
                    ]);
                }
            } else {
                if (count(Newsletter::where('email', Request::get('email'))->get()) != 0) {
                    Newsletter::where('email', Request::get('email'))->delete();
                }
            }

            return ["status" => 1, "description" => 201];
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'updateUserInfo',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function userSetReminder(setReminderRequest $request)
    {
        $userId = \Authorizer::getResourceOwnerId();
        $customerId = Customer::where('user_id', '=', $userId)->get()[0]->id;
        try {
            Reminder::create([
                'name' => Request::get('name'),
                'description' => Request::get('description'),
                'reminder_day' => Request::get('reminder_day'),
                'reminder_month' => Request::get('reminder_month'),
                'customers_id' => $customerId
            ]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'setReminder',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "description" => 201];
    }

    public function completeCompanySale(\Illuminate\Http\Request $request){
        $userId = \Authorizer::getResourceOwnerId();
        $userInfo = User::where('id' , $userId )->get()[0];
        if($userInfo->company_user){
            $useCoupon = false;
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
                    ->select('marketing_acts.id', 'marketing_acts.name', 'marketing_acts.type', 'marketing_acts.value', 'marketing_acts.description' , 'marketing_acts.long_term' , 'marketing_acts.product_coupon')->get();

                if (count($couponList) > 0) {
                    if($couponList[0]->product_coupon != null && $couponList[0]->product_coupon != 0){
                        if(DB::table('productList_coupon')->where('coupon_id' , $couponList[0]->product_coupon)->where('product_id' , $productId)->count() == 0){
                            Sale::where('id', Request::get('sale_number'))->update([
                                'payment_methods' => 419
                            ]);
                            logEventController::logErrorToDB('completeSale','Çiçek ve kupon eşleşmesi hatalı','Çiçek ve kupon eşleşmesi hatalı','WS','');
                            DB::commit();
                            $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                            if($tempLangId == 'en'){
                                return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' .  Request::get('sale_number'));
                            }
                            else{
                                return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
                            }
                        }
                    }
                    $useCoupon = true;
                    $priceWithDiscount = str_replace(',', '.', $tempPrice);
                    if ($couponList[0]->type == 2) {
                        $priceWithDiscount = floatval($priceWithDiscount) * (100 - floatval($couponList[0]->value)) / 100;
                    } else {
                        $priceWithDiscount = floatval($priceWithDiscount) - floatval($couponList[0]->value);
                    }
                } else {
                    Sale::where('id', Request::get('sale_number'))->update([
                        'payment_methods' => 409
                    ]);
                    logEventController::logErrorToDB('completeSale','Hatalı kupon girildi.','Hatalı kupon girildi.','WS','');
                    DB::commit();
                    $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
                    if($tempLangId == 'en'){
                        return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' .  Request::get('sale_number'));
                    }
                    else{
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
            else{
                Sale::where('id', Request::get('sale_number'))->update([
                    'sum_total' => $priceWithDiscount
                ]);
            }

            DB::table('sales')->where('id' , Request::get('sale_number') )->update([
                'created_at' => Carbon::now()
            ]);

            $mailData = DB::table('sales')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.id', '=', Request::get('sale_number'))
                ->select('delivery_locations.district', 'sales.id', 'sales.sum_total', 'customer_contacts.surname as contact_surname', 'customer_contacts.name as contact_name', 'deliveries.wanted_delivery_limit',
                    'deliveries.created_at', 'deliveries.wanted_delivery_date', 'deliveries.products', 'sales.receiver_address as address', 'sales_products.products_id',
                    'sales.sender_name as name', 'sales.sender_surname as surname', 'sales.sender_mobile as mobile')
                ->get()[0];

            $created = new Carbon($mailData->wanted_delivery_limit);

            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');
            $mailDate = new Carbon($mailData->wanted_delivery_limit);
            $mailDate = $mailDate->formatLocalized('%A %d %B');

            if(Request::input('cross_sell') > 0){
                $tempCrossProduct = DB::table('cross_sell_products')->where('id', Request::input('cross_sell'))->get()[0];
                $tempCrossSellPrice = floatval(str_replace(',', '.', $tempCrossProduct->price));
                $tempCrossSellTax = number_format(floatval($tempCrossSellPrice / 100.0 * 8.0), 2);
                $tempCrossSellTotal = $tempCrossSellPrice + $tempCrossSellTax;
                DB::table('cross_sell')->where('sales_id' , Request::get('sale_number'))->delete();
                DB::table('cross_sell')->insert([
                    'sales_id' => Request::get('sale_number'),
                    'product_id' => Request::input('cross_sell'),
                    'product_price' => str_replace('.', ',', $tempCrossSellPrice),
                    'tax' => str_replace('.', ',', $tempCrossSellTax),
                    'total_price' => str_replace('.', ',', $tempCrossSellTotal)
                ]);
            }

            \MandrillMail::messages()->sendTemplate('siparisuyari', null, array(
                'html' => '<p>Example HTML content</p>',
                'text' => 'Sipariş verildi.',
                'subject' => 'Sipariş - ' . $mailDate,
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
                        'content' => ucwords(strtolower($mailData->name)),
                    ), array(
                        'name' => 'SALEID',
                        'content' => $mailData->id,
                    ), array(
                        'name' => 'CNTCNAME',
                        'content' => ucwords(strtolower($mailData->contact_name)),
                    ), array(
                        'name' => 'CNTCLNAME',
                        'content' => ucwords(strtolower($mailData->contact_surname)),
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
                        'content' => ucwords(strtolower($mailData->surname))
                    )
                )
            ));

            Sale::where('id', Request::get('sale_number'))->update([
                'payment_methods' => 'OK',
                'payment_type' => 'KURUMSAL'
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
                'delivery_notification' => $couponId
            ]);
            $couponData = DB::table('marketing_acts_sales')->where('sales_id', Request::get('sale_number'))->get();
            if (count($couponData) != 0) {
                $tempCoupon = MarketingAct::where( 'id' , $couponData[0]->marketing_acts_id )->get()[0];
                if($tempCoupon->long_term){

                }
                else{
                    MarketingAct::where('id', $tempCoupon->id)->update([
                        'used' => '1',
                        'valid' => '0'
                    ]);
                }
            }

            DB::commit();
            $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
            if($tempLangId == 'en'){
                return redirect()->away($this->site_url . '/order-details?orderId=' .  Request::get('sale_number'));
            }
            else{
                return redirect()->away($this->site_url . '/satis-ozet?orderId=' . Request::get('sale_number'));
            }

        }
        else{
            Sale::where('id', Request::get('sale_number'))->update([
                'payment_methods' => 418
            ]);
            logEventController::logErrorToDB('completeCompanySale','companySaleLoginFail','companySaleLoginFail','WS',Request::get('sale_number'));
            DB::commit();
            $tempLangId = Sale::where('id', Request::get('sale_number'))->select('lang_id')->get()[0]->lang_id;
            if($tempLangId == 'en'){
                return redirect()->away($this->site_url . '/order-flowers/payment?orderId=' .  Request::get('sale_number'));
            }
            else{
                return redirect()->away($this->site_url . '/satin-alma/odeme-bilgileri?orderId=' . Request::get('sale_number'));
            }
        }
    }

    public function userDeleteReminder()
    {
        $userId = \Authorizer::getResourceOwnerId();
        $customerId = Customer::where('user_id', '=', $userId)->get()[0]->id;
        try {
            Reminder::where([
                'id' => Request::get('id'),
                'customers_id' => $customerId
            ])->delete();
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'setReminder',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "description" => 201];
    }

    public function userUpdateReminder(updateReminderRequest $request)
    {
        $userId = \Authorizer::getResourceOwnerId();
        try {
            $customerId = Customer::where('user_id', '=', $userId)->get()[0]->id;
            Reminder::where('id', '=', Request::get('id'))->update([
                'name' => Request::get('name'),
                'description' => Request::get('description'),
                'reminder_day' => Request::get('reminder_day'),
                'reminder_month' => Request::get('reminder_month'),
                'customers_id' => $customerId
            ]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'updateReminder',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "description" => 201];
    }

    public function userInsertPersonalBilling(setPersonalBillingRequest $request)
    {
        $userId = \Authorizer::getResourceOwnerId();
        try {
            $customerId = Customer::where('user_id', '=', $userId)->get()[0]->id;
            $billingId = DB::table('customer_billings')->insertGetId([
                //'billing_name' => Request::get('billing_name'),
                //'billing_surname' => Request::get('billing_surname'),
                'personal_address' => Request::get('billing_address'),
                'small_city' => Request::get('small_city'),
                'city' => Request::get('city'),
                'tc' => Request::get('tc'),
                'billing_type' => 1,
                'customers_id' => $customerId
            ]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'insertPersonalBilling',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "id" => $billingId];
    }

    public function userInsertCompanyBilling(setCompanyBillingRequest $request)
    {
        $userId = \Authorizer::getResourceOwnerId();
        try {
            $customerId = Customer::where('user_id', '=', $userId)->get()[0]->id;
            $billingId = CustomerBilling::create([
                'company' => Request::get('company'),
                'billing_address' => Request::get('billing_address'),
                'tax_office' => Request::get('tax_office'),
                'tax_no' => Request::get('tax_no'),
                'billing_type' => 2,
                'customers_id' => $customerId
            ])->id;
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'insertCompanyBilling',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "id" => $billingId];
    }

    public function userUpdateCompanyBilling(updateCompanyBillingRequest $request)
    {
        $userId = \Authorizer::getResourceOwnerId();
        try {
            $customerId = Customer::where('user_id', '=', $userId)->get()[0]->id;
            CustomerBilling::where('customers_id', '=', $customerId)->where('id', '=', Request::get('id'))->update([
                'company' => Request::get('company'),
                'billing_address' => Request::get('billing_address'),
                'tax_office' => Request::get('tax_office'),
                'tax_no' => Request::get('tax_no'),
                'billing_type' => 2
            ]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'updateCompanyBillings',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "description" => 201];
    }

    public function userUpdatePersonalBilling(updatePersonalBillingRequest $request)
    {
        $userId = \Authorizer::getResourceOwnerId();
        try {
            $customerId = Customer::where('user_id', '=', $userId)->get()[0]->id;
            DB::table('customer_billings')->where('customers_id', '=', $customerId)->where('id', '=', Request::get('id'))->update([
                //'billing_name' => Request::get('billing_name'),
                //'billing_surname' => Request::get('billing_surname'),
                'personal_address' => Request::get('billing_address'),
                'small_city' => Request::get('small_city'),
                'city' => Request::get('city'),
                'tc' => Request::get('tc'),
                'billing_type' => 1
            ]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'updatePersonalBillings',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "description" => 201];
    }

    public function userSetContactList(setContactListRequest $request)
    {
        $userId = \Authorizer::getResourceOwnerId();
        $customerId = Customer::where('user_id', $userId)->get()[0]->id;
        try {
            $tempNameSurname = logEventController::splitNameSurname( Request::get('contact_name') );
            $iconId = rand(1, 8);
            if (Request::get('icon_id'))
                $iconId = intval(Request::get('icon_id'));
            $contactId = CustomerContact::create([
                'mobile' => Request::get('contact_mobile'),
                'address' => Request::get('contact_address'),
                'name' => $tempNameSurname[0],
                'surname' => $tempNameSurname[1],
                'email' => Request::get('contact_email'),
                'customer_id' => $customerId,
                'delivery_location_id' => Request::get('city_id'),
                'customer_list' => true,
                'icon_id' => $iconId
            ])->id;
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'setContactList',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "description" => 201, "id" => $contactId];
    }

    public function userUpdateContactList()
    {
        $userId = \Authorizer::getResourceOwnerId();
        $customerId = Customer::where('user_id', $userId)->get()[0]->id;
        try {
            $tempNameSurname = logEventController::splitNameSurname( Request::get('contact_name') );
            $affectedRows = CustomerContact::where('id', '=', Request::input('contact_id'))->update([
                'mobile' => Request::get('contact_mobile'),
                'address' => Request::get('contact_address'),
                'name' => $tempNameSurname[0],
                'surname' => $tempNameSurname[1],
                'email' => Request::get('contact_email'),
                'customer_id' => $customerId,
                'delivery_location_id' => Request::get('city_id')
            ]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'updateContactList',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return $affectedRows;
    }

    public function userDeleteContactList(deleteContactRequest $request)
    {
        $userId = \Authorizer::getResourceOwnerId();
        try {
            CustomerContact::where('id', Request::get('contact_id'))->update([
                'customer_list' => false
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            ErrorLog::create([
                'method_name' => 'deleteContactList',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        DB::commit();
        $saleList = DB::table('users')
            ->join('customers', 'users.id', '=', 'customers.user_id')
            ->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
            ->join('delivery_locations', 'customer_contacts.delivery_location_id', '=', 'delivery_locations.id')
            ->where('users.id', '=', $userId)
            ->where('customer_contacts.customer_list', '=', true)
            ->select('customer_contacts.id', 'customer_contacts.name', 'customer_contacts.surname', 'customer_contacts.mobile', 'delivery_locations.district',
                'customer_contacts.address')
            ->get();
        return response()->json($saleList);
    }

    public function getSaleList(\Illuminate\Http\Request $request)
    {
        try {
            $userId = \Authorizer::getResourceOwnerId();
            $saleList = DB::table('users')
                ->join('customers', 'users.id', '=', 'customers.user_id')
                ->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
                ->join('sales', 'customer_contacts.id', '=', 'sales.customer_contact_id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->orderBy('deliveries.wanted_delivery_date', 'desc')
                ->where('users.id', '=', $userId)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.visible_customer', '=', '1')
                ->select('sales.ups', 'sales.sum_total', 'customer_contacts.surname as customer_surname', 'customer_contacts.name as customer_name', 'customer_contacts.mobile',
                    'deliveries.delivery_date', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit as wanted_delivery_date_end' , 'deliveries.status', 'products.name', 'products.id')
                ->get();
            for ($x = 0; $x < count($saleList); $x++) {
                if($saleList[$x]->status == 6){
                    $saleList[$x]->status = "1";
                }
                $imageList = DB::table('images')
                    ->where('products_id', '=', $saleList[$x]->id)
                    ->select('type', 'image_url')
                    ->get();
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $saleList[$x]->MainImage = $imageList[$y]->image_url;
                    }
                }
            }
            return response()->json($saleList);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'getSaleList',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getContactList(\Illuminate\Http\Request $request)
    {
        try {
            $userId = \Authorizer::getResourceOwnerId();
            $saleList = DB::table('users')
                ->join('customers', 'users.id', '=', 'customers.user_id')
                ->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
                ->join('delivery_locations', 'customer_contacts.delivery_location_id', '=', 'delivery_locations.id')
                ->where('users.id', '=', $userId)
                ->where('customer_contacts.customer_list', '=', true)
                ->select('customer_contacts.id', 'customer_contacts.name', 'customer_contacts.icon_id', 'customer_contacts.surname', 'customer_contacts.mobile', 'delivery_locations.district',
                    'customer_contacts.address')
                ->get();
            for ($x = 0; $x < count($saleList); $x++) {
                $saleList[$x]->name = $saleList[$x]->name . ' ' .  $saleList[$x]->surname;
                $tagList = DB::table('sales')
                    ->where('customer_contact_id', '=', $saleList[$x]->id)
                    ->where('payment_methods', '=', 'OK')
                    ->get();
                $saleList[$x]->count = count($tagList);
            }
            return response()->json($saleList);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'getContactList',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getReminder(\Illuminate\Http\Request $request)
    {
        $Id = \Authorizer::getResourceOwnerId();
        if (count(Customer::where('user_id', '=', $Id)->get()) != 0)
            $customerId = Customer::where('user_id', '=', $Id)->get()[0]->id;
        else
            return response()->json(["status" => -4, "description" => 411], 400);
        return Reminder::where('customers_id', '=', $customerId)->get();

    }

    public function getBillings(\Illuminate\Http\Request $request)
    {
        $Id = \Authorizer::getResourceOwnerId();
        if (count(Customer::where('user_id', '=', $Id)->get()) != 0)
            $customerId = Customer::where('user_id', '=', $Id)->get()[0]->id;
        else
            return response()->json(["status" => -4, "description" => 411], 400);
        return CustomerBilling::where('customers_id', '=', $customerId)->get();

    }
}
