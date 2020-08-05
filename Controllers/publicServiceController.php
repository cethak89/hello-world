<?php namespace App\Http\Controllers;

use App\Http\Requests\deleteContactListRequest;
use App\Http\Requests\newsLetterRequest;
use App\Http\Requests\saveDateBeforeSaleRequest;
use App\Http\Requests\testRequest;
use App\Models\CustomerContact;
use App\Models\Billing;
use App\Models\Sale;
use Carbon\Carbon;
use DB;
use App\Models\Newsletter;
use Request;
use App\Models\Customer;
use App\Models\User;
use App\Models\messages;
use App\Models\FailLog;
use Session;
use Authorizer;

class publicServiceController extends Controller
{
    public  $site_url = 'https://bloomandfresh.com';
    //public $site_url = 'http://188.166.86.116';
    //public $backend_url = 'http://188.166.86.116:3000';
    public $backend_url = 'https://everybloom.com';

    public function insertCompanyRequest()
    {
        DB::beginTransaction();
        try {
            $now = Carbon::now()->addHour(-1);
            $logInfo = FailLog::where('ip', $_SERVER['REMOTE_ADDR'])->where('type', 'message')->get();

            if (count($logInfo) > 0) {
                FailLog::where('id', $logInfo[0]->id)->update([
                    'count' => $logInfo[0]->count + 1
                ]);
                if ($logInfo[0]->count > 5 && $logInfo[0]->updated_at > $now) {
                    return response()->json(["status" => 402, "description" => 412], 402);
                }
            } else {
                FailLog::create([
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'type' => 'message',
                    'count' => 1,
                ]);
            }
            messages::create([
                'name' => Request::get('company') . " " . Request::get('name'),
                //'surname' => Request::get('surname'),
                'email' => Request::get('email'),
                'mobile' => Request::get('mobile'),
                'message' => Request::get('message')
            ]);
            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');
            $limitDateInfo = new Carbon();
            $dateInfo = ' ' . str_pad($limitDateInfo->hour, 2, '0', STR_PAD_LEFT)  . ':' . str_pad($limitDateInfo->minute, 2, '0', STR_PAD_LEFT)  . ' ' . $limitDateInfo->formatLocalized('%A %d %B %Y');
            //Test
            \MandrillMail::messages()->sendTemplate('messageWarning', null, array(
                'html' => '<p>Example HTML content</p>',
                'text' => 'Mesaj alındı.',
                'subject' => Request::get('company') . ' Şirketinden İstek Var',
                'from_email' => 'teknik@bloomandfresh.com',
                //'from_email' => 'teknik@bloomandfresh.com',
                'from_name' => 'Bloom And Fresh',
                'to' => array(
                    array(
                        'email' => 'murat@bloomandfresh.com',
                        //'email' => 'teknik@bloomandfresh.com',
                        'type' => 'to'
                    ),
                    array(
                        'email' => 'hello@bloomandfresh.com',
                        //'email' => 'teknik@bloomandfresh.com',
                        'type' => 'to'
                    )
                ),
                'merge' => true,
                'merge_language' => 'mailchimp',
                'global_merge_vars' => array(
                    array(
                        'name' => 'name',
                        'content' => Request::get('company'),
                    ), array(
                        'name' => 'phone',
                        'content' => Request::get('mobile'),
                    ), array(
                        'name' => 'mail',
                        'content' => Request::get('email'),
                    ), array(
                        'name' => 'message',
                        'content' => Request::get('message')
                    ), array(
                        'name' => 'date',
                        'content' => $dateInfo
                    )
                )
            ));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('insertMessages',$e->getCode(),$e->getMessage(),'WS','');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "description" => 'Başarılı'];
    }

