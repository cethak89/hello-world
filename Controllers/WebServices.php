<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Request;
use App\Models\DeliveryLocation;
use Session;
use Authorizer;
use Auth;

class WebServices extends Controller
{
    public $site_url = 'https://bloomandfresh.com';
    //public $site_url = 'http://188.166.86.116';
    //public $backend_url = 'http://188.166.86.116:3000';
    public $backend_url = 'https://everybloom.com';

    public function updateFBUserNewsletter($user_id, $status){

        try{

            DB::table('users')->where('id', $user_id )->update([
                'doNotShowNewsletter' => 1
            ]);

            $userEmail = DB::table('users')->where('id', $user_id )->get()[0]->email;

            if( $status && DB::table('newsletters')->where('email', $userEmail )->count() == 0 ){
                DB::table('newsletters')->insert([
                    'email' => $userEmail
                ]);

                $mailchimp = \App::make('Mailchimp');
                $mailchimp->lists->subscribe('65e73389d3', array('email' => $userEmail), null, 'html', false, true, true, false);
            }

            return response()->json([]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'getDailySalesInfo',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }

    }

    public function updateSaleDetailInfo($user_id, $status){

        try{

            DB::table('users')->where('id', $user_id )->update([
                'sale_info' => $status
            ]);

            return response()->json([]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'getDailySalesInfo',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getSaleShowInfo($user_id){

        try{

            $saleInfo = DB::table('users')->where('id', $user_id )->select('sale_info')->get()[0]->sale_info;

            return response()->json([$saleInfo]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'getDailySalesInfo',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getFBIfNewsLetter($user_id){

        try {

            $tempUserData = DB::table('users')->where('id', $user_id)->where('status', 'FB')->where('doNotShowNewsletter', 0)->get();

            if( count($tempUserData) > 0 ){

                if( DB::table('newsletters')->where('email', $tempUserData[0]->email )->count() == 0 ){
                    return response()->json(["status" => 1]);
                }
            }

            return response()->json(["status" => 0]);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'getFBIfNewsLetter',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }

    }

    public function getDailySalesInfo($user_id){

        try {

            $nowEnd = Carbon::now();
            $nowStart = Carbon::now();

            $nowEnd = $nowEnd->endOfDay();
            $nowStart = $nowStart->startOfDay();

            $saleList = DB::table('users')
                ->join('customers', 'users.id', '=', 'customers.user_id')
                ->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
                ->join('sales', 'customer_contacts.id', '=', 'sales.customer_contact_id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->orderBy('deliveries.wanted_delivery_date', 'desc')
                ->where('users.id', '=', $user_id)
                ->where('users.sale_info', '=', 1)
                ->where('deliveries.status', '!=', 4)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.wanted_delivery_date', '>', $nowStart)
                ->where('deliveries.wanted_delivery_date', '<', $nowEnd)
                ->where('sales.ups', '!=', 1)
                ->select('sales.ups', 'sales.sum_total', 'customer_contacts.surname as customer_surname', 'customer_contacts.name as customer_name', 'customer_contacts.mobile', 'deliveries.picker','sales.id as sale_id',
                    'deliveries.delivery_date', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit as wanted_delivery_date_end' , 'deliveries.status', 'products.name', 'products.id')
                ->get();

            $saleListUps = DB::table('users')
                ->join('customers', 'users.id', '=', 'customers.user_id')
                ->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
                ->join('sales', 'customer_contacts.id', '=', 'sales.customer_contact_id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->orderBy('deliveries.wanted_delivery_date', 'desc')
                ->where('users.id', '=', $user_id)
                ->where('users.sale_info', '=', 1)
                ->where('deliveries.status', '!=', 4)
                ->where('deliveries.status', '!=', 3)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.ups', '=', 1)
                ->select('sales.ups', 'sales.sum_total', 'customer_contacts.surname as customer_surname', 'customer_contacts.name as customer_name', 'customer_contacts.mobile', 'deliveries.picker', 'sales.id as sale_id',
                    'deliveries.delivery_date', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit as wanted_delivery_date_end' , 'deliveries.status', 'products.name', 'products.id')
                ->get();

            $saleList = array_merge($saleList,$saleListUps);

            for ($x = 0; $x < count($saleList); $x++) {
                if($saleList[$x]->status == 6){
                    $saleList[$x]->status = "1";
                }

                $tempCrossSell = DB::table('cross_sell')
                    ->join('cross_sell_products', 'cross_sell.product_id', '=', 'cross_sell_products.id')
                    ->where('sales_id', $saleList[$x]->sale_id )
                    ->select('cross_sell_products.name')
                    ->get();

                if( count($tempCrossSell) > 0 ){
                    $saleList[$x]->extraProduct = $tempCrossSell[0]->name;
                }
                else{
                    $saleList[$x]->extraProduct = '';
                }

            }
            return response()->json($saleList);
        } catch (\Exception $e) {
            ErrorLog::create([
                'method_name' => 'getDailySalesInfo',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'WS'
            ]);
            return response()->json(["status" => -1, "description" => 400], 400);
        }

    }

    public function getUPSDeliveryTime(){
        try{
            $cityList = DB::table('ups_cities')->get();

            return response()->json([ "status" => 1, "data" => $cityList ], 200);
        }
        catch (\Exception $e) {
            logEventController::logErrorToDB('getUPSDeliveryTime', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getUPSCities (){
        try {

            $tempVar = DB::table('ups_cities')->where('active', 1)->select('value', 'name', 'delivery_days')->get();

            return response()->json($tempVar);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getCityList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getCityListByCityWithUps($site)
    {
        try {

            $tempUpsStatus = DB::table('ups_active')->where('name', 'active')->get()[0]->status;

            if( $tempUpsStatus ){
                $tempVar = DB::table('delivery_locations')->where('shop_id', $site)->where('active', 1)->orderBy('district')->get();
            }
            else{
                $tempVar = DB::table('delivery_locations')->where('continent_id', '!=' , 'Ups')->where('shop_id', $site)->where('active', 1)->orderBy('district')->get();
            }

            return response()->json($tempVar);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getCityList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getUpsStatus(){
        $status =  DB::table('ups_active')->where('name', 'active' )->get()[0]->status;

        return response()->json(["status" => 1, "data" => $status], 200);
    }

    public function getMainCities(){
        $big_cities = DB::table('delivery_locations')->groupBy('city')->select('city as name')->get();

        return response()->json(["status" => 1, "data" => $big_cities], 200);
    }

    public function getFlowersAndPromosLanding(){
        $landingPromos = DB::table('landing_with_promo')->orderBy('order')->get();

        foreach ( $landingPromos as $element ){
            if( $element->type == 2 ){
                $element->promoObject = DB::table('landing_promo')->where('id', $element->promo_id )->get()[0];
            }
        }

        return response()->json(["status" => 1, "data" => $landingPromos], 200);
    }

    public function getFlowerPages(){
        try {
            $flowerPageList = DB::table('flowers_page')
                ->where('active', 1)
                ->get();

            return response()->json(["status" => 1, "data" => $flowerPageList], 200);
        }
        catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerPages', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function siteMap(){
        $tempProductList = DB::table('products')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->join('tags', 'products.tag_id', '=', 'tags.id')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('descriptions.lang_id', '=', 'tr')
            ->where('product_city.coming_soon', '0')
            ->where('product_city.limit_statu', '0')
            ->where('product_city.activation_status_id', '1')
            ->where('products.id', '!=', '75')
            ->where('product_city.city_id', '=', '1')
            ->where('products.company_product', '=', '0')
            ->where('tags.lang_id', '=', 'tr')
            ->select('products.price', 'products.id', 'products.name', 'descriptions.landing_page_desc', 'tags.tag_ceo', 'url_title', 'products.url_parametre')
            ->get();

        $tempString = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://bloomandfresh.com</loc><changefreq>Daily</changefreq><priority>1.0</priority></url>';
        $tempString = $tempString . '<url><loc>https://bloomandfresh.com/cicekler/</loc><changefreq>weekly</changefreq><priority>0.9</priority></url>';
        $tempString = $tempString . '<url><loc>https://bloomandfresh.com/cicekler/ayni-gun-teslim-hizli-cicek-gonder</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>';

        $tags = DB::table('tags')->where('lang_id', 'tr')->get();
        $flowerPages = DB::table('flowers_page')->where('active', 1)->get();

        foreach( $tags as $tag ){
            $tempString = $tempString . '<url><loc>https://bloomandfresh.com/' . $tag->tag_ceo . '</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>' ;
        }

        foreach( $flowerPages as $page ){
            $tempString = $tempString . '<url><loc>https://bloomandfresh.com/' . $page->url_name . '</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>' ;
        }

        $tempString = $tempString . '<url><loc>https://bloomandfresh.com/istanbul-online-cicek-siparisi</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>';

        foreach ($tempProductList as $product) {
            $tempString =  $tempString . '<url><loc>https://bloomandfresh.com/' . htmlspecialchars($product->tag_ceo) . '/' . htmlspecialchars($product->url_parametre) . '-' . $product->id . '</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>';
        }

        $tempString = $tempString . '<url><loc>https://bloomandfresh.com/hakkimizda</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/tasarim</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/detay</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/tazelik</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/sinirli-koleksiyon</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/gorundugu-gibi</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/hizli-siparis-ve-teslimat</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/bize-ulasin</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/destek</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/kurumsal-siparisler</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/godiva-cikolata-gonder</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>';

        $tempString = $tempString . '</urlset>';

        return \Response::make($tempString, '200')->header('Content-Type', 'text/xml');
    }

    public function getFlowerListForCategory($categoryName){
        try {

            /*if($cityId == 'ist'){
                $city_id = '1';
            }
            else{
                $city_id = '2';
            }*/

            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('product_city', 'products.id', '=', 'product_city.product_id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->join('page_flower_production', 'products.id', '=', 'page_flower_production.product_id')
                ->join('flowers_page', 'page_flower_production.page_id', '=', 'flowers_page.id')
                ->where('shops.id', '=', 1)
                ->where('descriptions.lang_id', '=', 'tr')
                ->where('flowers_page.active', '=', 1)
                ->where('flowers_page.url_name', '=', $categoryName)
                ->where('product_city.activation_status_id', '=', 1)
                ->where('product_city.active', '=', 1)
                ->select('products.tag_id','products.product_type', 'product_city.coming_soon', 'product_city.limit_statu', 'products.id', 'products.name', 'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                    'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre', 'products.company_product', 'product_city.city_id'
                    , 'descriptions.how_to_detail', 'products.youtube_url', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description', 'product_city.avalibility_time'
                    , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3', 'products.speciality' )
                ->orderBy('product_city.landing_page_order')
                ->get();

            $now = Carbon::now();
            $tomorrow = Carbon::now();
            $theDayAfter = Carbon::now();
            $nowAnk = Carbon::now();
            $tomorrowAnk = Carbon::now();
            $theDayAfterAnk = Carbon::now();
            $TomorrowTag = false;
            $theDayAfterTag = false;
            $tomorrowDay = ($tomorrow->dayOfWeek + 1) % 8;
            $TomorrowTagAnk = false;
            $theDayAfterTagAnk = false;
            $tomorrowDayAnk = ($tomorrow->dayOfWeek + 1) % 8;
            $tempNowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            $tempNowTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 2)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 2)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            //Ankara

            $NowTagAnk = false;
            if (count($tempNowTagAnk) == 0) {
                $NowTagAnk = false;
            } else {
                $NowTagAnk = true;
                $nowAnk->hour(explode(":", $tempNowTagAnk[0]->start_hour)[0]);
                if($tempNowTagAnk[0]->start_hour != "18"){
                    $nowAnk->addHours(1);
                }
                $nowAnk->minute(0);
            }
            if (count($tempTomorrowTagAnk) > 0) {
                $TomorrowTagAnk = true;
                $tomorrowAnk->addDays(1)->hour(explode(":", $tempTomorrowTagAnk[0]->start_hour)[0]);
                $tomorrowAnk->minute(0);
            }
            //if ($TomorrowTag == false && $NowTag == false) {
            $tempDayAfterTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', '>', $tomorrowDayAnk)
                ->where('dayHours.active', 1)
                ->select('dayHours.start_hour', 'delivery_hours.day_number')
                ->orderBy('delivery_hours.day_number')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            //dd($tempDayAfterTag);

            if (count($tempDayAfterTagAnk) > 0) {
                $theDayAfterAnk->hour(explode(":", $tempDayAfterTagAnk[0]->start_hour)[0]);
                $theDayAfterAnk->minute(0);
                $theDayAfterAnk->addDays($tempDayAfterTagAnk[0]->day_number - $theDayAfterAnk->dayOfWeek);
                $theDayAfterTagAnk = true;
            }
            else {
                $tempDayAfterTagAnk = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $nowAnk->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTagAnk) > 0) {
                    $theDayAfterAnk->hour(explode(":", $tempDayAfterTagAnk[0]->start_hour)[0]);
                    $theDayAfterAnk->minute(0);
                    $theDayAfterAnk->addDays(7 + $tempDayAfterTagAnk[0]->day_number - $theDayAfterAnk->dayOfWeek);
                    $theDayAfterTagAnk = true;
                }
            }

            //

            $NowTag = false;
            if (count($tempNowTag) == 0) {
                $NowTag = false;
            } else {
                $NowTag = true;
                $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                if($tempNowTag[0]->start_hour != "18"){
                    $now->addHours(1);
                }
                $now->minute(0);
            }
            if (count($tempTomorrowTag) > 0) {
                $TomorrowTag = true;
                $tomorrow->addDays(1)->hour(explode(":", $tempTomorrowTag[0]->start_hour)[0]);
                $tomorrow->minute(0);
            }
            //if ($TomorrowTag == false && $NowTag == false) {
            $tempDayAfterTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', '>', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->select('dayHours.start_hour', 'delivery_hours.day_number')
                ->orderBy('delivery_hours.day_number')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            //dd($tempDayAfterTag);

            if (count($tempDayAfterTag) > 0) {
                $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                $theDayAfter->minute(0);
                $theDayAfter->addDays($tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                $theDayAfterTag = true;
            }
            else {
                $tempDayAfterTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $now->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTag) > 0) {
                    $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                    $theDayAfter->minute(0);
                    $theDayAfter->addDays(7 + $tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                    $theDayAfterTag = true;
                }
            }
            //}

            for ($x = 0; $x < count($flowerList); $x++) {

                if( $flowerList[$x]->city_id == 2 ){

                    $tempFlowerNowTagAnk = $NowTagAnk;
                    $tempFlowerTomorrowTagAnk = $TomorrowTagAnk;
                    if ($flowerList[$x]->avalibility_time > $now) {
                        $tempFlowerNowTagAnk = false;
                    }
                    $nowTemp2 = Carbon::now();
                    if($nowTemp2 > $nowAnk){
                        $tempFlowerNowTagAnk = false;
                    }
                    if ($flowerList[$x]->limit_statu) {
                        $tempFlowerNowTagAnk = false;
                        $tempFlowerTomorrowTagAnk = false;
                    }
                    if ($flowerList[$x]->coming_soon) {
                        $tempFlowerNowTagAnk = false;
                        $tempFlowerTomorrowTagAnk = false;
                    }
                    if (!$tempFlowerNowTagAnk && $flowerList[$x]->avalibility_time > $tomorrow) {
                        $tempFlowerTomorrowTagAnk = false;
                        //dd($flowerList[$x]);
                    }
                    if ($theDayAfterTagAnk || (!$tempFlowerTomorrowTagAnk && !$tempFlowerNowTagAnk)) {
                        setlocale(LC_TIME, "");
                        setlocale(LC_ALL, 'tr_TR.utf8');
                        if ($flowerList[$x]->avalibility_time > $theDayAfterAnk) {
                            $flowerList[$x]->theDayAfter = new Carbon($flowerList[$x]->avalibility_time);
                            $flowerList[$x]->theDayAfter = $flowerList[$x]->theDayAfter->formatLocalized('%d %B');
                        } else {
                            $flowerList[$x]->theDayAfter = $theDayAfterAnk->formatLocalized('%d %B');
                        }
                    }
                    $flowerList[$x]->tomorrow = $tempFlowerTomorrowTagAnk && !$tempFlowerNowTagAnk;
                    $flowerList[$x]->today = $tempFlowerNowTagAnk;
                    $tagList = DB::table('products_tags')
                        ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                        ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                        ->where('tags.lang_id', '=', 'tr')
                        ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url', 'tags.tag_header')
                        ->get();

                    $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', 'tr')->get();
                    if (count($primaryTag) > 0) {
                        $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                    } else {
                        $flowerList[$x]->tag_main = 'cicek';
                    }

                    $flowerList[$x]->tags = $tagList;
                }
                else{

                    $tempFlowerNowTag = $NowTag;
                    $tempFlowerTomorrowTag = $TomorrowTag;
                    if ($flowerList[$x]->avalibility_time > $now) {
                        $tempFlowerNowTag = false;
                    }
                    $nowTemp2 = Carbon::now();
                    if($nowTemp2 > $now){
                        $tempFlowerNowTag = false;
                    }
                    if ($flowerList[$x]->limit_statu) {
                        $tempFlowerNowTag = false;
                        $tempFlowerTomorrowTag = false;
                    }
                    if ($flowerList[$x]->coming_soon) {
                        $tempFlowerNowTag = false;
                        $tempFlowerTomorrowTag = false;
                    }
                    if (!$tempFlowerNowTag && $flowerList[$x]->avalibility_time > $tomorrow) {
                        $tempFlowerTomorrowTag = false;
                        //dd($flowerList[$x]);
                    }
                    if ($theDayAfterTag || (!$tempFlowerTomorrowTag && !$tempFlowerNowTag)) {
                        setlocale(LC_TIME, "");
                        setlocale(LC_ALL, 'tr_TR.utf8');
                        if ($flowerList[$x]->avalibility_time > $theDayAfter) {
                            $flowerList[$x]->theDayAfter = new Carbon($flowerList[$x]->avalibility_time);
                            $flowerList[$x]->theDayAfter = $flowerList[$x]->theDayAfter->formatLocalized('%d %B');
                        } else {
                            $flowerList[$x]->theDayAfter = $theDayAfter->formatLocalized('%d %B');
                        }
                    }
                    $flowerList[$x]->tomorrow = $tempFlowerTomorrowTag && !$tempFlowerNowTag;
                    $flowerList[$x]->today = $tempFlowerNowTag;
                    $tagList = DB::table('products_tags')
                        ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                        ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                        ->where('tags.lang_id', '=', 'tr')
                        ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url', 'tags.tag_header')
                        ->get();

                    $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', 'tr')->get();
                    if (count($primaryTag) > 0) {
                        $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                    } else {
                        $flowerList[$x]->tag_main = 'cicek';
                    }

                    /*if ($tempFlowerNowTag) {
                        array_push($tagList, (object)[
                            'id' => '999',
                            'tag_header' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                            'tag_ceo' => 'ayni-gun-teslim-hizli-cicek-gonder',
                            'tags_name' => 'Hızlı Çiçekler',
                            'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                            'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_selected.svg',
                            'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_unselected.svg'
                        ]);
                    }*/
                    $flowerList[$x]->tags = $tagList;
                }
            }

            for ($x = 0; $x < count($flowerList); $x++) {
                $imageList = DB::table('images')
                    ->where('products_id', '=', $flowerList[$x]->id)
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get();
                $detailListImage = [];
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "detailImages") {
                        array_push($detailListImage, $imageList[$y]->image_url);
                    }
                    else if ($imageList[$y]->type == "mobile") {
                        $flowerList[$x]->mobileImage = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "detailPhoto") {
                        $flowerList[$x]->DetailImage = $imageList[$y]->image_url;
                    }
                }
                if ($flowerList[$x]->youtube_url) {
                    array_push($detailListImage, $flowerList[$x]->youtube_url);
                }
                $flowerList[$x]->detailListImage = $detailListImage;
            }

            $infoData = DB::table('flowers_page')
                ->where('flowers_page.active', '=', 1)
                ->where('flowers_page.url_name', '=', $categoryName)->get();

            return response()->json(["status" => 1, "data" => $flowerList, "info" => $infoData ], 200);
        }
        catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function dropDowMenu(){
        try {
            $column1 = DB::table('mega_drop_down')->orderBy('order')->where('name', '!=' , '')->where('segment', 1)->get();
            $column2 = DB::table('mega_drop_down')->orderBy('order')->where('name', '!=' , '')->where('segment', 2)->get();
            $column3 = DB::table('mega_drop_down')->orderBy('order')->where('name', '!=' , '')->where('segment', 3)->get();

            $menuBanner  = DB::table('drop_down_banner')->where('active', 1)->get();

            if( count($menuBanner) > 0 ){
                $menuBanner = $menuBanner[0];
            }

            return response()->json(["status" => 1, "menuBanner" => $menuBanner, "column1" => $column1, "column2" => $column2, "column3" => $column3 ], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('dropDowMenu', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getRelatedProductsWithCityIdGenerated($productId, $cityId)
    {
        try {

            $allFlowers = DB::table('product_city')->join('products', 'product_city.product_id', '=', 'products.id')
                ->join('images', 'products.id', '=', 'images.products_id')
                ->where('products.id', $productId)
                ->where('products.city_id', 1)
                ->select( 'products.tag_id', 'products.id')->get();

            $activeFlowers = DB::table('product_city')
                ->join('products', 'product_city.product_id', '=', 'products.id')
                ->where('products.company_product', 0)->where('product_city.activation_status_id', 1)->where('product_city.city_id', $cityId)->where('products.city_id', 1)
                ->select('products.name', 'products.tag_id', 'products.id', 'product_city.activation_status_id', 'product_city.limit_statu', 'product_city.coming_soon')->get();

            foreach ( $activeFlowers as $activeFlower ) {
                $activeFlower->tags = DB::table('products_tags')->where('products_id', $activeFlower->id)->select('tags_id')->get();
            }

            foreach ( $allFlowers as $flower){

                $flower->tags = DB::table('products_tags')->where('products_id', $flower->id)->select('tags_id')->get();

                $flower->similarProducts = [];
                $flower->similarProductsNoMain = [];

                foreach ( $activeFlowers as $activeFlower ){

                    if( $activeFlower->id != $flower->id ){

                        if( $activeFlower->limit_statu == 0 && $activeFlower->coming_soon == 0 ){

                            if( $activeFlower->tag_id == $flower->tag_id ){

                                $tagNumber = 0;

                                foreach ( $activeFlower->tags as $activeTags ){

                                    foreach ( $flower->tags as $allTags ){

                                        if( $allTags->tags_id == $activeTags->tags_id ){
                                            $tagNumber++;
                                        }
                                    }
                                }

                                //$tempObject = $activeFlower;
                                //$tempObject->commonTagNumber = $tagNumber;
                                //$tempObject->mainTag = 1;

                                array_push($flower->similarProducts, (object)[
                                    'id' => $activeFlower->id,
                                    'name' => $activeFlower->name,
                                    'commonTagNumber' => $tagNumber,
                                    'mainTag' => 1
                                ]);

                            }
                            else{

                                $tagNumber = 0;

                                foreach ( $activeFlower->tags as $activeTags ){

                                    foreach ( $flower->tags as $allTags ){

                                        if( $allTags->tags_id == $activeTags->tags_id ){
                                            $tagNumber++;
                                        }
                                    }
                                }

                                //$tempObject = $activeFlower;
                                // $tempObject->commonTagNumber = $tagNumber;
                                //$tempObject->mainTag = 0;

                                array_push($flower->similarProductsNoMain, (object)[
                                    'id' => $activeFlower->id,
                                    'name' => $activeFlower->name,
                                    'commonTagNumber' => $tagNumber,
                                    'mainTag' => 0
                                ]);

                            }
                        }
                    }
                }

                if( count($flower->similarProducts) > 3 ){



                    //dd($flower->similarProducts);

                    usort($flower->similarProducts, function($a, $b)
                    {
                        return strcmp( $b->commonTagNumber, $a->commonTagNumber);
                    });

                    $flower->similarProducts = array_slice($flower->similarProducts, 0, 4);

                    //dd($flower->similarProducts);

                }
                else if( count($flower->similarProducts) > 0 ){

                    usort($flower->similarProducts, function($a, $b)
                    {
                        return strcmp( $b->commonTagNumber, $a->commonTagNumber);
                    });

                    usort($flower->similarProductsNoMain, function($a, $b)
                    {
                        return strcmp( $b->commonTagNumber, $a->commonTagNumber);
                    });

                    $flower->similarProductsNoMain = array_slice($flower->similarProductsNoMain, 0, 4 - count($flower->similarProducts));

                    $flower->similarProducts = array_merge($flower->similarProducts, $flower->similarProductsNoMain);

                    //array_push($flower->similarProducts,$flower->similarProductsNoMain);

                }
                else{
                    usort($flower->similarProductsNoMain, function($a, $b)
                    {
                        return strcmp( $b->commonTagNumber, $a->commonTagNumber);
                    });

                    $flower->similarProducts = array_slice($flower->similarProductsNoMain, 0, 4 );
                }

                $flower->similarProductsNoMain = [];

                if( count($flower->similarProducts) == 2 ){
                    dd($flower);
                }
            }

            //dd($allFlowers);

            $productIds = [];

            foreach ( $allFlowers[0]->similarProducts as $flower){
                array_push($productIds, $flower->id);
            }

            $flowerList = DB::table('products')
                ->join('product_city', 'product_city.product_id', '=', 'products.id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->whereIn('products.id', $productIds)
                ->where('product_city.city_id', '=', $cityId)
                ->where('descriptions.lang_id', '=', 'tr')
                //->where('products.activation_status_id', '=', 1)
                ->select('products.tag_id', 'product_city.coming_soon', 'product_city.activation_status_id', 'product_city.limit_statu', 'products.id', 'products.name', 'products.url_parametre' ,
                    'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color', 'descriptions.landing_page_desc'
                )
                ->get();

            for ($x = 0; $x < count($flowerList); $x++) {
                $imageList = DB::table('images')
                    ->where('products_id', '=', $flowerList[$x]->id)
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get();

                $flowerList[$x]->tag_main = DB::table('tags')->where('lang_id', '=', 'tr')->where('id', '=', $flowerList[$x]->tag_id)->get()[0]->tag_ceo;

                $detailListImage = [];
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    }
                    else if($imageList[$y]->type == "mobile"){
                        $flowerList[$x]->mobileImage = $imageList[$y]->image_url;
                    }
                }
            }

            return response()->json(["status" => 1, "data" => $flowerList], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getRelatedProductsWithCityId', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getRelatedProductsWithCityId($productId, $cityId)
    {
        try {
            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('product_city', 'product_city.product_id', '=', 'products.id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->join('related_products', 'products.id', '=', 'related_products.related_product')
                ->where('shops.id', '=', 1)
                ->where('product_city.city_id', '=', $cityId)
                ->where('related_products.city_id', '=', $cityId)
                ->where('descriptions.lang_id', '=', 'tr')
                //->where('products.activation_status_id', '=', 1)
                ->where('related_products.main_product', '=', $productId)
                ->select('products.tag_id', 'product_city.coming_soon', 'product_city.activation_status_id', 'product_city.limit_statu', 'products.id', 'products.name', 'products.url_parametre' ,
                    'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color', 'descriptions.landing_page_desc'
                )
                ->orderBy('product_city.landing_page_order')
                ->get();

            for ($x = 0; $x < count($flowerList); $x++) {
                $imageList = DB::table('images')
                    ->where('products_id', '=', $flowerList[$x]->id)
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get();

                $flowerList[$x]->tag_main = DB::table('tags')->where('lang_id', '=', 'tr')->where('id', '=', $flowerList[$x]->tag_id)->get()[0]->tag_ceo;

                $detailListImage = [];
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    }
                    else if($imageList[$y]->type == "mobile"){
                        $flowerList[$x]->mobileImage = $imageList[$y]->image_url;
                    }
                }
            }

            return response()->json(["status" => 1, "data" => $flowerList], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getRelatedProductsWithCityId', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getFlowerListForAllCity(){
        try {

            /*if($cityId == 'ist'){
                $city_id = '1';
            }
            else{
                $city_id = '2';
            }*/

            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('product_city', 'products.id', '=', 'product_city.product_id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                //->join('landing_with_promo', 'products.id', '=', 'landing_with_promo.product_id')
                ->where('shops.id', '=', 1)
                ->where('descriptions.lang_id', '=', 'tr')
                ->where('product_city.activation_status_id', '=', 1)
                ->where('product_city.active', '=', 1)
                //->where('landing_with_promo.promo_id', '=', 0)
                //->whereRaw('landing_with_promo.city_id = product_city.city_id ')
                ->select('products.choosen','product_city.best_seller','products.cargo_sendable', 'products.tag_id','products.product_type', 'product_city.coming_soon', 'product_city.limit_statu', 'products.id', 'products.name', 'products.price', 'products.old_price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                    'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre', 'products.company_product', 'product_city.city_id'
                    , 'descriptions.how_to_detail', 'products.youtube_url', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description', 'product_city.avalibility_time'
                    , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3', 'products.speciality', 'product_city.landing_page_order as order' )
                ->orderBy('product_city.landing_page_order')
                ->get();

            $now = Carbon::now();
            $tomorrow = Carbon::now();
            $theDayAfter = Carbon::now();
            $nowAnk = Carbon::now();
            $tomorrowAnk = Carbon::now();
            $theDayAfterAnk = Carbon::now();
            $TomorrowTag = false;
            $theDayAfterTag = false;
            $tomorrowDay = ($tomorrow->dayOfWeek + 1) % 8;
            $TomorrowTagAnk = false;
            $theDayAfterTagAnk = false;
            $tomorrowDayAnk = ($tomorrow->dayOfWeek + 1) % 8;

            $nowUps = Carbon::now();
            $tomorrowUps = Carbon::now();
            $theDayAfterUps = Carbon::now();
            $TomorrowTagUps = false;
            $theDayAfterTagUps = false;
            $tomorrowDayUps = ($tomorrowUps->dayOfWeek + 1) % 8;

            $tempNowTagUps = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $nowUps->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 1)
                ->where('delivery_hours.continent_id', 'Ups')
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTagUps = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDayUps)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.continent_id', 'Ups')
                ->where('delivery_hours.city_id', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            if (count($tempNowTagUps) == 0) {
                $NowTagUps = false;
            } else {
                $NowTagUps = true;
                $nowUps->hour(explode(":", $tempNowTagUps[0]->start_hour)[0]);
                if (explode(":", $tempNowTagUps[0]->start_hour)[0] != "18") {
                    $nowUps->addHours(1);
                } else {
                    $nowUps->addHours(-1);
                }
                $nowUps->minute(0);
            }
            if (count($tempTomorrowTagUps) > 0) {
                $TomorrowTagUps = true;
                $tomorrowUps->addDays(1)->hour(explode(":", $tempTomorrowTagUps[0]->start_hour)[0]);
                $tomorrowUps->minute(0);
            }
            //if ($TomorrowTag == false && $NowTag == false) {
            $tempDayAfterTagUps = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', '>', $tomorrowDayUps)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 1)
                ->where('delivery_hours.continent_id', 'Ups')
                ->select('dayHours.start_hour', 'delivery_hours.day_number')
                ->orderBy('delivery_hours.day_number')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            //dd($tempDayAfterTag);

            if (count($tempDayAfterTagUps) > 0) {
                $theDayAfterUps->hour(explode(":", $tempDayAfterTagUps[0]->start_hour)[0]);
                $theDayAfterUps->minute(0);
                $theDayAfterUps->addDays($tempDayAfterTagUps[0]->day_number - $theDayAfterUps->dayOfWeek);
                $theDayAfterTagUps = true;
            } else {
                $tempDayAfterTagUps = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $nowUps->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.city_id', 1)
                    ->where('delivery_hours.continent_id', 'Ups')
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTagUps) > 0) {
                    $theDayAfterUps->hour(explode(":", $tempDayAfterTagUps[0]->start_hour)[0]);
                    $theDayAfterUps->minute(0);
                    $theDayAfterUps->addDays(7 + $tempDayAfterTagUps[0]->day_number - $theDayAfterUps->dayOfWeek);
                    $theDayAfterTagUps = true;
                }
            }

            $tempNowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            $tempNowTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 2)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 2)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();


            //Ankara

            $NowTagAnk = false;
            if (count($tempNowTagAnk) == 0) {
                $NowTagAnk = false;
            } else {
                $NowTagAnk = true;
                $nowAnk->hour(explode(":", $tempNowTagAnk[0]->start_hour)[0]);
                if( explode(":", $tempNowTagAnk[0]->start_hour)[0] != "18"){
                    $nowAnk->addHours(1);
                }
                else{
                    $nowAnk->addHours(-1);
                }
                $nowAnk->minute(0);
            }
            if (count($tempTomorrowTagAnk) > 0) {
                $TomorrowTagAnk = true;
                $tomorrowAnk->addDays(1)->hour(explode(":", $tempTomorrowTagAnk[0]->start_hour)[0]);
                $tomorrowAnk->minute(0);
            }
            //if ($TomorrowTag == false && $NowTag == false) {
            $tempDayAfterTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', '>', $tomorrowDayAnk)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 2)
                ->select('dayHours.start_hour', 'delivery_hours.day_number')
                ->orderBy('delivery_hours.day_number')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            //dd($tempDayAfterTag);

            if (count($tempDayAfterTagAnk) > 0) {
                $theDayAfterAnk->hour(explode(":", $tempDayAfterTagAnk[0]->start_hour)[0]);
                $theDayAfterAnk->minute(0);
                $theDayAfterAnk->addDays($tempDayAfterTagAnk[0]->day_number - $theDayAfterAnk->dayOfWeek);
                $theDayAfterTagAnk = true;
            }
            else {
                $tempDayAfterTagAnk = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $nowAnk->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.city_id', 2)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTagAnk) > 0) {
                    $theDayAfterAnk->hour(explode(":", $tempDayAfterTagAnk[0]->start_hour)[0]);
                    $theDayAfterAnk->minute(0);
                    $theDayAfterAnk->addDays(7 + $tempDayAfterTagAnk[0]->day_number - $theDayAfterAnk->dayOfWeek);
                    $theDayAfterTagAnk = true;
                }
            }

            $NowTag = false;
            if (count($tempNowTag) == 0) {
                $NowTag = false;
            } else {
                $NowTag = true;
                $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                if( explode(":", $tempNowTag[0]->start_hour)[0] != "18"){
                    $now->addHours(1);
                }
                else{
                    $now->addHours(-1);
                }
                $now->minute(0);
            }
            if (count($tempTomorrowTag) > 0) {
                $TomorrowTag = true;
                $tomorrow->addDays(1)->hour(explode(":", $tempTomorrowTag[0]->start_hour)[0]);
                $tomorrow->minute(0);
            }
            //if ($TomorrowTag == false && $NowTag == false) {
            $tempDayAfterTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', '>', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 1)
                ->select('dayHours.start_hour', 'delivery_hours.day_number')
                ->orderBy('delivery_hours.day_number')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            //dd($tempDayAfterTag);

            if (count($tempDayAfterTag) > 0) {
                $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                $theDayAfter->minute(0);
                $theDayAfter->addDays($tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                $theDayAfterTag = true;
            } else {
                $tempDayAfterTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $now->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.city_id', 1)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTag) > 0) {
                    $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                    $theDayAfter->minute(0);
                    $theDayAfter->addDays(7 + $tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                    $theDayAfterTag = true;
                }
            }
            //}

            for ($x = 0; $x < count($flowerList); $x++) {

                $tempFlowerNowTagUps = $NowTagUps;
                $tempFlowerTomorrowTagUps = $TomorrowTagUps;
                if ($flowerList[$x]->avalibility_time > $nowUps) {
                    $tempFlowerNowTagUps = false;
                }
                $nowTemp2Ups = Carbon::now();
                if ($nowTemp2Ups > $nowUps) {
                    $tempFlowerNowTagUps = false;
                }
                if ($flowerList[$x]->limit_statu) {
                    $tempFlowerNowTagUps = false;
                    $tempFlowerTomorrowTagUps = false;
                }
                if ($flowerList[$x]->coming_soon) {
                    $tempFlowerNowTagUps = false;
                    $tempFlowerTomorrowTagUps = false;
                }
                if (!$tempFlowerNowTagUps && $flowerList[$x]->avalibility_time > $tomorrowUps) {
                    $tempFlowerTomorrowTagUps = false;
                    //dd($flowerList[$x]);
                }
                if ($theDayAfterTagUps || (!$tempFlowerTomorrowTagUps && !$tempFlowerNowTagUps)) {
                    setlocale(LC_TIME, "");
                    setlocale(LC_ALL, 'tr_TR.utf8');
                    if ($flowerList[$x]->avalibility_time > $theDayAfterUps) {
                        $flowerList[$x]->theDayAfter_ups = new Carbon($flowerList[$x]->avalibility_time);
                        $flowerList[$x]->theDayAfter_ups = $flowerList[$x]->theDayAfter_ups->formatLocalized('%d %B');
                    } else {
                        $flowerList[$x]->theDayAfter_ups = $theDayAfterUps->formatLocalized('%d %B');
                    }
                }
                else{
                    $flowerList[$x]->theDayAfter_ups = $theDayAfterUps->formatLocalized('%d %B');
                }
                $flowerList[$x]->tomorrow_ups = $tempFlowerTomorrowTagUps && !$tempFlowerNowTagUps;
                $flowerList[$x]->today_ups = $tempFlowerNowTagUps;

                //$flowerList[$x]->istanbul = DB::table('product_city')->where('product_id', $flowerList[$x]->id )->where('city_id', 1 )->exists();
                //$flowerList[$x]->ankara = DB::table('product_city')->where('product_id', $flowerList[$x]->id )->where('city_id', 2 )->exists();

                if( $flowerList[$x]->city_id == 2 ){

                    $tempFlowerNowTagAnk = $NowTagAnk;
                    $tempFlowerTomorrowTagAnk = $TomorrowTagAnk;
                    if ($flowerList[$x]->avalibility_time > $nowAnk) {
                        $tempFlowerNowTagAnk = false;
                    }
                    $nowTemp2 = Carbon::now();
                    if($nowTemp2 > $nowAnk){
                        $tempFlowerNowTagAnk = false;
                    }
                    if ($flowerList[$x]->limit_statu) {
                        $tempFlowerNowTagAnk = false;
                        $tempFlowerTomorrowTagAnk = false;
                    }
                    if ($flowerList[$x]->coming_soon) {
                        $tempFlowerNowTagAnk = false;
                        $tempFlowerTomorrowTagAnk = false;
                    }
                    if (!$tempFlowerNowTagAnk && $flowerList[$x]->avalibility_time > $tomorrowAnk) {
                        $tempFlowerTomorrowTagAnk = false;
                        //dd($flowerList[$x]);
                    }
                    if ($theDayAfterTagAnk || (!$tempFlowerTomorrowTagAnk && !$tempFlowerNowTagAnk)) {
                        setlocale(LC_TIME, "");
                        setlocale(LC_ALL, 'tr_TR.utf8');
                        if ($flowerList[$x]->avalibility_time > $theDayAfterAnk) {
                            $flowerList[$x]->theDayAfter = new Carbon($flowerList[$x]->avalibility_time);
                            $flowerList[$x]->theDayAfter = $flowerList[$x]->theDayAfter->formatLocalized('%d %B');
                        } else {
                            $flowerList[$x]->theDayAfter = $theDayAfterAnk->formatLocalized('%d %B');
                        }
                    }
                    $flowerList[$x]->tomorrow = $tempFlowerTomorrowTagAnk && !$tempFlowerNowTagAnk;
                    $flowerList[$x]->today = $tempFlowerNowTagAnk;
                    $tagList = DB::table('products_tags')
                        ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                        ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                        ->where('tags.lang_id', '=', 'tr')
                        ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url', 'tags.tag_header')
                        ->get();

                    $pageList = DB::table('flowers_page')
                        ->join('page_flower_production', 'flowers_page.id', '=', 'page_flower_production.page_id')
                        ->where('page_flower_production.product_id', '=', $flowerList[$x]->id)
                        ->where('flowers_page.active', '=', 1)
                        ->select('flowers_page.*')
                        ->get();

                    $flowerList[$x]->bestSellerOrder = 100;

                    if( DB::table('best_seller_products')->where('product_id', $flowerList[$x]->id )->where('city_id', 2)->count() > 0 ){
                        $flowerList[$x]->bestSellerOrder = DB::table('best_seller_products')->where('product_id', $flowerList[$x]->id )->where('city_id', 2)->get()[0]->orderId;
                        array_push($pageList, (object)[
                            'id' => '21',
                            'head' => 'Çok Satanlar',
                            'ist_on' => '1',
                            'ank_on' => '1',
                            'desc' => 'Çok Satanlar',
                            'image' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-selected.svg',
                            'meta_tittle' => 'Çok Satanlar',
                            'meta_desc' => 'Çok Satanlar',
                            'url_name' => 'cok-satanlar',
                            'active' => '1',
                            'created_at' => '2018-04-12 14:53:20'
                        ]);
                    }

                    $flowerList[$x]->pageList = $pageList;

                    $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', 'tr')->get();
                    if (count($primaryTag) > 0) {
                        $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                    } else {
                        $flowerList[$x]->tag_main = 'cicek';
                    }

                    if ($tempFlowerNowTagAnk) {
                        array_push($tagList, (object)[
                            'id' => '999',
                            'tag_header' => 'Aynı Gün Teslim Online Çiçek Gönder - Bloom and Fresh',
                            'tag_ceo' => 'ayni-gun-teslim-cicekler',
                            'tags_name' => 'Hızlı Çiçekler',
                            'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                            'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-selected.svg',
                            'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-unselected.svg',
                            'big_image' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/40X40/aynigunteslim-gold.svg',
                            'banner_image' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler'
                        ]);
                    }

                    $flowerList[$x]->tags = $tagList;
                }
                else{

                    $tempFlowerNowTag = $NowTag;
                    $tempFlowerTomorrowTag = $TomorrowTag;
                    if ($flowerList[$x]->avalibility_time > $now) {
                        $tempFlowerNowTag = false;
                    }
                    $nowTemp2 = Carbon::now();
                    if($nowTemp2 > $now){
                        $tempFlowerNowTag = false;
                    }
                    if ($flowerList[$x]->limit_statu) {
                        $tempFlowerNowTag = false;
                        $tempFlowerTomorrowTag = false;
                    }
                    if ($flowerList[$x]->coming_soon) {
                        $tempFlowerNowTag = false;
                        $tempFlowerTomorrowTag = false;
                    }
                    if (!$tempFlowerNowTag && $flowerList[$x]->avalibility_time > $tomorrow) {
                        $tempFlowerTomorrowTag = false;
                        //dd($flowerList[$x]);
                    }
                    if ($theDayAfterTag || (!$tempFlowerTomorrowTag && !$tempFlowerNowTag)) {
                        setlocale(LC_TIME, "");
                        setlocale(LC_ALL, 'tr_TR.utf8');
                        if ($flowerList[$x]->avalibility_time > $theDayAfter) {
                            $flowerList[$x]->theDayAfter = new Carbon($flowerList[$x]->avalibility_time);
                            $flowerList[$x]->theDayAfter = $flowerList[$x]->theDayAfter->formatLocalized('%d %B');
                        } else {
                            $flowerList[$x]->theDayAfter = $theDayAfter->formatLocalized('%d %B');
                        }
                    }
                    $flowerList[$x]->tomorrow = $tempFlowerTomorrowTag && !$tempFlowerNowTag;
                    $flowerList[$x]->today = $tempFlowerNowTag;
                    $tagList = DB::table('products_tags')
                        ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                        ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                        ->where('tags.lang_id', '=', 'tr')
                        ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url', 'tags.tag_header')
                        ->get();

                    $pageList = DB::table('flowers_page')
                        ->join('page_flower_production', 'flowers_page.id', '=', 'page_flower_production.page_id')
                        ->where('page_flower_production.product_id', '=', $flowerList[$x]->id)
                        ->where('flowers_page.active', '=', 1)
                        ->select('flowers_page.*')
                        ->get();

                    $flowerList[$x]->bestSellerOrder = 100;

                    if( DB::table('best_seller_products')->where('product_id', $flowerList[$x]->id )->where('city_id', 1)->count() > 0 ){
                        $flowerList[$x]->bestSellerOrder = DB::table('best_seller_products')->where('product_id', $flowerList[$x]->id )->where('city_id', 1)->get()[0]->orderId;
                        array_push($pageList, (object)[
                            'id' => '21',
                            'head' => 'Çok Satanlar',
                            'ist_on' => '1',
                            'ank_on' => '1',
                            'desc' => 'Çok Satanlar',
                            'image' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-selected.svg',
                            'meta_tittle' => 'Çok Satanlar',
                            'meta_desc' => 'Çok Satanlar',
                            'url_name' => 'cok-satanlar',
                            'active' => '1',
                            'created_at' => '2018-04-12 14:53:20'
                        ]);
                    }


                    $flowerList[$x]->pageList = $pageList;

                    $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', 'tr')->get();
                    if (count($primaryTag) > 0) {
                        $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                    } else {
                        $flowerList[$x]->tag_main = 'cicek';
                    }

                    if ($tempFlowerNowTag) {
                        array_push($tagList, (object)[
                            'id' => '999',
                            'tag_header' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                            'tag_ceo' => 'ayni-gun-teslim-hizli-cicek-gonder',
                            'tags_name' => 'Hızlı Çiçekler',
                            'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                            'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-selected.svg',
                            'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-unselected.svg',
                            'big_image' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/40X40/aynigunteslim-gold.svg',
                            'banner_image' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler'
                        ]);
                    }
                    $flowerList[$x]->tags = $tagList;
                }
            }

            for ($x = 0; $x < count($flowerList); $x++) {
                $imageList = DB::table('images')
                    ->where('products_id', '=', $flowerList[$x]->id)
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get();
                $detailListImage = [];
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    } else if ($imageList[$y]->type == "mobile") {
                        $flowerList[$x]->mobileImage = $imageList[$y]->image_url;
                    } else if ($imageList[$y]->type == "detailImages") {
                        array_push($detailListImage, $imageList[$y]->image_url);
                    } else if ($imageList[$y]->type == "detailPhoto") {
                        $flowerList[$x]->DetailImage = $imageList[$y]->image_url;
                    }
                }
                if ($flowerList[$x]->youtube_url) {
                    array_push($detailListImage, $flowerList[$x]->youtube_url);
                }
                $flowerList[$x]->detailListImage = $detailListImage;
            }
            return $flowerList;
        }
        catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getProductFeedOnlyUps(){

        $tempProductList = DB::table('products')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->join('tags', 'products.tag_id', '=', 'tags.id')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('descriptions.lang_id', '=', 'tr')
            ->where('product_city.coming_soon', '0')
            ->where('product_city.limit_statu', '0')
            ->where('product_city.activation_status_id', '1')
            ->where('products.id', '!=', '75')
            ->where('products.cargo_sendable', '=', '1')
            ->where('product_city.city_id', '=', '1')
            ->where('products.company_product', '=', '0')
            ->where('tags.lang_id', '=', 'tr')
            ->select('products.price', 'products.id', 'products.name', 'descriptions.landing_page_desc', 'tags.tag_ceo', 'url_title', 'products.url_parametre')
            ->get();

        $tempString = '<?xml version="1.0"?>
            <rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
                <channel>
                    <title>Bloomandfresh Products</title>
                    <link>https://bloomandfresh.com/</link>
                    <description>This is a sample feed containing the required and recommended attributes for a variety of different products</description>';
        foreach ($tempProductList as $product) {
            $tempString = $tempString .
                '<item>
            <g:id>BNF' . $product->id . '</g:id>
            <g:title>' . htmlspecialchars($product->url_title) . '</g:title>
            <g:description>' . htmlspecialchars($product->landing_page_desc) . '</g:description>
            <g:link>https://bloomandfresh.com/' . htmlspecialchars($product->tag_ceo) . '/' . htmlspecialchars($product->url_parametre) . '-' . $product->id . '</g:link>
            <g:image_link>https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/' . $product->id . '.jpg</g:image_link>
            <g:brand>Bloomandfresh</g:brand>
            <g:condition>new</g:condition>
            <g:availability>in stock</g:availability>
            <g:price>' . str_replace(',', '.', $product->price) . ' TRY</g:price>
            <g:identifier_exists>no</g:identifier_exists>
            <g:google_product_category>2899</g:google_product_category>
            </item>';
        }
        $tempString = $tempString . '</channel></rss>';

        return \Response::make($tempString, '200')->header('Content-Type', 'text/xml');

    }

    public function getProductFeed(){

        $tempProductList = DB::table('products')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->join('tags', 'products.tag_id', '=', 'tags.id')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('descriptions.lang_id', '=', 'tr')
            ->where('product_city.coming_soon', '0')
            ->where('product_city.limit_statu', '0')
            ->where('product_city.activation_status_id', '1')
            ->where('products.id', '!=', '75')
            ->where('product_city.city_id', '=', '1')
            ->where('products.company_product', '=', '0')
            ->where('tags.lang_id', '=', 'tr')
            ->select('products.price', 'products.id', 'products.name', 'descriptions.landing_page_desc', 'tags.tag_ceo', 'url_title', 'products.url_parametre')
            ->get();

        $tempString = '<?xml version="1.0"?>
            <rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
                <channel>
                    <title>Bloomandfresh Products</title>
                    <link>https://bloomandfresh.com/</link>
                    <description>This is a sample feed containing the required and recommended attributes for a variety of different products</description>';
        foreach ($tempProductList as $product) {
            $tempString = $tempString .
                '<item>
            <g:id>BNF' . $product->id . '</g:id>
            <g:title>' . htmlspecialchars($product->url_title) . '</g:title>
            <g:description>' . htmlspecialchars($product->landing_page_desc) . '</g:description>
            <g:link>https://bloomandfresh.com/' . htmlspecialchars($product->tag_ceo) . '/' . htmlspecialchars($product->url_parametre) . '-' . $product->id . '</g:link>
            <g:image_link>https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/' . $product->id . '.jpg</g:image_link>
            <g:brand>Bloomandfresh</g:brand>
            <g:condition>new</g:condition>
            <g:availability>in stock</g:availability>
            <g:price>' . str_replace(',', '.', $product->price) . ' TRY</g:price>
            <g:identifier_exists>no</g:identifier_exists>
            <g:google_product_category>2899</g:google_product_category>
            </item>';
        }
        $tempString = $tempString . '</channel></rss>';

        return \Response::make($tempString, '200')->header('Content-Type', 'text/xml');

    }

    public function getFlowerListWithCity($siteId, $langId, $cityId)
    {
        try {

            if ($cityId == 'ist') {
                $city_id = '1';
            } else {
                $city_id = '2';
            }

            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->join('product_city', 'products.id', '=', 'product_city.product_id')
                ->where('shops.id', '=', $siteId)
                ->where('product_city.city_id', $city_id)
                ->where('descriptions.lang_id', '=', $langId)
                ->where('product_city.activation_status_id', '=', 1)
                ->select('products.tag_id', 'product_city.coming_soon','products.product_type', 'product_city.limit_statu', 'products.id', 'products.name', 'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                    'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre', 'products.company_product'
                    , 'descriptions.how_to_detail', 'products.youtube_url', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description', 'product_city.avalibility_time'
                    , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3', 'products.speciality'
                )
                ->orderBy('product_city.landing_page_order')
                ->get();

            /*
            $now = Carbon::now();
            $tomorrow = Carbon::now();
            $theDayAfter = Carbon::now();
            $TomorrowTag = false;
            $theDayAfterTag = false;
            $tomorrowDay = ($tomorrow->dayOfWeek + 1) % 8;
            $tempNowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', $city_id)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', $city_id)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            $NowTag = false;
            if (count($tempNowTag) == 0) {
                $NowTag = false;
            } else {
                $NowTag = true;
                $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                if($tempNowTag[0]->start_hour != "18"){
                    $now->addHours(1);
                }
                $now->minute(0);
            }
            if (count($tempTomorrowTag) > 0) {
                $TomorrowTag = true;
                $tomorrow->addDays(1)->hour(explode(":", $tempTomorrowTag[0]->start_hour)[0]);
                $tomorrow->minute(0);
            }
            //if ($TomorrowTag == false && $NowTag == false) {
            $tempDayAfterTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', '>', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', $city_id)
                ->select('dayHours.start_hour', 'delivery_hours.day_number')
                ->orderBy('delivery_hours.day_number')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            //dd($tempDayAfterTag);

            if (count($tempDayAfterTag) > 0) {
                $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                $theDayAfter->minute(0);
                $theDayAfter->addDays($tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                $theDayAfterTag = true;
            } else {
                $tempDayAfterTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $now->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.city_id', $city_id)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTag) > 0) {
                    $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                    $theDayAfter->minute(0);
                    $theDayAfter->addDays(7 + $tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                    $theDayAfterTag = true;
                }
            }
            //}

            for ($x = 0; $x < count($flowerList); $x++) {
                $tempFlowerNowTag = $NowTag;
                $tempFlowerTomorrowTag = $TomorrowTag;
                if ($flowerList[$x]->avalibility_time > $now) {
                    $tempFlowerNowTag = false;
                }
                $nowTemp2 = Carbon::now();
                if($nowTemp2 > $now){
                    $tempFlowerNowTag = false;
                }
                if ($flowerList[$x]->limit_statu) {
                    $tempFlowerNowTag = false;
                    $tempFlowerTomorrowTag = false;
                }
                if ($flowerList[$x]->coming_soon) {
                    $tempFlowerNowTag = false;
                    $tempFlowerTomorrowTag = false;
                }
                if (!$tempFlowerNowTag && $flowerList[$x]->avalibility_time > $tomorrow) {
                    $tempFlowerTomorrowTag = false;
                    //dd($flowerList[$x]);
                }
                if ($theDayAfterTag || (!$tempFlowerTomorrowTag && !$tempFlowerNowTag)) {
                    setlocale(LC_TIME, "");
                    setlocale(LC_ALL, 'tr_TR.utf8');
                    if ($flowerList[$x]->avalibility_time > $theDayAfter) {
                        $flowerList[$x]->theDayAfter = new Carbon($flowerList[$x]->avalibility_time);
                        $flowerList[$x]->theDayAfter = $flowerList[$x]->theDayAfter->formatLocalized('%d %B');
                    } else {
                        $flowerList[$x]->theDayAfter = $theDayAfter->formatLocalized('%d %B');
                    }
                }
                $flowerList[$x]->tomorrow = $tempFlowerTomorrowTag && !$tempFlowerNowTag;
                $flowerList[$x]->today = $tempFlowerNowTag;
                $tagList = DB::table('products_tags')
                    ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                    ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                    ->where('tags.lang_id', '=', $langId)
                    ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url')
                    ->get();

                $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', $langId)->get();
                if (count($primaryTag) > 0) {
                    $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                } else {
                    $flowerList[$x]->tag_main = 'cicek';
                }

                if ($tempFlowerNowTag) {
                    array_push($tagList, (object)[
                        'id' => '999',
                        'tag_header' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'tag_ceo' => 'ayni-gun-teslim-hizli-cicek-gonder',
                        'tags_name' => 'Hızlı Çiçekler',
                        'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_selected.svg',
                        'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_unselected.svg'
                    ]);
                }
                $flowerList[$x]->tags = $tagList;
            }*/

            $now = Carbon::now();
            $tomorrow = Carbon::now();
            $theDayAfter = Carbon::now();
            $TomorrowTag = false;
            $theDayAfterTag = false;
            $tomorrowDay = ($tomorrow->dayOfWeek + 1) % 8;
            $tempNowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', $city_id)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', $city_id)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            $NowTag = false;
            if (count($tempNowTag) == 0) {
                $NowTag = false;
            } else {
                $NowTag = true;
                $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                //if($tempNowTag[0]->start_hour != "18"){
                //    $now->addHours(1);
                //}
                if ($now->hour != "18") {
                    $now->addHours(1);
                } else if ($now->hour == "18") {
                    $now->subHours(1);
                }
                $now->minute(0);
            }
            if (count($tempTomorrowTag) > 0) {
                $TomorrowTag = true;
                $tomorrow->addDays(1)->hour(explode(":", $tempTomorrowTag[0]->start_hour)[0]);
                $tomorrow->minute(0);
            }
            //if ($TomorrowTag == false && $NowTag == false) {
            $tempDayAfterTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', '>', $tomorrowDay)
                ->where('delivery_hours.city_id', $city_id)
                ->where('dayHours.active', 1)
                ->select('dayHours.start_hour', 'delivery_hours.day_number')
                ->orderBy('delivery_hours.day_number')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            if (count($tempDayAfterTag) > 0) {
                $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                $theDayAfter->minute(0);
                $theDayAfter->addDays($tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                $theDayAfterTag = true;
            } else {
                $tempDayAfterTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $now->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTag) > 0) {
                    $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                    $theDayAfter->minute(0);
                    $theDayAfter->addDays(7 + $tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                    $theDayAfterTag = true;
                }
            }
            //}

            for ($x = 0; $x < count($flowerList); $x++) {
                $tempFlowerNowTag = $NowTag;
                $tempFlowerTomorrowTag = $TomorrowTag;
                if ($flowerList[$x]->avalibility_time > $now) {
                    $tempFlowerNowTag = false;
                }
                $nowTemp2 = Carbon::now();
                if ($nowTemp2 > $now) {
                    $tempFlowerNowTag = false;
                }
                if ($flowerList[$x]->limit_statu) {
                    $tempFlowerNowTag = false;
                    $tempFlowerTomorrowTag = false;
                }
                if ($flowerList[$x]->coming_soon) {
                    $tempFlowerNowTag = false;
                    $tempFlowerTomorrowTag = false;
                }
                if (!$tempFlowerNowTag && $flowerList[$x]->avalibility_time > $tomorrow) {
                    $tempFlowerTomorrowTag = false;
                    //dd($flowerList[$x]);
                }
                if ($theDayAfterTag || (!$tempFlowerTomorrowTag && !$tempFlowerNowTag)) {
                    setlocale(LC_TIME, "");
                    setlocale(LC_ALL, 'tr_TR.utf8');
                    if ($flowerList[$x]->avalibility_time > $theDayAfter) {
                        $flowerList[$x]->theDayAfter = new Carbon($flowerList[$x]->avalibility_time);
                        $flowerList[$x]->theDayAfter = $flowerList[$x]->theDayAfter->formatLocalized('%d %B');
                    } else {
                        $flowerList[$x]->theDayAfter = $theDayAfter->formatLocalized('%d %B');
                    }
                }
                $flowerList[$x]->tomorrow = $tempFlowerTomorrowTag && !$tempFlowerNowTag;
                $flowerList[$x]->today = $tempFlowerNowTag;
                $tagList = DB::table('products_tags')
                    ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                    ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                    ->where('tags.lang_id', '=', $langId)
                    ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url', 'tags.tag_header')
                    ->get();

                $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', $langId)->get();
                if (count($primaryTag) > 0) {
                    $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                } else {
                    $flowerList[$x]->tag_main = 'cicek';
                }

                /*if ($tempFlowerNowTag) {
                    array_push($tagList, (object)[
                        'id' => '999',
                        'tag_header' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'tag_ceo' => 'ayni-gun-teslim-hizli-cicek-gonder',
                        'tags_name' => 'Hızlı Çiçekler',
                        'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_selected.svg',
                        'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_unselected.svg'
                    ]);
                }*/
                $flowerList[$x]->tags = $tagList;
            }

            for ($x = 0; $x < count($flowerList); $x++) {
                $imageList = DB::table('images')
                    ->where('products_id', '=', $flowerList[$x]->id)
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get();
                $detailListImage = [];
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    } else if ($imageList[$y]->type == "detailImages") {
                        array_push($detailListImage, $imageList[$y]->image_url);
                    } else if ($imageList[$y]->type == "mobile") {
                        $flowerList[$x]->mobileImage = $imageList[$y]->image_url;
                    } else if ($imageList[$y]->type == "detailPhoto") {
                        $flowerList[$x]->DetailImage = $imageList[$y]->image_url;
                    }
                }
                if ($flowerList[$x]->youtube_url) {
                    array_push($detailListImage, $flowerList[$x]->youtube_url);
                }
                $flowerList[$x]->detailListImage = $detailListImage;
            }
            return $flowerList;
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getCityListByCity($site, $cityId)
    {
        try {

            if ($cityId == 'ist') {
                $city_id = '1';
            } else {
                $city_id = '2';
            }

            $tempVar = DB::table('delivery_locations')->where('shop_id', $site)->where('active', 1)->where('city_id', $city_id)->orderBy('district')->get();
            return response()->json($tempVar);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getCityList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getActiveChocolateWithCity($cityId)
    {
        try {

            if ($cityId == 'ist') {
                $city_id = '1';
            } else {
                $city_id = '2';
            }

            if (DB::table('cross_sell_options')->where('city_id', $city_id)->get()[0]->active == false)
                return response()->json(["status" => -1, "description" => 400], 400);
            $tempCrossSellProducts = DB::table('cross_sell_products')->where('status', 1)->where('city_id', $city_id)->orderBy('sort_number')->get();
            if (count($tempCrossSellProducts) == 0)
                return response()->json(["status" => -1, "description" => 400], 400);
            else {
                foreach ($tempCrossSellProducts as $tempRow) {
                    $tempRow->price = str_replace(',', '.', $tempRow->price);
                }
                return response()->json(["status" => 1, "data" => $tempCrossSellProducts], 200);
            }
        } catch (\Exception $e) {
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getLocationsWithCity($cityId)
    {

        if ($cityId == 'ist') {
            $city_id = '1';
        } else {
            $city_id = '2';
        }

        $tempLocations = DB::table('delivery_locations')->where('active', 1)->where('city_id', $city_id)->orderBy('district')->select('district')->get();
        return response()->json(["status" => 1, "locations" => $tempLocations], 200);
    }

    public function getProductSoonTimeWithLocation($id, $location)
    {
        try {
            $flower = DB::table('products')
                ->join('product_city', 'products.id', '=', 'product_city.product_id')
                ->where('products.id', '=', $id)
                ->where('product_city.city_id', $location)
                ->select('product_city.*')
                ->get()[0];

            $now = Carbon::now();
            $tomorrow = Carbon::now();
            $theDayAfter = Carbon::now();
            $tomorrowDay = ($tomorrow->dayOfWeek + 1) % 8;

            $continent_ids = [
                (object)[
                    'continent_id' => 'Asya',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Avrupa',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Avrupa-2',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Avrupa-3',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Asya-2',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Ankara-1',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Ankara-2',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Ups',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ]
            ];

            foreach ($continent_ids as $continent) {
                $tempNowTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', $now->dayOfWeek)
                    ->where('delivery_hours.continent_id', $continent->continent_id)
                    ->where('dayHours.active', 1)
                    ->select('dayHours.start_hour')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();
                $tempTomorrowTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', $tomorrowDay)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.continent_id', $continent->continent_id)
                    ->select('dayHours.start_hour')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                $NowTag = false;
                $TomorrowTag = false;
                $theDayAfterTag = false;
                if (count($tempNowTag) == 0) {
                    $NowTag = false;
                } else {
                    $NowTag = true;
                    $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                    
                    if( $now->hour == "11" && ( $continent->continent_id == 'Asya' || $continent->continent_id == 'Asya-2' ) ){
                        $now->addHours(3);
                    }
                    else if ($now->hour != "18") {
                        $now->addHours(1);
                    } else if ($now->hour == "18") {
                        $now->subHours(1);
                    }
                    $now->minute(0);
                }
                if (count($tempTomorrowTag) > 0) {
                    $TomorrowTag = true;
                    $tomorrow->addDays(1)->hour(explode(":", $tempTomorrowTag[0]->start_hour)[0]);
                    $tomorrow->minute(0);
                }
                //if ($TomorrowTag == false && $NowTag == false) {
                $tempDayAfterTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '>', $tomorrowDay)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.continent_id', $continent->continent_id)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTag) > 0) {
                    $continent->theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                    $continent->theDayAfter->minute(0);
                    $continent->theDayAfter->addDays($tempDayAfterTag[0]->day_number - $continent->theDayAfter->dayOfWeek);
                    $theDayAfterTag = true;
                } else {
                    $tempDayAfterTag = DB::table('delivery_hours')
                        ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                        ->where('delivery_hours.day_number', '<', $now->dayOfWeek)
                        ->where('dayHours.active', 1)
                        ->where('delivery_hours.continent_id', $continent->continent_id)
                        ->select('dayHours.start_hour', 'delivery_hours.day_number')
                        ->orderBy('delivery_hours.day_number')
                        ->orderBy('dayHours.start_hour', 'DESC')
                        ->get();

                    if (count($tempDayAfterTag) > 0) {
                        $continent->theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                        $continent->theDayAfter->minute(0);
                        $continent->theDayAfter->addDays(7 + $tempDayAfterTag[0]->day_number - $continent->theDayAfter->dayOfWeek);
                        $theDayAfterTag = true;
                    }
                }
                //}

                $continent->now = $NowTag;
                $continent->tomorrow = $TomorrowTag;
                //$tempFlowerNowTag = $NowTag;
                //$tempFlowerTomorrowTag = $TomorrowTag;
                if ($flower->avalibility_time > $now) {
                    $continent->now = false;
                }
                $nowTemp2 = Carbon::now();
                if ($nowTemp2 > $now) {
                    $continent->now = false;
                }
                if ($flower->limit_statu) {
                    $continent->now = false;
                    $continent->tomorrow = false;
                }
                if ($flower->coming_soon) {
                    $continent->now = false;
                    $continent->tomorrow = false;
                }
                if (!$continent->now && $flower->avalibility_time > $tomorrow) {
                    $continent->tomorrow = false;
                    //dd($flowerList[$x]);
                }
                if ($theDayAfterTag || (!$continent->tomorrow && !$continent->now)) {
                    setlocale(LC_TIME, "");
                    setlocale(LC_ALL, 'tr_TR.utf8');
                    if ($flower->avalibility_time > $continent->theDayAfter) {
                        $continent->theDayAfter = new Carbon($flower->avalibility_time);
                        $continent->theDayAfter = $continent->theDayAfter->formatLocalized('%d %B');
                    } else {
                        $continent->theDayAfter = $continent->theDayAfter->formatLocalized('%d %B');
                    }
                }
                $continent->tomorrow = $continent->tomorrow && !$continent->now;
                $continent->now = $continent->now;
            }

            return response()->json(["status" => 1, "data" => $continent_ids], 200);

        } catch (\Exception $e) {
            logEventController::logErrorToDB('getProductSoonTime', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getProductSoonTime($id)
    {
        try {
            $flower = DB::table('products')
                ->where('products.id', '=', $id)
                ->get()[0];

            $now = Carbon::now();
            $tomorrow = Carbon::now();
            $theDayAfter = Carbon::now();
            $tomorrowDay = ($tomorrow->dayOfWeek + 1) % 8;

            $continent_ids = [
                (object)[
                    'continent_id' => 'Asya',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Avrupa',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Avrupa-2',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Avrupa-3',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Asya-2',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Ankara-1',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Ankara-2',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ]
            ];

            foreach ($continent_ids as $continent) {
                $tempNowTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', $now->dayOfWeek)
                    ->where('delivery_hours.continent_id', $continent->continent_id)
                    ->where('dayHours.active', 1)
                    ->select('dayHours.start_hour')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();
                $tempTomorrowTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', $tomorrowDay)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.continent_id', $continent->continent_id)
                    ->select('dayHours.start_hour')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                $NowTag = false;
                $TomorrowTag = false;
                $theDayAfterTag = false;
                if (count($tempNowTag) == 0) {
                    $NowTag = false;
                } else {
                    $NowTag = true;
                    $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                    if ($now->hour != "18") {
                        $now->addHours(1);
                    } else if ($now->hour == "18") {
                        $now->subHours(1);
                    }
                    $now->minute(0);
                }
                if (count($tempTomorrowTag) > 0) {
                    $TomorrowTag = true;
                    $tomorrow->addDays(1)->hour(explode(":", $tempTomorrowTag[0]->start_hour)[0]);
                    $tomorrow->minute(0);
                }
                //if ($TomorrowTag == false && $NowTag == false) {
                $tempDayAfterTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '>', $tomorrowDay)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.continent_id', $continent->continent_id)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTag) > 0) {
                    $continent->theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                    $continent->theDayAfter->minute(0);
                    $continent->theDayAfter->addDays($tempDayAfterTag[0]->day_number - $continent->theDayAfter->dayOfWeek);
                    $theDayAfterTag = true;
                } else {
                    $tempDayAfterTag = DB::table('delivery_hours')
                        ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                        ->where('delivery_hours.day_number', '<', $now->dayOfWeek)
                        ->where('dayHours.active', 1)
                        ->where('delivery_hours.continent_id', $continent->continent_id)
                        ->select('dayHours.start_hour', 'delivery_hours.day_number')
                        ->orderBy('delivery_hours.day_number')
                        ->orderBy('dayHours.start_hour', 'DESC')
                        ->get();

                    if (count($tempDayAfterTag) > 0) {
                        $continent->theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                        $continent->theDayAfter->minute(0);
                        $continent->theDayAfter->addDays(7 + $tempDayAfterTag[0]->day_number - $continent->theDayAfter->dayOfWeek);
                        $theDayAfterTag = true;
                    }
                }
                //}

                $continent->now = $NowTag;
                $continent->tomorrow = $TomorrowTag;
                //$tempFlowerNowTag = $NowTag;
                //$tempFlowerTomorrowTag = $TomorrowTag;
                if ($flower->avalibility_time > $now) {
                    $continent->now = false;
                }
                $nowTemp2 = Carbon::now();
                if ($nowTemp2 > $now) {
                    $continent->now = false;
                }
                if ($flower->limit_statu) {
                    $continent->now = false;
                    $continent->tomorrow = false;
                }
                if ($flower->coming_soon) {
                    $continent->now = false;
                    $continent->tomorrow = false;
                }
                if (!$continent->now && $flower->avalibility_time > $tomorrow) {
                    $continent->tomorrow = false;
                    //dd($flowerList[$x]);
                }
                if ($theDayAfterTag || (!$continent->tomorrow && !$continent->now)) {
                    setlocale(LC_TIME, "");
                    setlocale(LC_ALL, 'tr_TR.utf8');
                    if ($flower->avalibility_time > $continent->theDayAfter) {
                        $continent->theDayAfter = new Carbon($flower->avalibility_time);
                        $continent->theDayAfter = $continent->theDayAfter->formatLocalized('%d %B');
                    } else {
                        $continent->theDayAfter = $continent->theDayAfter->formatLocalized('%d %B');
                    }
                }
                $continent->tomorrow = $continent->tomorrow && !$continent->now;
                $continent->now = $continent->now;
            }

            return response()->json(["status" => 1, "data" => $continent_ids], 200);

        } catch (\Exception $e) {
            logEventController::logErrorToDB('getProductSoonTime', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getCompanyFlowersStatus($id)
    {
        try {
            $tempList = DB::table('companies_info')
                ->where('id', '=', $id)
                ->where('flower_status', '=', 1)
                ->get();

            if (count($tempList) > 0) {
                return response()->json(["data" => 1], 200);
            } else
                return response()->json(["data" => 0], 200);

        } catch (\Exception $e) {
            logEventController::logErrorToDB('getCompanyFlowerStatus', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["data" => 1], 200);
        }
    }

    public function getRelatedProducts($productId)
    {
        try {
            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->join('related_products', 'products.id', '=', 'related_products.related_product')
                ->where('shops.id', '=', 1)
                ->where('descriptions.lang_id', '=', 'tr')
                //->where('products.activation_status_id', '=', 1)
                ->where('related_products.main_product', '=', $productId)
                ->select('products.tag_id', 'products.coming_soon', 'products.activation_status_id', 'products.limit_statu', 'products.id', 'products.name','products.url_parametre' ,
                    'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color', 'descriptions.landing_page_desc'
                )
                ->orderBy('landing_page_order')
                ->get();

            for ($x = 0; $x < count($flowerList); $x++) {
                $imageList = DB::table('images')
                    ->where('products_id', '=', $flowerList[$x]->id)
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get();

                $flowerList[$x]->tag_main = DB::table('tags')->where('lang_id', '=', 'tr')->where('id', '=', $flowerList[$x]->tag_id)->get()[0]->tag_ceo;

                $detailListImage = [];
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    }
                    else if($imageList[$y]->type == "mobile"){
                        $flowerList[$x]->mobileImage = $imageList[$y]->image_url;
                    }
                }
            }

            return response()->json(["status" => 1, "data" => $flowerList], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerListRelated', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getUpMenu()
    {
        try {
            $tempUpMenu = DB::table('up_menu')->orderBy('order')->get();
            if (count($tempUpMenu) == 0)
                return response()->json(["status" => -1, "description" => 400], 400);

            return response()->json(["status" => 1, "data" => $tempUpMenu], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getActiveChocolate()
    {
        try {
            if (DB::table('cross_sell_options')->get()[0]->active == false)
                return response()->json(["status" => -1, "description" => 400], 400);
            $tempCrossSellProducts = DB::table('cross_sell_products')->where('status', 1)->orderBy('sort_number')->get();
            if (count($tempCrossSellProducts) == 0)
                return response()->json(["status" => -1, "description" => 400], 400);
            else {
                foreach ($tempCrossSellProducts as $tempRow) {
                    $tempRow->price = str_replace(',', '.', $tempRow->price);
                }
                return response()->json(["status" => 1, "data" => $tempCrossSellProducts], 200);
            }
        } catch (\Exception $e) {
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function studioBloomGetSuccessData($salesId)
    {
        try {
            $tempStudioBloomData = DB::table('studioBloom')->where('id', $salesId)->where('status', 'Ödeme Yapıldı')->get()[0];
            return response()->json(["status" => 1, "data" => $tempStudioBloomData], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function studioSuccessCallBack(\Illuminate\Http\Request $request)
    {
        //dd('Success');

        DB::beginTransaction();
        try {
            $tempSessionInfo = Request::input('SessionInfo');
            $sale_id = explode("_", $tempSessionInfo)[0];
            $long_sale_id = DB::table('studioBloom')->whereRaw('id like "%' . $sale_id . '%"')->get()[0]->id;
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
                    'sale_id' => $long_sale_id,
                    'transaction_id' => $sale_id,
                    'code' => 'NNNN',
                    'error_message' => 'Doğrulama başarısız!',
                    'log_location' => 'After 3D Check Fail'
                ]);
                DB::commit();

                return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . $long_sale_id . '&error=true');
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
                'sale_id' => $long_sale_id,
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

                $tempMoneyTaken = true;
                $sales_id = explode("_", $xml_response->TransactionId)[0];
                DB::table('is_bank_log')->insert([
                    'sale_id' => $sales_id,
                    'transaction_id' => $xml_response->TransactionId,
                    'code' => '0000',
                    'error_message' => '',
                    'log_location' => 'Sale Success With 3D Secure'
                ]);

                $now = Carbon::now();
                DB::table('studioBloom')->whereRaw('id like "%' . $sales_id . '%"')->update([
                    'status' => 'Ödeme Yapıldı',
                    'payment_date' => $now
                ]);
                //BillingOperation::studioBillingSend($oid);
                DB::commit();
                return redirect()->away($this->site_url . '/studioBloom-satis-basarili?orderId=' . $long_sale_id);
            } else {
                DB::commit();
                return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . $long_sale_id . '&error=true');
            }
        }
        catch (\Exception $e) {
            DB::rollback();
            DB::table('is_bank_log')->insert([
                'sale_id' => $long_sale_id,
                'transaction_id' => 'Exception',
                'code' => 'Exception',
                'error_message' => 'Exception',
                'log_location' => 'StudioBloom exception successCallback'
            ]);
            DB::rollback();
            return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . $long_sale_id . '&error=true');
        }
        /*
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

            if ($request->mdstatus == 0 || $request->mdstatus == 5 || $request->mdstatus == 7 || $request->mdstatus == 8) {
                logEventController::logErrorToDB('studioSuccessCallBack', $request->mdstatus, $request->mdstatus, 'WS', $oid);
                DB::commit();
                return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . $oid);
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
                $now = Carbon::now();
                DB::table('studioBloom')->where('id', $oid)->update([
                    'status' => 'Ödeme Yapıldı',
                    'payment_date' => $now
                ]);
                //BillingOperation::studioBillingSend($oid);
                DB::commit();
                return redirect()->away($this->site_url . '/studioBloom-satis-basarili?orderId=' . $oid);
            } else {
                logEventController::logErrorToDB('3DSuccessTransactionFailErrorStudioBloom', $response->xml()->Transaction->Response->Code, $response->xml()->Transaction->Response->Code, 'WS', $oid);
                DB::commit();
                return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . $oid . '&error=true');
            }
        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('successCallbackExceptionErrorStudioBloom', $e->getCode(), $e->getMessage(), 'WS', $oid);
            return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . $oid . '&error=true');
        }*/
    }

    public function studioErrorCallBack(\Illuminate\Http\Request $request)
    {
        //dd('error');
        //logEventController::logErrorToDB('3DFailErrorStudioBloom', $request->mdstatus, $request->mdstatus, 'WS', $request->oid);

        $tempSessionInfo = Request::input('SessionInfo');
        $sale_id = explode("_", $tempSessionInfo)[0];
        $long_sale_id = DB::table('studioBloom')->whereRaw('id like "%' . $sale_id . '%"')->get()[0]->id;

        return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . $long_sale_id . '&error=true');
    }

    public function completeSaleStudio()
    {

        DB::beginTransaction();
        try {
            $tempStudioBloomRequest = DB::table('studioBloom')->where('id', Request::get('id'))->get()[0];
            //if($tempStudioBloomRequest->price != explode( ',' ,Request::input('price'))[0] ){
            //    return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . Request::get('id') . '&error=true');
            //}
            /*
            $price = str_replace(',', '.', Request::get('newPrice'));
            $price = floatval($price) * 100.00;
            parse_str($price);
            $tempArray = explode(".", $price);
            $price = $tempArray[0];
            $client = new \GuzzleHttp\Client();
            $strType = "sales";
            $strInstallmentCount = "";
            $strOrderID = Request::get('id');
            $strTerminalID = "10025261";
            $strTerminalID_ = "010025261";
            $strStoreKey = "696632333569663233356966323335696632333569663233";
            $strProvisionPassword = "Hakan1234";
            $strSuccessURL = $this->backend_url . "/studio-call-back-success";
            $strErrorURL = $this->backend_url . "/studio-call-back-error";
            $SecurityData = strtoupper(sha1($strProvisionPassword . $strTerminalID_));
            $HashData = strtoupper(sha1($strTerminalID . $strOrderID . $price . $strSuccessURL . $strErrorURL . $strType . $strInstallmentCount . $strStoreKey . $SecurityData));
            $cardNumber = str_replace('-', '', Request::input('card_no'));
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
                "orderid" => Request::get('id'),
                "customeripaddress" => $_SERVER['REMOTE_ADDR'],
                "customeremailaddress" => '',
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
            */

            $pan = str_replace('-', '', Request::input('card_no'));
            $transactionId = substr(Request::get('id'), 0, 8);
            $client = new \GuzzleHttp\Client();
            $randomEnrollment = str_random(10);

            $numberFirstNumber = substr($pan, 0, 1);

            if ($numberFirstNumber == '4') {
                $tempType = '100';
            } else if ($numberFirstNumber == '5') {
                $tempType = '200';
            } else if ($numberFirstNumber == '3') {
                $tempType = '300';
            } else {
                $tempType = '300';
            }

            $price = str_replace(',', '.', Request::get('newPrice'));
            $price = floatval($price) * 100.00;
            parse_str($price);
            $tempArray = explode(".", $price);
            $amount = $tempArray[0];

            if (($amount % 100) < 10) {
                $tempAmount = '0' . (string)($amount % 100);
            } else {
                $tempAmount = (string)($amount % 100);
            }
            $tempAmount = (int)($amount / 100) . '.' . $tempAmount;

            $response = $client->post('https://mpi.vpos.isbank.com.tr/Enrollment.aspx', [
                //$response = $client->post('http://sanalpos.innova.com.tr/ISBANK/MpiWeb/Enrollment.aspx', [
                'body' => [
                    'MerchantId' => '661414755',
                    'MerchantPassword' => 's8I0bBvhlB',
                    'VerifyEnrollmentRequestId' => $transactionId . '-' . $randomEnrollment,
                    'Pan' => $pan,
                    'ExpiryDate' => Request::input('card_year') . Request::input('card_month'),
                    'PurchaseAmount' => $tempAmount,
                    'Currency' => '949',
                    'BrandName' => $tempType,
                    'SessionInfo' => $transactionId . '_' . Request::input('card_cvv'),
                    'SuccessUrl' => 'https://everybloom.com/studio-call-back-success',
                    'FailureUrl' => 'https://everybloom.com/studio-call-back-error'
                ]
            ])->xml();
            //dd($response);
            if ($response->VERes->Status == 'Y') {
                $data = [
                    'ACSUrl' => $response->VERes->ACSUrl,
                    'PAReq' => $response->VERes->PAReq,
                    'TermUrl' => $response->VERes->TermUrl,
                    'MD' => $response->VERes->MD
                ];
                DB::table('is_bank_log')->insert([
                    'sale_id' => $transactionId,
                    'transaction_id' => $transactionId . '-' . $randomEnrollment,
                    'code' => '0000',
                    'error_message' => 'Banka 3D Secure Page bulundu ve yönlendiriliyor.',
                    'log_location' => 'Routing 3D Secure Page'
                ]);
                //Sale::where('id', $sales_id)->update([
                //    'payment_methods' => "Banka sayfasında.",
                //    'created_at' => Carbon::now()
                //]);
                DB::commit();
                return view('before3DIsBank', compact('data'));
            }
            else {
                if ($response->VERes->Status == 'N') {
                    DB::table('is_bank_log')->insert([
                        'sale_id' => $transactionId,
                        'transaction_id' => $transactionId . '-' . $randomEnrollment,
                        'code' => 'NNNN',
                        'error_message' => 'Kart 3D hizmete kayıtlı değil!',
                        'log_location' => 'Routing 3D Secure Page'
                    ]);
                } else {
                    DB::table('is_bank_log')->insert([
                        'sale_id' => $transactionId,
                        'transaction_id' => $transactionId . '-' . $randomEnrollment,
                        'code' => $response->VERes->Status,
                        'error_message' => $response->ResultDetail->ErrorMessage,
                        'log_location' => 'Routing 3D Secure Page'
                    ]);
                }
                DB::commit();
                return redirect()->away('https://bloomandfresh.com/satin-alma/odeme-bilgileri?orderId=' . Request::get('id'));
            }

            //DB::commit();
            //return view('before3D', compact('data'));
        } catch (\Exception $e) {
            DB::rollback();
            logEventController::logErrorToDB('before3DSecure', $e->getCode(), $e->getMessage(), 'WS', '');
            return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . Request::get('id') . '&error=true');
        }
    }

    public function studioBloomCompletePayment()
    {
        DB::beginTransaction();
        $tempMoneyTaken = false;
        try {
            $tempStudioBloomRequest = DB::table('studioBloom')->where('id', Request::get('id'))->get()[0];
            //if($tempStudioBloomRequest->price != explode( ',' ,Request::input('price'))[0] ){
            //    return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . Request::get('id') . '&error=true');
            //}
            /*
            $price = str_replace(',', '.', Request::get('newPrice'));
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
            $HashData = strtoupper(sha1(Request::get('id') . $strTerminalID . $cardNumber . $price . $SecurityData));

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
                    <OrderID>" . Request::get('id') . "</OrderID>
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
                $now = Carbon::now();
                DB::table('studioBloom')->where('id' ,Request::get('id') )->update([
                    'status' => 'Ödeme Yapıldı',
                    'payment_date' => $now
                ]);
                //BillingOperation::studioBillingSend(Request::get('id'));
                DB::commit();
                return redirect()->away($this->site_url . '/studioBloom-satis-basarili?orderId=' . Request::get('id'));
            } else {
                logEventController::logErrorToDB('studioBloomPaymentFail', $response->xml()->Transaction->Response->Code, $response->xml()->Transaction->Response->Code, 'WS', Request::get('id'));
                DB::commit();
                return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . Request::get('id') . '&error=true');
            }
            */

            $pan = str_replace('-', '', Request::input('card_no'));
            $tempMoneyTaken = false;
            $tempType = '';
            $number = substr($pan, 0, 6);
            $numberFirstNumber = substr($pan, 0, 1);

            if ($numberFirstNumber == '4') {
                $tempType = '100';
            } else if ($numberFirstNumber == '5') {
                $tempType = '200';
            } else if ($numberFirstNumber == '3') {
                $tempType = '300';
            } else {
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

            $randomEnrollment = str_random(10);
            $transactionId = substr(Request::get('id'), 0, 8);
            $transactionId = $transactionId . '-' . $randomEnrollment;

            $price = str_replace(',', '.', Request::get('newPrice'));
            $price = floatval($price) * 100.00;
            parse_str($price);
            $tempArray = explode(".", $price);
            $amount = $tempArray[0];

            if (($amount % 100) < 10) {
                $tempAmount = '0' . (string)($amount % 100);
            } else {
                $tempAmount = (string)($amount % 100);
            }
            $tempAmount = (int)($amount / 100) . '.' . $tempAmount;
            $tempExpiredDate = '20' . Request::input('card_year') . Request::input('card_month');
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
                <Cvv>' . Request::input('card_cvv') . '</Cvv>
                <Expiry>' . $tempExpiredDate . '</Expiry>
                <OrderId>' . Request::get('id') . '</OrderId>
            </VposRequest>';
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

            if ($xml_response->ResultCode == "00") {
                $now = Carbon::now();
                DB::table('studioBloom')->where('id', Request::get('id'))->update([
                    'status' => 'Ödeme Yapıldı',
                    'payment_date' => $now
                ]);
                //BillingOperation::studioBillingSend(Request::get('id'));
                DB::commit();
                return redirect()->away($this->site_url . '/studioBloom-satis-basarili?orderId=' . Request::get('id'));
            } else {
                DB::table('is_bank_log')->insert([
                    'sale_id' => Request::get('id'),
                    'transaction_id' => 'Exception',
                    'code' => 'Exception',
                    'error_message' => 000,
                    'log_location' => 'StudioBloom before Sale Without 3D'
                ]);
                DB::commit();
                return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . Request::get('id') . '&error=true');
            }

        } catch (\Exception $e) {
            DB::rollback();
            DB::table('is_bank_log')->insert([
                'sale_id' => Request::get('id'),
                'transaction_id' => 'Exception',
                'code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'log_location' => 'StudioBloom Exception before Sale Without 3D'
            ]);
            //logEventController::logErrorToDB('studioBloomPaymentFail', $e->getCode(), $e->getMessage(), 'WS', '');
            return redirect()->away($this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . Request::get('id') . '&error=true');
        }
    }

    public function getStudioBloomPaymentInfo($paymentCode)
    {
        try {
            $tempPaymentInfo = DB::table('studioBloom')->where('id', $paymentCode)->where('status', 'Ödeme Bekleniyor')->select('customer_name', 'customer_surname', 'flower_name as name', 'flower_desc as landing_page_desc', 'wanted_date', 'price', 'id')->get()[0];
            //$tempPaymentInfo->newPrice = floatval(floatval($tempPaymentInfo->price) * 118 / 100);
            //$tempPaymentInfo->newPrice = str_replace('.', ',', $tempPaymentInfo->newPrice);
            //$tempPaymentInfo->price = str_replace('.', ',', $tempPaymentInfo->price);
            return response()->json(["status" => 1, "data" => $tempPaymentInfo], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getStudioBloomPaymentInfo', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function activateCoupon()
    {
        if (Request::input('user') == 'testUser' && Request::input('password') == 'testPassword') {
            $tempCoupon = DB::table('marketing_acts')->where('publish_id', Request::input('coupon_id'))->get();
            if (count($tempCoupon) > 0) {
                $now = Carbon::now();
                $now->addYear(1);
                if ($tempCoupon[0]->valid == 1 || $tempCoupon[0]->used == 1 || $tempCoupon[0]->active == 1) {
                    return ["status" => 0, "description" => 'Already Used'];
                }
                DB::table('marketing_acts')->where('publish_id', Request::input('coupon_id'))->update([
                    'expiredDate' => $now,
                    'valid' => 1
                ]);
                return ["status" => 1, "description" => 'Success'];
            } else {
                return ["status" => 0, "description" => 'Coupon Not Fount!'];
            }
        } else {
            return ["status" => 0, "description" => 'Login Fail!'];
        }
    }

    public function howToDetail($detailPage)
    {
        $queryString = '';
        return view('phpClient.bnfHowTo', compact('queryString'));
    }

    public function howTo()
    {
        $queryString = '';
        return view('phpClient.bnfHowTo', compact('queryString'));
    }

    public function getSupport()
    {
        $queryString = '';
        return view('phpClient.bnfSupport', compact('queryString'));
    }

    public function contactUs()
    {
        $queryString = '';
        return view('phpClient.bnfContactUs', compact('queryString'));
    }

    public function contactCompanyDelivery()
    {
        $queryString = '';
        return view('phpClient.bnfCompanyDelivery', compact('queryString'));
    }

    public function getAboutUs()
    {
        $queryString = '';
        return view('phpClient.bnfAboutUs', compact('queryString'));
    }

    public function contracts()
    {
        $queryString = '';
        return view('phpClient.bnfContract', compact('queryString'));
    }

    public function testFlowersWithTag($tag_name)
    {
        $siteId = 1;
        $langId = 'tr';
        $flowerList = DB::table('shops')
            ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
            ->join('products', 'products_shops.products_id', '=', 'products.id')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->where('shops.id', '=', $siteId)
            ->where('descriptions.lang_id', '=', $langId)
            ->where('products.activation_status_id', '=', 1)
            ->select('products.coming_soon', 'products.limit_statu', 'products.id', 'products.name', 'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc'
                , 'descriptions.how_to_detail', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description'
                , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3'
            )
            ->orderBy('landing_page_order')
            ->get();


        for ($x = 0; $x < count($flowerList); $x++) {

            $tagList = DB::table('products_tags')
                ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                ->where('tags.lang_id', '=', $langId)
                ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url')
                ->get();
            $flowerList[$x]->tags = $tagList;

            //if( count(DB::table('products_tags')->where('products_id', $flowerList[$x]->id )->where('tags_id' ,$tag_name)->get()) == 0 ){
            //unset($flowerList[$x]);
            //continue;
            //}
        }

        $selectedTag = DB::table('tags')->where('tag_ceo', $tag_name)->get()[0];

        foreach ($flowerList as $index => $flower) {
            if (count(DB::table('products_tags')->join('tags', 'products_tags.tags_id', '=', 'tags.id')->where('products_id', $flower->id)->where('tags.tag_ceo', $tag_name)->get()) == 0) {
                unset($flowerList[$index]);
            }
            //else
            //dd($flowerList);
        }

        foreach ($flowerList as $flower) {
            $imageList = DB::table('images')
                ->where('products_id', '=', $flower->id)
                ->select('type', 'image_url')
                ->orderBy('order_no')
                ->get();
            for ($y = 0; $y < count($imageList); $y++) {
                if ($imageList[$y]->type == "main") {
                    $flower->MainImage = $imageList[$y]->image_url;
                }
            }
        }

        $tagList = DB::table('tags')->select('tag_header', 'id', 'tags_name', 'tag_ceo', 'description', 'active_image_url', 'inactive_image_url')
            ->where('lang_id', $langId)->orderBy('tags_name')->get();

        $filterPage = true;

        $queryString = '';
        return view('phpClient.flowersPage', compact('flowerList', 'tagList', 'selectedTag', 'filterPage', 'queryString'));
    }

    public function testFlowers()
    {
        $siteId = 1;
        $langId = 'tr';
        $flowerList = DB::table('shops')
            ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
            ->join('products', 'products_shops.products_id', '=', 'products.id')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->where('shops.id', '=', $siteId)
            ->where('descriptions.lang_id', '=', $langId)
            ->where('products.activation_status_id', '=', 1)
            ->select('products.coming_soon', 'products.limit_statu', 'products.id', 'products.name', 'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc'
                , 'descriptions.how_to_detail', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description'
                , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3'
            )
            ->orderBy('landing_page_order')
            ->get();

        for ($x = 0; $x < count($flowerList); $x++) {
            $tagList = DB::table('products_tags')
                ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                ->where('tags.lang_id', '=', $langId)
                ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url')
                ->get();
            $flowerList[$x]->tags = $tagList;
        }

        for ($x = 0; $x < count($flowerList); $x++) {
            $imageList = DB::table('images')
                ->where('products_id', '=', $flowerList[$x]->id)
                ->select('type', 'image_url')
                ->orderBy('order_no')
                ->get();
            for ($y = 0; $y < count($imageList); $y++) {
                if ($imageList[$y]->type == "main") {
                    $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                }
            }
        }

        $tagList = DB::table('tags')->select('tag_header', 'id', 'tags_name', 'tag_ceo', 'description', 'active_image_url', 'inactive_image_url')
            ->where('lang_id', $langId)->orderBy('tags_name')->get();

        $filterPage = false;
        $queryString = '';

        return view('phpClient.flowersPage', compact('flowerList', 'tagList', 'selectedTag', 'filterPage', 'queryString'));
    }

    public function testSaleReceiver()
    {
        $queryString = '';
        return view('phpClient.salesReceiver', compact('queryString'));
    }

    public function testSalePayment()
    {
        $queryString = '';
        return view('phpClient.salesPayment', compact('queryString'));
    }

    public function testSaleSuccess()
    {
        $queryString = '';
        return view('phpClient.salesSucess', compact('queryString'));
    }

    public function getHourListWithProductIdWithNow($productId)
    {
        try {
            $dayList = DB::table('delivery_hours')->orderBy('continent_id')->get();
            $i = -1;

            $specialDays = DB::table('speacial_delivery_hours')->orderBy('delivery_date')->get();

            foreach ($dayList as $day) {
                $now = Carbon::now();
                $i++;
                $tempProductDate2 = DB::table('products')->where('id', $productId)->select('avalibility_time', 'avalibility_time_end')->get()[0];
                $tempProductDate = $tempProductDate2->avalibility_time;
                $tempProductDateEnd = $tempProductDate2->avalibility_time_end;
                $productDate = new Carbon($tempProductDate);
                $productDateEnd = new Carbon($tempProductDateEnd);
                $hoursList = DB::table('dayHours')->where('day_number', $day->id)->where('active', true)->orderBy('start_hour')->get();
                $myArray = [];

                if ($now->dayOfWeek > $day->day_number) {
                    $tempDayNumber = 7 - $now->dayOfWeek + $day->day_number;
                } else {
                    $tempDayNumber = $day->day_number - $now->dayOfWeek;
                }

                foreach ($hoursList as $hour) {
                    $tempHour = explode(":", $hour->start_hour)[0];
                    $tempMin = explode(":", $hour->start_hour)[1];
                    $hourTemp = 1;
                    if ($tempHour == '18') {
                        $hourTemp = -1;
                    }
                    $tempNow = Carbon::now();
                    $tempNow->addDay($tempDayNumber);

                    $tempLater = Carbon::now();
                    $tempLater->addDay($tempDayNumber);

                    $tempLater->hour(intval($tempHour));
                    $tempLater->minute(intval($tempMin));

                    $tempNow->hour(intval($tempHour));
                    $tempNow->minute(intval($tempMin));
                    //$tempNow->minute(0);
                    if ($tempNow < $productDate || $tempNow > $productDateEnd) {
                        continue;
                    } else {
                        if (intval($now->dayOfWeek) == intval($day->day_number)) {
                            if ((intval($tempHour) + $hourTemp) > $now->hour) {
                                array_push($myArray, $hour);
                            }
                        } else {
                            array_push($myArray, $hour);
                        }
                    }
                }
                $day->hours = $myArray;

                if ($now->dayOfWeek > $day->day_number) {
                    $day->day_number = 7 - $now->dayOfWeek + $day->day_number;
                } else {
                    $day->day_number = $day->day_number - $now->dayOfWeek;
                }

                if (count($myArray) == 0) {
                    unset($dayList[$i]);
                }
                $tempDate = Carbon::now();
                $now->addDay($day->day_number);
                //if($productDate > $now) {
                //    unset($dayList[$i]);
                //}
                //if($productDateEnd < $now){
                //    unset($dayList[$i]);
                //}
            }
            usort($dayList, function ($a, $b) {
                return $a->day_number - $b->day_number;
            });
            foreach ($specialDays as $days) {
                for ($x = 0; $x < 21; $x++) {
                    $tempNow = Carbon::now();
                    $tempNow->addDay($x);
                    $tempNow->second(0);
                    $tempNow->minute(0);
                    $tempNow->hour(0);

                    $tempNow2 = Carbon::now();
                    $tempNow2->addDay($x);
                    $tempNow2->second(59);
                    $tempNow2->minute(59);
                    $tempNow2->hour(23);

                    $tempProductDate2 = DB::table('products')->where('id', $productId)->select('avalibility_time', 'avalibility_time_end')->get()[0];
                    $tempProductDate = $tempProductDate2->avalibility_time;
                    $tempProductDateEnd = $tempProductDate2->avalibility_time_end;
                    $productDate = new Carbon($tempProductDate);
                    $productDateEnd = new Carbon($tempProductDateEnd);

                    if ($days->delivery_date == $tempNow && $productDate <= $tempNow2 && $productDateEnd > $tempNow) {

                        $hoursList = DB::table('speacial_day_hours')->where('day_number', $days->id)->where('active', true)->get();
                        $myArray = [];
                        foreach ($hoursList as $hour) {
                            $tempHour = explode(":", $hour->start_hour)[0];
                            $hourTemp = 1;
                            if ($tempHour == '18') {
                                $hourTemp = 0;
                            }
                            //if (intval($now->dayOfWeek) == intval($day->day_number)) {
                            //    if ((intval($tempHour) + $hourTemp) > $now->hour) {
                            //        array_push($myArray, $hour);
                            //    }
                            //} else {
                            array_push($myArray, $hour);
                            //}
                        }
                        $days->hours = $myArray;
                        $days->day_number = $x;
                        if (count($myArray) != 0) {
                            array_push($dayList, $days);
                        }
                    }
                }
            }
            //dd($dayList);
            usort($dayList, function ($a, $b) {
                return $a->day_number - $b->day_number;
            });
            $now = Carbon::now();
            $tempNow = (object)[
                'year' => $now->year,
                'month' => $now->month - 1,
                'day' => $now->day,
                'hour' => $now->hour,
                'minutes' => $now->minute,
                'second' => $now->second
            ];

            return response()->json(["status" => 1, "data" => $dayList, 'now' => $tempNow], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getHourList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }


    public function getHourListWithProductIdCityIdWithNow($productId, $cityId){
        try {
            $dayList = DB::table('delivery_hours')->orderBy('continent_id')->get();
            $i = -1;

            $specialDays = DB::table('speacial_delivery_hours')->orderBy('delivery_date')->get();

            foreach ($dayList as $day) {
                $now = Carbon::now();
                $i++;
                $tempProductDate2 = DB::table('product_city')->where('product_id', $productId)->where('city_id', $cityId)->select('avalibility_time', 'avalibility_time_end')->get()[0];
                $tempProductDate = $tempProductDate2->avalibility_time;
                $tempProductDateEnd = $tempProductDate2->avalibility_time_end;
                $productDate = new Carbon($tempProductDate);
                $productDateEnd = new Carbon($tempProductDateEnd);
                $hoursList = DB::table('dayHours')->join('delivery_hours', 'dayHours.day_number', '=', 'delivery_hours.id')->where('dayHours.day_number', $day->id)->where('dayHours.active', true)->select('dayHours.*', 'delivery_hours.continent_id')->orderBy('start_hour')->get();
                $myArray = [];

                if ($now->dayOfWeek > $day->day_number) {
                    $tempDayNumber = 7 - $now->dayOfWeek + $day->day_number;
                } else {
                    $tempDayNumber = $day->day_number - $now->dayOfWeek;
                }

                foreach ($hoursList as $hour) {
                    $tempHour = explode(":", $hour->start_hour)[0];
                    $tempMin = explode(":", $hour->start_hour)[1];
                    $hourTemp = 1;
                    if ($tempHour == '18') {
                        $hourTemp = -1;
                    }

                    if( ( $hour->continent_id == 'Asya' || $hour->continent_id == 'Asya-2' ) && $tempHour == '11' ){
                        $hourTemp = 3;
                    }

                    $tempNow = Carbon::now();
                    $tempNow->addDay($tempDayNumber);

                    $tempLater = Carbon::now();
                    $tempLater->addDay($tempDayNumber);

                    $tempLater->hour(intval($tempHour));
                    $tempLater->minute(intval($tempMin));

                    $tempNow->hour(intval($tempHour));
                    $tempNow->minute(intval($tempMin));
                    //$tempNow->minute(0);
                    if ($tempNow < $productDate || $tempNow > $productDateEnd) {
                        continue;
                    } else {
                        if (intval($now->dayOfWeek) == intval($day->day_number)) {
                            if ((intval($tempHour) + $hourTemp) > $now->hour) {
                                array_push($myArray, $hour);
                            }
                        } else {
                            array_push($myArray, $hour);
                        }
                    }
                }
                $day->hours = $myArray;

                if ($now->dayOfWeek > $day->day_number) {
                    $day->day_number = 7 - $now->dayOfWeek + $day->day_number;
                } else {
                    $day->day_number = $day->day_number - $now->dayOfWeek;
                }

                if (count($myArray) == 0) {
                    unset($dayList[$i]);
                }
                $tempDate = Carbon::now();
                $now->addDay($day->day_number);
                //if($productDate > $now) {
                //    unset($dayList[$i]);
                //}
                //if($productDateEnd < $now){
                //    unset($dayList[$i]);
                //}
            }
            usort($dayList, function ($a, $b) {
                return $a->day_number - $b->day_number;
            });
            foreach ($specialDays as $days) {
                for ($x = 0; $x < 21; $x++) {
                    $tempNow = Carbon::now();
                    $tempNow->addDay($x);
                    $tempNow->second(0);
                    $tempNow->minute(0);
                    $tempNow->hour(0);

                    $tempNow2 = Carbon::now();
                    $tempNow2->addDay($x);
                    $tempNow2->second(59);
                    $tempNow2->minute(59);
                    $tempNow2->hour(23);

                    $tempProductDate2 = DB::table('product_city')->where('product_id', $productId)->where('city_id', $cityId)->select('avalibility_time', 'avalibility_time_end')->get()[0];
                    $tempProductDate = $tempProductDate2->avalibility_time;
                    $tempProductDateEnd = $tempProductDate2->avalibility_time_end;
                    $productDate = new Carbon($tempProductDate);
                    $productDateEnd = new Carbon($tempProductDateEnd);

                    if ($days->delivery_date == $tempNow && $productDate <= $tempNow2 && $productDateEnd > $tempNow) {

                        $hoursList = DB::table('speacial_day_hours')->where('day_number', $days->id)->where('active', true)->get();
                        $myArray = [];
                        foreach ($hoursList as $hour) {
                            $tempHour = explode(":", $hour->start_hour)[0];
                            $hourTemp = 1;
                            if ($tempHour == '18') {
                                $hourTemp = 0;
                            }
                            //if (intval($now->dayOfWeek) == intval($day->day_number)) {
                            //    if ((intval($tempHour) + $hourTemp) > $now->hour) {
                            //        array_push($myArray, $hour);
                            //    }
                            //} else {
                            array_push($myArray, $hour);
                            //}
                        }
                        $days->hours = $myArray;
                        $days->day_number = $x;
                        if (count($myArray) != 0) {
                            array_push($dayList, $days);
                        }
                    }
                }
            }
            //dd($dayList);
            usort($dayList, function ($a, $b) {
                return $a->day_number - $b->day_number;
            });
            $now = Carbon::now();
            $tempNow = (object)[
                'year' => $now->year,
                'month' => $now->month - 1,
                'day' => $now->day,
                'hour' => $now->hour,
                'minutes' => $now->minute,
                'second' => $now->second
            ];

            return response()->json(["status" => 1, "data" => $dayList, 'now' => $tempNow], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getHourList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function testProduct($prod)
    {
        $flower = DB::table('shops')
            ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
            ->join('products', 'products_shops.products_id', '=', 'products.id')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->where('products.id', '=', $prod)
            ->where('descriptions.lang_id', '=', 'tr')
            ->select('products.coming_soon', 'products.limit_statu', 'products.url_parametre', 'products.id', 'products.name', 'products.price', 'products.description', 'products.background_color', 'products.second_background_color',
                'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc'
                , 'descriptions.how_to_detail', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description'
                , 'descriptions.img_title', 'descriptions.url_title', 'products.activation_status_id', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3')
            ->get();

        for ($x = 0; $x < count($flower); $x++) {
            $tagList = DB::table('products_tags')
                ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                ->where('products_tags.products_id', '=', $flower[$x]->id)
                ->where('tags.lang_id', '=', 'tr')
                ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url')
                ->get();
            $flower[$x]->tags = $tagList;
        }

        for ($x = 0; $x < count($flower); $x++) {
            $imageList = DB::table('images')
                ->where('products_id', '=', $flower[$x]->id)
                ->select('type', 'image_url')
                ->orderBy('order_no')
                ->get();
            $detailListImage = [];
            for ($y = 0; $y < count($imageList); $y++) {
                if ($imageList[$y]->type == "main") {
                    $flower[$x]->MainImage = $imageList[$y]->image_url;
                } else if ($imageList[$y]->type == "detailImages") {
                    array_push($detailListImage, $imageList[$y]->image_url);
                } else if ($imageList[$y]->type == "detailPhoto") {
                    $flower[$x]->DetailImage = $imageList[$y]->image_url;
                }
            }
            $flower[$x]->detailListImage = $detailListImage;
        }
        $flower = $flower[0];

        $bannerList = DB::table('landingBanner')->where('active', 1)->where('mobile', 0)->orderBy('order_number')->get();

        $queryString = '';
        return view('phpClient.bnfFlowerDetail', compact('flower', 'tagList', 'bannerList', 'detailListImage', 'queryString'));
    }

    public function testLoginWithMail()
    {
        if (Auth::attempt(['email' => Request::input('email'), 'password' => Request::input('password')])) {
            return redirect('/testLanding');
        } else {
            return redirect('/testLanding?login=false');
        }
    }

    public function testSaleNote()
    {
        $queryString = '';
        return view('phpClient.salesNote', compact('queryString'));
    }

    public function logout()
    {
        Auth::logout();
        $queryString = '';
        return redirect('/testLanding');
    }

    public function testLanding()
    {
        $siteId = 1;
        $langId = 'tr';
        $flowerList = DB::table('shops')
            ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
            ->join('products', 'products_shops.products_id', '=', 'products.id')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->where('shops.id', '=', $siteId)
            ->where('descriptions.lang_id', '=', $langId)
            ->where('products.activation_status_id', '=', 1)
            ->select('products.coming_soon', 'products.limit_statu', 'products.id', 'products.name', 'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc'
                , 'descriptions.how_to_detail', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description'
                , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3'
            )
            ->orderBy('landing_page_order')
            ->get();

        //for ($x = 0; $x < count($flowerList); $x++) {
        //    $tagList = DB::table('products_tags')
        //        ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
        //        ->where('products_tags.products_id', '=', $flowerList[$x]->id)
        //        ->where('tags.lang_id', '=', $langId)
        //        ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url')
        //        ->get();
        //    $flowerList[$x]->tags = $tagList;
        //}

        for ($x = 0; $x < count($flowerList); $x++) {
            $imageList = DB::table('images')
                ->where('products_id', '=', $flowerList[$x]->id)
                ->select('type', 'image_url')
                ->orderBy('order_no')
                ->get();
            $detailListImage = [];
            for ($y = 0; $y < count($imageList); $y++) {

                if ($imageList[$y]->type == "main") {
                    $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                }
                //else if ($imageList[$y]->type == "detailImages") {
                //    array_push($detailListImage, $imageList[$y]->image_url);
                //}
                //else if ($imageList[$y]->type == "detailPhoto") {
                //    $flowerList[$x]->DetailImage = $imageList[$y]->image_url;
                //}
            }
            //$flowerList[$x]->detailListImage = $detailListImage;
        }

        $bannerList = DB::table('landingBanner')->where('active', 1)->orderBy('order_number')->get();
        if ($langId != 'tr') {
            foreach ($bannerList as $banner) {
                $banner->header = DB::table('banner_description')->where('banner_id', $banner->id)->where('lang_id', 'tr')->get()[0]->content;
            }
        }
        $queryString = '';
        if (Request::input('login')) {
            $queryString = Request::input('login');
        }


        return view('phpClient.bnfLanding', compact('flowerList', 'bannerList', 'queryString'));
    }

    public function getHourListWithProductId($productId)
    {
        try {
            $dayList = DB::table('delivery_hours')->orderBy('continent_id')->get();
            $i = -1;
            foreach ($dayList as $day) {
                $i++;
                $now = Carbon::now();
                $tempProductDate = DB::table('products')->where('id', $productId)->select('avalibility_time')->get()[0]->avalibility_time;
                $productDate = new Carbon($tempProductDate);
                $hoursList = DB::table('dayHours')->where('day_number', $day->id)->where('active', true)->orderBy('start_hour')->get();
                $myArray = [];
                foreach ($hoursList as $hour) {
                    $tempNow = Carbon::now();
                    $tempNow->addDay($day->day_number);

                    $tempNow->hour($hour->start_hour);

                    $tempHour = explode(":", $hour->start_hour)[0];
                    $hourTemp = 1;

                    if ($tempNow > $productDate) {
                        continue;
                    }

                    if ($tempHour == '18') {
                        $hourTemp = 0;
                    }
                    if (intval($now->dayOfWeek) == intval($day->day_number)) {
                        if ((intval($tempHour) + $hourTemp) > $now->hour) {
                            array_push($myArray, $hour);
                        }
                    } else {
                        array_push($myArray, $hour);
                    }
                }
                $day->hours = $myArray;

                if ($now->dayOfWeek > $day->day_number) {
                    $day->day_number = 7 - $now->dayOfWeek + $day->day_number;
                } else {
                    $day->day_number = $day->day_number - $now->dayOfWeek;
                }

                if (count($myArray) == 0) {
                    unset($dayList[$i]);
                }
                $tempDate = Carbon::now();
                $now = Carbon::now();
                $now->addDay($day->day_number);
                if ($productDate > $now) {
                    unset($dayList[$i]);
                }
            }
            usort($dayList, function ($a, $b) {
                return $a->day_number - $b->day_number;
            });
            return response()->json(["status" => 1, "data" => $dayList], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getHourList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    /*
     * Ana sayfa headerda görüntülenen bannerların özelliklerinin(resim url, görüntülenme sayısı, barındırdıkları linler) karşılandığı web servis
     */
    public function getBannerList($langId, $device)
    {
        try {
            $bannerList = DB::table('landingBanner')->where('active', 1)->where('mobile', $device)->orderBy('order_number')->get();
            if ($langId != 'tr') {
                foreach ($bannerList as $banner) {
                    $banner->header = DB::table('banner_description')->where('banner_id', $banner->id)->where('lang_id', $langId)->get()[0]->content;
                }
            }
            return response()->json(["status" => 1, "data" => $bannerList], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getBannerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getBannerListOld($langId)
    {
        try {
            $bannerList = DB::table('landingBanner')->where('active', 1)->where('mobile', 0)->orderBy('order_number')->get();
            if ($langId != 'tr') {
                foreach ($bannerList as $banner) {
                    $banner->header = DB::table('banner_description')->where('banner_id', $banner->id)->where('lang_id', $langId)->get()[0]->content;
                }
            }
            return response()->json(["status" => 1, "data" => $bannerList], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getBannerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getActiveLang()
    {
        try {
            $langList = DB::table('bnf_languages')->where('active', true)->get();
            return response()->json(["status" => 1, "data" => $langList], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getActiveLang', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getTagList($langId)
    {
        try {
            $bannerList = DB::table('tags')->select('tag_header', 'id', 'tags_name', 'tag_ceo', 'description', 'active_image_url', 'inactive_image_url', 'meta_description', 'big_image', 'banner_image')
                ->where('lang_id', $langId)->orderBy('position')->get();
            array_push($bannerList, (object)[
                'id' => '999',
                'tag_header' => 'Aynı Gün Teslim Online Çiçek Gönder - Bloom and Fresh',
                'tag_ceo' => 'ayni-gun-teslim-hizli-cicek-gonder',
                'tags_name' => 'Hızlı Çiçekler',
                'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                'meta_description' => 'Hemen çiçek gönderip siparişinizin aynı günde teslim edilmesini istiyorsanız Bloom and Fresh\'te listenen bu özel çiçek aranjmanlarına göz atın.	',
                'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-selected.svg',
                'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-unselected.svg',
                'big_image' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/40X40/aynigunteslim-gold.svg',
                'banner_image' => 'https://d1z5skrvc8vebc.cloudfront.net/188.166.86.116:3000/1324.jpg'
            ]);
            return response()->json(["status" => 1, "data" => $bannerList], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getBannerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    /*
     * Kıtalara göre farklılık gösteren teslimat saatlerinin karşılandığı web servis
     */
    public function getHourList()
    {
        try {
            $dayList = DB::table('delivery_hours')->orderBy('continent_id')->get();
            $i = -1;
            foreach ($dayList as $day) {
                $i++;
                $now = Carbon::now();
                $hoursList = DB::table('dayHours')->where('day_number', $day->id)->where('active', true)->orderBy('start_hour')->get();
                $myArray = [];
                foreach ($hoursList as $hour) {
                    $tempHour = explode(":", $hour->start_hour)[0];
                    $hourTemp = 1;
                    if ($tempHour == '18') {
                        $hourTemp = 0;
                    }
                    if (intval($now->dayOfWeek) == intval($day->day_number)) {
                        if ((intval($tempHour) + $hourTemp) > $now->hour) {
                            array_push($myArray, $hour);
                        }
                    } else {
                        array_push($myArray, $hour);
                    }
                }
                $day->hours = $myArray;

                if ($now->dayOfWeek > $day->day_number) {
                    $day->day_number = 7 - $now->dayOfWeek + $day->day_number;
                } else {
                    $day->day_number = $day->day_number - $now->dayOfWeek;
                }

                if (count($myArray) == 0) {
                    unset($dayList[$i]);
                }
            }
            usort($dayList, function ($a, $b) {
                return $a->day_number - $b->day_number;
            });
            return response()->json(["status" => 1, "data" => $dayList], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getHourList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    /*
     * Sitede görüntülenen çiçeklerin bilgisinin( Çiçek ana sayfa resmi, çiçek detay sayfası resimleri, çiçek açıklama metinleri, çiçeğin durumu) karşılandığı web servis
     */
    /*public function getFlowerList($siteId, $langId)
    {
        try {
            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->where('shops.id', '=', $siteId)
                                                                                                                        ->where('descriptions.lang_id', '=', $langId)
                ->where('products.activation_status_id', '=', 1)
                ->select('products.tag_id', 'products.coming_soon', 'products.limit_statu', 'products.id', 'products.name', 'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                    'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre', 'products.company_product'
                    , 'descriptions.how_to_detail','products.youtube_url' , 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description', 'products.avalibility_time'
                    , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3' , 'products.speciality'
                )
                ->orderBy('landing_page_order')
                ->get();

            $now = Carbon::now();

            $tempNowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number' , $now->dayOfWeek)
                ->where('dayHours.active' , 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $NowTag = false;
            if(count($tempNowTag) == 0){
                $NowTag = false;
            }
            else{
                $tempHour = explode(":", $tempNowTag[0]->start_hour)[0];
                if($tempHour > $now->hour){
                    $NowTag = true;
                }
            }
            //dd($NowTag);
            for ($x = 0; $x < count($flowerList); $x++) {
                $tempFlowerNowTag = $NowTag;
                if($flowerList[$x]->avalibility_time > $now ){
                    $tempFlowerNowTag = false;
                }
                if($flowerList[$x]->limit_statu){
                    $tempFlowerNowTag = false;
                }
                if($flowerList[$x]->coming_soon){
                    $tempFlowerNowTag = false;
                }
                $tagList = DB::table('products_tags')
                    ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                    ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                    ->where('tags.lang_id', '=', $langId)
                    ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url')
                    ->get();

                $primaryTag =  DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', $langId)->get();
                if(count($primaryTag) > 0){
                    $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                }
                else{
                    $flowerList[$x]->tag_main = 'cicek';
                }

                if($tempFlowerNowTag){
                    array_push($tagList, (object)[
                        'id' => '999',
                        'tag_header' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'tag_ceo' => 'ayni-gun-teslim-hizli-cicek-gonder',
                        'tags_name' => 'Hızlı Çiçekler',
                        'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_selected.svg',
                        'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_unselected.svg'
                    ]);
                }
                $flowerList[$x]->tags = $tagList;
            }

            for ($x = 0; $x < count($flowerList); $x++) {
                $imageList = DB::table('images')
                    ->where('products_id', '=', $flowerList[$x]->id)
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get();
                $detailListImage = [];
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    } else if ($imageList[$y]->type == "detailImages") {
                        array_push($detailListImage, $imageList[$y]->image_url);
                    } else if ($imageList[$y]->type == "detailPhoto") {
                        $flowerList[$x]->DetailImage = $imageList[$y]->image_url;
                    }
                }
                if($flowerList[$x]->youtube_url){
                    array_push($detailListImage, $flowerList[$x]->youtube_url);
                }
                $flowerList[$x]->detailListImage = $detailListImage;
            }
            return $flowerList;
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }*/

    public function getFlowerList($siteId, $langId)
    {
        try {
            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->where('shops.id', '=', $siteId)
                ->where('descriptions.lang_id', '=', $langId)
                ->where('products.activation_status_id', '=', 1)
                ->select('products.tag_id', 'products.coming_soon', 'products.limit_statu', 'products.id', 'products.name', 'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                    'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre', 'products.company_product'
                    , 'descriptions.how_to_detail', 'products.youtube_url', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description', 'products.avalibility_time'
                    , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3', 'products.speciality'
                )
                ->orderBy('landing_page_order')
                ->get();

            $now = Carbon::now();
            $tomorrow = Carbon::now();
            $theDayAfter = Carbon::now();
            $TomorrowTag = false;
            $theDayAfterTag = false;
            $tomorrowDay = ($tomorrow->dayOfWeek + 1) % 8;
            $tempNowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            $NowTag = false;
            if (count($tempNowTag) == 0) {
                $NowTag = false;
            } else {
                $NowTag = true;
                $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                //if($tempNowTag[0]->start_hour != "18"){
                //    $now->addHours(1);
                //}
                if ($now->hour != "18") {
                    $now->addHours(1);
                } else if ($now->hour == "18") {
                    $now->subHours(1);
                }
                $now->minute(0);
            }
            if (count($tempTomorrowTag) > 0) {
                $TomorrowTag = true;
                $tomorrow->addDays(1)->hour(explode(":", $tempTomorrowTag[0]->start_hour)[0]);
                $tomorrow->minute(0);
            }
            //if ($TomorrowTag == false && $NowTag == false) {
            $tempDayAfterTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', '>', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->select('dayHours.start_hour', 'delivery_hours.day_number')
                ->orderBy('delivery_hours.day_number')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            if (count($tempDayAfterTag) > 0) {
                $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                $theDayAfter->minute(0);
                $theDayAfter->addDays($tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                $theDayAfterTag = true;
            } else {
                $tempDayAfterTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $now->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTag) > 0) {
                    $theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                    $theDayAfter->minute(0);
                    $theDayAfter->addDays(7 + $tempDayAfterTag[0]->day_number - $theDayAfter->dayOfWeek);
                    $theDayAfterTag = true;
                }
            }
            //}

            for ($x = 0; $x < count($flowerList); $x++) {
                $tempFlowerNowTag = $NowTag;
                $tempFlowerTomorrowTag = $TomorrowTag;
                if ($flowerList[$x]->avalibility_time > $now) {
                    $tempFlowerNowTag = false;
                }
                $nowTemp2 = Carbon::now();
                if ($nowTemp2 > $now) {
                    $tempFlowerNowTag = false;
                }
                if ($flowerList[$x]->limit_statu) {
                    $tempFlowerNowTag = false;
                    $tempFlowerTomorrowTag = false;
                }
                if ($flowerList[$x]->coming_soon) {
                    $tempFlowerNowTag = false;
                    $tempFlowerTomorrowTag = false;
                }
                if (!$tempFlowerNowTag && $flowerList[$x]->avalibility_time > $tomorrow) {
                    $tempFlowerTomorrowTag = false;
                    //dd($flowerList[$x]);
                }
                if ($theDayAfterTag || (!$tempFlowerTomorrowTag && !$tempFlowerNowTag)) {
                    setlocale(LC_TIME, "");
                    setlocale(LC_ALL, 'tr_TR.utf8');
                    if ($flowerList[$x]->avalibility_time > $theDayAfter) {
                        $flowerList[$x]->theDayAfter = new Carbon($flowerList[$x]->avalibility_time);
                        $flowerList[$x]->theDayAfter = $flowerList[$x]->theDayAfter->formatLocalized('%d %B');
                    } else {
                        $flowerList[$x]->theDayAfter = $theDayAfter->formatLocalized('%d %B');
                    }
                }
                $flowerList[$x]->tomorrow = $tempFlowerTomorrowTag && !$tempFlowerNowTag;
                $flowerList[$x]->today = $tempFlowerNowTag;
                $tagList = DB::table('products_tags')
                    ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                    ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                    ->where('tags.lang_id', '=', $langId)
                    ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url')
                    ->get();

                $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', $langId)->get();
                if (count($primaryTag) > 0) {
                    $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                } else {
                    $flowerList[$x]->tag_main = 'cicek';
                }

                /*if ($tempFlowerNowTag) {
                    array_push($tagList, (object)[
                        'id' => '999',
                        'tag_header' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'tag_ceo' => 'ayni-gun-teslim-hizli-cicek-gonder',
                        'tags_name' => 'Hızlı Çiçekler',
                        'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_selected.svg',
                        'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/today_unselected.svg'
                    ]);
                }*/
                $flowerList[$x]->tags = $tagList;
            }

            for ($x = 0; $x < count($flowerList); $x++) {
                $imageList = DB::table('images')
                    ->where('products_id', '=', $flowerList[$x]->id)
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get();
                $detailListImage = [];
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    } else if ($imageList[$y]->type == "detailImages") {
                        array_push($detailListImage, $imageList[$y]->image_url);
                    } else if ($imageList[$y]->type == "detailPhoto") {
                        $flowerList[$x]->DetailImage = $imageList[$y]->image_url;
                    }
                }
                if ($flowerList[$x]->youtube_url) {
                    array_push($detailListImage, $flowerList[$x]->youtube_url);
                }
                $flowerList[$x]->detailListImage = $detailListImage;
            }
            return $flowerList;
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    /*
     * Teslimat yapılan şehirlerin listesinin karşılandığı web servis
     */
    public function getCityList($site)
    {
        try {
            $tempVar = DeliveryLocation::where('shop_id', $site)->where('continent_id', '!=' , 'Ups')->where('active', 1)->orderBy('district')->get();
            return response()->json($tempVar);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getCityList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getPeople()
    {
        $tempLocations = DB::table('about_us_people')->orderBy('order')->get();
        return response()->json(["status" => 1, "people" => $tempLocations], 200);
    }

    /*
     * Id'si belirtilen çiçeğin bilgilerinin karşılandığı web servis. Çiçek listesindeki bilgilerin aynıları sadece id'si belirtilen çiçek için gönderilir.
     */
    public function getFlowerDetail($siteId, $prod, $langId)
    {
        try {
            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->join('product_city', 'product_city.product_id', '=', 'products.id')
                ->where('shops.id', '=', $siteId)
                ->where('products.id', '=', $prod)
                ->where('products.company_product', '=', '0')
                ->where('descriptions.lang_id', '=', $langId)
                ->select('products.tag_id', 'product_city.coming_soon', 'products.youtube_url', 'product_city.limit_statu', 'products.url_parametre', 'products.id', 'products.name', 'products.price', 'products.description', 'products.background_color', 'products.second_background_color',
                    'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.company_product'
                    , 'descriptions.how_to_detail', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description', 'products.speciality'
                    , 'descriptions.img_title', 'descriptions.url_title', 'product_city.activation_status_id', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3')
                ->get();

            for ($x = 0; $x < count($flowerList); $x++) {
                $tagList = DB::table('products_tags')
                    ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                    ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                    ->where('tags.lang_id', '=', $langId)
                    ->select('tags.id', 'tags.tags_name', 'tags.description', 'tags.active_image_url', 'tags.inactive_image_url')
                    ->get();
                $flowerList[$x]->tags = $tagList;

                $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', $langId)->get();
                if (count($primaryTag) > 0) {
                    $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                } else {
                    $flowerList[$x]->tag_main = 'cicek';
                }
            }

            for ($x = 0; $x < count($flowerList); $x++) {
                $imageList = DB::table('images')
                    ->where('products_id', '=', $flowerList[$x]->id)
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get();
                $detailListImage = [];
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "mobile") {
                        $flowerList[$x]->mobileImage = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "detailImages") {
                        array_push($detailListImage, $imageList[$y]->image_url);
                    } else if ($imageList[$y]->type == "detailPhoto") {
                        $flowerList[$x]->DetailImage = $imageList[$y]->image_url;
                    }
                }
                $flowerList[$x]->detailListImage = $detailListImage;
            }
            return $flowerList;
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerDetail', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function getFlowerXml()
    {

        $tempProductList = DB::table('products')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->join('product_city', 'product_city.product_id', '=', 'products.id')
            ->where('descriptions.lang_id', '=', 'tr')
            ->where('product_city.coming_soon', '0')
            ->where('product_city.limit_statu', '0')
            ->where('products.company_product', '=', '0')
            ->where('product_city.activation_status_id', '1')
            ->where('product_city.city_id', '1')
            ->where('products.id', '!=', '75')
            ->select('products.price', 'products.id', 'products.name', 'descriptions.landing_page_desc')
            ->get();

        $tempString = '<products>';
        foreach ($tempProductList as $product) {
            $tempString = $tempString .
                '<product id="' . $product->id . '">
                    <name>' . htmlspecialchars($product->name) . '</name>
                    <producturl>https://bloomandfresh.com/cicek-detay/' . htmlspecialchars($product->name) . '-' . $product->id . '</producturl>
                    <smallimage>https://s3.eu-central-1.amazonaws.com/bloomandfresh/300-300/' . $product->id . '.jpg</smallimage>
                    <bigimage>https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/' . $product->id . '.jpg</bigimage>
                    <image>https://s3.eu-central-1.amazonaws.com/bloomandfresh/400-400/' . $product->id . '.jpg</image>
                    <description>' . htmlspecialchars($product->landing_page_desc) . '</description>
                    <price>' . str_replace(',', '.', $product->price) . '</price>
                </product>';
        }
        $tempString = $tempString . '</products>';

        return \Response::make($tempString, '200')->header('Content-Type', 'text/xml');
    }

    public function getLocations()
    {
        $tempLocations = DB::table('delivery_locations')->where('active', 1)->where('city_id', 1)->orderBy('district')->select('district')->get();
        return response()->json(["status" => 1, "locations" => $tempLocations], 200);
    }

    public function getLocationsAnkara()
    {
        $tempLocations = DB::table('delivery_locations')->where('active', 1)->where('city_id', 2)->orderBy('district')->select('district')->get();
        return response()->json(["status" => 1, "locations" => $tempLocations], 200);
    }

    public function getLocationsUps($city_name)
    {
        $tempLocations = DB::table('delivery_locations')->where('active', 1)->where('city', $city_name)->where('city_id', 3)->orderBy('district')->select('district')->get();
        return response()->json(["status" => 1, "locations" => $tempLocations], 200);
    }


    public function getFlowerXmlForFB()
    {
        $tempProductList = DB::table('products')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->join('product_city', 'product_city.product_id', '=', 'products.id')
            ->join('tags', 'products.tag_id', '=', 'tags.id')
            ->where('descriptions.lang_id', '=', 'tr')
            ->where('tags.lang_id', '=', 'tr')
            //->where('products.coming_soon' , '0')
            //->where('products.limit_statu' , '0')
            ->where('products.company_product', '=', '0')
            ->where('product_city.activation_status_id', '1')
            ->where('product_city.city_id', '1')
            ->where('products.id', '!=', '75')
            ->select('products.price', 'products.id', 'products.name', 'descriptions.landing_page_desc', 'tags.tags_name', 'product_city.coming_soon', 'product_city.limit_statu')
            ->get();

        //dd($tempProductList);

        $tempString = '<?xml version="1.0"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:g="http://base.google.com/ns/1.0">
<title>BNF Active Flowers</title>
<link rel="self" href="https://bloomandfresh.com"/>';
        foreach ($tempProductList as $product) {

            if ($product->coming_soon == 1 || $product->limit_statu == 1) {
                $tempAvailability = 'out of stock';
            } else {
                $tempAvailability = 'in stock';
            }

            $tempMainImage = DB::table('images_social')->where('type', '1080Main')->where('products_id', $product->id)->get();

            if( count($tempMainImage) > 0 ){
                $tempMainImage = $tempMainImage[0]->image_url;
            }
            else{
                $tempMainImage = 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/' . $product->id . '.jpg ';
            }

            $extraImages = DB::table('images_social')->where('products_id', '=', $product->id)->where('type', '!=' , '1080Main')->get();

            if( $tempMainImage == ( 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/' . $product->id . '.jpg' ) )
            {
                $tempExtraImagesUrls = '';
            }
            else{
                $tempExtraImagesUrls = '<additional_image_link>https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/' . $product->id . '.jpg';
            }

            foreach ($extraImages as $key => $image){
                //dd($image->image_url);
                if( $tempExtraImagesUrls == '' && ( $image->image_url != ( 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/' . $product->id . '.jpg' ) ) ){
                    $tempExtraImagesUrls = '<additional_image_link>' . $image->image_url;
                }
                else if( $image->image_url != ( 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/' . $product->id . '.jpg' ) ){
                    $tempExtraImagesUrls = $tempExtraImagesUrls . ',' . $image->image_url;
                }
            }

            if( $tempExtraImagesUrls !=  '' ){
                $tempExtraImagesUrls = $tempExtraImagesUrls . '</additional_image_link>';
            }

            $tempString = $tempString .
                '<entry>
            <g:id>' . $product->id . '</g:id>
            <g:title>' . htmlspecialchars($product->name) . '</g:title>
            <g:google_product_category>2899</g:google_product_category>
            <g:description>' . htmlspecialchars($product->landing_page_desc) . '</g:description>
            <g:link>https://bloomandfresh.com/cicek-detay/' . htmlspecialchars($product->name) . '-' . $product->id . '</g:link>
            <g:image_link>' . $tempMainImage . '</g:image_link>
            <g:brand>Bloom And Fresh</g:brand>
            <g:condition>new</g:condition>
            <g:availability>' . $tempAvailability . '</g:availability>
            <g:price>' . str_replace(',', '.', $product->price) . ' TRY</g:price>
            ' . $tempExtraImagesUrls . '</entry>';
        }
        $tempString = $tempString . '</feed>';

        //dd($tempString);

        return \Response::make($tempString, '200')->header('Content-Type', 'text/xml');
    }
}
