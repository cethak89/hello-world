<?php namespace App\Http\Controllers;

use App\Http\Requests;

use App\Models\User;
use App\Models\Newsletter;
use App\Models\Customer;
use App\Models\UserGroup;
use App\Models\MarketingAct;
use Request;
use DB;
use App\Models\ErrorLog;
use Authorizer;
use Carbon\Carbon;
use App\Models\FailLog;

class AuthenticateController extends Controller
{
    public function updateUserLogin()
    {
        try {

            $tempValue = User::where('email', Request::get('username'))->get()[0]->fb_id;
            if($tempValue)
                $tempValue = 'FB';
            else
                $tempValue = 'Login';
            User::where('email', Request::get('username'))->update([
                'updated_at' => Carbon::now(),
                'status' => $tempValue
            ]);
            $returnInfo = User::where('email', Request::get('username'))->get()[0];
            $returnInfo->mobile = Customer::where('user_id', $returnInfo->id)->get()[0]->mobile;

            $returnInfo->name = $returnInfo->name . ' ' . $returnInfo->surname;
            return $returnInfo;
        }
        catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'updateUserLogin',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 401], 400);
        }
    }

    /*
     * Facebook kayıt ve login için kullanılan metot
     */
    public function loginOrRegisterWithFacebook()
    {
        DB::beginTransaction();
        $tempFirstOrNotLogin = true;
        try {
            $userTempInformation = User::where('email', Request::get('username'))->get();
            if (count($userTempInformation) != 0) {
                if ($userTempInformation[0]->status != 'FB') {
                    ErrorLog::create([
                        'method_name' => 'FBlogin',
                        'error_code' => 'FBLoginWithEmail',
                        'error_message' => 'Bu e-posta ile zaten kayıtlısınız. Lütfen e-posta ile giriş yapınız.',
                        'type' => 'WS'
                    ]);
                    DB::commit();
                    return response()->json(["status" => -1, "description" => 402], 400);
                }
            }
            $userInformation = User::where('fb_id', Request::get('fb_id'))->get();
            //dd(Request::all());
            //return response()->json(Request::all(), 200);
            if (count($userInformation) == 0) {
                $userId = User::create([
                    "fb_id" => Request::get('fb_id'),
                    "email" => Request::get('username'),
                    "gender" => Request::get('gender'),
                    "password" => bcrypt('dunyaninenzorf2#$%^&**!gsdfgs23477faghqajk['),
                    "name" => Request::get('name'),
                    "surname" => Request::get('surname'),
                    "user_group_id" => UserGroup::where('name', 'customer')->get()[0]->id,
                    "status" => "FB",
                    "register_ip" => Request::ip()
                ])->id;


                if(DB::table('newsletters')->where('email' , Request::input('username'))->count() == 0 ){
                    $NewLetterInfo = New Newsletter();
                    $NewLetterInfo->email = Request::input('username');
                    $NewLetterInfo->save();
                    $mailchimp = \App::make('Mailchimp');
                    $mailchimp->lists->subscribe('65e73389d3', array('email' => Request::input('username')), null, 'html', false, true, true, false);
                }

                $CustomerId = Customer::create([
                    'user_id' => $userId,
                    "name" => Request::get('name'),
                    "surname" => Request::get('surname')
                ])->id;
                AuthenticateController::setNewUserMailingAndCoupon(Request::get('username'), $CustomerId, Request::get('name'), Request::get('surname'), Request::get('lang_id'));
                $tempFirstOrNotLogin = false;
            } else {
                User::where('email', Request::get('username'))->update([
                    'updated_at' => Carbon::now()
                ]);
            }
            DB::commit();
            $userInformation = User::where('fb_id', Request::get('fb_id'))->get()[0];
            Request::replace([
                'username' => $userInformation->email,
                "grant_type" => "password",
                'password' => 'dunyaninenzorf2#$%^&**!gsdfgs23477faghqajk[',
                'client_id' => Request::get('client_id'),
                'client_secret' => Request::get('client_secret')
            ]);

            $token = Authorizer::issueAccessToken()['access_token'];
            $userInformation->mobile = Customer::where('user_id', $userInformation->id)->get()[0]->mobile;
            $userInformation->access_token = $token;
            $userInformation->firstLogin = $tempFirstOrNotLogin;
            $userInformation->name = $userInformation->name . ' ' . $userInformation->surname;
            return response()->json($userInformation, 200);
        } catch (\Exception $e) {
            DB::rollback();
            ErrorLog::create([
                'method_name' => 'FB',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    /*
     * Mail ile kayıt olunması için kullanılan metot
     */
    public function registerWithMail()
    {
        if (count(User::where('email', '=', Request::get('username'))->get())) {
            return response()->json(["status" => -3, "description" => 403], 401);
        }
        DB::beginTransaction();
        try {
            $tempNameSurname = logEventController::splitNameSurname( Request::get('name') );
            $userId = User::create([
                "email" => Request::get('username'),
                "password" => bcrypt(Request::get('password')),
                "name" => $tempNameSurname[0],
                "surname" => $tempNameSurname[1],
                "user_group_id" => UserGroup::where('name', 'customer')->get()[0]->id,
                "register_ip" => Request::ip()
            ])->id;

            $idCustomer = Customer::create([
                'user_id' => $userId,
                "name" => $tempNameSurname[0],
                "surname" => $tempNameSurname[1]
            ])->id;

            AuthenticateController::setNewUserMailingAndCoupon(Request::get('username'), $idCustomer, Request::get('name'), Request::get('surname'), Request::get('lang_id'));
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
            ErrorLog::create([
                'method_name' => 'register',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
        if (Request::get('newsLetter')) {
            try {

                if(DB::table('newsletters')->where('email' , Request::input('username'))->count() == 0 ){
                    $NewLetterInfo = New Newsletter();
                    $NewLetterInfo->email = Request::input('username');
                    $NewLetterInfo->save();
                    $mailchimp = \App::make('Mailchimp');
                    $mailchimp->lists->subscribe('65e73389d3', array('email' => Request::input('username')), null, 'html', false, true, true, false);
                }

                DB::commit();
                return ["status" => 1, "userId" => $userId, "access_token" => Authorizer::issueAccessToken()['access_token']];
            } catch (\Exception $e) {
                if ($e->getCode() == 23000) {
                    return ["status" => 0, "description" => "Bu e-posta sistemimizde kayıtlı. Bilgilerinle giriş yapabilirsin."];
                } else {
                    DB::rollback();
                    ErrorLog::create([
                        'method_name' => 'newsLetter',
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                        'type' => 'WS'
                    ]);
                    return response()->json(["status" => -1, "description" => 404], 400);
                }
            }
        } else {
            DB::commit();
            return ["status" => 1, "userId" => $userId, "access_token" => Authorizer::issueAccessToken()['access_token']];
        }

    }

    /*
     * Mail ile login yapılması için kullanılan işlemdir
     */
    public function loginWithMail()
    {
        try {
            $now = Carbon::now()->addMinute(-10);
            $logInfo = FailLog::where('ip', $_SERVER['REMOTE_ADDR'])->where('type', 'login')->get();

            if (count($logInfo) > 0) {
                if ($logInfo[0]->count > 5 && $logInfo[0]->updated_at > $now) {
                    return response()->json(["status" => 402, "description" => 405], 402);
                }
            }

            if (\Auth::validate([
                'email' => Request::get('username'),
                'password' => Request::get('password'),
            ])
            ) {
                FailLog::where('ip', $_SERVER['REMOTE_ADDR'])->where('type', 'login')->delete();
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
                User::where('email', Request::get('username'))->update([
                    'updated_at' => Carbon::now()
                ]);

                if($returnInfo->company_user ){
                    $returnInfo->company_name = DB::table('company_user_info')->where('user_id' , $returnInfo->id )->get()[0]->company_name;
                    $returnInfo->logo_img = DB::table('company_user_info')->where('user_id' , $returnInfo->id )->get()[0]->logo_img;
                }

                $returnInfo->name = $returnInfo->name . ' ' . $returnInfo->surname;
                return $returnInfo;
            } else {
                if (count($logInfo) > 0) {
                    FailLog::where('id', $logInfo[0]->id)->update([
                        'count' => $logInfo[0]->count + 1
                    ]);
                } else {
                    FailLog::create([
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'type' => 'login',
                        'count' => 1,
                    ]);
                }
                return response()->json(["status" => -1, "description" => 401], 400);
            }
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'authenticate',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 401], 400);
        }
    }

    /*
     * Facebook yada mail ile yapılan ilk kayıtlarda kuponların ve maillerin set edildiği metottur
     */
    public static function setNewUserMailingAndCoupon($userName, $idCustomer, $name, $surname, $langId)
    {
        $couponId = str_random(20);

        $mailBase = explode("@", $userName)[1];

        $companyCoupon = DB::table('company_coupon')->where('mail', $mailBase)->where('valid', '1')->where('expiredDate', '>', Carbon::now())->get();

        if (count($companyCoupon) > 0) {
            $marketingActId = MarketingAct::create(
                [
                    'publish_id' => $couponId,
                    'name' => $companyCoupon[0]->name,
                    'description' => $companyCoupon[0]->description,
                    'image_type' => $companyCoupon[0]->image_type,
                    'type' => $companyCoupon[0]->type,
                    'value' => $companyCoupon[0]->value,
                    'active' => true,
                    'valid' => 1,
                    'expiredDate' => $companyCoupon[0]->expiredDate,
                    'used' => false,
                    'administrator_id' => 1,
                    'long_term' => 1
                ]
            )->id;
            DB::table('company_coupon')->where('id', $companyCoupon[0]->id)->increment('count');

            DB::table('customers_marketing_acts')->insert(
                array(
                    'marketing_acts_id' => $marketingActId,
                    'customers_id' => $idCustomer
                )
            );
        }

        $tempProductCoupons = DB::table('product_coupon')->where('expired_date', '>', Carbon::now())->get();

        if (count($tempProductCoupons) > 0) {
            foreach ($tempProductCoupons as $tempCoupon) {

                $couponId = str_random(20);

                $marketingActId = MarketingAct::create(
                    [
                        'publish_id' => $couponId,
                        'name' => $tempCoupon->name,
                        'description' => $tempCoupon->description,
                        'image_type' => $tempCoupon->image_type,
                        'type' => $tempCoupon->type,
                        'value' => $tempCoupon->value,
                        'active' => true,
                        'valid' => 1,
                        'expiredDate' => $tempCoupon->expired_date,
                        'used' => false,
                        'administrator_id' => 1,
                        'product_coupon' => $tempCoupon->id
                    ]
                )->id;

                DB::table('customers_marketing_acts')->insert(
                    array(
                        'marketing_acts_id' => $marketingActId,
                        'customers_id' => $idCustomer
                    )
                );
            }
        }

        $couponId = str_random(20);

        $marketingActId = MarketingAct::create(
            [
                'publish_id' => $couponId,
                'name' => '10% Tanışma İndirimi',
                'description' => 'İlk çiçek siparişinde geçerli %10 indirim',
                'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/10_indirim.png',
                'type' => 2,
                'value' => '10',
                'active' => true,
                'valid' => 1,
                'expiredDate' => Carbon::now()->addDay(1000),
                'used' => false,
                'administrator_id' => 1
            ]
        )->id;

        DB::table('customers_marketing_acts')->insert(
            array(
                'marketing_acts_id' => $marketingActId,
                'customers_id' => $idCustomer
            )
        );

        $tempMailTemplateName = "v2_BNF_Onboarding";
        if($langId == 'en'){
            $tempMailTemplateName = "boarding_eng";
        }

        $tempMailSubjectName = ", Stil Sahibi Çiçekler Seni Bekliyor!";
        if($langId == 'en'){
            $tempMailSubjectName = ", Stylish Flowers Is Waiting For You";
        }

        //$tempName = ucfirst(Request::get('name'));

        \MandrillMail::messages()->sendTemplate($tempMailTemplateName , null, array(
            'html' => '<p>Example HTML content</p>',
            'text' => 'Bloomandfresh dünyasına hoşgeldiniz',
            'subject' => ucwords(strtolower($name)) . ' ' . ucwords(strtolower($surname))  . $tempMailSubjectName,
            'from_email' => 'hello@bloomandfresh.com',
            'from_name' => 'Bloom And Fresh',
            'to' => array(
                array(
                    'email' => $userName,
                    'type' => 'to'
                )
            ),
            'merge' => true,
            'merge_language' => 'mailchimp',
            'global_merge_vars' => array(
                array(
                    'name' => 'FNAME',
                    'content' => ucfirst(strtolower($name)),
                ), array(
                    'name' => 'LNAME',
                    'content' => ucfirst(strtolower($surname)),
                )
            )
        ));
    }
}
