<?php namespace App\Http\Controllers;

use Request;
use App\Models\ErrorLog;
use Session;
use Carbon\Carbon;
use DB;

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

    public function testPeriodic(){

            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('product_city', 'products.id', '=', 'product_city.product_id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->where('shops.id', '=', 1)
                ->where('descriptions.lang_id', '=', 'tr')
                ->where('product_city.activation_status_id', '=', 1)
                ->where('product_city.active', '=', 1)
                ->select('products.tag_id', 'products.product_type', 'product_city.coming_soon', 'product_city.limit_statu', 'products.id', 'products.name', 'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                    'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre', 'products.company_product', 'product_city.city_id'
                    , 'descriptions.how_to_detail', 'products.youtube_url', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description', 'product_city.avalibility_time'
                    , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3', 'products.speciality')
                ->orderBy('product_city.landing_page_order')
                ->get();

            $now = Carbon::now();
            $tomorrow = Carbon::now();
            $theDayAfter = Carbon::now();
            $nowAnk = Carbon::now();
            $tomorrowAnk = Carbon::now();
            $theDayAfterAnk = Carbon::now();
            $nowAsya = Carbon::now();
            $tomorrowAsya = Carbon::now();
            $theDayAfterAsya = Carbon::now();
            $TomorrowTag = false;
            $theDayAfterTag = false;
            $tomorrowDay = ($tomorrow->dayOfWeek + 1) % 8;
            $TomorrowTagAnk = false;
            $theDayAfterTagAnk = false;
            $tomorrowDayAnk = ($tomorrow->dayOfWeek + 1) % 8;
            $TomorrowTagAsya = false;
            $theDayAfterTagAsya = false;
            $tomorrowDayAsya = ($tomorrow->dayOfWeek + 1) % 8;

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
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
                ->select('dayHours.start_hour', 'delivery_hours.continent_id')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
                ->where('delivery_hours.city_id', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            $tempNowTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 2)
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
                ->where('delivery_hours.city_id', 2)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            $tempNowTagAsya = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 2)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTagAsya = DB::table('delivery_hours')
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
                if (explode(":", $tempNowTagAnk[0]->start_hour)[0] != "18") {
                    $nowAnk->addHours(1);
                } else {
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
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
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
            } else {
                $tempDayAfterTagAnk = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $nowAnk->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.city_id', 2)
                    ->where('delivery_hours.continent_id', '!=' , 'Ups')
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

            // Asya

            $NowTagAsya = false;
            if (count($tempNowTagAsya) == 0) {
                $NowTagAsya = false;
            } else {
                $NowTagAsya = true;
                $nowAsya->hour(explode(":", $tempNowTagAsya[0]->start_hour)[0]);
                if( explode(":", $tempNowTagAsya[0]->start_hour)[0] != "18"){
                    $nowAsya->addHours(1);
                }
                else if (explode(":", $tempNowTag[0]->start_hour)[0] != "11" && ( $tempNowTag[0]->continent_id == 'Asya' || $tempNowTag[0]->continent_id == 'Asya-2' ) ) {
                    $now->addHours(-3);
                }
                else{
                    $nowAsya->addHours(-1);
                }
                $nowAsya->minute(0);
            }
            if (count($tempTomorrowTagAsya) > 0) {
                $TomorrowTagAsya = true;
                $tomorrowAsya->addDays(1)->hour(explode(":", $tempTomorrowTagAsya[0]->start_hour)[0]);
                $tomorrowAsya->minute(0);
            }
            //if ($TomorrowTag == false && $NowTag == false) {
            $tempDayAfterTagAsya = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', '>', $tomorrowDayAsya)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 341)
                ->select('dayHours.start_hour', 'delivery_hours.day_number')
                ->orderBy('delivery_hours.day_number')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            //dd($tempDayAfterTag);

            if (count($tempDayAfterTagAsya) > 0) {
                $theDayAfterAsya->hour(explode(":", $tempDayAfterTagAsya[0]->start_hour)[0]);
                $theDayAfterAsya->minute(0);
                $theDayAfterAsya->addDays($tempDayAfterTagAsya[0]->day_number - $theDayAfterAsya->dayOfWeek);
                $theDayAfterTagAsya = true;
            }
            else {
                $tempDayAfterTagAsya = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', '<', $nowAsya->dayOfWeek)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.city_id', 341)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTagAsya) > 0) {
                    $theDayAfterAsya->hour(explode(":", $tempDayAfterTagAsya[0]->start_hour)[0]);
                    $theDayAfterAsya->minute(0);
                    $theDayAfterAsya->addDays(7 + $tempDayAfterTagAsya[0]->day_number - $theDayAfterAsya->dayOfWeek);
                    $theDayAfterTagAsya = true;
                }
            }

            $NowTag = false;
            if (count($tempNowTag) == 0) {
                $NowTag = false;
            } else {
                $NowTag = true;
                $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                if (explode(":", $tempNowTag[0]->start_hour)[0] != "18") {
                    $now->addHours(1);
                }
                else if (explode(":", $tempNowTag[0]->start_hour)[0] != "11" && ( $tempNowTag[0]->continent_id == 'Asya' || $tempNowTag[0]->continent_id == 'Asya-2' ) ) {
                    $now->addHours(-3);
                }
                else {
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
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
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
                    ->where('delivery_hours.continent_id', '!=' , 'Ups')
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
                    //$tempFlowerNowTagUps = false;
                    //$tempFlowerTomorrowTagUps = false;
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

                if ($flowerList[$x]->city_id == 2) {

                    $tempFlowerNowTagAnk = $NowTagAnk;
                    $tempFlowerTomorrowTagAnk = $TomorrowTagAnk;
                    if ($flowerList[$x]->avalibility_time > $nowAnk) {
                        $tempFlowerNowTagAnk = false;
                    }
                    $nowTemp2 = Carbon::now();
                    if ($nowTemp2 > $nowAnk) {
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
                    else{
                        $flowerList[$x]->theDayAfter = $theDayAfter->formatLocalized('%d %B');
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
                else if( $flowerList[$x]->city_id == 341 ){

                    $tempFlowerNowTagAsya = $NowTagAsya;
                    $tempFlowerTomorrowTagAsya = $TomorrowTagAsya;
                    if ($flowerList[$x]->avalibility_time > $nowAsya) {
                        $tempFlowerNowTagAsya = false;
                    }
                    $nowTemp2 = Carbon::now();
                    if($nowTemp2 > $nowAsya){
                        $tempFlowerNowTagAsya = false;
                    }
                    if ($flowerList[$x]->limit_statu) {
                        $tempFlowerNowTagAsya = false;
                        $tempFlowerTomorrowTagAsya = false;
                    }
                    if ($flowerList[$x]->coming_soon) {
                        $tempFlowerNowTagAsya = false;
                        $tempFlowerTomorrowTagAsya = false;
                    }
                    if (!$tempFlowerNowTagAsya && $flowerList[$x]->avalibility_time > $tomorrowAsya) {
                        $tempFlowerTomorrowTagAsya = false;
                        //dd($flowerList[$x]);
                    }
                    if ($theDayAfterTagAsya || (!$tempFlowerTomorrowTagAsya && !$tempFlowerNowTagAsya)) {
                        setlocale(LC_TIME, "");
                        setlocale(LC_ALL, 'tr_TR.utf8');
                        if ($flowerList[$x]->avalibility_time > $theDayAfterAsya) {
                            $flowerList[$x]->theDayAfter = new Carbon($flowerList[$x]->avalibility_time);
                            $flowerList[$x]->theDayAfter = $flowerList[$x]->theDayAfter->formatLocalized('%d %B');
                        } else {
                            $flowerList[$x]->theDayAfter = $theDayAfterAsya->formatLocalized('%d %B');
                        }
                    }
                    $flowerList[$x]->tomorrow = $tempFlowerTomorrowTagAsya && !$tempFlowerNowTagAsya;
                    $flowerList[$x]->today = $tempFlowerNowTagAsya;
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

                    if( DB::table('best_seller_products')->where('product_id', $flowerList[$x]->id )->where('city_id', 341)->count() > 0 ){
                        $flowerList[$x]->bestSellerOrder = DB::table('best_seller_products')->where('product_id', $flowerList[$x]->id )->where('city_id', 341)->get()[0]->orderId;
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

                    if ($tempFlowerNowTagAsya) {
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
                else {

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
                    else{
                        $flowerList[$x]->theDayAfter = $theDayAfter->formatLocalized('%d %B');
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
                $flowerList[$x]->landingAnimation = '';
                $flowerList[$x]->landingAnimation2 = '';
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    } else if ($imageList[$y]->type == "mobile") {
                        $flowerList[$x]->mobileImage = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "landingAnimation") {
                        $flowerList[$x]->landingAnimation = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "landingAnimation2") {
                        $flowerList[$x]->landingAnimation2 = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "detailImages") {
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

            //DB::table('landing_products_times')->delete();

            foreach ($flowerList as $flower) {

                if( DB::table('landing_products_times')->where('product_id', $flower->id )->where('city_id', $flower->city_id )->count() == 0 ){

                    DB::table('landing_products_times')->insert([
                        'product_id' => $flower->id,
                        'city_id' => $flower->city_id,
                        'avalibility_time' => $flower->avalibility_time,
                        'theDayAfter' => $flower->theDayAfter,
                        'today' => $flower->today,
                        'tomorrow' => $flower->tomorrow,
                        'theDayAfter_ups' => $flower->theDayAfter_ups,
                        'today_ups' => $flower->today_ups,
                        'tomorrow_ups' => $flower->tomorrow_ups,
                        'tomorrow' => $flower->tomorrow,
                        'MainImage' => $flower->MainImage,
                        'mobileImage' => $flower->mobileImage,
                        'landingAnimation' => $flower->landingAnimation,
                        'landingAnimation2' => $flower->landingAnimation2,
                        'tag_main' => $flower->tag_main

                    ]);

                }
                else{
                    DB::table('landing_products_times')->where('product_id', $flower->id )->where('city_id', $flower->city_id )->update([
                        'avalibility_time' => $flower->avalibility_time,
                        'theDayAfter' => $flower->theDayAfter,
                        'today' => $flower->today,
                        'tomorrow' => $flower->tomorrow,
                        'theDayAfter_ups' => $flower->theDayAfter_ups,
                        'today_ups' => $flower->today_ups,
                        'tomorrow_ups' => $flower->tomorrow_ups,
                        'tomorrow' => $flower->tomorrow,
                        'MainImage' => $flower->MainImage,
                        'mobileImage' => $flower->mobileImage,
                        'landingAnimation' => $flower->landingAnimation,
                        'landingAnimation2' => $flower->landingAnimation2,
                        'tag_main' => $flower->tag_main
                    ]);
                }

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