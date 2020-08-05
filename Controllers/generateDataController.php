<?php namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Request;
use App\Models\DeliveryLocation;
use Session;
use Authorizer;
use Auth;

class generateDataController extends Controller
{

    public function produceProductStocks() {

         $tempProducts = DB::table('product_stocks')->where('city_id', 1)->get();

         foreach ( $tempProducts as $product ){

             DB::table('product_stocks')->insert([
                'product_id' => $product->product_id,
                'cross_sell_id' => $product->cross_sell_id,
                'city_id' => 341,
                'count' => $product->count,
                'future_stock' => $product->future_stock,
                'active' => $product->future_stock
             ]);

         }

    }

    public function produceAsyaProducts(){

        $products = DB::table('product_city')->where('city_id', 1)->get();

        foreach ( $products as $product){

            DB::table('product_city')->insert([
                "product_id" => $product->product_id,
                "active" => 1,
                "best_seller" => "0",
                "city_id" => "341",
                "landing_page_order" => $product->landing_page_order,
                "activation_status_id" => "0",
                "limit_statu" => "0",
                "coming_soon" => "0",
                "future_delivery_day" => "0",
                "avalibility_time" => "0000-00-00 00:00:00",
                "avalibility_time_end" => "2030-01-29 12:00:40",
                "created_at" => "2018-03-20 15:21:07"
            ]);

        }

        dd($products);

    }

    public function productsDiscount(){

        $products = DB::table('products')->where('old_price', '!=', '')->get();

        DB::table('page_flower_production')->where('page_id', '23')->delete();

        foreach ( $products as $product ){

            DB::table('page_flower_production')->insert([
                'page_id' => '23',
                'product_id' => $product->id
            ]);

        }
    }

    public static function callSetProductFromSaleId($saleId)
    {
        $productData = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.id', $saleId)
            ->select('sales_products.products_id', 'delivery_locations.city_id', 'sales.id')
            ->get();

        if (count($productData) > 0) {
            $productData = $productData[0];

            $tempCrossSellData = DB::table('cross_sell')->where('sales_id', $productData->id)->get();

            if( $productData->city_id == 3 ){
                $productData->city_id = 1;
            }

            if (count($tempCrossSellData) > 0) {
                generateDataController::setCrossSellCountOneLess($tempCrossSellData[0]->product_id, $productData->city_id);

                $productCrossSellData = DB::table('cross_sell_products')->where('id', $tempCrossSellData[0]->product_id)->where('city_id', $productData->city_id)->get();

                if ($productCrossSellData[0]->product_id > 0) {
                    $productStockData = DB::table('product_stocks')->where('product_id', $productCrossSellData[0]->product_id)->where('city_id', $productData->city_id)->get()[0];
                } else {
                    $productStockData = DB::table('product_stocks')->where('cross_sell_id', $tempCrossSellData[0]->product_id)->where('city_id', $productData->city_id)->get()[0];
                }

                generateDataController::logStock('CROSS-SELL SATIŞ', $productStockData->id, $productData->id, $productStockData->count + 1, $productStockData->count, 1);
            }


            generateDataController::setProductCountOneLess($productData->products_id, $productData->city_id);

            $productStockData = DB::table('product_stocks')->where('product_id', $productData->products_id)->where('city_id', $productData->city_id)->get();

            if (count($productStockData) > 0) {
                $productStockData = $productStockData[0];
                generateDataController::logStock('SATIŞ', $productStockData->id, $productData->id, $productStockData->count + 1, $productStockData->count, 1);
            }
        }
    }