    public function addBillingInfo(){
        try{
            $tempNameSurname = logEventController::splitNameSurname( Request::get('name') );
            if (Request::get('billing_type') == "1") {
                Billing::where('sales_id' ,  Request::get('sales_id') )->update([
                    'billing_name' => $tempNameSurname[0],
                    'billing_surname' => $tempNameSurname[1],
                    'billing_address' => Request::get('billing_address'),
                    'billing_send' => 1,
                    'small_city' => Request::get('small_city'),
                    'city' => Request::get('city'),
                    'sales_id' => Request::get('sales_id'),
                    'tc' => Request::get('tc'),
                    'billing_type' => 1,
                    'userBilling' => 1
                ]);
            }
            else {
                Billing::where('sales_id' ,  Request::get('sales_id') )->update([
                    'company' => Request::get('company'),
                    'billing_address' => Request::get('billing_address'),
                    'tax_office' => Request::get('tax_office'),
                    'tax_no' => Request::get('tax_no'),
                    'billing_send' => 1,
                    'small_city' => Request::get('small_city'),
                    'city' => Request::get('city'),
                    'billing_type' => 2,
                    'sales_id' => Request::get('sales_id'),
                    'userBilling' => 1
                ]);
            }

            return response()->json(["status" => 1, "description" => 200], 200);
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

    public function addStudioBillingInfo(){
        try{
            $tempNameSurname = logEventController::splitNameSurname( Request::get('name') );
            if (Request::get('billing_type') == "1") {
                DB::table('studio_billings')->where('sales_id' ,  Request::get('sales_id') )->update([
                    'billing_name' => $tempNameSurname[0],
                    'billing_surname' => $tempNameSurname[1],
                    'billing_address' => Request::get('billing_address'),
                    'billing_send' => 1,
                    'small_city' => Request::get('small_city'),
                    'city' => Request::get('city'),
                    'sales_id' => Request::get('sales_id'),
                    'tc' => Request::get('tc'),
                    'billing_type' => 1,
                    'userBilling' => 1
                ]);
            }
            else {
                DB::table('studio_billings')->where('sales_id' ,  Request::get('sales_id') )->update([
                    'company' => Request::get('company'),
                    'billing_address' => Request::get('billing_address'),
                    'tax_office' => Request::get('tax_office'),
                    'tax_no' => Request::get('tax_no'),
                    'billing_send' => 1,
                    'small_city' => Request::get('small_city'),
                    'city' => Request::get('city'),
                    'sales_id' => Request::get('sales_id'),
                    'billing_type' => 2,
                    'userBilling' => 1
                ]);
            }

            return response()->json(["status" => 1, "description" => 200], 200);
        } catch (\Exception $e) {
            \DB::table('error_logs')->insert([
                'method_name' => 'addStudioBillingInfo',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function updateMobileNumber(){
        try{
            $tempControl = DB::table('sales')
                ->where('sales.id' , Request::get('id') )
                ->get();

            if(count($tempControl) > 0){
                Sale::where('id' , Request::get('id'))->update([
                    'sender_mobile' => Request::get('mobile')
                ]);

                Customer::where('id' , CustomerContact::where('id' , Sale::where('id' , Request::get('id') )->get()[0]->customer_contact_id )->get()[0]->customer_id )->update([
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

    public function insertMessages()
    {
        DB::beginTransaction();
        try {
            $now = Carbon::now()->addHour(-1);
            $logInfo = FailLog::where('ip', $_SERVER['REMOTE_ADDR'])->where('type', 'message')->get();

            if (count($logInfo) > 0) {
                FailLog::where('id', $logInfo[0]->id)->update([
                    'count' => $logInfo[0]->count + 1
                ]);
                if ($logInfo[0]->count > 5 && $logInfo[0]->updated_at > $now) {
                    return response()->json(["status" => 402, "description" => 412], 402);
                }
            } else {
                FailLog::create([
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'type' => 'message',
                    'count' => 1,
                ]);
            }
            messages::create([
                'name' => Request::get('name'),
                //'surname' => Request::get('surname'),
                'email' => Request::get('email'),
                'mobile' => Request::get('mobile'),
                'message' => Request::get('message')
            ]);
            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');
            $limitDateInfo = new Carbon();
            $dateInfo = ' ' . str_pad($limitDateInfo->hour, 2, '0', STR_PAD_LEFT)  . ':' . str_pad($limitDateInfo->minute, 2, '0', STR_PAD_LEFT)  . ' ' . $limitDateInfo->formatLocalized('%A %d %B %Y');
            //Test
            \MandrillMail::messages()->sendTemplate('messageWarning', null, array(
                'html' => '<p>Example HTML content</p>',
                'text' => 'Mesaj alındı.',
                'subject' => Request::get('name') . ' Müsterimizden Mesaj var',
                'from_email' => 'teknik@bloomandfresh.com',
                //'from_email' => 'teknik@bloomandfresh.com',
                'from_name' => 'Bloom And Fresh',
                'to' => array(
                    array(
                        'email' => 'hello@bloomandfresh.com',
                        //'email' => 'teknik@bloomandfresh.com',
                        'type' => 'to'
                    )
                ),
                'merge' => true,
                'merge_language' => 'mailchimp',
                'global_merge_vars' => array(
                    array(
                        'name' => 'name',
                        'content' => Request::get('name'),
                    ), array(
                        'name' => 'phone',
                        'content' => Request::get('mobile'),
                    ), array(
                        'name' => 'mail',
                        'content' => Request::get('email'),
                    ), array(
                        'name' => 'message',
                        'content' => Request::get('message')
                    ), array(
                        'name' => 'date',
                        'content' => $dateInfo
                    )
                )
            ));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('insertMessages',$e->getCode(),$e->getMessage(),'WS','');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        return ["status" => 1, "description" => 'Başarılı'];
    }

    public function addMailProductReminder(){
        try {
            if (DB::table('products')->where('id', Request::get('product_id'))->where('limit_statu', 1)->count() == 0 && DB::table('products')->where('id', Request::get('product_id'))->where('coming_soon', 1)->count() == 0) {
                return response()->json(["status" => -1, "description" => 424], 400);
            }

            if (DB::table('product_reminder')->where('product_id', Request::get('product_id'))->where('mail', Request::get('mail'))->where('mail_send', false)->count() > 0) {
                return response()->json(["status" => -1, "description" => 423], 400);
            }

            DB::table('product_reminder')->insert([
                'product_id' => Request::get('product_id'),
                'mail' => Request::get('mail'),
                'created_at' => Carbon::now(),
                'mail_send' => false

            ]);
            return response()->json(["status" => 1, "description" => 200], 200);
        }    catch (\Exception $e) {
            logEventController::logErrorToDB('addMailProductReminder',$e->getCode(),$e->getMessage(),'WS','');
                return response()->json(["status" => 400, "description" => 400], 400);
        }
    }

    public function addMailProductReminderWithCity(){
        try {
            if (DB::table('product_city')->where('city_id', Request::get('city_id'))->where('product_id', Request::get('product_id'))->where('limit_statu', 1)->count() == 0 && DB::table('product_city')->where('product_id', Request::get('product_id'))->where('coming_soon', 1)->count() == 0) {
                return response()->json(["status" => -1, "description" => 424], 400);
            }

            if (DB::table('product_reminder')->where('city_id', Request::get('city_id'))->where('product_id', Request::get('product_id'))->where('mail', Request::get('mail'))->where('mail_send', false)->count() > 0) {
                return response()->json(["status" => -1, "description" => 423], 400);
            }

            DB::table('product_reminder')->insert([
                'product_id' => Request::get('product_id'),
                'city_id' => Request::get('city_id'),
                'mail' => Request::get('mail'),
                'created_at' => Carbon::now(),
                'mail_send' => false

            ]);
            return response()->json(["status" => 1, "description" => 200], 200);
        }    catch (\Exception $e) {
            logEventController::logErrorToDB('addMailProductReminder',$e->getCode(),$e->getMessage(),'WS','');
            return response()->json(["status" => 400, "description" => 400], 400);
        }
    }


    public function addMailProductLater(){
        try {

            if (DB::table('product_later')->where('product_id', Request::get('product_id'))->where('mail', Request::get('mail'))->where('mail_send', false)->count() > 0) {
                return response()->json(["status" => -1, "description" => 423], 400);
            }

            DB::table('product_later')->insert([
                'product_id' => Request::get('product_id'),
                'mail' => Request::get('mail'),
                'created_at' => Carbon::now(),
                'mail_send' => false

            ]);
            return response()->json(["status" => 1, "description" => 200], 200);
        }    catch (\Exception $e) {
            logEventController::logErrorToDB('addMailProductLater',$e->getCode(),$e->getMessage(),'WS','');
            return response()->json(["status" => 400, "description" => 400], 400);
        }
    }

    public function registerNewsLetter(newsLetterRequest $request)
    {
        try {
            $now = Carbon::now()->addHour(-1);
            $logInfo = FailLog::where('ip', $_SERVER['REMOTE_ADDR'])->where('type', 'newsLetter')->get();

            if (count($logInfo) > 0) {
                FailLog::where('id', $logInfo[0]->id)->update([
                    'count' => $logInfo[0]->count + 1
                ]);
                if ($logInfo[0]->count > 5 && $logInfo[0]->updated_at > $now) {
                    return response()->json(["status" => 402, "description" => 412], 402);
                }
            } else {
                FailLog::create([
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'type' => 'newsLetter',
                    'count' => 1,
                ]);
            }

            if(DB::table('newsletters')->where('email' , Request::input('mail'))->count() > 0 ){
                return response()->json(["status" => 401, "description" => 410], 401);
            }

            $NewLetterInfo = New Newsletter();
            $NewLetterInfo->email = Request::input('mail');
            $NewLetterInfo->save();
            $mailchimp = \App::make('Mailchimp');
            $mailchimp->lists->subscribe('65e73389d3', array('email' => Request::input('mail')), null, 'html', false, true, true, false);

            return ["status" => 1, "description" => 201];
        } catch (\Exception $e) {
            logEventController::logErrorToDB('registerNewsLetter',$e->getCode(),$e->getMessage(),'WS','');
            if ($e->getCode() == 23000) {
                return response()->json(["status" => 401, "description" => 410], 401);
            } else {
                return response()->json(["status" => 400, "description" => 400], 400);
            }
        }
    }

    public function userChangePassword()
    {
        DB::beginTransaction();
        try {
            $now = Carbon::now()->addHour(-1);
            $logInfo = FailLog::where('ip', $_SERVER['REMOTE_ADDR'])->where('type', 'changePassword')->get();

            /*if (count($logInfo) > 0) {
                FailLog::where('id', $logInfo[0]->id)->update([
                    'count' => $logInfo[0]->count + 1
                ]);
                if ($logInfo[0]->count > 5 && $logInfo[0]->updated_at > $now) {
                    DB::commit();
                    return response()->json(["status" => 402, "description" => 412], 402);
                }
            } else {
                FailLog::create([
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'type' => 'changePassword',
                    'count' => 1,
                ]);
            }*/


            $now = Carbon::now();
            $mail = Request::get('email');
            if (count(User::where('email', $mail)->get()) == 0) {
                DB::commit();
                return response()->json(["status" => -1, "description" => 413], 401);
            } else if (User::where('email', $mail)->get()[0]->status == "FB") {
                DB::commit();
                return response()->json(["status" => -1, "description" => 414], 401);
            }
            $userInfo = User::where('email', $mail)->get()[0];
            $password = str_random(20);
            $password = strtoupper($password);

            if (count(DB::table('passwords_email')->where('users_id', $userInfo->id)->get()) == 1) {
                DB::table('passwords_email')->where('users_id', $userInfo->id)->update([
                    'token_id' => $password,
                    'updated_at' => $now
                ]);
            } else {
                DB::table('passwords_email')->insert([
                    'users_id' => $userInfo->id,
                    'token_id' => $password,
                    'updated_at' => $now
                ]);
            }

            $passwordLink = $this->site_url . '/sifre-degistir?userId=' . $userInfo->id . '&token=' . $password;

            User::where('email', $mail)->update([
                'status' => 'PR'
            ]);
            \MandrillMail::messages()->sendTemplate('v2_BNF_Password_Reset', null, array(
                'html' => '<p>Example HTML content</p>',
                'text' => 'Şifre hatırlatma.',
                'subject' => 'Bloom And Fresh Yeni Şifre Talebin Bize Ulaştı.',
                'from_email' => 'teknik@bloomandfresh.com',
                'from_name' => 'Bloom And Fresh',
                'to' => array(
                    array(
                        'email' => Request::get('email'),
                        'type' => 'to'
                    )
                ),
                'merge' => true,
                'merge_language' => 'mailchimp',
                'global_merge_vars' => array(
                    array(
                        'name' => 'FNAME',
                        'content' => $userInfo->name,
                    ), array(
                        'name' => 'LNAME',
                        'content' => $userInfo->surname,
                    ), array(
                        'name' => 'Link',
                        'content' => $passwordLink,
                    )
                )
            ));
            DB::commit();
            return ["status" => 1, "description" => 201];
        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('userChangePassword',$e->getCode(),$e->getMessage(),'WS','');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function loginWithNewPassword()
    {
        DB::beginTransaction();
        try {
            $now = Carbon::now()->addDay(-1);
            if (count(User::where('id', Request::get('user_id'))->get()) == 0) {
                return response()->json(["status" => -1, "description" => 415], 400);
            }
            if (count(DB::table('passwords_email')->where('users_id', Request::get('user_id'))->where('token_id', Request::get('token'))->where('updated_at', '>', $now)->get()) == 0) {
                return response()->json(["status" => -1, "description" => 416], 400);
            }
            if (count(User::where('id', Request::get('user_id'))->where('status', '!=', 'PR')->get()) == 1) {
                return response()->json(["status" => -1, "description" => 417], 400);
            }
            User::where('id', Request::get('user_id'))->update([
                'password' => bcrypt(Request::get('password')),
                'status' => Null
            ]);

            \Auth::validate([
                'email' => User::where('id', Request::get('user_id'))->get()[0]->email,
                'password' => Request::get('password'),
            ]);

            Request::replace([
                'username' => User::where('id', Request::get('user_id'))->get()[0]->email,
                "grant_type" => "password",
                'password' => Request::get('password'),
                'client_id' => ')EG0LZ2i9+pm.ox4+[SC2_K-S-E]@Z',
                'client_secret' => '4m5ii2X>(#-17wqYbNZD_%}Azu2V'
            ]);

            $tokenInfo = Authorizer::issueAccessToken();
            foreach ($tokenInfo as $key => $value) {
                if ($key == 'access_token') {
                    $token = $value;
                }
            }
            $newsletter = false;
            $returnInfo = User::where('email', Request::get('username'))->get()[0];
            $returnInfo->mobile = Customer::where('user_id', $returnInfo->id)->get()[0]->mobile;
            if (count(Newsletter::where('email', Request::get('username'))->get()) > 0)
                $newsletter = true;
            $returnInfo->newsletter = $newsletter;
            $returnInfo->access_token = $token;
            DB::commit();
            return $returnInfo;

        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('loginWithNewPassword',$e->getCode(),$e->getMessage(),'WS','');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

}

