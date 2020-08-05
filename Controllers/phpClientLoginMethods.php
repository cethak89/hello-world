<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Models\Billing;
use App\Models\CustomerBilling;
use App\Models\Delivery;
use App\Models\DeliveryLocation;
use App\Models\Newsletter;
use App\Models\Reminder;
use App\Models\Sale;
use App\Models\Tag;
use App\Models\Image;
use App\Models\Product;
use App\Models\Description;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\MarketingAct;
//use Illuminate\Http\Request;
use App\Models\User;
use Request;
use DB;
use Excel;
use Redirect;
use Auth;
use App\Models\ErrorLog;
use App\Http\Requests\insertProductRequest;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

class phpClientLoginMethods extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getReminders(){
        $Id = Auth::user()->id;
        if (count(Customer::where('user_id', '=', $Id)->get()) != 0)
            $customerId = Customer::where('user_id', '=', $Id)->get()[0]->id;
        else
            return response()->json(["status" => -4, "description" => 411], 400);

        $tempReminderList = Reminder::where('customers_id', '=', $customerId)->get();

        return response()->json(["status" => 1, "description" => $tempReminderList ], 200);
    }

    public function getPersonalBilling(){
        $Id = Auth::user()->id;
        if (count(Customer::where('user_id', '=', $Id)->get()) != 0)
            $customerId = Customer::where('user_id', '=', $Id)->get()[0]->id;
        else
            return response()->json(["status" => -4, "description" => 411], 400);
        return response()->json(["status" => 1, "description" => CustomerBilling::where('customers_id', '=', $customerId)->get()], 200);
    }

    public function insertCompanyBilling(){
        $userId = Auth::user()->id;
        try {
            $customerId = Customer::where('user_id', '=', $userId)->get()[0]->id;
            CustomerBilling::where('customers_id' , $customerId )->delete();
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

    public function insertPersonalBilling(){
        $userId = Auth::user()->id;
        try {
            $customerId = Customer::where('user_id', '=', $userId)->get()[0]->id;
            CustomerBilling::where('customers_id' , $customerId )->delete();
            $billingId = CustomerBilling::create([
                //'billing_name' => Request::get('billing_name'),
                //'billing_surname' => Request::get('billing_surname'),
                'billing_address' => Request::get('billing_address'),
                'small_city' => Request::get('small_city'),
                'city' => Request::get('city'),
                'tc' => Request::get('tc'),
                'billing_type' => 1,
                'customers_id' => $customerId
            ])->id;
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

    public function updatePassword(){
        try {
            $mail = Auth::user()->email;
            if (\Auth::validate(['email' => $mail, 'password' => Request::input('old_password')])) {
                User::where('email', $mail)->update([
                    'password' => bcrypt(Request::get('new_password'))
                ]);
            }
            else {
                return response()->json(["status" => -2, "description" => 407], 400);
            }
            return response()->json(["status" => 1, "description" => 200], 200);

        }
        catch (\Exception $e) {
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

    public function getUserInfo(){
        try{
            $returnInfo = Auth::user();
            $returnInfo->mobile = Customer::where('user_id', $returnInfo->id)->get()[0]->mobile;

            if($returnInfo->company_user ){
                $returnInfo->company_name = DB::table('company_user_info')->where('user_id' , $returnInfo->id )->get()[0]->company_name;
            }

            $returnInfo->name = $returnInfo->name . ' ' . $returnInfo->surname;
            return $returnInfo;
        }
        catch (\Exception $e) {
            ErrorLog::create([
            'method_name' => 'authenticate',
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 401], 400);
        }
    }

    public function updateUserInfo(){
        $userId = Auth::user()->id;
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

    public function getCoupons(){
        try {
            $userId = Auth::user()->id;
            $customerId = Customer::where('user_id', $userId)->get()[0]->id;
            $now = Carbon::now();

            MarketingAct::where('expiredDate', '<', $now)->update([
                'valid' => '0'
            ]);

            $couponList = DB::table('customers')
                ->join('customers_marketing_acts', 'customers.id', '=', 'customers_marketing_acts.customers_id')
                ->join('marketing_acts', 'customers_marketing_acts.marketing_acts_id', '=', 'marketing_acts.id')
                ->where('customers.id', $customerId)
                ->where('used', '0')
                ->where('valid', '1')
                ->select('marketing_acts.description', 'marketing_acts.image_type', 'marketing_acts.id', 'marketing_acts.name', 'marketing_acts.type', 'marketing_acts.value', 'marketing_acts.description' , 'marketing_acts.product_coupon')->get();

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

    public function addCoupon(){
        try {
            $userId = Auth::user()->id;
            $customerId = Customer::where('user_id', $userId)->get()[0]->id;
            $now = Carbon::now();

            MarketingAct::where('expiredDate', '<', $now)->update([
                'valid' => '0'
            ]);

            $couponList = MarketingAct::where('publish_id', Request::get('coupon_id'))
                ->where('used', '0')
                ->where('active', '0')
                ->where('valid', '1')
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

            } else {
                return response()->json(["status" => -1, "description" => 406], 400);
            }
        }
        catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'setCoupon',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 406], 400);
        }
        return response()->json(["status" => 1, "coupon" => $couponList[0]], 200);
    }

    public function getSales(){
        try {
            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');
            $userId = Auth::user()->id;
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
                ->select('sales.sum_total', 'customer_contacts.surname as customer_surname', 'customer_contacts.name as customer_name', 'customer_contacts.mobile',
                    'deliveries.delivery_date', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit as wanted_delivery_date_end' , 'deliveries.status', 'products.name', 'products.id')
                ->get();
            for ($x = 0; $x < count($saleList); $x++) {

                $deliveryDate = new Carbon($saleList[$x]->wanted_delivery_date);
                $deliveryDateEnd = new Carbon($saleList[$x]->wanted_delivery_date_end);
                $dateInfo = $deliveryDate->formatLocalized('%d %b %Y, %A');
                $dateInfoTime =  str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT) . '-' . str_pad($deliveryDateEnd->hour, 2, '0', STR_PAD_LEFT)  . ':' . str_pad($deliveryDateEnd->minute, 2, '0', STR_PAD_LEFT);
                $saleList[$x]->wanted_delivery_date = $dateInfo;
                $saleList[$x]->wanted_delivery_date_time = $dateInfoTime;



                if($saleList[$x]->status == 6){
                    $saleList[$x]->status = "1";
                }

                if( $saleList[$x]->status == "1" ){
                    $saleList[$x]->statusText = 'Hazırlanıyor';
                }
                else if( $saleList[$x]->status == "2"){
                    $saleList[$x]->statusText = 'Yolda';
                }
                else if( $saleList[$x]->status == "3"){
                    $saleList[$x]->statusText = 'Teslim Edildi';
                }
                else if( $saleList[$x]->status == "4"){
                    $saleList[$x]->statusText = 'İptal Edildi';
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

    public function getContacts(){
        try {
            $userId = Auth::user()->id;
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

    public function getDeliveryLocation(){
        try {
            $tempVar = DeliveryLocation::where('shop_id', 1)->where('active', 1)->orderBy('district')->get();
            return response()->json($tempVar);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getCityList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function addContact(){
        $userId =  Auth::user()->id;
        $customerId = Customer::where('user_id', $userId)->get()[0]->id;
        try {
            $tempNameSurname = logEventController::splitNameSurname( Request::get('contact_name') );
            $iconId = rand(1, 8);

            $locationId = DeliveryLocation::where('district' , Request::get('district') )->get()[0]->id;

            if (Request::get('icon_id'))
                $iconId = intval(Request::get('icon_id'));

            $contactId = CustomerContact::create([
                'mobile' => Request::get('contact_mobile'),
                'address' => Request::get('contact_address'),
                'name' => $tempNameSurname[0],
                'surname' => $tempNameSurname[1],
                //'email' => Request::get('contact_email'),
                'customer_id' => $customerId,
                'delivery_location_id' => $locationId,
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
}