    public static function checkFlowerDummy($salesId)
    {
        try {
            //if (DB::table('sales')->where('id', $salesId)->get()[0]->payment_methods == 'OK') {
            //    return true;
            //}
            $productId = DB::table('sales_products')->where('sales_id', $salesId)->get()[0]->products_id;
            $wanted_time = DB::table('deliveries')->where('sales_id', $salesId)->get()[0]->wanted_delivery_date;

            $saleCity = DB::table('sales')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.id', $salesId)->get()[0]->city_id;

            $delivery_location = DB::table('sales')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.id', $salesId)->select('delivery_locations.active')->get()[0]->active;

            if ($delivery_location == 0) {
                return false;
            }

            /*$tempAvalibility = DB::table('products')
                ->where( 'id', $productId)
                ->where('activation_status_id' , 1)
                ->where('limit_statu' , 0)
                ->where('coming_soon' , 0)
                ->where('avalibility_time', '<' , $wanted_time)
                ->where('avalibility_time_end' , '>' , $wanted_time)
                ->count();*/

            if ($saleCity == 3) {
                $saleCity = 1;
            }

            $tempAvalibility = DB::table('product_city')
                ->where('product_id', $productId)
                ->where('activation_status_id', 1)
                ->where('limit_statu', 0)
                ->where('coming_soon', 0)
                ->where('city_id', $saleCity)
                ->where('avalibility_time', '<', $wanted_time)
                ->where('avalibility_time_end', '>', $wanted_time)
                ->count();

            $dateTemp = new Carbon($wanted_time);
            $today = Carbon::now();

            if ($dateTemp->hour != 18) {
                $dateTemp->addHour(1);
            }

            if ($dateTemp < $today) {
                return false;
            }

            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');

            $tempContinentId = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.id', $salesId)->select('delivery_locations.continent_id', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit')->get()[0];

            $tempDeliveryHours = DB::table('delivery_hours')->where('continent_id', $tempContinentId->continent_id)->get();

            $wantedDeliveryDate = new Carbon($tempContinentId->wanted_delivery_date);

            $isTimeActive = false;

            foreach ($tempDeliveryHours as $day) {
                $now = Carbon::now();
                $hoursList = DB::table('dayHours')->where('day_number', $day->id)->get();
                $day->hours = $hoursList;

                if ($now->dayOfWeek > $day->day_number) {
                    $now->addDay(7 - $now->dayOfWeek + $day->day_number);
                } else {
                    $now->addDay($day->day_number - $now->dayOfWeek);
                }

                if ($wantedDeliveryDate->toDateString() == $now->toDateString()) {

                    foreach ($hoursList as $hour) {
                        if (explode(":", $hour->start_hour)[0] == $wantedDeliveryDate->hour && $hour->active) {
                            //dd($hour->active);
                            $isTimeActive = true;
                            break;
                        }
                    }
                }


                $day->date = $now->formatLocalized('%A %d %B');
                $day->dateFull = $now;
                $day->number = $now->dayOfYear;
            }

            if (!$isTimeActive) {
                return false;
            }

            if ($tempAvalibility == 0) {
                return false;
            } else
                return true;

        } catch (\Exception $e) {

            logEventController::logErrorToDB('checkFlower', $e->getCode(), $e->getMessage(), 'WS', $salesId);

            return true;
        }

    }

    public static function isProductAvailable($productId, $cityId)
    {
        if( $cityId == 3 ){
            $cityId = 1;
        }

        $productStockData = DB::table('product_stocks')->where('product_id', $productId)->where('city_id', $cityId)->get();
        $productStatusData = DB::table('product_city')->where('product_id', $productId)->where('city_id', $cityId)->get();

        if (count($productStockData) > 0) {
            if ($productStockData[0]->count > 0 && $productStatusData[0]->activation_status_id && $productStatusData[0]->limit_statu == 0) {
                //dd(true);
                return true;
            }
        }

        //dd(false);
        return false;
    }

    public static function isCrossSellAvailable($productId, $cityId)
    {
        if( $cityId == 3 ){
            $cityId = 1;
        }

        $productCrossSellData = DB::table('cross_sell_products')->where('id', $productId)->where('city_id', $cityId)->get();

        if (count($productCrossSellData) > 0) {
            if ($productCrossSellData[0]->product_id > 0) {
                return generateDataController::isProductAvailable($productCrossSellData[0]->product_id, $cityId);
            } else {
                $productStockData = DB::table('product_stocks')->where('cross_sell_id', $productId)->where('city_id', $cityId)->get();

                if ($productStockData[0]->count > 0 && $productCrossSellData[0]->status) {
                    //dd(true);
                    return true;
                }

            }
        }

        //dd(false);
        return false;
    }

    public static function setProductCountOneLess($productId, $cityId)
    {
        if( $cityId == 3 ){
            $cityId = 1;
        }

        $productStockData = DB::table('product_stocks')->where('product_id', $productId)->where('city_id', $cityId)->get();

        if (count($productStockData) > 0) {
            if ($productStockData[0]->count > 0) {
                DB::table('product_stocks')->where('product_id', $productId)->where('city_id', $cityId)->decrement('count', 1);
            }
        }

        $productStockDataUpdated = DB::table('product_stocks')->where('product_id', $productId)->where('city_id', $cityId)->get();

        if (count($productStockDataUpdated) > 0) {
            if ($productStockDataUpdated[0]->count == 0) {
                DB::table('product_city')->where('product_id', $productId)->where('city_id', $cityId)->update([
                    'limit_statu' => 1
                ]);

                generateDataController::setEmailFunction($productStockDataUpdated[0]->id, 0, 1);

                $crossSellData = DB::table('cross_sell_products')->where('product_id', $productId)->where('city_id', $cityId)->get();

                if (count($crossSellData) > 0) {
                    DB::table('cross_sell_products')->where('product_id', $productId)->where('city_id', $cityId)->update([
                        'status' => 0
                    ]);
                }
            } elseif ($productStockDataUpdated[0]->count == 4) {
                generateDataController::setEmailFunction($productStockDataUpdated[0]->id, 1, 0);
            }
        }
    }

    public static function setCrossSellCountOneLess($productId, $cityId)
    {
        if( $cityId == 3 ){
            $cityId = 1;
        }

        $productCrossSellData = DB::table('cross_sell_products')->where('id', $productId)->where('city_id', $cityId)->get();

        if (count($productCrossSellData) > 0) {
            if ($productCrossSellData[0]->product_id > 0) {

                return generateDataController::setProductCountOneLess($productCrossSellData[0]->product_id, $cityId);

            } else {

                $crossSellStock = DB::table('product_stocks')->where('cross_sell_id', $productId)->where('city_id', $cityId)->get();

                if (count($crossSellStock) > 0) {
                    if ($crossSellStock[0]->count > 0) {
                        DB::table('product_stocks')->where('cross_sell_id', $productId)->where('city_id', $cityId)->decrement('count', 1);
                    }
                }

                $crossSellStock = DB::table('product_stocks')->where('cross_sell_id', $productId)->where('city_id', $cityId)->get();

                if (count($crossSellStock) > 0) {
                    if ($crossSellStock[0]->count == 0) {
                        DB::table('cross_sell_products')->where('id', $productId)->where('city_id', $cityId)->update([
                            'status' => 0
                        ]);

                        generateDataController::setEmailFunction($crossSellStock[0]->id, 0, 1);
                    } elseif ($crossSellStock[0]->count === 4) {
                        generateDataController::setEmailFunction($crossSellStock[0]->id, 1, 0);
                    }
                }
            }
        }
    }

    public static function setProductCountOneMore($productId, $cityId)
    {
        if( $cityId == 3 ){
            $cityId = 1;
        }

        $productStockData = DB::table('product_stocks')->where('product_id', $productId)->where('city_id', $cityId)->get();

        if (count($productStockData) > 0) {
            DB::table('product_stocks')->where('product_id', $productId)->where('city_id', $cityId)->increment('count', 1);
        }

        $productStockDataUpdated = DB::table('product_stocks')->where('product_id', $productId)->where('city_id', $cityId)->get();

        if (count($productStockDataUpdated) > 0) {
            DB::table('product_city')->where('product_id', $productId)->where('city_id', $cityId)->update([
                'limit_statu' => 0
            ]);

            $crossSellData = DB::table('cross_sell_products')->where('product_id', $productId)->where('city_id', $cityId)->get();

            if (count($crossSellData) > 0) {
                DB::table('cross_sell_products')->where('product_id', $productId)->where('city_id', $cityId)->update([
                    'status' => 1
                ]);
            }
        }
    }

    public static function setCrossSellCountOneMore($productId, $cityId)
    {
        if( $cityId == 3 ){
            $cityId = 1;
        }

        $productCrossSellData = DB::table('cross_sell_products')->where('id', $productId)->where('city_id', $cityId)->get();

        if (count($productCrossSellData) > 0) {
            if ($productCrossSellData[0]->product_id > 0) {
                return generateDataController::setProductCountOneMore($productCrossSellData[0]->product_id, $cityId);
            } else {

                DB::table('product_stocks')->where('cross_sell_id', $productId)->where('city_id', $cityId)->increment('count', 1);

                $crossSellStock = DB::table('product_stocks')->where('cross_sell_id', $productId)->where('city_id', $cityId)->get();

                if (count($crossSellStock) > 0) {
                    DB::table('cross_sell_products')->where('id', $productId)->where('city_id', $cityId)->update([
                        'status' => 1
                    ]);
                }
            }
        }
    }

    public static function logStock($comment, $id, $saleId, $oldValue, $newValue, $userId)
    {

        $productStockData = DB::table('product_stocks')->where('id', $id)->get();

        if (count($productStockData) > 0) {
            DB::table('product_stock_user_log')->insert([
                'user_id' => $userId,
                'product_stock_id' => $productStockData[0]->id,
                'type' => '',
                'comment' => $comment,
                'sale_id' => $saleId,
                'old_value' => $oldValue,
                'new_value' => $newValue
            ]);
        }

    }

    public static function setEmailFunction($productStockId, $underEmail, $stockEmail)
    {

        DB::table('product_stock_mails')->insert([
            'product_stock_id' => $productStockId,
            'under_mail' => $underEmail,
            'no_stock' => $stockEmail,
            'is_mail_sent' => 0
        ]);

    }

    public function generateLastDeliveryTime($id)
    {

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        $tempContinentId = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.id', $id)->select('delivery_locations.continent_id', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit')->get()[0];

        $tempDeliveryHours = DB::table('delivery_hours')->where('continent_id', $tempContinentId->continent_id)->get();

        $wantedDeliveryDate = new Carbon($tempContinentId->wanted_delivery_date);

        $isTimeActive = false;

        foreach ($tempDeliveryHours as $day) {
            $now = Carbon::now();
            $hoursList = DB::table('dayHours')->where('day_number', $day->id)->get();
            $day->hours = $hoursList;

            if ($now->dayOfWeek > $day->day_number) {
                $now->addDay(7 - $now->dayOfWeek + $day->day_number);
            } else {
                $now->addDay($day->day_number - $now->dayOfWeek);
            }

            if ($wantedDeliveryDate->toDateString() == $now->toDateString()) {

                foreach ($hoursList as $hour) {
                    if (explode(":", $hour->start_hour)[0] == $wantedDeliveryDate->hour && $hour->active) {
                        //dd($hour->active);
                        $isTimeActive = true;
                        break;
                    }
                }
            }


            $day->date = $now->formatLocalized('%A %d %B');
            $day->dateFull = $now;
            $day->number = $now->dayOfYear;
        }

        dd($isTimeActive);

    }

    public function generateLandingProduct()
    {

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
            ->where('delivery_hours.continent_id', '!=', 'Ups')
            ->select('dayHours.start_hour')
            ->orderBy('dayHours.start_hour', 'DESC')
            ->get();
        $tempTomorrowTag = DB::table('delivery_hours')
            ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
            ->where('delivery_hours.day_number', $tomorrowDay)
            ->where('dayHours.active', 1)
            ->where('delivery_hours.continent_id', '!=', 'Ups')
            ->where('delivery_hours.city_id', 1)
            ->select('dayHours.start_hour')
            ->orderBy('dayHours.start_hour', 'DESC')
            ->get();

        $tempNowTagAnk = DB::table('delivery_hours')
            ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
            ->where('delivery_hours.day_number', $now->dayOfWeek)
            ->where('dayHours.active', 1)
            ->where('delivery_hours.city_id', 2)
            ->where('delivery_hours.continent_id', '!=', 'Ups')
            ->select('dayHours.start_hour')
            ->orderBy('dayHours.start_hour', 'DESC')
            ->get();
        $tempTomorrowTagAnk = DB::table('delivery_hours')
            ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
            ->where('delivery_hours.day_number', $tomorrowDay)
            ->where('dayHours.active', 1)
            ->where('delivery_hours.continent_id', '!=', 'Ups')
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
            ->where('delivery_hours.continent_id', '!=', 'Ups')
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
                ->where('delivery_hours.continent_id', '!=', 'Ups')
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
            if (explode(":", $tempNowTag[0]->start_hour)[0] != "18") {
                $now->addHours(1);
            } else {
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
            ->where('delivery_hours.continent_id', '!=', 'Ups')
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
                ->where('delivery_hours.continent_id', '!=', 'Ups')
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

        dd($theDayAfter);
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
            } else {
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
                } else {
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
                    /*array_push($tagList, (object)[
                        'id' => '999',
                        'tag_header' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'tag_ceo' => 'ayni-gun-teslim-hizli-cicek-gonder',
                        'tags_name' => 'Hızlı Çiçekler',
                        'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-selected.svg',
                        'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-unselected.svg',
                        'big_image' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/40X40/aynigunteslim-gold.svg',
                        'banner_image' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler'
                    ]);*/
                }

                $flowerList[$x]->tags = $tagList;
            } else {

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
                } else {
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
                    /*array_push($tagList, (object)[
                        'id' => '999',
                        'tag_header' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'tag_ceo' => 'ayni-gun-teslim-hizli-cicek-gonder',
                        'tags_name' => 'Hızlı Çiçekler',
                        'description' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler',
                        'active_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-selected.svg',
                        'inactive_image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/aynigunteslim-unselected.svg',
                        'big_image' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/tags/40X40/aynigunteslim-gold.svg',
                        'banner_image' => 'Online çiçek siparişini şimdi verirsen bugün teslim edebileceğimiz hızlı çiçekler'
                    ]);*/
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
                } else if ($imageList[$y]->type == "landingAnimation") {
                    $flowerList[$x]->landingAnimation = $imageList[$y]->image_url;
                } else if ($imageList[$y]->type == "landingAnimation2") {
                    $flowerList[$x]->landingAnimation2 = $imageList[$y]->image_url;
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

        //DB::table('landing_products_times')->delete();

        foreach ($flowerList as $flower) {

            if (DB::table('landing_products_times')->where('product_id', $flower->id)->where('city_id', $flower->city_id)->count() == 0) {

                /*DB::table('landing_products_times')->insert([
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

                ]);*/

            } else {
                /*DB::table('landing_products_times')->where('product_id', $flower->id )->where('city_id', $flower->city_id )->update([
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
                ]);*/
            }

        }


    }

    public function generateUserRights()
    {

        $allUsers = DB::table('user_rights')->where('name_id', '=', 'products')->select('user_id')->get();

        foreach ($allUsers as $user) {
            DB::table('user_rights')->insert([
                'name' => 'Ürün Tedarikçi',
                'group_name' => 'product',
                'active' => 0,
                'user_id' => $user->user_id,
                'name_id' => 'supplier'
            ]);
        }

    }

    public function generateBestSeller()
    {

        $today = Carbon::now();
        $today = $today->startOfWeek();

        $tempSales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
            ->where('sales.payment_methods', 'OK')
            ->where('delivery_locations.city_id', 1)
            ->where('sales.created_at', '>', $today)
            ->where('deliveries.status', '<>', '4')
            ->orderByRaw('count(sales_products.products_id) DESC')
            ->groupBy('sales_products.products_id')
            ->take(8)
            ->get();

        if (count($tempSales) < 8) {

            $today = Carbon::now();
            $today->addDay(-10);

            $tempSales = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
                ->where('sales.payment_methods', 'OK')
                ->where('delivery_locations.city_id', 1)
                ->where('sales.created_at', '>', $today)
                ->where('deliveries.status', '<>', '4')
                ->orderByRaw('count(sales_products.products_id) DESC')
                ->groupBy('sales_products.products_id')
                ->take(8)
                ->get();
        }

        dd($tempSales);

        DB::table('best_seller_products')->where('city_id', 1)->delete();

        DB::table('product_city')->update([
            'best_seller' => 0
        ]);

        foreach ($tempSales as $key => $sale) {

            DB::table('product_city')->where('product_id', $sale->products_id)->where('city_id', 1)->update([
                'best_seller' => 1
            ]);

            DB::table('best_seller_products')->insert([
                'product_id' => $sale->products_id,
                'city_id' => 1,
                'page_id' => 21,
                'orderId' => $key
            ]);
        }

        $tempSales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
            ->where('sales.payment_methods', 'OK')
            ->where('delivery_locations.city_id', 2)
            ->where('sales.created_at', '>', $today)
            ->where('deliveries.status', '<>', '4')
            ->orderByRaw('count(sales_products.products_id) DESC')
            ->groupBy('sales_products.products_id')
            ->take(6)
            ->get();

        if (count($tempSales) < 6) {

            $today = Carbon::now();
            $today->addDay(-10);

            $tempSales = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
                ->where('sales.payment_methods', 'OK')
                ->where('delivery_locations.city_id', 2)
                ->where('sales.created_at', '>', $today)
                ->where('deliveries.status', '<>', '4')
                ->orderByRaw('count(sales_products.products_id) DESC')
                ->groupBy('sales_products.products_id')
                ->take(6)
                ->get();
        }

        DB::table('best_seller_products')->where('city_id', 2)->delete();

        foreach ($tempSales as $key => $sale) {

            DB::table('product_city')->where('product_id', $sale->products_id)->where('city_id', 2)->update([
                'best_seller' => 1
            ]);

            DB::table('best_seller_products')->insert([
                'product_id' => $sale->products_id,
                'city_id' => 2,
                'page_id' => 21,
                'orderId' => $key
            ]);
        }

    }

    public function generateFutureDelivery()
    {


        $productFuture = DB::table('product_city')->where('future_delivery_day', '>', 0)->get();

        foreach ($productFuture as $product) {

            $today = Carbon::now();

            $today->addDay($product->future_delivery_day);

            $today->startOfDay();

            if ($today > $product->avalibility_time) {
                DB::table('product_city')->where('id', $product->id)->update([
                    'avalibility_time' => $today
                ]);
            }

        }

        dd($productFuture);

    }

    public function generatePopularTag()
    {

        $today = Carbon::now();
        $today = $today->startOfWeek();

        $tempSales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
            ->where('sales.payment_methods', 'OK')
            ->where('delivery_locations.city_id', 1)
            ->where('sales.created_at', '>', $today)
            ->where('deliveries.status', '<>', '4')
            ->orderByRaw('count(sales_products.products_id) DESC')
            ->groupBy('sales_products.products_id')
            ->take(8)
            ->get();

        if (count($tempSales) < 8) {

            $today = Carbon::now();
            $today->addDay(-10);

            $tempSales = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
                ->where('sales.payment_methods', 'OK')
                ->where('delivery_locations.city_id', 1)
                ->where('sales.created_at', '>', $today)
                ->where('deliveries.status', '<>', '4')
                ->orderByRaw('count(sales_products.products_id) DESC')
                ->groupBy('sales_products.products_id')
                ->take(8)
                ->get();
        }


        DB::table('best_seller_products')->where('city_id', 1)->delete();

        foreach ($tempSales as $sale) {

            DB::table('product_city')->where('product_id', $sale->products_id)->where('city_id', 1)->update([
                'best_seller' => 1
            ]);

            DB::table('best_seller_products')->insert([
                'product_id' => $sale->products_id,
                'city_id' => 1,
                'page_id' => 21
            ]);
        }

        $tempSales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
            ->where('sales.payment_methods', 'OK')
            ->where('delivery_locations.city_id', 2)
            ->where('sales.created_at', '>', $today)
            ->where('deliveries.status', '<>', '4')
            ->orderByRaw('count(sales_products.products_id) DESC')
            ->groupBy('sales_products.products_id')
            ->take(6)
            ->get();

        if (count($tempSales) < 6) {

            $today = Carbon::now();
            $today->addDay(-10);

            $tempSales = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
                ->where('sales.payment_methods', 'OK')
                ->where('delivery_locations.city_id', 2)
                ->where('sales.created_at', '>', $today)
                ->where('deliveries.status', '<>', '4')
                ->orderByRaw('count(sales_products.products_id) DESC')
                ->groupBy('sales_products.products_id')
                ->take(6)
                ->get();
        }

        DB::table('best_seller_products')->where('city_id', 2)->delete();

        foreach ($tempSales as $sale) {

            DB::table('product_city')->where('product_id', $sale->products_id)->where('city_id', 2)->update([
                'best_seller' => 1
            ]);

            DB::table('best_seller_products')->insert([
                'product_id' => $sale->products_id,
                'city_id' => 2,
                'page_id' => 21
            ]);
        }

        dd($tempSales);

    }

    public function generateProductSubCategory()
    {

        $temSubs = DB::table('temp_product_sub')->get();

        foreach ($temSubs as $sub) {

            DB::table('products')->where('id', $sub->id)->update([
                'product_type_sub' => $sub->sub_id
            ]);

        }

    }

    public function generateSimilarProducts()
    {

        $allFlowers = DB::table('product_city')->join('products', 'product_city.product_id', '=', 'products.id')
            ->join('images', 'products.id', '=', 'images.products_id')
            ->where('products.company_product', 0)
            ->where('images.type', 'main')
            //->where('products.id', 366)
            ->where('products.city_id', 1)->where('product_city.city_id', 1)->where('product_city.activation_status_id', 1)->orderBy('products.id', 'DESC')
            ->select('products.name', 'products.tag_id', 'products.id', 'product_city.activation_status_id', 'product_city.limit_statu', 'product_city.coming_soon', 'images.image_url')->get();

        $activeFlowers = DB::table('product_city')
            ->join('products', 'product_city.product_id', '=', 'products.id')
            ->join('images', 'products.id', '=', 'images.products_id')
            ->where('images.type', 'main')
            ->where('products.company_product', 0)->where('product_city.activation_status_id', 1)->where('product_city.city_id', 1)->where('products.city_id', 1)
            ->select('products.name', 'products.tag_id', 'products.id', 'product_city.activation_status_id', 'product_city.limit_statu', 'product_city.coming_soon', 'images.image_url')->get();

        foreach ($activeFlowers as $activeFlower) {
            $activeFlower->tags = DB::table('products_tags')->where('products_id', $activeFlower->id)->select('tags_id')->get();
        }

        foreach ($allFlowers as $flower) {

            $flower->tags = DB::table('products_tags')->where('products_id', $flower->id)->select('tags_id')->get();

            $flower->similarProducts = [];
            $flower->similarProductsNoMain = [];

            foreach ($activeFlowers as $activeFlower) {

                if ($activeFlower->id != $flower->id) {

                    if ($activeFlower->limit_statu == 0 && $activeFlower->coming_soon == 0) {

                        if ($activeFlower->tag_id == $flower->tag_id) {

                            $tagNumber = 0;

                            foreach ($activeFlower->tags as $activeTags) {

                                foreach ($flower->tags as $allTags) {

                                    if ($allTags->tags_id == $activeTags->tags_id) {
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
                                'mainTag' => 1,
                                'image_url' => $activeFlower->image_url
                            ]);

                        } else {

                            $tagNumber = 0;

                            foreach ($activeFlower->tags as $activeTags) {

                                foreach ($flower->tags as $allTags) {

                                    if ($allTags->tags_id == $activeTags->tags_id) {
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
                                'mainTag' => 0,
                                'image_url' => $activeFlower->image_url
                            ]);

                        }
                    }
                }
            }

            if (count($flower->similarProducts) > 3) {


                //dd($flower->similarProducts);

                usort($flower->similarProducts, function ($a, $b) {
                    return strcmp($b->commonTagNumber, $a->commonTagNumber);
                });

                $flower->similarProducts = array_slice($flower->similarProducts, 0, 4);

                //dd($flower->similarProducts);

            } else if (count($flower->similarProducts) > 0) {

                usort($flower->similarProducts, function ($a, $b) {
                    return strcmp($b->commonTagNumber, $a->commonTagNumber);
                });

                usort($flower->similarProductsNoMain, function ($a, $b) {
                    return strcmp($b->commonTagNumber, $a->commonTagNumber);
                });

                $flower->similarProductsNoMain = array_slice($flower->similarProductsNoMain, 0, 4 - count($flower->similarProducts));

                $flower->similarProducts = array_merge($flower->similarProducts, $flower->similarProductsNoMain);

                //array_push($flower->similarProducts,$flower->similarProductsNoMain);

            } else {
                usort($flower->similarProductsNoMain, function ($a, $b) {
                    return strcmp($b->commonTagNumber, $a->commonTagNumber);
                });

                $flower->similarProducts = array_slice($flower->similarProductsNoMain, 0, 4);
            }

            $flower->similarProductsNoMain = [];

            if (count($flower->similarProducts) == 2) {
                dd($flower);
            }
        }

        //dd($allFlowers);

        return view('admin.generateSimilarProducts', compact('allFlowers'));

    }

    public function generatePaiPrice()
    {

        $today = Carbon::now();
        $today = $today->startOfWeek();

        $tempSales = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->where('sales.payment_methods', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $today)
            ->select('sales.sum_total', 'sales.id')
            ->orderBy('sales.created_at', 'DESC')
            ->get();

        $tempSum = 0.0;

        foreach ($tempSales as $sale) {

            $tempCrossSell = DB::table('cross_sell')->where('sales_id', $sale->id)->select('total_price')->get();

            if (count($tempCrossSell) > 0) {
                $sale->finalPrice = floatval(str_replace(',', '.', $tempCrossSell[0]->total_price)) + floatval(str_replace(',', '.', $sale->sum_total));
            } else {
                $sale->finalPrice = floatval(str_replace(',', '.', $sale->sum_total));
            }

            $tempSum = $tempSum + floatval(str_replace(',', '.', $sale->finalPrice));
        }

        //dd($tempSum);


        $list = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('billings', 'sales.id', '=', 'billings.sales_id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('sales.payment_methods', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $today)
            ->orderBy('sales.created_at', 'DESC')
            ->select('customers.user_id', 'sales.sender_email', 'sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.city', 'delivery_locations.city as real_city', 'billings.small_city', 'billings.tc',
                'billings.userBilling', 'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no',
                'billings.billing_send', 'billings.billing_surname', 'billings.billing_name', 'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'products.name as products',
                'sales.sender_name', 'sales.sender_surname', 'sales.product_price as price', 'sales.sender_mobile', 'products.id', 'products.product_type', 'delivery_locations.city_id')
            ->get();

        $count = 0;
        $firstPrice = 0;
        $totalDiscount = 0;
        $totalPartial = 0;
        $totalKDV = 0;
        $total = 0;

        $cikilotCount = 0;
        $cikilotTotalPrice = 0;
        $cikilotTotalTax = 0;
        $cikilotGeneral = 0;
        $cikilotBigGeneral = 0;

        foreach ($list as $row) {
            $count++;
            $tempTotal = 0;
            $tempVal = str_replace(',', '.', $row->price);
            $firstPrice = $firstPrice + floatval($tempVal);
            $discount = DB::table('marketing_acts_sales')
                ->join('marketing_acts', 'marketing_acts_sales.marketing_acts_id', '=', 'marketing_acts.id')
                ->where('sales_id', $row->sales_id)->get();

            if ($row->billing_type == 1 && ($row->userBilling == 1 || $row->billing_send == 1)) {
                $row->name = $row->billing_name . ' ' . $row->billing_surname;
                $row->bigCity = $row->city;
                $row->smallCity = $row->small_city;
                //$row->address = DeliveryLocation::where('id' , $row->delivery_locations_id )->get()[0]->district;
                $row->address2 = $row->billing_address;
                $row->tax_office = $row->tc;
            } else if ($row->billing_type == 1) {
                $row->name = $row->sender_name . ' ' . $row->sender_surname;
                $districtTemp = DeliveryLocation::where('id', $row->delivery_locations_id)->get()[0]->district;
                $row->bigCity = explode("-", $districtTemp)[0];
                $row->smallCity = explode("-", $districtTemp)[1];
                //$row->address = DeliveryLocation::where('id' , $row->delivery_locations_id )->get()[0]->district;
                $row->address2 = $row->real_city;
            } else {
                $row->name = $row->company;
                $row->bigCity = $row->billing_address;
                $row->smallCity = "";
                //$row->address = $row->billing_address;
                $row->address2 = "";
                $row->tax_office = $row->tax_office . "-" . $row->tax_no;
            }

            $dateTemp = new Carbon($row->wanted_delivery_date);

            $row->wantedDate = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year;

            $dateTemp = new Carbon($row->created_at);

            $row->created_at = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year . ' ' . sprintf("%02d", $dateTemp->hour) . ':' . sprintf("%02d", $dateTemp->minute);

            $row->id = sprintf("%03d", $row->id);

            if (count($discount) == 0) {
                $row->discount = 0;
                $row->discountVal = 0;
                $row->sumPartial = $row->price;

                $priceWithDiscount = $row->price;
                $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);
                $totalPartial = $totalPartial + $priceWithDiscount;

                if ($row->product_type == 2) {
                    $row->discountValue = floatval(floatval($priceWithDiscount) * 8 / 100);
                } else {
                    $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);
                }

                $totalKDV = $totalKDV + $row->discountValue;

                $row->discountValue = number_format($row->discountValue, 2);
                parse_str($row->discountValue);
                $row->discountValue = str_replace('.', ',', $row->discountValue);

                if ($row->product_type == 2) {
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100);
                } else {
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                }
                $priceWithDiscount = number_format($priceWithDiscount, 2);

                $tempTotal = $priceWithDiscount;
                parse_str($priceWithDiscount);
                $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                $row->sumTotal = $priceWithDiscount;

            } else {
                $row->discount = $discount[0]->value;

                $priceWithDiscount = str_replace(',', '.', $row->price);
                if ($discount[0]->type == 2) {

                    $row->discountVal = floatval($priceWithDiscount) * (floatval($discount[0]->value)) / 100;
                    $row->discountVal = number_format($row->discountVal, 2);
                    $totalDiscount = $totalDiscount + $row->discountVal;
                    parse_str($row->discountVal);
                    $row->discountVal = str_replace('.', ',', $row->discountVal);

                    $priceWithDiscount = floatval($priceWithDiscount) * (100 - floatval($discount[0]->value)) / 100;
                    $tempPriceWithDiscount = $priceWithDiscount;

                } else {

                    if ($row->product_type == 2) {
                        //$row->discountValue = floatval(floatval($tempPriceWithDiscount) * 8 / 100);
                        $row->discountValue = floatval(floatval($priceWithDiscount) * 8 / 100);
                        $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100) - floatval($discount[0]->value);
                    } else {
                        //$row->discountValue = floatval(floatval($tempPriceWithDiscount) * 18 / 100);
                        $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);
                        $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100) - floatval($discount[0]->value);
                    }

                    //$priceWithDiscount = floatval($priceWithDiscount) - floatval($discount[0]->value);

                    if ($priceWithDiscount < 0) {
                        $priceWithDiscount = 0.0;
                    }

                    $tempPriceWithDiscount = $priceWithDiscount;
                    $row->discountVal = floatval($discount[0]->value);

                    $row->discountVal = number_format($row->discountVal, 2);
                    parse_str($row->discountVal);
                    $row->discountVal = str_replace('.', ',', $row->discountVal);
                }
                $priceWithDiscount = number_format($priceWithDiscount, 2);
                $totalPartial = $totalPartial + $priceWithDiscount;
                parse_str($priceWithDiscount);
                $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);
                $row->sumPartial = $priceWithDiscount;

                $priceWithDiscount = $row->sumPartial;
                $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);

                if ($discount[0]->type == 2) {
                    if ($row->product_type == 2) {
                        $row->discountValue = floatval(floatval($tempPriceWithDiscount) * 8 / 100);
                    } else {
                        $row->discountValue = floatval(floatval($tempPriceWithDiscount) * 18 / 100);
                    }
                }

                $totalKDV = $totalKDV + $row->discountValue;
                $row->discountValue = number_format($row->discountValue, 2);
                parse_str($row->discountValue);
                $row->discountValue = str_replace('.', ',', $row->discountValue);

                if ($discount[0]->type == 2) {
                    if ($row->product_type == 2) {
                        $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 108 / 100);
                    } else {
                        $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 118 / 100);
                    }
                }

                $priceWithDiscount = number_format($priceWithDiscount, 2);

                $tempTotal = $priceWithDiscount;
                parse_str($priceWithDiscount);
                $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                $row->sumTotal = $priceWithDiscount;

            }
            $total = $total + $tempTotal;

            $tempCikolat = AdminPanelController::getCikolatData($row->sales_id);

            if ($tempCikolat) {
                $cikilotCount++;
                $row->cikilotName = $tempCikolat->name;
                $row->cikilotPrice = $tempCikolat->product_price;
                $row->cikilotDiscount = $tempCikolat->discount;
                $row->cikilotTax = $tempCikolat->tax;
                $row->cikilotTotal = $tempCikolat->total_price;
                $priceWithDiscount = str_replace(',', '.', $row->sumTotal);
                $row->cikilotTotalGeneral = number_format(floatval(str_replace(',', '.', $row->cikilotTotal)) + floatval($priceWithDiscount), 2);
                $row->cikilotTotalGeneral = str_replace('.', ',', $row->cikilotTotalGeneral);

                $priceWithDiscount = str_replace(',', '.', $row->cikilotPrice);
                $cikilotTotalPrice = $cikilotTotalPrice + $priceWithDiscount;

                $priceWithDiscount = str_replace(',', '.', $row->cikilotTax);
                $cikilotTotalTax = $cikilotTotalTax + $priceWithDiscount;

                $priceWithDiscount = str_replace(',', '.', $row->cikilotTotal);
                $cikilotGeneral = $cikilotGeneral + $priceWithDiscount;

            } else {
                $row->cikilotName = '';
                $row->cikilotDiscount = '0,0';
                $row->cikilotPrice = '';
                $row->cikilotTax = '';
                $row->cikilotTotal = '';
                $row->cikilotTotalGeneral = $row->sumTotal;
            }
            //$total = $total + $row->sumTotal;

            $row->isBank = false;
            $tempIsbank = DB::table('is_bank_log')->where('sale_id', $row->sales_id)->where('log_location', 'Sale Success Without 3D')->where('code', '0000')->count();
            if ($tempIsbank == 0) {
                $tempIsbank = DB::table('is_bank_log')->where('sale_id', $row->sales_id)->where('log_location', 'Sale Success With 3D Secure')->where('code', '0000')->count();
                if ($tempIsbank > 0) {
                    $row->isBank = true;
                }
            } else {
                $row->isBank = true;
            }

        }

        foreach ($tempSales as $key => $value) {

            $tempOne = floatval($value->finalPrice);
            $tempTwo = floatval(str_replace(',', '.', $list[$key]->cikilotTotalGeneral));

            if ($tempOne < $tempTwo) {

                //dd($list[$key]->sumTotal);

                dd($tempOne . ' = = = ' . $tempTwo);

            }
        }

        if (count($list) == 0) {
            $avarageDiscount = 0;
            $firstPrice = 0;
            $totalDiscount = 0;
            $totalPartial = 0;
            $totalKDV = 0;
            $total = 0;

            $cikilotTotalPrice = 0;
            $cikilotTotalTax = 0;
            $cikilotGeneral = 0;
            $cikilotBigGeneral = 0;

            return view('admin.billingExport', compact('list', 'cikilotCount', 'cikilotTotalPrice', 'cikilotTotalTax', 'cikilotGeneral', 'cikilotBigGeneral', 'queryParams', 'total', 'totalKDV', 'totalPartial', 'totalDiscount', 'firstPrice', 'count', 'avarageDiscount', 'companyList'));
        }
        //$cikilotBigGeneral = number_format(floatval($cikilotGeneral), 2)   + $total;
        $cikilotBigGeneral = $cikilotGeneral + $total;
        $cikilotBigGeneral = str_replace('.', ',', $cikilotBigGeneral);

        $cikilotTotalPrice = str_replace('.', ',', $cikilotTotalPrice);
        $cikilotTotalTax = str_replace('.', ',', $cikilotTotalTax);
        $cikilotGeneral = str_replace('.', ',', $cikilotGeneral);

        $avarageDiscount = $totalDiscount / $firstPrice;
        $avarageDiscount = number_format($avarageDiscount, 2);
        $avarageDiscount = $avarageDiscount * 100;
        $avarageDiscount = str_replace('.', '!', $avarageDiscount);
        $avarageDiscount = str_replace(',', '.', $avarageDiscount);
        $avarageDiscount = str_replace('!', ',', $avarageDiscount);
        $avarageDiscount = '% ' . $avarageDiscount;

        $firstPrice = number_format($firstPrice / $count, 2);
        $firstPrice = str_replace('.', '!', $firstPrice);
        $firstPrice = str_replace(',', '.', $firstPrice);
        $firstPrice = str_replace('!', ',', $firstPrice);
        $totalDiscount = number_format($totalDiscount / $count, 2);
        $totalDiscount = str_replace('.', '!', $totalDiscount);
        $totalDiscount = str_replace(',', '.', $totalDiscount);
        $totalDiscount = str_replace('!', ',', $totalDiscount);
        $totalPartial = number_format($totalPartial, 2);
        $totalPartial = str_replace('.', '!', $totalPartial);
        $totalPartial = str_replace(',', '.', $totalPartial);
        $totalPartial = str_replace('!', ',', $totalPartial);
        $totalKDV = number_format($totalKDV, 2);
        $totalKDV = str_replace('.', '!', $totalKDV);
        $totalKDV = str_replace(',', '.', $totalKDV);
        $totalKDV = str_replace('!', ',', $totalKDV);
        $total = number_format($total, 2);
        $total = str_replace('.', '!', $total);
        $total = str_replace(',', '.', $total);
        $total = str_replace('!', ',', $total);

        return view('admin.billingExport', compact('list', 'cikilotCount', 'cikilotTotalPrice', 'cikilotTotalTax', 'cikilotGeneral', 'cikilotBigGeneral', 'queryParams', 'total', 'totalKDV', 'totalPartial', 'totalDiscount', 'firstPrice', 'count', 'avarageDiscount', 'companyList'));


    }

    public function customerSegment()
    {

        $tempCustomerList = DB::table('sales')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('users', 'customers.user_id', '=', 'users.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.created_at', '<', '2019-01-01 00:00:01')
            ->select('sales.*')
            ->groupBy('users.id')
            ->select('users.id', 'users.name', 'users.surname', 'users.created_at', 'users.email', 'users.updated_at')
            ->get();

        $dateLastYear = Carbon::now();
        $dateLastYear->year(2019);
        $dateLastYear->month(01);
        $dateLastYear->day(01);
        $dateLastYear->hour(0);
        $dateLastYear->minute(0);
        $dateLastYear->second(0);

        $segmentCustomers = [];

        foreach ($tempCustomerList as $customer) {

            $lastSale = DB::table('sales')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('users', 'customers.user_id', '=', 'users.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('users.id', $customer->id)
                ->where('sales.payment_methods', '=', 'OK')
                ->orderBy('sales.created_at', 'desc')
                ->take(1)
                ->select('sales.created_at', 'products.name')
                ->get()[0];

            if ($lastSale->created_at < $dateLastYear) {

                $firstSale = DB::table('sales')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('users', 'customers.user_id', '=', 'users.id')
                    ->where('users.id', $customer->id)
                    ->where('sales.payment_methods', '=', 'OK')
                    ->orderBy('sales.created_at')
                    ->take(1)
                    ->select('sales.created_at')
                    ->get()[0];

                $totalSales = DB::table('sales')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('users', 'customers.user_id', '=', 'users.id')
                    ->where('users.id', $customer->id)
                    ->where('sales.payment_methods', '=', 'OK')
                    ->count();

                DB::table('temp_customer_segment_1')->insert([
                    'name' => $customer->name,
                    'surname' => $customer->surname,
                    'email' => $customer->email,
                    'register_date' => $customer->created_at,
                    'last_login_date' => $customer->updated_at,
                    'last_sale_date' => $lastSale->created_at,
                    'last_product' => $lastSale->name,
                    'first_sale_date' => $firstSale->created_at,
                    'total_order' => $totalSales
                ]);

            }

        }

        //dd($segmentCustomers);

    }


}