<?php namespace App\Http\Controllers;

use MongoDB\Driver\ReadConcern;
use Request;
use DB;
use Excel;
use App\Models\DeliveryLocation;
use Carbon\Carbon;
use App\Commands\SendEmail;
use SoapClient;
use App\Models\ErrorLog;
use App\Models\Image;
use App\Models\Reminder;
use SimpleXMLElement;
use stdClass;
use Mapper;
use Collection;
use App\Models\Delivery;
use Facebook;
use FacebookAds\Api;
use FacebookAds\Object\AdUser;
use FacebookAds\Object\ProductCatalog;
use FacebookAds\Object\Fields\ProductCatalogFields;
use FacebookAds\Object\ProductFeed;
use FacebookAds\Object\Fields\ProductFeedFields;
use FacebookAds\Object\Fields\ProductFeedScheduleFields;
use Datatables;
use App\Models\Customer;
use App\Models\User;

class testMethodsController extends Controller
{
    public function getIndex()
    {
        return view('admin.newCustomers');
    }

    public function testForPrice(){

        $tempPrice = '20059';

        $tempNumber = floatval($tempPrice)/100;

        parse_str($tempNumber);
        $tempNumber = str_replace('.', ',', $tempNumber);

        dd($tempNumber);

    }

    public static function checkFlower($salesId)
    {
        try {
            if (DB::table('sales')->where('id', $salesId)->get()[0]->payment_methods == 'OK') {
                return true;
            }
            $productId = DB::table('sales_products')->where('sales_id', $salesId)->get()[0]->products_id;
            $wanted_time = DB::table('deliveries')->where('sales_id', $salesId)->get()[0]->wanted_delivery_date;

            $saleCity = DB::table('sales')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.id', $salesId)->get()[0]->city_id;

            $delivery_location = DB::table('sales')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.id', $salesId)->select('delivery_locations.active')->get()[0]->active;

            if ($delivery_location == 0) {
                //dd(1);
                return false;
            }

            $productData = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.id', $salesId)
                ->select('sales_products.products_id', 'delivery_locations.city_id', 'sales.id')
                ->get()[0];

            if ($saleCity == 3) {
                $saleCity = 1;
            }

            if (!generateDataController::isProductAvailable($productData->products_id, $saleCity)) {

                //dd(2);
                return false;
            }
            else{
                $tempCrossSellData = DB::table('cross_sell')->where('sales_id', $productData->id )->get();

                if( count($tempCrossSellData) > 0 ){
                    if( !generateDataController::isCrossSellAvailable($tempCrossSellData[0]->product_id , $saleCity) ){

                        //dd(3);
                        return false;
                    }
                }
            }

            /*$tempAvalibility = DB::table('products')
                ->where( 'id', $productId)
                ->where('activation_status_id' , 1)
                ->where('limit_statu' , 0)
                ->where('coming_soon' , 0)
                ->where('avalibility_time', '<' , $wanted_time)
                ->where('avalibility_time_end' , '>' , $wanted_time)
                ->count();*/



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

                //dd(4);
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

                //dd(5);
                return false;
            }

            if ($tempAvalibility == 0) {

                //dd(6);
                return false;
            } else{
                //dd(true);
                return true;
            }

        } catch (\Exception $e) {

            logEventController::logErrorToDB('checkFlower', $e->getCode(), $e->getMessage(), 'WS', $salesId);

            return true;
        }

    }

    public function billingEntegrationWithHBWithTroy()
    {

        if (Request::get("key") == 'hx_s2fhJ=0fbKf23KgnKgy2u4wq5') {

            if (Request::get("date_start")) {
                $dateStart = Carbon::now();
                $dateStart->hour(00);
                $dateStart->minute(00);
                $dateStart->second(00);
                $dateStart->year(explode('-', Request::get("date_start"))[2]);
                $dateStart->month(explode('-', Request::get("date_start"))[1]);
                $dateStart->day(explode('-', Request::get("date_start"))[0]);
            } else {
                $dateStart = Carbon::now();
                $dateStart->hour(00);
                $dateStart->minute(00);
                $dateStart->second(00);
            }

            if (Request::get("date_end")) {
                $dateEnd = Carbon::now();
                $dateEnd->hour(23);
                $dateEnd->minute(59);
                $dateEnd->second(59);
                $dateEnd->year(explode('-', Request::get("date_end"))[2]);
                $dateEnd->month(explode('-', Request::get("date_end"))[1]);
                $dateEnd->day(explode('-', Request::get("date_end"))[0]);
            } else {
                $dateEnd = Carbon::now();
                $dateEnd->hour(23);
                $dateEnd->minute(59);
                $dateEnd->second(59);
            }

            if (Request::get("status")) {
                $statusString = ' deliveries.status = 3';
            } else {
                $statusString = ' deliveries.status != 1231';
            }

            if (Request::get("page_count")) {
                $tempPageCount = Request::get("page_count");
            } else {
                $tempPageCount = 50;
            }

            if (Request::get("page")) {
                $tempSkip = (Request::get("page") - 1) * $tempPageCount;
                if ($tempSkip < 0) {
                    $tempSkip = 0;
                }
            } else {
                $tempSkip = 0;
            }

            $queryString = ' deliveries.wanted_delivery_date > "' . $dateStart . '" and deliveries.wanted_delivery_date < "' . $dateEnd . '" and ' . $statusString;

            //dd($queryString);

            $list = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->whereRaw($queryString)
                ->where('sales.payment_methods', 'OK')
                ->where('sales.payment_type', '!=', 'KURUMSAL')
                ->skip($tempSkip)
                ->take($tempPageCount)
                ->orderBy('deliveries.delivery_date')
                ->select('sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling', 'sales.IsTroyCard',
                    'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
                    'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'sales.sender_mobile', 'products.name as products', 'products.city_id', 'sales.sender_name', 'sales.sender_surname',
                    'sales.product_price as price', 'products.id', 'products.product_type', 'sales.customer_contact_id', 'sales.send_billing', 'deliveries.delivery_date', 'sales.payment_type', 'sales.sender_email', 'deliveries.status')
                ->get();

            $queryStringStudio = ' price != "" and price != "0" and studioBloom.payment_date > "' . $dateStart . '" and studioBloom.payment_date < "' . $dateEnd . '"';


            $countStudio = DB::table('studioBloom')
                ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
                ->whereRaw($queryStringStudio)
                ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                    , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                    , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile', 'studio_billings.billing_surname')->count();


            $totalCount = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->whereRaw($queryString)
                ->where('sales.payment_methods', 'OK')
                ->where('sales.payment_type', '!=', 'KURUMSAL')
                ->count();

            $totalCount = $countStudio + $totalCount;

            foreach ($list as $row) {

                $firstPrice = 0;
                $totalDiscount = 0;
                $totalPartial = 0;
                $totalKDV = 0;
                $total = 0;
                $total_discount = 0;
                $flower_discount = 0;

                if ($row->product_type == 2) {
                    $row->kdv_percentage = '8.0';
                } else {
                    $row->kdv_percentage = '18.0';
                }

                $tempTotal = 0;
                $tempVal = str_replace(',', '.', $row->price);
                $firstPrice = $firstPrice + floatval($tempVal);
                $discount = DB::table('marketing_acts_sales')
                    ->join('marketing_acts', 'marketing_acts_sales.marketing_acts_id', '=', 'marketing_acts.id')
                    ->where('sales_id', $row->sales_id)->get();

                if ($row->billing_type == 1 && ($row->userBilling == 1 || $row->billing_send == 1)) {
                    $row->name = $row->billing_name . ' ' . $row->billing_surname;
                    $row->sender_name = $row->billing_name;
                    $row->sender_surname = $row->billing_surname;
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
                    $row->address2 = $row->city;
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
                    $total_discount = $row->discountVal;
                    $flower_discount = $row->discountVal;

                } else {
                    $row->discount = $discount[0]->value;
                    //$total_discount = $row->discountValue;
                    //$flower_discount = $row->discountValue;

                    $priceWithDiscount = str_replace(',', '.', $row->price);
                    if ($discount[0]->type == 2) {

                        $row->discount_type = 'percentage';

                        $row->discountVal = floatval($priceWithDiscount) * (floatval($discount[0]->value)) / 100;
                        $row->discountVal = number_format($row->discountVal, 2);
                        $totalDiscount = $totalDiscount + $row->discountVal;
                        parse_str($row->discountVal);
                        $row->discountVal = str_replace('.', ',', $row->discountVal);

                        $priceWithDiscount = floatval($priceWithDiscount) * (100 - floatval($discount[0]->value)) / 100;
                        $tempPriceWithDiscount = $priceWithDiscount;
                        $total_discount = str_replace(',', '.', $row->discountVal);
                        $flower_discount = str_replace(',', '.', $row->discountVal);

                    } else {

                        $row->discount_type = 'coupon';

                        $row->discountVal = $priceWithDiscount;

                        if ($row->product_type == 2) {
                            $row->discountValue = floatval(floatval($priceWithDiscount) * 8 / 100);
                            $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100) - floatval($discount[0]->value);
                        } else {
                            $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);
                            $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100) - floatval($discount[0]->value);
                        }

                        //$priceWithDiscount = floatval($priceWithDiscount) - floatval($discount[0]->value);
                        if ($priceWithDiscount <= 0) {
                            $priceWithDiscount = 0;
                            $flower_discount = 0;
                            $total_discount = 0;
                            $row->price = 0;
                        } else {

                            if ($row->product_type == 2) {
                                $row->products = 'Çikolata Bedeli (Hediye Çeki kullanılmıştır)';
                                $row->price = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 108 * 100, 2);
                                $row->discountValue = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 108 * 8, 2);
                            } else {
                                $row->products = 'Çiçek Bedeli (Hediye Çeki kullanılmıştır)';
                                $row->price = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 118 * 100, 2);
                                $row->discountValue = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 118 * 18, 2);
                            }
                            $flower_discount = 0;
                            $total_discount = 0;
                            $row->discountVal = 0;

                            //$row->discountVal = floatval($discount[0]->value);
                            //$flower_discount = str_replace(',', '.', $row->discountVal);
                            //$total_discount = str_replace(',', '.', $row->discountVal);
                        }

                        $tempPriceWithDiscount = $priceWithDiscount;

                        $row->discountVal = number_format($row->discountVal, 2);
                        parse_str($row->discountVal);
                        $row->discountVal = str_replace('.', ',', $row->discountVal);
                        $row->discountVal = $row->discount;
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
                        if ($row->product_type == 2) {
                            $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 108 / 100);
                        } else {
                            $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 118 / 100);
                        }
                    }

                    $totalKDV = $totalKDV + $row->discountValue;
                    $row->discountValue = number_format($row->discountValue, 2);
                    parse_str($row->discountValue);
                    $row->discountValue = str_replace('.', ',', $row->discountValue);

                    //if( $row->product_type == 2 ){
                    //    $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 108 / 100);
                    //}
                    //else{
                    //    $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 118 / 100);
                    //}


                    $priceWithDiscount = number_format($priceWithDiscount, 2);

                    parse_str($priceWithDiscount);
                    $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                    $row->sumTotal = $priceWithDiscount;

                }

                $tempCikolat = AdminPanelController::getCikolatData($row->sales_id);

                if ($tempCikolat) {
                    $cikolatPrice = $tempCikolat->total_price;
                    $cikolatProductPrice = $tempCikolat->product_price;
                    $cikolatProductTax = $tempCikolat->tax;

                    if ($tempCikolat->discount > 0) {
                        $tempDiscountTextCrossSell = '<ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                İndirim
                            </ns4:AllowanceChargeReason>
                            <ns4:Amount currencyID="TRY">
                                ' . str_replace(',', '.', $tempCikolat->discount) . '
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                ' . str_replace(',', '.', $row->price) . '
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>';
                        $tempDiscountTextCrossSell = '';
                        $tempCikolat->name = 'Çikolata bedeli (Hediye çeki kullanılmıştır)';
                        $tempCikolatNumber = '1';
                        $tempCikolat->tax = number_format(floatval(str_replace(',', '.', $tempCikolat->total_price)) / 108 * 8, 2);
                        $tempCikolat->product_price = number_format(floatval(str_replace(',', '.', $tempCikolat->total_price)) / 108 * 100, 2);
                        $cikolatProductPrice = $tempCikolat->product_price;
                        $tempDiscountTextCrossSell = '';

                        $row->extra_urun_discount = (object)[
                            'discount_amount' => str_replace(',', '.', $tempCikolat->discount),
                            'base_amount' => $row->price
                        ];

                    } else {
                        $tempDiscountTextCrossSell = '';
                        $tempCikolatNumber = '2';
                    }

                    $row->extra_urun = (object)[
                        'id' => $tempCikolatNumber,
                        'price' => str_replace(',', '.', $tempCikolat->product_price),
                        'name' => $tempCikolat->name,
                        'item_identification' => $tempCikolat->id,
                        'tax' => str_replace(',', '.', $tempCikolat->tax),
                        'kdv_percentage' => 8.0
                    ];

                } else {
                    $cikolatLine = '';
                    $cikolatTaxLine = '';
                    $cikolatPrice = 0;
                    $cikolatProductPrice = 0;
                    $cikolatProductTax = 0;
                }

                $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                $word = $f->format(intval(str_replace(',', '.', $cikolatPrice) + str_replace(',', '.', $row->sumTotal)));

                $wordExtra = $f->format(explode(".", number_format(floatval(str_replace(',', '.', $row->sumTotal)) + floatval(str_replace(',', '.', $cikolatPrice)), 2))[1]);
                $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';
                //dd($word . ' Lira ' . $wordExtra . ' Kuruş');

                $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
                $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';

                $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
                $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';

                $row->price = str_replace(',', '.', $row->price);
                $row->discountVal = str_replace(',', '.', $row->discountVal);
                $row->discountValue = str_replace(',', '.', $row->discountValue);
                if ($row->discountValue == '0.0') {
                    $row->discountValue = '';
                }
                $row->sumPartial = str_replace(',', '.', $row->sumPartial);
                $row->sumTotal = str_replace(',', '.', $row->sumTotal);
                $tempPaymentType = "";
                $tempPaymentTool = "";
                if ($row->payment_type == "POS") {
                    $row->payment_type_string = "SANAL POS";
                    $row->payment_tool_string = "KREDIKARTI/BANKAKARTI";
                    $tempPaymentType = "SANAL POS";
                    $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
                } else {
                    $row->payment_type_string = "EFT/HAVALE";
                    $row->payment_tool_string = "EFT/HAVALE";
                    $tempPaymentType = "EFT/HAVALE";
                    $tempPaymentTool = "EFT/HAVALE";
                }

                if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {

                    if ($row->tc == '' || $row->tc == null)
                        $row->tc = '11111111111';
                    else {
                        $row->bigCity = $row->small_city;
                    }

                    if ($row->sender_surname == '' || $row->sender_surname == null)
                    {
                        $row->sender_surname = 'Yılmaz';
                    }

                    if ( $row->billing_surname == '' || $row->billing_surname == null ){
                        $row->billing_surname = 'Yılmaz';
                    }


                } else {

                    if ($row->payment_type == 'KURUMSAL') {
                        //dd($row->customer_contact_id);
                        $tempCompanyInfo = DB::table('customer_contacts')
                            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                            ->join('company_user_info', 'customers.user_id', '=', 'company_user_info.user_id')
                            ->where('customer_contacts.id', $row->customer_contact_id)
                            ->select('company_name', 'tax_no', 'tax_office', 'billing_address')
                            ->get()[0];

                        $row->tax_no = $tempCompanyInfo->tax_no;
                        $row->company = $tempCompanyInfo->company_name;
                        $row->billing_address = $tempCompanyInfo->billing_address;
                        $row->tax_office = $tempCompanyInfo->tax_office;
                    }

                }

                unset($row->payment_type);
                unset($row->device);
                unset($row->delivery_locations_id);
                unset($row->billing_send);
                unset($row->sender_name);
                unset($row->sender_surname);
                unset($row->customer_contact_id);
                unset($row->send_billing);
                unset($row->address2);
                unset($row->wantedDate);
                unset($row->billing_id);
                unset($row->sender_mobile);
                unset($row->city_id);
                unset($row->name);
                unset($row->userBilling);
                unset($row->smallCity);
                unset($row->bigCity);

                $tempCreatedAt = $row->created_at;
                $tempCreatedAtDate = explode(" ", $tempCreatedAt)[0];

                $row->created_at = explode(".", $tempCreatedAtDate)[2] . '-' . explode(".", $tempCreatedAtDate)[1] . '-' . explode(".", $tempCreatedAtDate)[0] . ' ' . explode(" ", $tempCreatedAt)[1] . ':00';

                $row->discount_value = $row->discountVal;
                unset($row->discountVal);

                $row->tax = $row->discountValue;
                unset($row->discountValue);


                if ($row->billing_type == "1") {
                    unset($row->company);
                    unset($row->tax_office);
                    unset($row->tax_no);
                } else if ($row->billing_type == "2") {

                    $tempTaxOffice = explode("-", $row->tax_office);

                    if (count($tempTaxOffice) > 1) {
                        $row->tax_office = $tempTaxOffice[0];
                    }

                    //unset($row->small_city);
                    unset($row->tc);
                }

                if( $row->IsTroyCard ){

                    $row->sumTotal = $row->sumTotal - 30.0;
                    $row->sumPartial = $row->sumTotal;

                    if ($row->product_type == 2) {
                        $row->price = number_format(floatval(floatval($row->sumTotal) * 100 / 108), 2);
                    } else {
                        $row->price = number_format(floatval(floatval($row->sumTotal) * 100 / 118), 2);
                    }

                    $row->tax = $row->sumPartial - $row->price;

                    $row->discount = floatval($row->discount_value) + 30.0;
                    $row->discount_type = 'coupon';
                    $row->discount_value = $row->discount;


                    $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                    $word = $f->format(intval(str_replace(',', '.', $cikolatPrice) + str_replace(',', '.', $row->sumTotal)));

                    $wordExtra = $f->format(explode(".", number_format(floatval(str_replace(',', '.', $row->sumTotal)) + floatval(str_replace(',', '.', $cikolatPrice)), 2))[1]);
                    $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';

                    parse_str($row->discount);
                    $row->discount = str_replace(' ', '', $row->discount);

                    parse_str($row->discount_value);
                    $row->discount_value = str_replace(' ', '', $row->discount_value);

                    parse_str($row->sumPartial);
                    $row->sumPartial = str_replace(' ', '', $row->sumPartial);

                    parse_str($row->sumTotal);
                    $row->sumTotal = str_replace(' ', '', $row->sumTotal);

                    parse_str($row->tax);
                    $row->tax = str_replace(' ', '', $row->tax);

                }

            }

            if ($tempPageCount > count($list)) {
                $studioBloomCount = $tempPageCount - count($list);

                $listStudio = DB::table('studioBloom')
                    ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
                    ->whereRaw($queryStringStudio)
                    ->take($studioBloomCount)
                    ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                        , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                        , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile', 'studio_billings.billing_surname')->get();

                foreach ($listStudio as $row) {

                    $row->sales_id = substr($row->id, 0, 6);

                    $firstPrice = 0;
                    $totalDiscount = 0;
                    $totalPartial = 0;
                    $totalKDV = 0;
                    $total = 0;
                    $total_discount = 0;
                    $flower_discount = 0;

                    $row->product_type = 1;
                    $row->kdv_percentage = '18.0';
                    $row->wanted_delivery_date = $row->wanted_date;

                    $tempTotal = 0;
                    $tempVal = str_replace(',', '.', $row->price);
                    $firstPrice = $firstPrice + floatval($tempVal);

                    if ($row->billing_type == 1 && ($row->userBilling == 1 || $row->billing_send == 1)) {
                        $row->name = $row->billing_name . ' ' . $row->billing_surname;
                        $row->sender_name = $row->billing_name;
                        $row->sender_surname = $row->billing_surname;
                        $row->bigCity = $row->city;
                        $row->smallCity = $row->small_city;
                        $row->address2 = $row->billing_address;
                        $row->tax_office = $row->tc;
                    } else if ($row->billing_type == 1) {
                        $row->name = $row->billing_name . ' ' . $row->billing_surname;
                        $districtTemp = 'Sarıyer-Emirgan';
                        $row->bigCity = explode("-", $districtTemp)[0];
                        $row->smallCity = explode("-", $districtTemp)[1];
                        $row->address2 = $row->city;
                    } else {
                        $row->name = $row->company;
                        $row->bigCity = $row->billing_address;
                        $row->small_city = $row->tax_office;
                        $row->address2 = "";
                        $row->tax_office = $row->tax_office . "-" . $row->tax_no;
                    }

                    $dateTemp = new Carbon($row->wanted_delivery_date);

                    $row->wantedDate = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year;

                    $dateTemp = new Carbon($row->created_at);

                    $row->created_at = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year . ' ' . sprintf("%02d", $dateTemp->hour) . ':' . sprintf("%02d", $dateTemp->minute);

                    $row->id = sprintf("%03d", $row->id);

                    $row->discount = 0;
                    $row->discountVal = 0;
                    $row->sumPartial = $row->price;

                    $priceWithDiscount = $row->price;
                    $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);
                    $totalPartial = $totalPartial + $priceWithDiscount;

                    $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);

                    $row->discountValue = number_format($row->discountValue, 2);
                    parse_str($row->discountValue);
                    $row->discountValue = str_replace('.', ',', $row->discountValue);

                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                    $priceWithDiscount = number_format($priceWithDiscount, 2, '.', '');

                    $tempTotal = $priceWithDiscount;
                    parse_str($priceWithDiscount);
                    $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                    $row->sumTotal = $priceWithDiscount;
                    $total_discount = $row->discountVal;
                    $flower_discount = $row->discountVal;

                    $cikolatLine = '';
                    $cikolatTaxLine = '';
                    $cikolatPrice = 0;
                    $cikolatProductPrice = 0;
                    $cikolatProductTax = 0;


                    $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                    $word = $f->format(intval(str_replace(',', '.', $cikolatPrice) + str_replace(',', '.', $row->sumTotal)));

                    $wordExtra = $f->format(explode(".", number_format(floatval(str_replace(',', '.', $row->sumTotal)) + floatval(str_replace(',', '.', $cikolatPrice)), 2))[1]);
                    $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';
                    //dd($word . ' Lira ' . $wordExtra . ' Kuruş');

                    $row->price = str_replace(',', '.', $row->price);
                    $row->discountVal = str_replace(',', '.', $row->discountVal);
                    $row->discountValue = str_replace(',', '.', $row->discountValue);
                    if ($row->discountValue == '0.0') {
                        $row->discountValue = '';
                    }
                    $row->sumPartial = str_replace(',', '.', $row->sumPartial);
                    $row->sumTotal = str_replace(',', '.', $row->sumTotal);
                    $tempPaymentType = "";
                    $tempPaymentTool = "";
                    if ($row->payment_type == "POS") {
                        $row->payment_type_string = "SANAL POS";
                        $row->payment_tool_string = "KREDIKARTI/BANKAKARTI";
                        $tempPaymentType = "SANAL POS";
                        $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
                    } else {
                        $row->payment_type_string = "EFT/HAVALE";
                        $row->payment_tool_string = "EFT/HAVALE";
                        $tempPaymentType = "EFT/HAVALE";
                        $tempPaymentTool = "EFT/HAVALE";
                    }

                    if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {


                        if ($row->tc == '' || $row->tc == null)
                            $row->tc = '11111111111';
                        else {
                            $row->bigCity = $row->small_city;
                        }

                        //if ($row->sender_surname == '' || $row->sender_surname == null)
                        //    $row->sender_surname = 'Yılmaz';

                    }

                    unset($row->payment_type);
                    unset($row->device);
                    unset($row->delivery_locations_id);
                    unset($row->billing_send);
                    unset($row->sender_name);
                    unset($row->sender_surname);
                    unset($row->customer_contact_id);
                    unset($row->send_billing);
                    unset($row->address2);
                    unset($row->wantedDate);
                    unset($row->billing_id);
                    unset($row->sender_mobile);
                    unset($row->city_id);
                    unset($row->name);
                    unset($row->userBilling);
                    unset($row->smallCity);
                    unset($row->bigCity);
                    unset($row->billing_number);
                    unset($row->contact_name);
                    unset($row->contact_surname);
                    unset($row->continent_id);
                    unset($row->customer);
                    unset($row->customer_mobile);
                    unset($row->customer_name);
                    unset($row->customer_surname);
                    unset($row->district);
                    unset($row->customer_surname);
                    $row->status = $row->delivery_status;
                    $row->sender_email = $row->email;
                    unset($row->delivery_status);
                    unset($row->email);

                    unset($row->flower_desc);
                    $row->products = $row->flower_name;
                    unset($row->flower_name);

                    unset($row->note);
                    $row->created_at = $row->payment_date;
                    unset($row->operation_name);

                    unset($row->payment_mail);
                    unset($row->picker);
                    unset($row->receiver_address);
                    unset($row->wanted_date);
                    unset($row->wanted_delivery_limit);
                    unset($row->payment_date);

                    $tempCreatedAt = $row->created_at;
                    $tempCreatedAtDate = explode(" ", $tempCreatedAt)[0];

                    //$row->created_at = explode(".",  $tempCreatedAtDate)[2] . '-' . explode(".",  $tempCreatedAtDate)[1] . '-' . explode(".",  $tempCreatedAtDate)[0] . ' ' . explode(" ",  $tempCreatedAt)[1] . ':00';

                    $row->discount_value = $row->discountVal;
                    unset($row->discountVal);

                    $row->tax = $row->discountValue;
                    unset($row->discountValue);


                    if ($row->billing_type == "1") {
                        unset($row->company);
                        unset($row->tax_office);
                        unset($row->tax_no);
                    } else if ($row->billing_type == "2") {

                        $tempTaxOffice = explode("-", $row->tax_office);

                        if (count($tempTaxOffice) > 1) {
                            $row->tax_office = $tempTaxOffice[0];
                        }

                        //unset($row->small_city);
                        unset($row->tc);
                    }

                    array_push($list, $row);
                }

            }

            $hbDateEnd = Carbon::now();
            $hbDateEnd->hour(12);
            $hbDateEnd->minute(12);
            $hbDateEnd->second(12);
            $hbDateEnd->year(2019);
            $hbDateEnd->month(02);
            $hbDateEnd->day(14);

            if( $dateStart < $hbDateEnd && $dateEnd > $hbDateEnd ){
                $hbSales = DB::table('hb_billing_sales')->select('billing_address','billing_name','billing_surname','billing_type','city','created_at','delivery_date','discount','discount_value','id','kdv_percentage'
                    ,'payment_tool_string','payment_type_string','price','product_type','products','sales_id','sender_email','small_city','status','sumPartial','sumTotal','tax','tc','totalWithWord','wanted_delivery_date')->get();

                $hbSales2 = DB::table('hb_billing_sales_2')->select('billing_address','billing_name','billing_surname','billing_type','city','company','created_at','delivery_date','discount','discount_value','id','kdv_percentage'
                    ,'payment_tool_string','payment_type_string','price','product_type','products','sales_id','sender_email','small_city','status','sumPartial','sumTotal','tax','tax_no','tax_office','totalWithWord','wanted_delivery_date')->get();


                foreach ( $hbSales as $hbSale ){
                    $hbSale->discount = 0;

                    array_push($list, $hbSale);
                }

                foreach ( $hbSales2 as $hbSale ){
                    $hbSale->discount = 0;

                    array_push($list, $hbSale);
                }

                $totalCount = $totalCount + (int)count($hbSales) + (int)count($hbSales2);

                //dd($list);
            }

            //dd($list);
            return response()->json(["data" => $list, 'total' => $totalCount], 200);

        }

    }

    public function billingEntegrationWithHB()
    {

        if (Request::get("key") == 'hx_s2fhJ=0fbKf23KgnKgy2u4wq5') {

            if (Request::get("date_start")) {
                $dateStart = Carbon::now();
                $dateStart->hour(00);
                $dateStart->minute(00);
                $dateStart->second(00);
                $dateStart->year(explode('-', Request::get("date_start"))[2]);
                $dateStart->month(explode('-', Request::get("date_start"))[1]);
                $dateStart->day(explode('-', Request::get("date_start"))[0]);
            } else {
                $dateStart = Carbon::now();
                $dateStart->hour(00);
                $dateStart->minute(00);
                $dateStart->second(00);
            }

            if (Request::get("date_end")) {
                $dateEnd = Carbon::now();
                $dateEnd->hour(23);
                $dateEnd->minute(59);
                $dateEnd->second(59);
                $dateEnd->year(explode('-', Request::get("date_end"))[2]);
                $dateEnd->month(explode('-', Request::get("date_end"))[1]);
                $dateEnd->day(explode('-', Request::get("date_end"))[0]);
            } else {
                $dateEnd = Carbon::now();
                $dateEnd->hour(23);
                $dateEnd->minute(59);
                $dateEnd->second(59);
            }

            if (Request::get("status")) {
                $statusString = ' deliveries.status = 3';
            } else {
                $statusString = ' deliveries.status != 1231';
            }

            if (Request::get("page_count")) {
                $tempPageCount = Request::get("page_count");
            } else {
                $tempPageCount = 50;
            }

            if (Request::get("page")) {
                $tempSkip = (Request::get("page") - 1) * $tempPageCount;
                if ($tempSkip < 0) {
                    $tempSkip = 0;
                }
            } else {
                $tempSkip = 0;
            }

            $queryString = ' deliveries.wanted_delivery_date > "' . $dateStart . '" and deliveries.wanted_delivery_date < "' . $dateEnd . '" and ' . $statusString;

            //dd($queryString);

            $list = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->whereRaw($queryString)
                ->where('sales.payment_methods', 'OK')
                ->where('sales.payment_type', '!=', 'KURUMSAL')
                ->skip($tempSkip)
                ->take($tempPageCount)
                ->orderBy('deliveries.delivery_date')
                ->select('sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling',
                    'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
                    'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'sales.sender_mobile', 'products.name as products', 'products.city_id', 'sales.sender_name', 'sales.sender_surname',
                    'sales.product_price as price', 'products.id', 'products.product_type', 'sales.customer_contact_id', 'sales.send_billing', 'deliveries.delivery_date', 'sales.payment_type', 'sales.sender_email', 'deliveries.status')
                ->get();

            $queryStringStudio = ' price != "" and price != "0" and studioBloom.payment_date > "' . $dateStart . '" and studioBloom.payment_date < "' . $dateEnd . '"';


            $countStudio = DB::table('studioBloom')
                ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
                ->whereRaw($queryStringStudio)
                ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                    , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                    , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile', 'studio_billings.billing_surname')->count();


            $totalCount = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->whereRaw($queryString)
                ->where('sales.payment_methods', 'OK')
                ->where('sales.payment_type', '!=', 'KURUMSAL')
                ->count();

            $totalCount = $countStudio + $totalCount;

            foreach ($list as $row) {

                $firstPrice = 0;
                $totalDiscount = 0;
                $totalPartial = 0;
                $totalKDV = 0;
                $total = 0;
                $total_discount = 0;
                $flower_discount = 0;

                if ($row->product_type == 2) {
                    $row->kdv_percentage = '8.0';
                } else {
                    $row->kdv_percentage = '18.0';
                }

                $tempTotal = 0;
                $tempVal = str_replace(',', '.', $row->price);
                $firstPrice = $firstPrice + floatval($tempVal);
                $discount = DB::table('marketing_acts_sales')
                    ->join('marketing_acts', 'marketing_acts_sales.marketing_acts_id', '=', 'marketing_acts.id')
                    ->where('sales_id', $row->sales_id)->get();

                if ($row->billing_type == 1 && ($row->userBilling == 1 || $row->billing_send == 1)) {
                    $row->name = $row->billing_name . ' ' . $row->billing_surname;
                    $row->sender_name = $row->billing_name;
                    $row->sender_surname = $row->billing_surname;
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
                    $row->address2 = $row->city;
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
                    $total_discount = $row->discountVal;
                    $flower_discount = $row->discountVal;

                } else {
                    $row->discount = $discount[0]->value;
                    //$total_discount = $row->discountValue;
                    //$flower_discount = $row->discountValue;

                    $priceWithDiscount = str_replace(',', '.', $row->price);
                    if ($discount[0]->type == 2) {

                        $row->discount_type = 'percentage';

                        $row->discountVal = floatval($priceWithDiscount) * (floatval($discount[0]->value)) / 100;
                        $row->discountVal = number_format($row->discountVal, 2);
                        $totalDiscount = $totalDiscount + $row->discountVal;
                        parse_str($row->discountVal);
                        $row->discountVal = str_replace('.', ',', $row->discountVal);

                        $priceWithDiscount = floatval($priceWithDiscount) * (100 - floatval($discount[0]->value)) / 100;
                        $tempPriceWithDiscount = $priceWithDiscount;
                        $total_discount = str_replace(',', '.', $row->discountVal);
                        $flower_discount = str_replace(',', '.', $row->discountVal);

                    } else {

                        $row->discount_type = 'coupon';

                        $row->discountVal = $priceWithDiscount;

                        if ($row->product_type == 2) {
                            $row->discountValue = floatval(floatval($priceWithDiscount) * 8 / 100);
                            $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100) - floatval($discount[0]->value);
                        } else {
                            $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);
                            $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100) - floatval($discount[0]->value);
                        }

                        //$priceWithDiscount = floatval($priceWithDiscount) - floatval($discount[0]->value);
                        if ($priceWithDiscount <= 0) {
                            $priceWithDiscount = 0;
                            $flower_discount = 0;
                            $total_discount = 0;
                            $row->price = 0;
                        } else {

                            if ($row->product_type == 2) {
                                $row->products = 'Çikolata Bedeli (Hediye Çeki kullanılmıştır)';
                                $row->price = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 108 * 100, 2);
                                $row->discountValue = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 108 * 8, 2);
                            } else {
                                $row->products = 'Çiçek Bedeli (Hediye Çeki kullanılmıştır)';
                                $row->price = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 118 * 100, 2);
                                $row->discountValue = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 118 * 18, 2);
                            }
                            $flower_discount = 0;
                            $total_discount = 0;
                            $row->discountVal = 0;

                            //$row->discountVal = floatval($discount[0]->value);
                            //$flower_discount = str_replace(',', '.', $row->discountVal);
                            //$total_discount = str_replace(',', '.', $row->discountVal);
                        }

                        $tempPriceWithDiscount = $priceWithDiscount;

                        $row->discountVal = number_format($row->discountVal, 2);
                        parse_str($row->discountVal);
                        $row->discountVal = str_replace('.', ',', $row->discountVal);
                        $row->discountVal = $row->discount;
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
                        if ($row->product_type == 2) {
                            $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 108 / 100);
                        } else {
                            $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 118 / 100);
                        }
                    }

                    $totalKDV = $totalKDV + $row->discountValue;
                    $row->discountValue = number_format($row->discountValue, 2);
                    parse_str($row->discountValue);
                    $row->discountValue = str_replace('.', ',', $row->discountValue);

                    //if( $row->product_type == 2 ){
                    //    $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 108 / 100);
                    //}
                    //else{
                    //    $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 118 / 100);
                    //}


                    $priceWithDiscount = number_format($priceWithDiscount, 2);

                    parse_str($priceWithDiscount);
                    $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                    $row->sumTotal = $priceWithDiscount;

                }

                $tempCikolat = AdminPanelController::getCikolatData($row->sales_id);

                if ($tempCikolat) {
                    $cikolatPrice = $tempCikolat->total_price;
                    $cikolatProductPrice = $tempCikolat->product_price;
                    $cikolatProductTax = $tempCikolat->tax;

                    if ($tempCikolat->discount > 0) {
                        $tempDiscountTextCrossSell = '<ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                İndirim
                            </ns4:AllowanceChargeReason>
                            <ns4:Amount currencyID="TRY">
                                ' . str_replace(',', '.', $tempCikolat->discount) . '
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                ' . str_replace(',', '.', $row->price) . '
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>';
                        $tempDiscountTextCrossSell = '';
                        $tempCikolat->name = 'Çikolata bedeli (Hediye çeki kullanılmıştır)';
                        $tempCikolatNumber = '1';
                        $tempCikolat->tax = number_format(floatval(str_replace(',', '.', $tempCikolat->total_price)) / 108 * 8, 2);
                        $tempCikolat->product_price = number_format(floatval(str_replace(',', '.', $tempCikolat->total_price)) / 108 * 100, 2);
                        $cikolatProductPrice = $tempCikolat->product_price;
                        $tempDiscountTextCrossSell = '';

                        $row->extra_urun_discount = (object)[
                            'discount_amount' => str_replace(',', '.', $tempCikolat->discount),
                            'base_amount' => $row->price
                        ];

                    } else {
                        $tempDiscountTextCrossSell = '';
                        $tempCikolatNumber = '2';
                    }

                    $row->extra_urun = (object)[
                        'id' => $tempCikolatNumber,
                        'price' => str_replace(',', '.', $tempCikolat->product_price),
                        'name' => $tempCikolat->name,
                        'item_identification' => $tempCikolat->id,
                        'tax' => str_replace(',', '.', $tempCikolat->tax),
                        'kdv_percentage' => 8.0
                    ];

                } else {
                    $cikolatLine = '';
                    $cikolatTaxLine = '';
                    $cikolatPrice = 0;
                    $cikolatProductPrice = 0;
                    $cikolatProductTax = 0;
                }

                $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                $word = $f->format(intval(str_replace(',', '.', $cikolatPrice) + str_replace(',', '.', $row->sumTotal)));

                $wordExtra = $f->format(explode(".", number_format(floatval(str_replace(',', '.', $row->sumTotal)) + floatval(str_replace(',', '.', $cikolatPrice)), 2))[1]);
                $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';
                //dd($word . ' Lira ' . $wordExtra . ' Kuruş');

                $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
                $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';

                $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
                $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';

                $row->price = str_replace(',', '.', $row->price);
                $row->discountVal = str_replace(',', '.', $row->discountVal);
                $row->discountValue = str_replace(',', '.', $row->discountValue);
                if ($row->discountValue == '0.0') {
                    $row->discountValue = '';
                }
                $row->sumPartial = str_replace(',', '.', $row->sumPartial);
                $row->sumTotal = str_replace(',', '.', $row->sumTotal);
                $tempPaymentType = "";
                $tempPaymentTool = "";
                if ($row->payment_type == "POS") {
                    $row->payment_type_string = "SANAL POS";
                    $row->payment_tool_string = "KREDIKARTI/BANKAKARTI";
                    $tempPaymentType = "SANAL POS";
                    $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
                } else {
                    $row->payment_type_string = "EFT/HAVALE";
                    $row->payment_tool_string = "EFT/HAVALE";
                    $tempPaymentType = "EFT/HAVALE";
                    $tempPaymentTool = "EFT/HAVALE";
                }

                if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {


                    if ($row->tc == '' || $row->tc == null)
                        $row->tc = '11111111111';
                    else {
                        $row->bigCity = $row->small_city;
                    }

                    if ($row->sender_surname == '' || $row->sender_surname == null)
                        $row->sender_surname = 'Yılmaz';

                } else {

                    if ($row->payment_type == 'KURUMSAL') {
                        //dd($row->customer_contact_id);
                        $tempCompanyInfo = DB::table('customer_contacts')
                            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                            ->join('company_user_info', 'customers.user_id', '=', 'company_user_info.user_id')
                            ->where('customer_contacts.id', $row->customer_contact_id)
                            ->select('company_name', 'tax_no', 'tax_office', 'billing_address')
                            ->get()[0];

                        $row->tax_no = $tempCompanyInfo->tax_no;
                        $row->company = $tempCompanyInfo->company_name;
                        $row->billing_address = $tempCompanyInfo->billing_address;
                        $row->tax_office = $tempCompanyInfo->tax_office;
                    }

                }

                unset($row->payment_type);
                unset($row->device);
                unset($row->delivery_locations_id);
                unset($row->billing_send);
                unset($row->sender_name);
                unset($row->sender_surname);
                unset($row->customer_contact_id);
                unset($row->send_billing);
                unset($row->address2);
                unset($row->wantedDate);
                unset($row->billing_id);
                unset($row->sender_mobile);
                unset($row->city_id);
                unset($row->name);
                unset($row->userBilling);
                unset($row->smallCity);
                unset($row->bigCity);

                $tempCreatedAt = $row->created_at;
                $tempCreatedAtDate = explode(" ", $tempCreatedAt)[0];

                $row->created_at = explode(".", $tempCreatedAtDate)[2] . '-' . explode(".", $tempCreatedAtDate)[1] . '-' . explode(".", $tempCreatedAtDate)[0] . ' ' . explode(" ", $tempCreatedAt)[1] . ':00';

                $row->discount_value = $row->discountVal;
                unset($row->discountVal);

                $row->tax = $row->discountValue;
                unset($row->discountValue);


                if ($row->billing_type == "1") {
                    unset($row->company);
                    unset($row->tax_office);
                    unset($row->tax_no);
                } else if ($row->billing_type == "2") {

                    $tempTaxOffice = explode("-", $row->tax_office);

                    if (count($tempTaxOffice) > 1) {
                        $row->tax_office = $tempTaxOffice[0];
                    }

                    //unset($row->small_city);
                    unset($row->tc);
                }

            }

            if ($tempPageCount > count($list)) {
                $studioBloomCount = $tempPageCount - count($list);

                $listStudio = DB::table('studioBloom')
                    ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
                    ->whereRaw($queryStringStudio)
                    ->take($studioBloomCount)
                    ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                        , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                        , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile', 'studio_billings.billing_surname')->get();

                foreach ($listStudio as $row) {

                    $row->sales_id = substr($row->id, 0, 6);

                    $firstPrice = 0;
                    $totalDiscount = 0;
                    $totalPartial = 0;
                    $totalKDV = 0;
                    $total = 0;
                    $total_discount = 0;
                    $flower_discount = 0;

                    $row->product_type = 1;
                    $row->kdv_percentage = '18.0';
                    $row->wanted_delivery_date = $row->wanted_date;

                    $tempTotal = 0;
                    $tempVal = str_replace(',', '.', $row->price);
                    $firstPrice = $firstPrice + floatval($tempVal);

                    if ($row->billing_type == 1 && ($row->userBilling == 1 || $row->billing_send == 1)) {
                        $row->name = $row->billing_name . ' ' . $row->billing_surname;
                        $row->sender_name = $row->billing_name;
                        $row->sender_surname = $row->billing_surname;
                        $row->bigCity = $row->city;
                        $row->smallCity = $row->small_city;
                        $row->address2 = $row->billing_address;
                        $row->tax_office = $row->tc;
                    } else if ($row->billing_type == 1) {
                        $row->name = $row->billing_name . ' ' . $row->billing_surname;
                        $districtTemp = 'Sarıyer-Emirgan';
                        $row->bigCity = explode("-", $districtTemp)[0];
                        $row->smallCity = explode("-", $districtTemp)[1];
                        $row->address2 = $row->city;
                    } else {
                        $row->name = $row->company;
                        $row->bigCity = $row->billing_address;
                        $row->small_city = $row->tax_office;
                        $row->address2 = "";
                        $row->tax_office = $row->tax_office . "-" . $row->tax_no;
                    }

                    $dateTemp = new Carbon($row->wanted_delivery_date);

                    $row->wantedDate = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year;

                    $dateTemp = new Carbon($row->created_at);

                    $row->created_at = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year . ' ' . sprintf("%02d", $dateTemp->hour) . ':' . sprintf("%02d", $dateTemp->minute);

                    $row->id = sprintf("%03d", $row->id);

                    $row->discount = 0;
                    $row->discountVal = 0;
                    $row->sumPartial = $row->price;

                    $priceWithDiscount = $row->price;
                    $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);
                    $totalPartial = $totalPartial + $priceWithDiscount;

                    $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);

                    $row->discountValue = number_format($row->discountValue, 2);
                    parse_str($row->discountValue);
                    $row->discountValue = str_replace('.', ',', $row->discountValue);

                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                    $priceWithDiscount = number_format($priceWithDiscount, 2, '.', '');

                    $tempTotal = $priceWithDiscount;
                    parse_str($priceWithDiscount);
                    $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                    $row->sumTotal = $priceWithDiscount;
                    $total_discount = $row->discountVal;
                    $flower_discount = $row->discountVal;

                    $cikolatLine = '';
                    $cikolatTaxLine = '';
                    $cikolatPrice = 0;
                    $cikolatProductPrice = 0;
                    $cikolatProductTax = 0;


                    $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                    $word = $f->format(intval(str_replace(',', '.', $cikolatPrice) + str_replace(',', '.', $row->sumTotal)));

                    $wordExtra = $f->format(explode(".", number_format(floatval(str_replace(',', '.', $row->sumTotal)) + floatval(str_replace(',', '.', $cikolatPrice)), 2))[1]);
                    $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';
                    //dd($word . ' Lira ' . $wordExtra . ' Kuruş');

                    $row->price = str_replace(',', '.', $row->price);
                    $row->discountVal = str_replace(',', '.', $row->discountVal);
                    $row->discountValue = str_replace(',', '.', $row->discountValue);
                    if ($row->discountValue == '0.0') {
                        $row->discountValue = '';
                    }
                    $row->sumPartial = str_replace(',', '.', $row->sumPartial);
                    $row->sumTotal = str_replace(',', '.', $row->sumTotal);
                    $tempPaymentType = "";
                    $tempPaymentTool = "";
                    if ($row->payment_type == "POS") {
                        $row->payment_type_string = "SANAL POS";
                        $row->payment_tool_string = "KREDIKARTI/BANKAKARTI";
                        $tempPaymentType = "SANAL POS";
                        $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
                    } else {
                        $row->payment_type_string = "EFT/HAVALE";
                        $row->payment_tool_string = "EFT/HAVALE";
                        $tempPaymentType = "EFT/HAVALE";
                        $tempPaymentTool = "EFT/HAVALE";
                    }

                    if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {


                        if ($row->tc == '' || $row->tc == null)
                            $row->tc = '11111111111';
                        else {
                            $row->bigCity = $row->small_city;
                        }

                        //if ($row->sender_surname == '' || $row->sender_surname == null)
                        //    $row->sender_surname = 'Yılmaz';

                    }

                    unset($row->payment_type);
                    unset($row->device);
                    unset($row->delivery_locations_id);
                    unset($row->billing_send);
                    unset($row->sender_name);
                    unset($row->sender_surname);
                    unset($row->customer_contact_id);
                    unset($row->send_billing);
                    unset($row->address2);
                    unset($row->wantedDate);
                    unset($row->billing_id);
                    unset($row->sender_mobile);
                    unset($row->city_id);
                    unset($row->name);
                    unset($row->userBilling);
                    unset($row->smallCity);
                    unset($row->bigCity);
                    unset($row->billing_number);
                    unset($row->contact_name);
                    unset($row->contact_surname);
                    unset($row->continent_id);
                    unset($row->customer);
                    unset($row->customer_mobile);
                    unset($row->customer_name);
                    unset($row->customer_surname);
                    unset($row->district);
                    unset($row->customer_surname);
                    $row->status = $row->delivery_status;
                    $row->sender_email = $row->email;
                    unset($row->delivery_status);
                    unset($row->email);

                    unset($row->flower_desc);
                    $row->products = $row->flower_name;
                    unset($row->flower_name);

                    unset($row->note);
                    $row->created_at = $row->payment_date;
                    unset($row->operation_name);

                    unset($row->payment_mail);
                    unset($row->picker);
                    unset($row->receiver_address);
                    unset($row->wanted_date);
                    unset($row->wanted_delivery_limit);
                    unset($row->payment_date);

                    $tempCreatedAt = $row->created_at;
                    $tempCreatedAtDate = explode(" ", $tempCreatedAt)[0];

                    //$row->created_at = explode(".",  $tempCreatedAtDate)[2] . '-' . explode(".",  $tempCreatedAtDate)[1] . '-' . explode(".",  $tempCreatedAtDate)[0] . ' ' . explode(" ",  $tempCreatedAt)[1] . ':00';

                    $row->discount_value = $row->discountVal;
                    unset($row->discountVal);

                    $row->tax = $row->discountValue;
                    unset($row->discountValue);


                    if ($row->billing_type == "1") {
                        unset($row->company);
                        unset($row->tax_office);
                        unset($row->tax_no);
                    } else if ($row->billing_type == "2") {

                        $tempTaxOffice = explode("-", $row->tax_office);

                        if (count($tempTaxOffice) > 1) {
                            $row->tax_office = $tempTaxOffice[0];
                        }

                        //unset($row->small_city);
                        unset($row->tc);
                    }

                    array_push($list, $row);
                }

            }

            $hbDateEnd = Carbon::now();
            $hbDateEnd->hour(12);
            $hbDateEnd->minute(12);
            $hbDateEnd->second(12);
            $hbDateEnd->year(2019);
            $hbDateEnd->month(02);
            $hbDateEnd->day(14);

            if( $dateStart < $hbDateEnd && $dateEnd > $hbDateEnd ){
                $hbSales = DB::table('hb_billing_sales')->select('billing_address','billing_name','billing_surname','billing_type','city','created_at','delivery_date','discount','discount_value','id','kdv_percentage'
                    ,'payment_tool_string','payment_type_string','price','product_type','products','sales_id','sender_email','small_city','status','sumPartial','sumTotal','tax','tc','totalWithWord','wanted_delivery_date')->get();

                $hbSales2 = DB::table('hb_billing_sales_2')->select('billing_address','billing_name','billing_surname','billing_type','city','company','created_at','delivery_date','discount','discount_value','id','kdv_percentage'
                    ,'payment_tool_string','payment_type_string','price','product_type','products','sales_id','sender_email','small_city','status','sumPartial','sumTotal','tax','tax_no','tax_office','totalWithWord','wanted_delivery_date')->get();


                foreach ( $hbSales as $hbSale ){
                    $hbSale->discount = 0;

                    array_push($list, $hbSale);
                }

                foreach ( $hbSales2 as $hbSale ){
                    $hbSale->discount = 0;

                    array_push($list, $hbSale);
                }

                $totalCount = $totalCount + (int)count($hbSales) + (int)count($hbSales2);

                //dd($list);
            }

            //dd($list);
            return response()->json(["data" => $list, 'total' => $totalCount], 200);

        }

    }

    public function fixOrdering()
    {
        $flowerList = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('product_city.city_id', '=', '1')
            ->where('product_city.activation_status_id', '=', 1)
            ->where('product_city.active', '=', 1)
            ->where('products.company_product', '=', 0)
            ->select('product_city.coming_soon', 'product_city.limit_statu', 'products.id', 'products.name', 'products.price', 'product_city.landing_page_order')
            ->orderBy('product_city.landing_page_order')
            ->get();

        $lastOrder = DB::table('product_city')->orderBy('product_city.landing_page_order', 'desc')->take(1)->select('landing_page_order')->get()[0]->landing_page_order;

        foreach ($flowerList as $flower) {
            $flower->landing = DB::table('landing_with_promo')->where('product_id', $flower->id)->where('city_id', '1')->count();
        }

        $new_array = array_filter($flowerList, function($obj){
            if( $obj->landing == 0 ){
                return true;
            }
        });

        $count = 0;
        foreach ( $new_array as $flow ){
            $count++;
            DB::table('product_city')->where('product_id', $flow->id)->where('city_id', 1)->update([
                'landing_page_order' => $lastOrder + $count
            ]);
        }


        $flowerList = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('product_city.city_id', '=', '2')
            ->where('product_city.activation_status_id', '=', 1)
            ->where('product_city.active', '=', 1)
            ->where('products.company_product', '=', 0)
            ->select('product_city.coming_soon', 'product_city.limit_statu', 'products.id', 'products.name', 'products.price', 'product_city.landing_page_order')
            ->orderBy('product_city.landing_page_order')
            ->get();

        $lastOrder = DB::table('product_city')->orderBy('product_city.landing_page_order', 'desc')->take(1)->select('landing_page_order')->get()[0]->landing_page_order;

        foreach ($flowerList as $flower) {
            $flower->landing = DB::table('landing_with_promo')->where('product_id', $flower->id)->where('city_id', '2')->count();
        }

        $new_array = array_filter($flowerList, function($obj){
            if( $obj->landing == 0 ){
                return true;
            }
        });

        $count = 0;
        foreach ( $new_array as $flow ){
            $count++;
            DB::table('product_city')->where('product_id', $flow->id)->where('city_id', 2)->update([
                'landing_page_order' => $lastOrder + $count
            ]);
        }
    }

    public function getFlowerListForAllCity()
    {
        //try {

        $flowerList = DB::table('shops')
            ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
            ->join('products', 'products_shops.products_id', '=', 'products.id')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->join('landing_products_times', 'products.id', '=', 'landing_products_times.product_id')
            //->join( DB::raw(' landing_products_times on products.id = landing_products_times.product_id and product_city.city_id = landing_products_times.city_id ') )
            ->where('shops.id', '=', 1)
            ->where('descriptions.lang_id', '=', 'tr')
            ->where('product_city.activation_status_id', '=', 1)
            ->where('product_city.active', '=', 1)
            ->whereRaw(' landing_products_times.city_id = product_city.city_id ')
            ->select('products.tag_id', 'products.product_type', 'product_city.coming_soon', 'product_city.limit_statu', 'products.id', 'products.name', 'products.price', 'products.image_name', 'products.background_color', 'products.second_background_color',
                'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre', 'products.company_product', 'product_city.city_id'
                , 'descriptions.how_to_detail', 'products.youtube_url', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description', 'product_city.avalibility_time'
                , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3', 'products.speciality',
                'landing_products_times.avalibility_time', 'landing_products_times.theDayAfter', 'landing_products_times.today', 'landing_products_times.tomorrow')
            ->orderBy('product_city.landing_page_order')
            ->get();

        //return $flowerList;
        for ($x = 0; $x < count($flowerList); $x++) {

            //$flowerList[$x]->istanbul = DB::table('product_city')->where('product_id', $flowerList[$x]->id )->where('city_id', 1 )->exists();
            //$flowerList[$x]->ankara = DB::table('product_city')->where('product_id', $flowerList[$x]->id )->where('city_id', 2 )->exists();

            if ($flowerList[$x]->city_id == 2) {
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

                if ($flowerList[$x]->today) {
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
            } else {

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

                if ($flowerList[$x]->today) {
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
        /*}
        catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }*/
    }

    public function landingTimes()
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

        foreach ($flowerList as $flower) {

            DB::table('landing_products_times')->insert([
                'product_id' => $flower->id,
                'city_id' => $flower->city_id,
                'avalibility_time' => $flower->avalibility_time,
                'theDayAfter' => $flower->theDayAfter,
                'today' => $flower->today,
                'tomorrow' => $flower->tomorrow
            ]);


        }


        //return $flowerList;
    }

    public function billingEntegration()
    {

        if (Request::get("key") == 'hx_s2fhJ=0fbKf23KgnKgy2u4wq5') {

            if (Request::get("date_start")) {
                $dateStart = Carbon::now();
                $dateStart->hour(00);
                $dateStart->minute(00);
                $dateStart->second(00);
                $dateStart->year(explode('-', Request::get("date_start"))[2]);
                $dateStart->month(explode('-', Request::get("date_start"))[1]);
                $dateStart->day(explode('-', Request::get("date_start"))[0]);
            } else {
                $dateStart = Carbon::now();
                $dateStart->hour(00);
                $dateStart->minute(00);
                $dateStart->second(00);
            }

            if (Request::get("date_end")) {
                $dateEnd = Carbon::now();
                $dateEnd->hour(23);
                $dateEnd->minute(59);
                $dateEnd->second(59);
                $dateEnd->year(explode('-', Request::get("date_end"))[2]);
                $dateEnd->month(explode('-', Request::get("date_end"))[1]);
                $dateEnd->day(explode('-', Request::get("date_end"))[0]);
            } else {
                $dateEnd = Carbon::now();
                $dateEnd->hour(23);
                $dateEnd->minute(59);
                $dateEnd->second(59);
            }

            if (Request::get("status")) {
                $statusString = ' deliveries.status = 3';
            } else {
                $statusString = ' deliveries.status != 1231';
            }

            if (Request::get("page_count")) {
                $tempPageCount = Request::get("page_count");
            } else {
                $tempPageCount = 50;
            }

            if (Request::get("page")) {
                $tempSkip = (Request::get("page") - 1) * $tempPageCount;
                if ($tempSkip < 0) {
                    $tempSkip = 0;
                }
            } else {
                $tempSkip = 0;
            }

            $queryString = ' deliveries.wanted_delivery_date > "' . $dateStart . '" and deliveries.wanted_delivery_date < "' . $dateEnd . '" and ' . $statusString;

            //dd($queryString);

            $list = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->whereRaw($queryString)
                ->where('sales.payment_methods', 'OK')
                ->where('sales.payment_type', '!=', 'KURUMSAL')
                ->skip($tempSkip)
                ->take($tempPageCount)
                ->orderBy('deliveries.delivery_date')
                ->select('sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling',
                    'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
                    'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'sales.sender_mobile', 'products.name as products', 'products.city_id', 'sales.sender_name', 'sales.sender_surname',
                    'sales.product_price as price', 'products.id', 'products.product_type', 'sales.customer_contact_id', 'sales.send_billing', 'deliveries.delivery_date', 'sales.payment_type', 'sales.sender_email', 'deliveries.status')
                ->get();

            $queryStringStudio = ' price != "" and price != "0" and studioBloom.payment_date > "' . $dateStart . '" and studioBloom.payment_date < "' . $dateEnd . '"';


            $countStudio = DB::table('studioBloom')
                ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
                ->whereRaw($queryStringStudio)
                ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                    , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                    , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile', 'studio_billings.billing_surname')->count();


            $totalCount = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->whereRaw($queryString)
                ->where('sales.payment_methods', 'OK')
                ->where('sales.payment_type', '!=', 'KURUMSAL')
                ->count();

            $totalCount = $countStudio + $totalCount;

            foreach ($list as $row) {

                $firstPrice = 0;
                $totalDiscount = 0;
                $totalPartial = 0;
                $totalKDV = 0;
                $total = 0;
                $total_discount = 0;
                $flower_discount = 0;

                if ($row->product_type == 2) {
                    $row->kdv_percentage = '8.0';
                } else {
                    $row->kdv_percentage = '18.0';
                }

                $tempTotal = 0;
                $tempVal = str_replace(',', '.', $row->price);
                $firstPrice = $firstPrice + floatval($tempVal);
                $discount = DB::table('marketing_acts_sales')
                    ->join('marketing_acts', 'marketing_acts_sales.marketing_acts_id', '=', 'marketing_acts.id')
                    ->where('sales_id', $row->sales_id)->get();

                if ($row->billing_type == 1 && ($row->userBilling == 1 || $row->billing_send == 1)) {
                    $row->name = $row->billing_name . ' ' . $row->billing_surname;
                    $row->sender_name = $row->billing_name;
                    $row->sender_surname = $row->billing_surname;
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
                    $row->address2 = $row->city;
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
                    $total_discount = $row->discountVal;
                    $flower_discount = $row->discountVal;

                } else {
                    $row->discount = $discount[0]->value;
                    //$total_discount = $row->discountValue;
                    //$flower_discount = $row->discountValue;

                    $priceWithDiscount = str_replace(',', '.', $row->price);
                    if ($discount[0]->type == 2) {

                        $row->discount_type = 'percentage';

                        $row->discountVal = floatval($priceWithDiscount) * (floatval($discount[0]->value)) / 100;
                        $row->discountVal = number_format($row->discountVal, 2);
                        $totalDiscount = $totalDiscount + $row->discountVal;
                        parse_str($row->discountVal);
                        $row->discountVal = str_replace('.', ',', $row->discountVal);

                        $priceWithDiscount = floatval($priceWithDiscount) * (100 - floatval($discount[0]->value)) / 100;
                        $tempPriceWithDiscount = $priceWithDiscount;
                        $total_discount = str_replace(',', '.', $row->discountVal);
                        $flower_discount = str_replace(',', '.', $row->discountVal);

                    } else {

                        $row->discount_type = 'coupon';

                        $row->discountVal = $priceWithDiscount;

                        if ($row->product_type == 2) {
                            $row->discountValue = floatval(floatval($priceWithDiscount) * 8 / 100);
                            $priceWithDiscount = floatval(floatval($priceWithDiscount) * 108 / 100) - floatval($discount[0]->value);
                        } else {
                            $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);
                            $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100) - floatval($discount[0]->value);
                        }

                        //$priceWithDiscount = floatval($priceWithDiscount) - floatval($discount[0]->value);
                        if ($priceWithDiscount <= 0) {
                            $priceWithDiscount = 0;
                            $flower_discount = 0;
                            $total_discount = 0;
                            $row->price = 0;
                        } else {

                            if ($row->product_type == 2) {
                                $row->products = 'Çikolata Bedeli (Hediye Çeki kullanılmıştır)';
                                $row->price = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 108 * 100, 2);
                                $row->discountValue = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 108 * 8, 2);
                            } else {
                                $row->products = 'Çiçek Bedeli (Hediye Çeki kullanılmıştır)';
                                $row->price = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 118 * 100, 2);
                                $row->discountValue = number_format(floatval(str_replace(',', '.', $priceWithDiscount)) / 118 * 18, 2);
                            }
                            $flower_discount = 0;
                            $total_discount = 0;
                            $row->discountVal = 0;

                            //$row->discountVal = floatval($discount[0]->value);
                            //$flower_discount = str_replace(',', '.', $row->discountVal);
                            //$total_discount = str_replace(',', '.', $row->discountVal);
                        }

                        $tempPriceWithDiscount = $priceWithDiscount;

                        $row->discountVal = number_format($row->discountVal, 2);
                        parse_str($row->discountVal);
                        $row->discountVal = str_replace('.', ',', $row->discountVal);
                        $row->discountVal = $row->discount;
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
                        if ($row->product_type == 2) {
                            $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 108 / 100);
                        } else {
                            $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 118 / 100);
                        }
                    }

                    $totalKDV = $totalKDV + $row->discountValue;
                    $row->discountValue = number_format($row->discountValue, 2);
                    parse_str($row->discountValue);
                    $row->discountValue = str_replace('.', ',', $row->discountValue);

                    //if( $row->product_type == 2 ){
                    //    $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 108 / 100);
                    //}
                    //else{
                    //    $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 118 / 100);
                    //}


                    $priceWithDiscount = number_format($priceWithDiscount, 2);

                    parse_str($priceWithDiscount);
                    $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                    $row->sumTotal = $priceWithDiscount;

                }

                $tempCikolat = AdminPanelController::getCikolatData($row->sales_id);

                if ($tempCikolat) {
                    $cikolatPrice = $tempCikolat->total_price;
                    $cikolatProductPrice = $tempCikolat->product_price;
                    $cikolatProductTax = $tempCikolat->tax;

                    if ($tempCikolat->discount > 0) {
                        $tempDiscountTextCrossSell = '<ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                İndirim
                            </ns4:AllowanceChargeReason>
                            <ns4:Amount currencyID="TRY">
                                ' . str_replace(',', '.', $tempCikolat->discount) . '
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                ' . str_replace(',', '.', $row->price) . '
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>';
                        $tempDiscountTextCrossSell = '';
                        $tempCikolat->name = 'Çikolata bedeli (Hediye çeki kullanılmıştır)';
                        $tempCikolatNumber = '1';
                        $tempCikolat->tax = number_format(floatval(str_replace(',', '.', $tempCikolat->total_price)) / 108 * 8, 2);
                        $tempCikolat->product_price = number_format(floatval(str_replace(',', '.', $tempCikolat->total_price)) / 108 * 100, 2);
                        $cikolatProductPrice = $tempCikolat->product_price;
                        $tempDiscountTextCrossSell = '';

                        $row->extra_urun_discount = (object)[
                            'discount_amount' => str_replace(',', '.', $tempCikolat->discount),
                            'base_amount' => $row->price
                        ];

                    } else {
                        $tempDiscountTextCrossSell = '';
                        $tempCikolatNumber = '2';
                    }

                    $row->extra_urun = (object)[
                        'id' => $tempCikolatNumber,
                        'price' => str_replace(',', '.', $tempCikolat->product_price),
                        'name' => $tempCikolat->name,
                        'item_identification' => $tempCikolat->id,
                        'tax' => str_replace(',', '.', $tempCikolat->tax),
                        'kdv_percentage' => 8.0
                    ];

                } else {
                    $cikolatLine = '';
                    $cikolatTaxLine = '';
                    $cikolatPrice = 0;
                    $cikolatProductPrice = 0;
                    $cikolatProductTax = 0;
                }

                $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                $word = $f->format(intval(str_replace(',', '.', $cikolatPrice) + str_replace(',', '.', $row->sumTotal)));

                $wordExtra = $f->format(explode(".", number_format(floatval(str_replace(',', '.', $row->sumTotal)) + floatval(str_replace(',', '.', $cikolatPrice)), 2))[1]);
                $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';
                //dd($word . ' Lira ' . $wordExtra . ' Kuruş');

                $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
                $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';

                $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
                $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';

                $row->price = str_replace(',', '.', $row->price);
                $row->discountVal = str_replace(',', '.', $row->discountVal);
                $row->discountValue = str_replace(',', '.', $row->discountValue);
                if ($row->discountValue == '0.0') {
                    $row->discountValue = '';
                }
                $row->sumPartial = str_replace(',', '.', $row->sumPartial);
                $row->sumTotal = str_replace(',', '.', $row->sumTotal);
                $tempPaymentType = "";
                $tempPaymentTool = "";
                if ($row->payment_type == "POS") {
                    $row->payment_type_string = "SANAL POS";
                    $row->payment_tool_string = "KREDIKARTI/BANKAKARTI";
                    $tempPaymentType = "SANAL POS";
                    $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
                } else {
                    $row->payment_type_string = "EFT/HAVALE";
                    $row->payment_tool_string = "EFT/HAVALE";
                    $tempPaymentType = "EFT/HAVALE";
                    $tempPaymentTool = "EFT/HAVALE";
                }

                if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {


                    if ($row->tc == '' || $row->tc == null)
                        $row->tc = '11111111111';
                    else {
                        $row->bigCity = $row->small_city;
                    }

                    if ($row->sender_surname == '' || $row->sender_surname == null)
                        $row->sender_surname = 'Yılmaz';

                } else {

                    if ($row->payment_type == 'KURUMSAL') {
                        //dd($row->customer_contact_id);
                        $tempCompanyInfo = DB::table('customer_contacts')
                            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                            ->join('company_user_info', 'customers.user_id', '=', 'company_user_info.user_id')
                            ->where('customer_contacts.id', $row->customer_contact_id)
                            ->select('company_name', 'tax_no', 'tax_office', 'billing_address')
                            ->get()[0];

                        $row->tax_no = $tempCompanyInfo->tax_no;
                        $row->company = $tempCompanyInfo->company_name;
                        $row->billing_address = $tempCompanyInfo->billing_address;
                        $row->tax_office = $tempCompanyInfo->tax_office;
                    }

                }

                unset($row->payment_type);
                unset($row->device);
                unset($row->delivery_locations_id);
                unset($row->billing_send);
                unset($row->sender_name);
                unset($row->sender_surname);
                unset($row->customer_contact_id);
                unset($row->send_billing);
                unset($row->address2);
                unset($row->wantedDate);
                unset($row->billing_id);
                unset($row->sender_mobile);
                unset($row->city_id);
                unset($row->name);
                unset($row->userBilling);
                unset($row->smallCity);
                unset($row->bigCity);

                $tempCreatedAt = $row->created_at;
                $tempCreatedAtDate = explode(" ", $tempCreatedAt)[0];

                $row->created_at = explode(".", $tempCreatedAtDate)[2] . '-' . explode(".", $tempCreatedAtDate)[1] . '-' . explode(".", $tempCreatedAtDate)[0] . ' ' . explode(" ", $tempCreatedAt)[1] . ':00';

                $row->discount_value = $row->discountVal;
                unset($row->discountVal);

                $row->tax = $row->discountValue;
                unset($row->discountValue);


                if ($row->billing_type == "1") {
                    unset($row->company);
                    unset($row->tax_office);
                    unset($row->tax_no);
                } else if ($row->billing_type == "2") {

                    $tempTaxOffice = explode("-", $row->tax_office);

                    if (count($tempTaxOffice) > 1) {
                        $row->tax_office = $tempTaxOffice[0];
                    }

                    //unset($row->small_city);
                    unset($row->tc);
                }

            }

            if ($tempPageCount > count($list)) {
                $studioBloomCount = $tempPageCount - count($list);

                $listStudio = DB::table('studioBloom')
                    ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
                    ->whereRaw($queryStringStudio)
                    ->take($studioBloomCount)
                    ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                        , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                        , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile', 'studio_billings.billing_surname')->get();

                foreach ($listStudio as $row) {

                    $row->sales_id = substr($row->id, 0, 6);

                    $firstPrice = 0;
                    $totalDiscount = 0;
                    $totalPartial = 0;
                    $totalKDV = 0;
                    $total = 0;
                    $total_discount = 0;
                    $flower_discount = 0;

                    $row->product_type = 1;
                    $row->kdv_percentage = '18.0';
                    $row->wanted_delivery_date = $row->wanted_date;

                    $tempTotal = 0;
                    $tempVal = str_replace(',', '.', $row->price);
                    $firstPrice = $firstPrice + floatval($tempVal);

                    if ($row->billing_type == 1 && ($row->userBilling == 1 || $row->billing_send == 1)) {
                        $row->name = $row->billing_name . ' ' . $row->billing_surname;
                        $row->sender_name = $row->billing_name;
                        $row->sender_surname = $row->billing_surname;
                        $row->bigCity = $row->city;
                        $row->smallCity = $row->small_city;
                        $row->address2 = $row->billing_address;
                        $row->tax_office = $row->tc;
                    } else if ($row->billing_type == 1) {
                        $row->name = $row->billing_name . ' ' . $row->billing_surname;
                        $districtTemp = 'Sarıyer-Emirgan';
                        $row->bigCity = explode("-", $districtTemp)[0];
                        $row->smallCity = explode("-", $districtTemp)[1];
                        $row->address2 = $row->city;
                    } else {
                        $row->name = $row->company;
                        $row->bigCity = $row->billing_address;
                        $row->small_city = $row->tax_office;
                        $row->address2 = "";
                        $row->tax_office = $row->tax_office . "-" . $row->tax_no;
                    }

                    $dateTemp = new Carbon($row->wanted_delivery_date);

                    $row->wantedDate = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year;

                    $dateTemp = new Carbon($row->created_at);

                    $row->created_at = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year . ' ' . sprintf("%02d", $dateTemp->hour) . ':' . sprintf("%02d", $dateTemp->minute);

                    $row->id = sprintf("%03d", $row->id);

                    $row->discount = 0;
                    $row->discountVal = 0;
                    $row->sumPartial = $row->price;

                    $priceWithDiscount = $row->price;
                    $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);
                    $totalPartial = $totalPartial + $priceWithDiscount;

                    $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);

                    $row->discountValue = number_format($row->discountValue, 2);
                    parse_str($row->discountValue);
                    $row->discountValue = str_replace('.', ',', $row->discountValue);

                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                    $priceWithDiscount = number_format($priceWithDiscount, 2, '.', '');

                    $tempTotal = $priceWithDiscount;
                    parse_str($priceWithDiscount);
                    $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                    $row->sumTotal = $priceWithDiscount;
                    $total_discount = $row->discountVal;
                    $flower_discount = $row->discountVal;

                    $cikolatLine = '';
                    $cikolatTaxLine = '';
                    $cikolatPrice = 0;
                    $cikolatProductPrice = 0;
                    $cikolatProductTax = 0;


                    $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                    $word = $f->format(intval(str_replace(',', '.', $cikolatPrice) + str_replace(',', '.', $row->sumTotal)));

                    $wordExtra = $f->format(explode(".", number_format(floatval(str_replace(',', '.', $row->sumTotal)) + floatval(str_replace(',', '.', $cikolatPrice)), 2))[1]);
                    $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';
                    //dd($word . ' Lira ' . $wordExtra . ' Kuruş');

                    $row->price = str_replace(',', '.', $row->price);
                    $row->discountVal = str_replace(',', '.', $row->discountVal);
                    $row->discountValue = str_replace(',', '.', $row->discountValue);
                    if ($row->discountValue == '0.0') {
                        $row->discountValue = '';
                    }
                    $row->sumPartial = str_replace(',', '.', $row->sumPartial);
                    $row->sumTotal = str_replace(',', '.', $row->sumTotal);
                    $tempPaymentType = "";
                    $tempPaymentTool = "";
                    if ($row->payment_type == "POS") {
                        $row->payment_type_string = "SANAL POS";
                        $row->payment_tool_string = "KREDIKARTI/BANKAKARTI";
                        $tempPaymentType = "SANAL POS";
                        $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
                    } else {
                        $row->payment_type_string = "EFT/HAVALE";
                        $row->payment_tool_string = "EFT/HAVALE";
                        $tempPaymentType = "EFT/HAVALE";
                        $tempPaymentTool = "EFT/HAVALE";
                    }

                    if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {


                        if ($row->tc == '' || $row->tc == null)
                            $row->tc = '11111111111';
                        else {
                            $row->bigCity = $row->small_city;
                        }

                        //if ($row->sender_surname == '' || $row->sender_surname == null)
                        //    $row->sender_surname = 'Yılmaz';

                    }

                    unset($row->payment_type);
                    unset($row->device);
                    unset($row->delivery_locations_id);
                    unset($row->billing_send);
                    unset($row->sender_name);
                    unset($row->sender_surname);
                    unset($row->customer_contact_id);
                    unset($row->send_billing);
                    unset($row->address2);
                    unset($row->wantedDate);
                    unset($row->billing_id);
                    unset($row->sender_mobile);
                    unset($row->city_id);
                    unset($row->name);
                    unset($row->userBilling);
                    unset($row->smallCity);
                    unset($row->bigCity);
                    unset($row->billing_number);
                    unset($row->contact_name);
                    unset($row->contact_surname);
                    unset($row->continent_id);
                    unset($row->customer);
                    unset($row->customer_mobile);
                    unset($row->customer_name);
                    unset($row->customer_surname);
                    unset($row->district);
                    unset($row->customer_surname);
                    $row->status = $row->delivery_status;
                    $row->sender_email = $row->email;
                    unset($row->delivery_status);
                    unset($row->email);

                    unset($row->flower_desc);
                    $row->products = $row->flower_name;
                    unset($row->flower_name);

                    unset($row->note);
                    $row->created_at = $row->payment_date;
                    unset($row->operation_name);

                    unset($row->payment_mail);
                    unset($row->picker);
                    unset($row->receiver_address);
                    unset($row->wanted_date);
                    unset($row->wanted_delivery_limit);
                    unset($row->payment_date);

                    $tempCreatedAt = $row->created_at;
                    $tempCreatedAtDate = explode(" ", $tempCreatedAt)[0];

                    //$row->created_at = explode(".",  $tempCreatedAtDate)[2] . '-' . explode(".",  $tempCreatedAtDate)[1] . '-' . explode(".",  $tempCreatedAtDate)[0] . ' ' . explode(" ",  $tempCreatedAt)[1] . ':00';

                    $row->discount_value = $row->discountVal;
                    unset($row->discountVal);

                    $row->tax = $row->discountValue;
                    unset($row->discountValue);


                    if ($row->billing_type == "1") {
                        unset($row->company);
                        unset($row->tax_office);
                        unset($row->tax_no);
                    } else if ($row->billing_type == "2") {

                        $tempTaxOffice = explode("-", $row->tax_office);

                        if (count($tempTaxOffice) > 1) {
                            $row->tax_office = $tempTaxOffice[0];
                        }

                        //unset($row->small_city);
                        unset($row->tc);
                    }

                    array_push($list, $row);
                }

            }

            //dd($list);
            return response()->json(["data" => $list, 'total' => $totalCount], 200);

        }

    }

    public function getFlowerListForAllCityTest()
    {
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

            $tempNowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            //dd($tempNowTag);

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
                if ($tempNowTagAnk[0]->start_hour != "18") {
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
            } else {
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
                //dd(explode(":", $tempNowTag[0]->start_hour)[0]);
                $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                if ($now->hour != "18") {
                    $now->addHours(1);
                } else {
                    dd($tempNowTag[0]);
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
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function generateANDRelatedProducts()
    {
        $tempAnkProducts = DB::table('products')
            ->join('tags', 'products.tag_id', '=', 'tags.id')
            ->where('products.city_id', 2)
            ->where('products.id', '!=', '210')
            ->where('products.id', '!=', '268')
            ->where('products.id', '!=', '270')
            ->where('products.id', '!=', '275')
            ->where('tags.lang_id', 'tr')
            ->select('products.id', 'products.name')
            ->get();

        foreach ($tempAnkProducts as $product) {

            $tempIstId = DB::table('products')
                ->join('tags', 'products.tag_id', '=', 'tags.id')
                ->where('tags.lang_id', 'tr')
                ->where('name', $product->name)->where('city_id', 1)
                ->select('products.url_parametre', 'products.id', 'tags.tag_ceo', 'products.name')->get()[0];

            $tempAnkRelated = DB::table('related_products')->where('main_product', $product->id)->get();

            foreach ($tempAnkRelated as $related) {
                DB::table('related_products')->insert([
                    'main_product' => $tempIstId->id,
                    'related_product' => $related->related_product,
                    'order' => $related->order,
                    'city_id' => 2
                ]);
            }

        }

    }

    public function modifeOldSalesForAnkara()
    {
        $tempAnkProducts = DB::table('products')
            ->where('products.city_id', 2)
            ->where('products.id', '!=', '210')
            ->where('products.id', '!=', '268')
            ->where('products.id', '!=', '270')
            ->where('products.id', '!=', '275')
            ->select('products.id', 'products.name')
            ->get();

        foreach ($tempAnkProducts as $product) {

            $tempIstId = DB::table('products')
                ->where('name', $product->name)->where('city_id', 1)
                ->select('products.id')->get()[0];

            //dd($product);
            DB::table('sales_products')->where('products_id', $product->id)->update([
                'products_id' => $tempIstId->id
            ]);
        }
    }

    public function generateBNFCONF()
    {
        $tempAnkProducts = DB::table('products')
            ->join('tags', 'products.tag_id', '=', 'tags.id')
            ->where('products.city_id', 2)
            ->where('products.id', '!=', '210')
            ->where('products.id', '!=', '268')
            ->where('products.id', '!=', '270')
            ->where('products.id', '!=', '275')
            ->where('tags.lang_id', 'tr')
            ->select('products.url_parametre', 'products.id', 'tags.tag_ceo', 'products.name')
            ->get();

        $tempSting = '';

        foreach ($tempAnkProducts as $product) {

            $tempIstId = DB::table('products')
                ->join('tags', 'products.tag_id', '=', 'tags.id')
                ->where('tags.lang_id', 'tr')
                ->where('name', $product->name)->where('city_id', 1)
                ->select('products.url_parametre', 'products.id', 'tags.tag_ceo', 'products.name')->get()[0];

            //if($tempIstId == 0){
            //    dd($product);
            //}


            $tempSting = $tempSting . 'RedirectMatch permanent ^/' . $product->tag_ceo . '/' . $product->url_parametre . '-' . $product->id .
                ' https://bloomandfresh.com/' . $tempIstId->tag_ceo . '/' . $tempIstId->url_parametre . '-' . $tempIstId->id . '
                ';
        }

        dd($tempSting);
    }

    public function updateAnkaraProductCity()
    {
        $ankaraProducts = DB::table('products')->where('city_id', 2)->where('id', '!=', '210')->where('id', '!=', '268')->where('id', '!=', '270')->get();

        foreach ($ankaraProducts as $product) {
            $tempAnkaraData = DB::table('products')->where('name', $product->name)->where('city_id', 2)->get()[0];
            if (DB::table('products')->where('name', $product->name)->where('city_id', 1)->count() == 0) {
                dd($product->name);
            }
            $tempIstId = DB::table('products')->where('name', $product->name)->where('city_id', 1)->get()[0]->id;

            DB::table('product_city')->where('city_id', 2)->where('product_id', $tempIstId)->update([
                'active' => 1,
                'landing_page_order' => $tempAnkaraData->landing_page_order,
                'activation_status_id' => $tempAnkaraData->activation_status_id,
                'limit_statu' => $tempAnkaraData->limit_statu,
                'coming_soon' => $tempAnkaraData->coming_soon,
                'avalibility_time' => $tempAnkaraData->avalibility_time,
                'avalibility_time_end' => $tempAnkaraData->avalibility_time_end,
            ]);

        }
    }

    public function initiateProductCity()
    {
        $allProduct = DB::table('products')->where('id', '270')->get();
        //dd($allProduct);
        //DB::table('product_city')->delete();

        foreach ($allProduct as $product) {
            DB::table('product_city')->insert([
                'product_id' => $product->id,
                'active' => 1,
                'city_id' => 2,
                'landing_page_order' => $product->landing_page_order,
                'activation_status_id' => $product->activation_status_id,
                'limit_statu' => $product->limit_statu,
                'coming_soon' => $product->coming_soon,
                'avalibility_time' => $product->avalibility_time,
                'avalibility_time_end' => $product->avalibility_time_end
            ]);

            DB::table('product_city')->insert([
                'product_id' => $product->id,
                'active' => 0,
                'city_id' => 1,
                'landing_page_order' => $product->landing_page_order,
                'activation_status_id' => $product->activation_status_id,
                'limit_statu' => $product->limit_statu,
                'coming_soon' => $product->coming_soon,
                'avalibility_time' => $product->avalibility_time,
                'avalibility_time_end' => $product->avalibility_time_end
            ]);
        }

    }

    public function anyData()
    {
        //$arrStart = explode("/", Request::input('start_date'));
        //$arrEnd = explode("/", Request::input('end_date'));
        //$arrStart = str_replace("/","-",str_replace("/","-",Request::input('start_date')));
        //$arrEnd = str_replace("/","-",str_replace("/","-",Request::input('end_date')));
        $tempArray = Request::all();
        $tempOrder = 'customers.created_at';
        $tempSkipValue = $tempArray['start'];
        $tempTakeValue = $tempArray['length'];
        if ($tempArray['order'][0]['column']) {
            //dd($tempArray['columns'][$tempArray['order'][0]['column']]['data']);
            //'created_at', n
            //'updated_at', n
            //'name', name: '
            //'email', name:
            //'mobile', name:
            //'salesNumber',
            //'user_id', name
            //'status', name:
            //'contactNumber'
            $tempOrderWay = '';
            if ($tempArray['order'][0]['dir'] != 'asc') {
                $tempOrderWay = 'DESC';
            }

            $tempOrder = $tempArray['columns'][$tempArray['order'][0]['column']]['data'];
            if ($tempOrder == 'created_at') {
                $tempOrder = ' customers.created_at ';
            } else if ($tempOrder == 'updated_at') {
                $tempOrder = ' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) ';
            } else if ($tempOrder == 'name') {
                $tempOrder = ' customers.name ';
            } else if ($tempOrder == 'email') {
                $tempOrder = ' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) ';
            } else if ($tempOrder == 'mobile') {
                $tempOrder = ' customers.mobile ';
            } else if ($tempOrder == 'salesNumber') {
                $tempOrder = '(select count(*) from sales inner join deliveries on sales.id = deliveries.sales_id inner join customer_contacts on sales.customer_contact_id = customer_contacts.id where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK") ';
            } else if ($tempOrder == 'user_id') {
                $tempOrder = ' customers.user_id ';
            } else if ($tempOrder == 'status') {
                $tempOrder = ' (select status from users where users.id = customers.user_id) ';
            } else if ($tempOrder == 'contactNumber') {
                $tempOrder = ' (select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) ';
            }
            $tempOrder = $tempOrder . ' ' . $tempOrderWay;
        }

        $arrStart = '2014-01-01';
        $arrEnd = '2020-01-01';
        $arrStartUpdate = '2014-01-01';
        $arrEndUpdate = '2020-01-01';
        if (Request::input('start_date')) {
            $arrStart = Request::input('start_date');
        }
        if (Request::input('end_date')) {
            $arrEnd = Request::input('end_date');
        }
        if (Request::input('start_date_update')) {
            $arrStartUpdate = Request::input('start_date_update');
        }
        if (Request::input('end_date_update')) {
            $arrEndUpdate = Request::input('end_date_update');
        }
        $tempQuery = ' 1 = 1 ';
        if ($tempArray['search']['value']) {
            $tempQuery = '(CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) like "%' . $tempArray['search']['value'] . '%" ' .
                ' or (select status from users where users.id = customers.user_id) like "%' . $tempArray['search']['value'] . '%" ' .
                ' or (select status from users where users.id = customers.user_id) like "%' . $tempArray['search']['value'] . '%" ' .
                ' or customers.name like "%' . $tempArray['search']['value'] . '%"' .
                ' or customers.surname like "%' . $tempArray['search']['value'] . '%"' .
                ' or customers.user_id like "%' . $tempArray['search']['value'] . '%"' .
                ' or customers.id like "%' . $tempArray['search']['value'] . '%"';
        }
        //$start = Carbon::create($arrStart[2], $arrStart[0], $arrStart[1], 0, 0, 0);
        //$end = Carbon::create($arrEnd[2], $arrEnd[0], $arrEnd[1], 23, 59, 59);
        $customers = Customer::orderBy('created_at', 'DESC')
            ->select('customers.name', 'customers.surname', 'customers.created_at', 'customers.user_id', 'customers.mobile', 'customers.id',
                DB::raw('(select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) as contactNumber'),
                DB::raw('(select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) as salesNumber'),
                DB::raw('(select status from users where users.id = customers.user_id) as status'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) as email'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) as updated_at')
            )
            ->where('created_at', '>', $arrStart)
            ->where('created_at', '<', $arrEnd)
            ->whereRaw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END )  > "' . $arrStartUpdate . '"')
            ->whereRaw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END )  < "' . $arrEndUpdate . '"')
            ->whereRaw($tempQuery)
            ->orderByRaw($tempOrder)
            ->get();
        //$customers = DB::table('customers')->get();
        $tempArray = new \Illuminate\Database\Eloquent\Collection;
        $anonimNumber = Customer::where('user_id', '=', null)
            ->where('created_at', '>', $arrStart)->where('created_at', '<', $arrEnd)
            ->where('created_at', '>', $arrStartUpdate)->where('created_at', '<', $arrEndUpdate)
            ->whereRaw($tempQuery)
            ->count();
        $userNumber = User::join('customers', 'users.id', '=', 'customers.user_id')
            ->where('users.created_at', '>', $arrStart)
            ->where('users.created_at', '<', $arrEnd)
            ->where('users.updated_at', '>', $arrStartUpdate)
            ->where('users.updated_at', '<', $arrEndUpdate)
            ->whereRaw($tempQuery)
            ->count();
        $totalNumber = $userNumber + $anonimNumber;
        //$tempArray = $customers;
        //dd($tempArray[1]->attributes);
        foreach ($customers as $key => $value) {
            $dateTemp = new Carbon($value->created_at);
            $dateTempLast = new Carbon($value->updated_at);
            if ($value->status == '' && $value->user_id) {
                $value->status = 'Login';
            }
            $tempArray->push([
                'created_at' => $dateTemp->format('Y-m-d H:i:s'),
                'updated_at' => $dateTempLast->format('Y-m-d H:i:s'),
                'name' => $value->name . ' ' . $value->surname,
                'email' => $value->email,
                'mobile' => $value->mobile,
                'salesNumber' => $value->salesNumber,
                'user_id' => $value->user_id,
                'status' => $value->status,
                'contactNumber' => $value->contactNumber,
                'DT_RowId' => $value->id,
                'anonimNumber' => $anonimNumber,
                'userNumber' => $userNumber,
                'totalNumber' => $totalNumber
            ]);
        }
        return Datatables::of($tempArray)->make(true);
    }

    public function testAdminPanel()
    {
        $reminderList = DB::table('reminders')
            ->join('customers', 'reminders.customers_id', '=', 'customers.id')
            ->select('reminders.created_at', 'reminders.name as receiver_name', 'reminders.description', 'reminders.reminder_day', 'reminders.reminder_month', 'customers.name', 'customers.surname')
            ->orderBy('reminders.created_at', 'DESC')
            ->get();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($reminderList as $billing) {
            $now = Carbon::now();
            $now->month($billing->reminder_month);
            $now->day($billing->reminder_day);
            $billing->time = $now->formatLocalized('%d %B');
        }
        $queryParams = [];
        return view('admin.newPanel', compact('reminderList', 'queryParams'));
    }

    public function testCarbon()
    {
        $today = Carbon::now();
        $today = $today->subMonthsNoOverflow(1);
        dd($today);
    }

    public function googleMapOnway(\Illuminate\Http\Request $request)
    {
        //dd($request->all());
        $tempObject = $request->all();
        $tempIds = [];
        $tempOperationPerson = '';
        foreach ($tempObject as $key => $value) {
            if ($key != '_token' && explode('_', $key)[0] != 'status2') {
                array_push($tempIds, (object)['id' => explode('_', $key)[2], 'key' => explode('_', $value)]);
            } else if ($key != '_token' && explode('_', $key)[0] == 'status2') {
                $tempOperationPerson = explode('_', $key)[1];
            }
        }

        $tempOperationInfo = DB::table('operation_person')->where('id', $tempOperationPerson)->get()[0];
        //dd($tempIds);
        foreach ($tempIds as $id) {

            if (strlen($id->id) > 10) {
                DB::table('studioBloom')->where('id', '=', $id->id)->update([
                    'delivery_status' => 2,
                    'operation_name' => $tempOperationInfo->name
                ]);
            } else {
                $sales = DB::table('deliveries')
                    ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->where('sales.id', $id->id)
                    ->select('sales_products.products_id', 'customers.user_id as user_id', 'sales.sender_email as email',
                        'sales.sender_name as FNAME', 'sales.sender_surname as LNAME', 'sales.sum_total as PRICE',
                        'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME', 'deliveries.id as delivery_id'
                        , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD', 'deliveries.products as PRNAME', 'sales.lang_id')
                    ->get()[0];

                if (!$sales->email) {
                    $sales->email = User::where('id', $sales->user_id)->get()[0]->email;
                }

                $tempMailTemplateName = "v2_BNF_Siparis_Yola_Cikti";
                if ($sales->lang_id == 'en') {
                    $tempMailTemplateName = "siparis_yola_cikti_en";
                }

                $tempMailSubjectName = " Yola Çıkıyor!";
                if ($sales->lang_id == 'en') {
                    $tempMailSubjectName = " Has Just Left The Buillding";
                }

                \MandrillMail::messages()->sendTemplate($tempMailTemplateName, null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => 'Siparişiniz yola çıktı.',
                    'subject' => ucwords(strtolower($sales->FNAME)) . ', Bloom And Fresh - ' . $sales->PRNAME . $tempMailSubjectName,
                    'from_email' => 'siparis@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => $sales->email,
                            'type' => 'to'
                        )
                    ),
                    'merge' => true,
                    'merge_language' => 'mailchimp',
                    'global_merge_vars' => array(
                        array(
                            'name' => 'FNAME',
                            'content' => ucwords(strtolower($sales->FNAME)),
                        ), array(
                            'name' => 'LNAME',
                            'content' => ucwords(strtolower($sales->LNAME)),
                        ), array(
                            'name' => 'CNTCNAME',
                            'content' => ucwords(strtolower($sales->CNTCNAME)),
                        ), array(
                            'name' => 'CNTCLNAME',
                            'content' => ucwords(strtolower($sales->CNTCLNAME)),
                        ), array(
                            'name' => 'CNTTEL',
                            'content' => $sales->CNTTEL,
                        ), array(
                            'name' => 'CNTADD',
                            'content' => $sales->CNTADD,
                        ), array(
                            'name' => 'PRICE',
                            'content' => $sales->PRICE,
                        ), array(
                            'name' => 'PIMAGE',
                            'content' => DB::table('images')->where('type', 'main')->where('products_id', $sales->products_id)->get()[0]->image_url
                        ), array(
                            'name' => 'PRNAME',
                            'content' => $sales->PRNAME
                        )
                    )
                ));
                Delivery::where('id', '=', $sales->delivery_id)->update([
                    'status' => 2,
                    'operation_id' => $tempOperationInfo->id,
                    'operation_name' => $tempOperationInfo->name
                ]);
            }
        }

        return redirect('/admin/showDeliveriesOnMap');
    }

    public function findBestRoute(\Illuminate\Http\Request $request)
    {
        $today = Carbon::now();
        //$today->addDay(1);
        $today->startOfDay();

        $todayEnd = Carbon::now();
        //$todayEnd->addDay(1);
        $todayEnd->hour(10);


        $tempSaleList = [];
        $tempObject = $request->all();

        foreach ($tempObject as $key => $value) {
            if ($key != '_token' && $key != 'lastPoint') {
                array_push($tempSaleList, $key);
            }
        }
        if ($tempObject['lastPoint'] != 'BNF Dönüşlü') {
            $tempEndLocation = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->whereIn('sales.id', $tempSaleList)
                ->where('sales.id', '=', $tempObject['lastPoint'])
                ->select('sales.id', 'delivery_locations.district', 'sales.receiver_address', 'sales.geoAddress', 'sales.geoLocation')
                ->get()[0];
            $tempEndUrl = $tempEndLocation->geoLocation;
        } else {
            $tempEndUrl = "41.102617,29.052595999999994";
        }

        $tempLocations = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->whereIn('sales.id', $tempSaleList)
            ->where('sales.id', '!=', $tempObject['lastPoint'])
            //->where('deliveries.status', '!=', '3')
            //->where('deliveries.status', '!=', '4')
            //->where('deliveries.wanted_delivery_date', '>', $today)
            //->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->select('sales.id', 'delivery_locations.district', 'sales.receiver_address', 'sales.geoAddress', 'sales.geoLocation')
            //->limit(13)
            ->get();

        //$tempRequestUrl = '|41.081399,29.013959|41.065783,29.008692999999994|41.0792432,29.010581099999968|41.0735744,29.02813809999998|41.0355031,28.965780300000006';
        $tempRequestUrl = "";
        $tempKey = 1000;
        foreach ($tempLocations as $elementKey => $location) {
            //if( $tempObject['lastPoint'] == $location->id ){
            //    $tempKey = $elementKey;
            //    $tempEndUrl = $location->geoLocation;
            //    unset($tempLocations[$elementKey]);
            //}
            //else
            $tempRequestUrl = $tempRequestUrl . '|' . $location->geoLocation;
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://maps.googleapis.com/maps/api/directions/xml?origin=41.102617,29.052595999999994&destination=' . $tempEndUrl . '&waypoints=optimize:true' . $tempRequestUrl . '&key=AIzaSyBUP7E-W7GYbzWq9cA9XFPtn5Y9R3ViNNc');
        $tempRouteUrl = "/41.102617,29.052595999999994";
        //dd($response->xml());
        $xmlResponse = $response->xml()->route->waypoint_index;
        $array = (array)$xmlResponse;
        foreach ($array as $ordered) {
            $tempRouteUrl = $tempRouteUrl . "/" . $tempLocations[$ordered]->geoLocation;
        }
        //$tempRouteUrl = $tempRouteUrl . '/' . '41.102617,29.052595999999994';
        $tempRouteUrl = $tempRouteUrl . '/' . $tempEndUrl;
        $tempRouteUrl = 'https://www.google.com.tr/maps/dir' . $tempRouteUrl;
        return \Redirect::to($tempRouteUrl);
        dd($response->xml()->route->waypoint_index);
    }

    public function martTestPage()
    {
        return view('phpClient.test8Mart');
    }

    public function googleSearch()
    {
        return view('admin.googleMapDeliveries');
    }

    public function updateGoogleLocation()
    {
        DB::table('sales')->where('id', Request::input('id'))->update([
            'geoLocation' => Request::input('x') . ',' . Request::input('y'),
            'geoAddress' => Request::input('geoAddress'),
            'geoSearchName' => Request::input('geoSearchName')

        ]);
        return response()->json(["status" => Request::get('id')], 200);
    }

    public function getGMDeliveriesShowOnMap()
    {
        $today = Carbon::now();
        $today->startOfDay();

        $todayEnd = Carbon::now();
        $todayEnd->endOfDay();

        $tempLocations = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            //->where('deliveries.status', '!=', 3)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->select('sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'deliveries.products', 'sales.delivery_not', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit', 'sales.id', 'sales.geoAddress', 'sales.geoLocation', 'sales.geoSearchName',
                'delivery_locations.continent_id', 'delivery_locations.district', 'sales.receiver_address', 'sales.geoAddress', 'sales.geoLocation', 'sales.geoSearchName')
            ->get();

        foreach ($tempLocations as $key => $value) {
            $value->tempCount = $key + 1;
            $tempLocalLocation = explode(' ', $value->receiver_address);
            $foundLocalAdress = '';
            if ($key == 0)
                Mapper::map(41.0446351, 29.0578342, ['zoom' => 11, 'marker' => false]);

            if ($value->geoAddress) {
                Mapper::informationWindow(
                    explode(',', $value->geoLocation)[0],
                    explode(',', $value->geoLocation)[1],
                    str_replace("'", "", str_replace("'", "", trim(preg_replace('/\s+/', ' ', $value->receiver_address)))) . '<br/>' .
                    $value->geoSearchName . '<br/>' .
                    $value->geoAddress,
                    ['markers' => ['icon' => "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google_maps/" . ($key + 1) . ".svg"]]
                );
            }
        }

        $queryParams = (object)['deliveryHour' => "", 'continent_id' => ""];
        $queryParams->status_all = 'on';
        $queryParams->status_making = '';
        $queryParams->status_ready = '';
        $queryParams->status_delivering = '';
        $queryParams->status_delivered = '';
        $queryParams->status_cancel = '';
        $queryParams->operation_name = 'Hepsi';
        $queryParams->date = 'Bugün';

        $deliveryHourList = [];

        $operationList = DB::table('operation_person')->get();
        //array_push($operationList, (object)['name' => 'Hepsi']);

        //array_push($deliveryHourList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($deliveryHourList, (object)['information' => '09-13', 'status' => '09:00:00', 'active' => '1']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00', 'active' => '1']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00', 'active' => '1']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00', 'active' => '1']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00', 'active' => '1']);

        $continentList = [];

        array_push($continentList, (object)['information' => 'Avrupa', 'status' => 'Avrupa', 'active' => '1']);
        array_push($continentList, (object)['information' => 'Asya', 'status' => 'Asya', 'active' => '1']);
        array_push($continentList, (object)['information' => 'Oyaka', 'status' => 'Avrupa-2', 'active' => '1']);

        $operationList = DB::table('operation_person')->select('name', DB::raw(' (1) as active '))->get();
        //array_push($operationList, (object)['name' => 'Hepsi']);

        $operationListComplete = DB::table('operation_person')->get();

        return view('admin.testMap', compact('tempLocations', 'operationListComplete', 'operationList', 'queryParams', 'deliveryHourList', 'continentList', 'operationList'));
    }

    public function gmDeliveriesShowOnMap(\Illuminate\Http\Request $request)
    {
        //dd(Request::all());
        $tempSaleList = [];
        $tempObject = $request->all();

        $statusList = [];
        $tempObject['status_all'] = '';
        $tempObject['status_making'] = '';
        $tempObject['status_ready'] = '';
        $tempObject['status_delivering'] = '';
        $tempObject['status_delivered'] = '';
        $tempObject['status_cancel'] = '';

        if (Request::input('status_all') == "on") {
            $statusList = ['1', '2', '3', '4', '6'];
            $tempObject['status_all'] = 'on';
        }

        if (Request::input('status_making') == "on") {
            array_push($statusList, '1');
            $tempObject['status_making'] = 'on';
        }

        if (Request::input('status_ready') == "on") {
            array_push($statusList, '6');
            $tempObject['status_ready'] = 'on';
        }

        if (Request::input('status_delivering') == "on") {
            array_push($statusList, '2');
            $tempObject['status_delivering'] = 'on';
        }

        if (Request::input('status_delivered') == "on") {
            array_push($statusList, '3');
            $tempObject['status_delivered'] = 'on';
        }

        if (Request::input('status_cancel') == "on") {
            array_push($statusList, '4');
            $tempObject['status_cancel'] = 'on';
        }

        //dd(Request::input('date'));
        if (Request::input('date') == 'Dün') {
            $today = Carbon::now();
            $today->subDay(1);
            $today->startOfDay();

            $todayEnd = Carbon::now();
            $todayEnd->subDay(1);
            $todayEnd->endOfDay();
        } else if (Request::input('date') == 'Bugün') {
            $today = Carbon::now();
            $today->startOfDay();

            $todayEnd = Carbon::now();
            $todayEnd->endOfDay();
        } else if (Request::input('date') == 'Yarın') {
            $today = Carbon::now();
            $today->addDay(1);
            $today->startOfDay();

            $todayEnd = Carbon::now();
            $todayEnd->addDay(1);
            $todayEnd->endOfDay();
        }

        $QueryString = ' 1 = 1';
        //if(Request::input('deliveryHour'))
        //if(Request::input('deliveryHour') != 'Hepsi'){
        //    $QueryString = $QueryString . ' and hour(wanted_delivery_date) = ' . explode(':', Request::input('deliveryHour'))[0] ;
        //}
        //if(Request::input('continent_id'))
        //if(Request::input('continent_id') != 'Hepsi'){
        //    $QueryString = $QueryString . ' and continent_id = ' . " '" . Request::input('continent_id') . "' ";
        //}

        //if(Request::input('operation_name') != 'Hepsi'){
        //    $QueryString = $QueryString . ' and deliveries.operation_name = ' . " '" . Request::input('operation_name') . "' ";
        //}

        foreach ($tempObject as $key => $value) {
            if ($key != '_token' && $key != 'continent_id' && $key != 'operation_name' && $key != 'deliveryHour' && $key != 'date' && $key != 'status_making' && $key != 'status_ready' && $key != 'status_delivering' && $key != 'status_delivered' && $key != 'status_cancel' && $key != 'status_all'){
                array_push($tempSaleList, explode('_', $key)[1]);
            }
        }
        //dd(Request::input('continent_id'));
        $statusListHour = [];
        $deliveryHourList = [];
        array_push($deliveryHourList, (object)['information' => '09-13', 'status' => '09:00:00', 'active' => '0']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00', 'active' => '0']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00', 'active' => '0']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00', 'active' => '0']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00', 'active' => '0']);

        if (Request::input('deliveryHour'))
            foreach (Request::input('deliveryHour') as $tempVal) {
                $todayTemp = $today;
                $todayTemp->second(0);
                $todayTemp->minute(0);
                $todayTemp->hour(explode(':', $tempVal)[0]);

                if ($todayTemp->hour == 9)
                    $deliveryHourList[0]->active = 1;
                if ($todayTemp->hour == 11)
                    $deliveryHourList[1]->active = 1;
                if ($todayTemp->hour == 13)
                    $deliveryHourList[2]->active = 1;
                if ($todayTemp->hour == 18)
                    $deliveryHourList[3]->active = 1;
                if ($todayTemp->hour == 12)
                    $deliveryHourList[4]->active = 1;

                array_push($statusListHour, $todayTemp->toDateTimeString());
            }

        if (Request::input('operation_name')) {
            $tempOperationList = Request::input('operation_name');
        } else {
            $tempOperationList = [
                'Şuayp', 'Mesaut', 'Turgay', 'Ferdi', 'Diğer', '-'
            ];
        }


        //dd($statusListHour);
        if (count($tempSaleList) == 0) {
            $tempLocations = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.wanted_delivery_date', '>', $today)
                //->where('deliveries.status', '!=', 3)
                ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
                //->whereRaw($QueryString)
                ->whereIn('deliveries.status', $statusList)
                ->whereIn('continent_id', Request::input('continent_id'))
                ->whereIn('operation_name', $tempOperationList)
                ->whereIn('wanted_delivery_date', $statusListHour)
                ->select('sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'deliveries.products', 'sales.delivery_not', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit', 'sales.id', 'sales.geoAddress', 'sales.geoLocation', 'sales.geoSearchName',
                    'delivery_locations.continent_id', 'delivery_locations.district', 'sales.receiver_address', 'sales.geoAddress', 'sales.geoLocation', 'sales.geoSearchName')
                ->get();
        } else {
            $tempLocations = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                //->where('deliveries.status', '!=', 3)
                ->where('deliveries.wanted_delivery_date', '>', $today)
                ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
                ->whereRaw($QueryString)
                ->whereIn('sales.id', $tempSaleList)
                ->whereIn('deliveries.status', $statusList)
                ->select('sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'deliveries.products', 'sales.delivery_not', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit', 'sales.id', 'sales.geoAddress', 'sales.geoLocation', 'sales.geoSearchName',
                    'delivery_locations.continent_id', 'delivery_locations.district', 'sales.receiver_address', 'sales.geoAddress', 'sales.geoLocation', 'sales.geoSearchName')
                ->get();
        }

        foreach ($tempLocations as $key => $value) {
            $value->tempCount = $key + 1;
            $tempLocalLocation = explode(' ', $value->receiver_address);
            $foundLocalAdress = '';

            if ($key == 0)
                Mapper::map(41.0446351, 29.0578342, ['zoom' => 11, 'marker' => false]);
            $tempSameValue = false;
            if ($value->geoAddress) {
                for ($x = 0; $x < $key; $x++) {
                    if (explode(',', $tempLocations[$x]->geoLocation)[0] == explode(',', $value->geoLocation)[0] && explode(',', $tempLocations[$x]->geoLocation)[1] == explode(',', $value->geoLocation)[1]) {
                        $tempSameValue = true;
                    }
                }
            }

            if ($value->geoAddress) {
                $tempKey = $key + 1;
                if ($tempSameValue)
                    $tempKey = 'c';
                Mapper::informationWindow(
                    explode(',', $value->geoLocation)[0],
                    explode(',', $value->geoLocation)[1],
                    str_replace("'", "", str_replace("'", "", trim(preg_replace('/\s+/', ' ', $value->receiver_address)))) . '<br/>' .
                    $value->geoSearchName . '<br/>' .
                    $value->geoAddress,
                    ['markers' => ['icon' => "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google_maps/" . $tempKey . ".svg"]]
                );
            }
        }

        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $queryParams->operation_name = "";

        if (!Request::input('deliveryHour')) {
            $queryParams->deliveryHour = 'Hepsi';
        }
        if (!Request::input('continent_id')) {
            $queryParams->continent_id = 'Hepsi';
        }

        $queryParams->status_all = '';
        $queryParams->status_making = '';
        $queryParams->status_ready = '';
        $queryParams->status_delivering = '';
        $queryParams->status_delivered = '';
        $queryParams->status_cancel = '';
        if (Request::input('status_all') == "on") {
            $queryParams->status_all = 'on';
        }

        if (Request::input('status_making') == "on") {
            $queryParams->status_making = 'on';
        }

        if (Request::input('status_ready') == "on") {
            $queryParams->status_ready = 'on';
        }

        if (Request::input('status_delivering') == "on") {
            $queryParams->status_delivering = 'on';
        }

        if (Request::input('status_delivered') == "on") {
            $queryParams->status_delivered = 'on';
        }

        if (Request::input('status_cancel') == "on") {
            $queryParams->status_cancel = 'on';
        }
        //$deliveryHourList = [];
//
        //array_push($deliveryHourList, (object)['information' => 'Hepsi', 'active' => '0', 'status' => 'Hepsi']);
        //array_push($deliveryHourList, (object)['information' => '09-13', 'active' => '0', 'status' => '09:00:00']);
        //array_push($deliveryHourList, (object)['information' => '11-17', 'active' => '0', 'status' => '11:00:00']);
        //array_push($deliveryHourList, (object)['information' => '13-18', 'active' => '0', 'status' => '13:00:00']);
        //array_push($deliveryHourList, (object)['information' => '18-21', 'active' => '0', 'status' => '18:00:00']);
        //array_push($deliveryHourList, (object)['information' => '12-16', 'active' => '0', 'status' => '12:00:00']);

        $continentList = [];

        $operationList = DB::table('operation_person')->get();
        //array_push($operationList, (object)['name' => 'Hepsi']);

        //array_push($continentList, (object)['information' => 'Hepsi', 'status' => 'Hepsi', 'active' => '0']);
        array_push($continentList, (object)['information' => 'Avrupa', 'status' => 'Avrupa', 'active' => '0']);
        array_push($continentList, (object)['information' => 'Asya', 'status' => 'Asya', 'active' => '0']);
        array_push($continentList, (object)['information' => 'Oyaka', 'status' => 'Avrupa-2', 'active' => '0']);

        if (Request::input('continent_id'))
            foreach (Request::input('continent_id') as $tempVal) {
                if ($tempVal == 'Avrupa') {
                    $continentList[0]->active = 1;
                }
                if ($tempVal == 'Asya') {
                    $continentList[1]->active = 1;
                }
                if ($tempVal == 'Avrupa-2') {
                    $continentList[2]->active = 1;
                }
            }

        $operationList = DB::table('operation_person')->select('name', DB::raw(' (0) as active '))->get();
        $operationListComplete = DB::table('operation_person')->get();

        if (Request::input('operation_name'))
            foreach (Request::input('operation_name') as $tempVal) {
                if ($tempVal == 'Şuayp') {
                    $operationList[0]->active = 1;
                }
                if ($tempVal == 'Mesut') {
                    $operationList[1]->active = 1;
                }
                if ($tempVal == 'Turgay') {
                    $operationList[2]->active = 1;
                }
                if ($tempVal == 'Ferdi') {
                    $operationList[3]->active = 1;
                }
                if ($tempVal == 'Diğer') {
                    $operationList[4]->active = 1;
                }
            }

        //dd($tempLocations);

        return view('admin.testMap', compact('tempLocations', 'operationListComplete', 'queryParams', 'deliveryHourList', 'continentList', 'operationList'));
    }

    public function gmDeliveriesFilter(\Illuminate\Http\Request $request)
    {
        $today = Carbon::now();
        $today->startOfDay();

        $todayEnd = Carbon::now();
        $todayEnd->endOfDay();

        $QueryString = ' 1 = 1';

        if (Request::input('deliveryHour') != 'Hepsi') {
            $QueryString = $QueryString . ' and hour(wanted_delivery_date) = ' . explode(':', Request::input('deliveryHour'))[0];
        }
        if (Request::input('continent_id') != 'Hepsi') {
            $QueryString = $QueryString . ' and continent_id = ' . " '" . Request::input('continent_id') . "' ";
        }

        $tempLocations = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '!=', 3)
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->whereRaw($QueryString)
            ->select('deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit', 'delivery_locations.district', 'sales.receiver_address', 'sales.id', 'sales.geoAddress', 'sales.geoLocation', 'sales.geoSearchName')
            ->get();

        foreach ($tempLocations as $key => $value) {

            if ($key == 0)
                Mapper::map(41.0446351, 29.0578342, ['zoom' => 11, 'marker' => false]);

            $value->mapsAddress = "";
            $value->lat = "";
            $value->long = "";
        }

        //$queryParams = (object)[ 'deliveryHour' => "" , 'continent_id' => "" ];

        $tempObject = $request->all();
        $queryParams = (object)$tempObject;

        $deliveryHourList = [];

        array_push($deliveryHourList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($deliveryHourList, (object)['information' => '09-13', 'status' => '09:00:00']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00']);

        $continentList = [];

        array_push($continentList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($continentList, (object)['information' => 'Avrupa', 'status' => 'Avrupa']);
        array_push($continentList, (object)['information' => 'Asya', 'status' => 'Asya']);
        array_push($continentList, (object)['information' => 'Oyaka', 'status' => 'Avrupa-2']);

        return view('admin.gmDeliveries', compact('tempLocations', 'queryParams', 'deliveryHourList', 'continentList'));

    }

    public function gmDeliveries()
    {
        //Mapper::setKey('AIzaSyCAfPHfNDfmVzqQ7RPkN2jTrWJiVn6hcOI');
        //Mapper::map(41.0446351, 29.0578342, ['zoom' => 11, 'marker' => false]);

        //return view('admin.map');
        //dd(Mapper::map(41.0446351, 29.0578342, ['zoom' => 11, 'marker' => false]));
        //dd(Mapper::map(41.0446351, 29.0578342, ['zoom' => 11, 'marker' => false]));
        //dd(Mapper::location('istanbul')->getLatitude());
        $today = Carbon::now();
        //$today->addDay(1);
        $today->startOfDay();

        $todayEnd = Carbon::now();
        //$todayEnd->addDay(1);
        $todayEnd->endOfDay();

        $tempLocations = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            //->where('deliveries.status', '!=', 3)
            //->where('deliveries.status', '!=', '3')
            //->where('deliveries.status', '!=', '4')
            //->where('sales.id', 110764)
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->select('deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit', 'delivery_locations.district', 'sales.receiver_address', 'sales.id', 'sales.geoAddress', 'sales.geoLocation', 'sales.geoSearchName')
            //->limit(13)
            ->get();
        //dd(Mapper::location($tempLocations[0]->district)->getLatitude());
        //Mapper::map(Mapper::location($tempLocations[0]->district)->getLatitude(),  Mapper::location($tempLocations[0]->district)->getLongitude());

        foreach ($tempLocations as $key => $value) {
            //    $tempLocalLocation = explode( ' ' , $value->receiver_address);
            //    $foundLocalAdress = '';
            //    //$newFoundLocalAdress = '';
            //    foreach($tempLocalLocation as $key1 => $value1){
            //        if( stristr($value1, 'sok') !== false || stristr($value1, 'sk.') !== false  ){
            //            $foundLocalAdress = $tempLocalLocation[$key1 - 1];
            //            $foundLocalAdressNew = explode('.' , $foundLocalAdress);
            //            $foundLocalAdress = $foundLocalAdressNew[count($foundLocalAdressNew) - 1];
            //            $foundLocalAdress = $foundLocalAdress . ' sk.';
//
            //        }
            //        else if(  stristr($value1, 'Cad') !== false || stristr($value1, 'cad') !== false || stristr($value1, 'cd.') !== false ){
            //            $foundLocalAdress = $tempLocalLocation[$key1 - 1];
            //            $foundLocalAdressNew = explode('.' , $foundLocalAdress);
            //            $foundLocalAdress = $foundLocalAdressNew[count($foundLocalAdressNew) - 1];
            //            $foundLocalAdress = $foundLocalAdress . ' cd.';
//
            //        }
            //    }
            //    if($foundLocalAdress)
            //    foreach($tempLocalLocation as $key3 => $value3){
            //        if( stristr($value3, 'no:') != false || stristr($value3, 'no.') != false  || stristr($value3, 'no') != false ){
            //            //dd($value1);
            //            if(count(explode(':' , $value3)) > 1){
            //                //dd($value1);
            //                $foundLocalAdress = $foundLocalAdress . ' ' . explode('.' , explode('/' , explode(':' , $value3)[1])[0])[0];
            //            }
            //            else if(count(explode('.' , $value3)) > 1){
            //                $foundLocalAdress = $foundLocalAdress . ' ' . explode('.' , explode('/' , explode('.' , $value3)[1])[0])[0];
            //            }
            //            else if( strlen($value3) > 2 ){
            //                $foundLocalAdress = $foundLocalAdress . ' ' . explode('.' , explode('/' , explode('no' , $value3)[1])[0])[0];
            //            }
            //            else{
            //                $foundLocalAdress = $foundLocalAdress . ' ' .  $tempLocalLocation[$key3 + 1];
            //            }
            //        }
            //    }
            //    if($foundLocalAdress == ""){
            //        foreach($tempLocalLocation as $key2 => $value2){
            //            try{
            //                $tempObject = Mapper::location($value->district  . ' ' . $value2);
            //                $foundLocalAdress = $value2;
            //                //$foundLocalAdressNew = explode('.' , $foundLocalAdress);
            //                //$foundLocalAdress = $foundLocalAdressNew[count($foundLocalAdressNew) - 1];
            //                break;
            //            }
            //            catch (\Exception $e) {
            //                continue;
            //            }
            //        }
            //    }
            //dd(Mapper::location('istanbul'));
            if ($key == 0)
                //Mapper::map(Mapper::location($value->district)->getLatitude(),  Mapper::location($value->district)->getLongitude(), ['zoom' => 11]);
                Mapper::map(41.0446351, 29.0578342, ['zoom' => 11, 'marker' => false]);
            //else{


            //try{
            //    //Mapper::map(41.0446351,29.0578342, ['zoom' => 11]);
            //    $tempObject = Mapper::location($foundLocalAdress   . ' ' .  $value->district);
            //    //dd($tempObject);
            //    $value->mapsAddress = $tempObject->getAddress() . ' ' . $foundLocalAdress;
            //    $value->lat = $tempObject->getLatitude();
            //    $value->long = $tempObject->getLongitude();
            //    if($foundLocalAdress){
            //        $iconName = "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google-maps-green-dot.png";
            //    }
            //    else{
            //        $iconName = "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google-maps-red-dot.png";
            //    }
//
            //}
            //catch (\Exception $e) {
            //    //dd('qwrqwe');
            //    $tempObject = Mapper::location($value->district);
            $value->mapsAddress = "";
            $value->lat = "";
            $value->long = "";
            //    $iconName = "http://maps.google.com/mapfiles/ms/icons/red-dot.png";
            //    //$foundLocalAdress = '';
            //}

            //Mapper::informationWindow($tempObject->getLatitude(),  $tempObject->getLongitude(), $foundLocalAdress . ' ' . str_replace("'","" ,str_replace("'","" ,trim(preg_replace('/\s+/', ' ', $value->receiver_address)) )), ['markers' => ['icon' => $iconName]] );
            //Mapper::marker(Mapper::location($value->district)->getLatitude(),  Mapper::location($value->district)->getLongitude(), ['label' => 'Hakan']);
            //}
        }

        $queryParams = (object)['deliveryHour' => "", 'continent_id' => ""];

        $deliveryHourList = [];

        array_push($deliveryHourList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($deliveryHourList, (object)['information' => '09-13', 'status' => '09:00:00']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00']);

        $continentList = [];

        array_push($continentList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($continentList, (object)['information' => 'Avrupa', 'status' => 'Avrupa']);
        array_push($continentList, (object)['information' => 'Asya', 'status' => 'Asya']);
        array_push($continentList, (object)['information' => 'Oyaka', 'status' => 'Avrupa-2']);

        return view('admin.gmDeliveries', compact('tempLocations', 'queryParams', 'deliveryHourList', 'continentList'));
        //Mapper::marker(Mapper::location($tempLocations[0]->district)->getLatitude(),  Mapper::location($tempLocations[0]->district)->getLongitude());
        //Mapper::circle([['latitude' => 53.381128999999990000, 'longitude' => -1.470085000000040000]]);
        //Mapper::circle([['latitude' => 53.381128999999990000, 'longitude' => -1.470085000000040000]]);
        //Mapper::circle([['latitude' => 53.381128999999990000, 'longitude' => -1.470085000000040000]]);
        //Mapper::marker(53.381128999999990000, -1.470085000000040000);
        //Mapper::marker(53.381128999999990000, -1.570085000000040000);
        //return Mapper::map(53.381128999999990000, -1.470085000000040000)->view;
        //return view('admin.testMap');
    }

    public function testGoogleMap2()
    {
        //dd(Mapper::location('Sheffield')->getLatitude());
        $today = Carbon::now();
        //$today->addDay(1);
        $today->startOfDay();

        $todayEnd = Carbon::now();
        //$todayEnd->addDay(1);
        $todayEnd->endOfDay();

        $tempLocations = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            //->where('deliveries.status', '!=', '3')
            //->where('deliveries.status', '!=', '4')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->select('delivery_locations.district', 'sales.receiver_address', 'sales.geoAddress', 'sales.geoLocation', 'sales.geoSearchName')
            //->limit(13)
            ->get();
        //dd(Mapper::location($tempLocations[0]->district)->getLatitude());
        //Mapper::map(Mapper::location($tempLocations[0]->district)->getLatitude(),  Mapper::location($tempLocations[0]->district)->getLongitude());

        foreach ($tempLocations as $key => $value) {
            $tempLocalLocation = explode(' ', $value->receiver_address);
            $foundLocalAdress = '';
            //$newFoundLocalAdress = '';

            //foreach($tempLocalLocation as $key1 => $value1){
            //    if( stristr($value1, 'sok') !== false || stristr($value1, 'sk.') !== false  ){
            //        $foundLocalAdress = $tempLocalLocation[$key1 - 1];
            //        $foundLocalAdressNew = explode('.' , $foundLocalAdress);
            //        $foundLocalAdress = $foundLocalAdressNew[count($foundLocalAdressNew) - 1];
            //        $foundLocalAdress = $foundLocalAdress . ' sk.';
            //    }
            //    else if( stristr($value1, 'cad') !== false || stristr($value1, 'cd.') !== false ){
            //        $foundLocalAdress = $tempLocalLocation[$key1 - 1];
            //        $foundLocalAdressNew = explode('.' , $foundLocalAdress);
            //        $foundLocalAdress = $foundLocalAdressNew[count($foundLocalAdressNew) - 1];
            //        $foundLocalAdress = $foundLocalAdress . ' cd.';
            //    }
            //}
            //if($foundLocalAdress == ""){
            //    foreach($tempLocalLocation as $key2 => $value2){
            //        try{
            //            $tempObject = Mapper::location($value->district  . ' ' . $value2);
            //            $foundLocalAdress = $value2;
            //            //$foundLocalAdressNew = explode('.' , $foundLocalAdress);
            //            //$foundLocalAdress = $foundLocalAdressNew[count($foundLocalAdressNew) - 1];
            //            break;
            //        }
            //        catch (\Exception $e) {
            //            continue;
            //        }
            //    }
            //}
            //dd(Mapper::location('istanbul'));
            if ($key == 0)
                //Mapper::map(Mapper::location($value->district)->getLatitude(),  Mapper::location($value->district)->getLongitude(), ['zoom' => 11]);
                Mapper::map(41.0446351, 29.0578342, ['zoom' => 11, 'marker' => false]);
            //else{


            //try{
            //    //Mapper::map(41.0446351,29.0578342, ['zoom' => 11]);
            //    $tempObject = Mapper::location($value->district  . ' ' . $foundLocalAdress);
            //    if($foundLocalAdress){
            //        $iconName = "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google-maps-green-dot.png";
            //    }
            //    else{
            //        $iconName = "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google-maps-red-dot.png";
            //    }
//
            //}
            //catch (\Exception $e) {
            //    //dd('qwrqwe');
            //    $tempObject = Mapper::location($value->district);
            //    $iconName = "http://maps.google.com/mapfiles/ms/icons/red-dot.png";
            //    //$foundLocalAdress = '';
            //}
            if ($value->geoAddress) {
                Mapper::informationWindow(
                    explode(',', $value->geoLocation)[0],
                    explode(',', $value->geoLocation)[1],
                    str_replace("'", "", str_replace("'", "", trim(preg_replace('/\s+/', ' ', $value->receiver_address)))) . '<br/>' .
                    $value->geoSearchName . '<br/>' .
                    $value->geoAddress,
                    ['markers' => ['icon' => "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google-maps-green-dot.png"]]
                );
            }
            //else{
            //    Mapper::informationWindow(
            //        $tempObject->getLatitude(),
            //        $tempObject->getLongitude(),
            //        str_replace("'","" ,str_replace("'","" ,trim(preg_replace('/\s+/', ' ', $value->receiver_address)) )) .
            //        $foundLocalAdress . ' ' ,
            //        ['markers' => ['icon' => "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google-maps-red-dot.png"]] );
            //}
            //Mapper::informationWindow($tempObject->getLatitude(),  $tempObject->getLongitude(), $foundLocalAdress . ' ' . str_replace("'","" ,str_replace("'","" ,trim(preg_replace('/\s+/', ' ', $value->receiver_address)) )), ['markers' => ['icon' => $iconName]] );
            //Mapper::marker(Mapper::location($value->district)->getLatitude(),  Mapper::location($value->district)->getLongitude(), ['label' => 'Hakan']);
            //}
        }

        //Mapper::marker(Mapper::location($tempLocations[0]->district)->getLatitude(),  Mapper::location($tempLocations[0]->district)->getLongitude());
        //Mapper::circle([['latitude' => 53.381128999999990000, 'longitude' => -1.470085000000040000]]);
        //Mapper::circle([['latitude' => 53.381128999999990000, 'longitude' => -1.470085000000040000]]);
        //Mapper::circle([['latitude' => 53.381128999999990000, 'longitude' => -1.470085000000040000]]);
        //Mapper::marker(53.381128999999990000, -1.470085000000040000);
        //Mapper::marker(53.381128999999990000, -1.570085000000040000);
        //return Mapper::map(53.381128999999990000, -1.470085000000040000)->view;
        return view('admin.testMap');
    }

    public function testGoogleMap()
    {
        //dd(Mapper::location('Sheffield')->getLatitude());
        $today = Carbon::now();
        //$today->addDay(1);
        $today->startOfDay();

        $todayEnd = Carbon::now();
        //$todayEnd->addDay(1);
        $todayEnd->endOfDay();

        $tempLocations = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            //->where('deliveries.status', '!=', '3')
            //->where('deliveries.status', '!=', '4')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->select('delivery_locations.district', 'sales.receiver_address', 'sales.geoAddress', 'sales.geoLocation')
            //->limit(13)
            ->get();
        //dd(Mapper::location($tempLocations[0]->district)->getLatitude());
        //Mapper::map(Mapper::location($tempLocations[0]->district)->getLatitude(),  Mapper::location($tempLocations[0]->district)->getLongitude());

        foreach ($tempLocations as $key => $value) {
            $tempLocalLocation = explode(' ', $value->receiver_address);
            $foundLocalAdress = '';
            //$newFoundLocalAdress = '';
            foreach ($tempLocalLocation as $key1 => $value1) {
                if (stristr($value1, 'sok') !== false || stristr($value1, 'sk.') !== false) {
                    $foundLocalAdress = $tempLocalLocation[$key1 - 1];
                    $foundLocalAdressNew = explode('.', $foundLocalAdress);
                    $foundLocalAdress = $foundLocalAdressNew[count($foundLocalAdressNew) - 1];
                    $foundLocalAdress = $foundLocalAdress . ' sk.';
                } else if (stristr($value1, 'cad') !== false || stristr($value1, 'cd.') !== false) {
                    $foundLocalAdress = $tempLocalLocation[$key1 - 1];
                    $foundLocalAdressNew = explode('.', $foundLocalAdress);
                    $foundLocalAdress = $foundLocalAdressNew[count($foundLocalAdressNew) - 1];
                    $foundLocalAdress = $foundLocalAdress . ' cd.';
                }
            }
            if ($foundLocalAdress)
                foreach ($tempLocalLocation as $key3 => $value3) {
                    if (stristr($value3, 'no:') != false || stristr($value3, 'no.') != false || stristr($value3, 'no') != false) {
                        //dd($value1);
                        if (count(explode(':', $value3)) > 1) {
                            //dd($value1);
                            $foundLocalAdress = $foundLocalAdress . ' ' . explode('.', explode('/', explode(':', $value3)[1])[0])[0];
                        } else if (count(explode('.', $value3)) > 1) {
                            $foundLocalAdress = $foundLocalAdress . ' ' . explode('.', explode('/', explode('.', $value3)[1])[0])[0];
                        } else if (strlen($value3) > 2) {
                            $foundLocalAdress = $foundLocalAdress . ' ' . explode('.', explode('/', explode('no', $value3)[1])[0])[0];
                        } else {
                            $foundLocalAdress = $foundLocalAdress . ' ' . $tempLocalLocation[$key3 + 1];
                        }
                    }
                }
            //if($foundLocalAdress == ""){
            //    foreach($tempLocalLocation as $key2 => $value2){
            //        try{
            //            $tempObject = Mapper::location($value->district  . ' ' . $value2);
            //            $foundLocalAdress = $value2;
            //            //$foundLocalAdressNew = explode('.' , $foundLocalAdress);
            //            //$foundLocalAdress = $foundLocalAdressNew[count($foundLocalAdressNew) - 1];
            //            break;
            //        }
            //        catch (\Exception $e) {
            //            continue;
            //        }
            //    }
            //}
            //dd(Mapper::location('istanbul'));
            if ($key == 0)
                //Mapper::map(Mapper::location($value->district)->getLatitude(),  Mapper::location($value->district)->getLongitude(), ['zoom' => 11]);
                Mapper::map(41.0446351, 29.0578342, ['zoom' => 11, 'marker' => false]);
            //else{


            try {
                //Mapper::map(41.0446351,29.0578342, ['zoom' => 11]);
                $tempObject = Mapper::location($value->district . ' ' . $foundLocalAdress);
                if ($foundLocalAdress) {
                    $iconName = "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google-maps-green-dot.png";
                } else {
                    $iconName = "https://s3.eu-central-1.amazonaws.com/bloomandfresh/google-maps-red-dot.png";
                }

            } catch (\Exception $e) {
                //dd('qwrqwe');
                $tempObject = Mapper::location($value->district);
                $iconName = "http://maps.google.com/mapfiles/ms/icons/red-dot.png";
                //$foundLocalAdress = '';
            }
            Mapper::informationWindow(
                $tempObject->getLatitude(),
                $tempObject->getLongitude(),
                str_replace("'", "", str_replace("'", "", trim(preg_replace('/\s+/', ' ', $value->receiver_address)))) . '<br />' .
                $value->district . ' ' . $foundLocalAdress . '<br />' .
                $tempObject->getAddress()
                , ['markers' => ['icon' => $iconName]]
            );
            //Mapper::marker(Mapper::location($value->district)->getLatitude(),  Mapper::location($value->district)->getLongitude(), ['label' => 'Hakan']);
            //}
        }

        //Mapper::marker(Mapper::location($tempLocations[0]->district)->getLatitude(),  Mapper::location($tempLocations[0]->district)->getLongitude());
        //Mapper::circle([['latitude' => 53.381128999999990000, 'longitude' => -1.470085000000040000]]);
        //Mapper::circle([['latitude' => 53.381128999999990000, 'longitude' => -1.470085000000040000]]);
        //Mapper::circle([['latitude' => 53.381128999999990000, 'longitude' => -1.470085000000040000]]);
        //Mapper::marker(53.381128999999990000, -1.470085000000040000);
        //Mapper::marker(53.381128999999990000, -1.570085000000040000);
        //return Mapper::map(53.381128999999990000, -1.470085000000040000)->view;
        return view('admin.testMap');
    }

    public function sendTestBilling()
    {


        \MandrillMail::messages()->sendTemplate('v2_BNF_Siparis_Alindi', null, array(
            'html' => '<p>Example HTML content</p>',
            'text' => 'Siparişiniz başarıyla verilmiştir.',
            'subject' => ', Bloom And Fresh - ',
            'from_email' => 'teknik@bloomandfresh.com',
            'from_name' => 'Bloom And Fresh',
            'to' => array(
                array(
                    'email' => 'hakancetinh@gmail.com',
                    'type' => 'to'
                )
            ),
            'merge' => true,
            'merge_language' => 'mailchimp',
            'global_merge_vars' => array(),
            'attachments' => array(
                array(
                    'type' => 'application/pdf',
                    'name' => 'Mesafeli Satış Sözleşmesi.pdf'
                )
            )
        ));
    }

    public function showTest()
    {
        try {
            Excel::load(Request::file('file'), function ($reader) {
                dd($reader->toArray());
                //foreach ($reader->toArray() as $row) {
                //    dd($row);
                //}
            });
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function index()
    {
        return view('admin.importExcel');
    }

    public function billingTest($sales_id)
    {
        BillingOperation::soapTest($sales_id);
    }

    public function soapTest()
    {

        $list = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('billings', 'sales.id', '=', 'billings.sales_id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('sales.id', '=', '105493')
            ->orWhere('sales.id', '=', '105501')
            ->orderBy('deliveries.delivery_date')
            ->select('sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling',
                'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
                'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'sales.sender_mobile', 'products.name as products', 'sales.sender_name', 'sales.sender_surname',
                'sales.product_price as price', 'products.id', 'sales.customer_contact_id')
            ->get();

        //dd($list);

        $firstPrice = 0;
        $totalDiscount = 0;
        $totalPartial = 0;
        $totalKDV = 0;
        $total = 0;

        foreach ($list as $row) {
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
                $row->address2 = $row->city;
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

                $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);

                $row->discountValue = number_format($row->discountValue, 2);
                parse_str($row->discountValue);
                $row->discountValue = str_replace('.', ',', $row->discountValue);

                $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
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
                    $priceWithDiscount = floatval($priceWithDiscount) - floatval($discount[0]->value);
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

                $row->discountValue = floatval(floatval($tempPriceWithDiscount) * 18 / 100);
                $totalKDV = $totalKDV + $row->discountValue;
                $row->discountValue = number_format($row->discountValue, 2);
                parse_str($row->discountValue);
                $row->discountValue = str_replace('.', ',', $row->discountValue);


                $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 118 / 100);

                $priceWithDiscount = number_format($priceWithDiscount, 2);

                parse_str($priceWithDiscount);
                $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                $row->sumTotal = $priceWithDiscount;

            }

            //$total = $total + $row->sumTotal;
        }
        $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
        $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';

        //$tempList = array_slice($list, 0, 5);
        for ($x = 0; $x < 1; $x++) {
            $tempListQuery = [];
            //$tempListQuery[0] = $list[0];
            //$tempListQuery[1] = $list[1];
            //$tempListQuery[2] = $list[2];
            //$tempListQuery[3] = $list[3];
            //$tempListQuery[4] = $list[4];
            //if($x*5 + 1 != 251){
            //}

            $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
            $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';
            //$tempList = array_slice($tempListQuery, 5*($x - 1), 5);
            foreach ($list as $row) {

                $row->price = str_replace(',', '.', $row->price);
                $row->discountVal = str_replace(',', '.', $row->discountVal);
                $row->discountValue = str_replace(',', '.', $row->discountValue);
                $row->sumPartial = str_replace(',', '.', $row->sumPartial);
                $row->sumTotal = str_replace(',', '.', $row->sumTotal);


                if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {
                    if ($row->tc == '' || $row->tc == null)
                        $row->tc = '11111111111';
                    else {
                        $row->billing_address = '<ns4:StreetName>' . $row->billing_address . '</ns4:StreetName>';
                        $row->bigCity = $row->small_city;
                    }
                    if ($row->discount > 0) {
                        $tempDiscountText = '<ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                İndirim
                            </ns4:AllowanceChargeReason>
                            <ns4:MultiplierFactorNumeric>
                                ' . $row->discount / 100 . '
                            </ns4:MultiplierFactorNumeric>
                            <ns4:Amount currencyID="TRY">
                                ' . $row->discountVal . '
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                ' . $row->price . '
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>';
                    } else {
                        $tempDiscountText = '';
                    }
                    $tempSaleDate = explode('.', explode(' ', $row->created_at)[0]);
                    $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->sales_id . '">
                    <ns1:Invoice>
                        <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                        <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                        <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                        <ns4:CopyIndicator>false</ns4:CopyIndicator>
                        <ns4:IssueDate>2016-01-19</ns4:IssueDate>
                        <ns4:IssueTime>15:00:00</ns4:IssueTime>
                        <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                        <ns4:Note>Satış internet üzerinden gerçekleştirilmiştir.</ns4:Note>
                        <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                        <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                        <ns5:DespatchDocumentReference>
				    	    <ns4:ID>A-' . $row->sales_id . '</ns4:ID>
						    <ns4:IssueDate>2016-01-19</ns4:IssueDate>
				        </ns5:DespatchDocumentReference>
                        <ns5:AccountingSupplierParty>
                            <ns5:Party>
                                <ns4:WebsiteURI>bloomandfresh.com</ns4:WebsiteURI>
                                <ns5:PartyIdentification>
                                    <ns4:ID schemeID="VKN">4650412713</ns4:ID>
                                </ns5:PartyIdentification>
                                <ns5:PartyName>
                                    <ns4:Name>IF GİRİŞİM VE TEKNOLOJİ A.Ş.</ns4:Name>
                                </ns5:PartyName>
                                <ns5:PostalAddress>
                                    <ns4:StreetName>BOYACIÇEŞME SOKAK</ns4:StreetName>
                                    <ns4:BuildingNumber>12/2</ns4:BuildingNumber>
                                    <ns4:CitySubdivisionName>Sarıyer</ns4:CitySubdivisionName>
                                    <ns4:CityName>İSTANBUL</ns4:CityName>
                                    <ns4:District>EMİRGAN</ns4:District>
                                    <ns5:Country>
                                        <ns4:Name>Türkiye</ns4:Name>
                                    </ns5:Country>
                                </ns5:PostalAddress>
                                <ns5:PartyTaxScheme>
                                    <ns5:TaxScheme>
                                        <ns4:Name>Sarıyer</ns4:Name>
                                    </ns5:TaxScheme>
                                </ns5:PartyTaxScheme>
                                <ns5:Contact>
                                    <ns4:Telephone>02122120282</ns4:Telephone>
                                    <ns4:Telefax>02122120292</ns4:Telefax>
                                    <ns4:ElectronicMail>hello@bloomandfresh.com</ns4:ElectronicMail>
                                </ns5:Contact>
                            </ns5:Party>
                        </ns5:AccountingSupplierParty>
                        <ns5:AccountingCustomerParty>
                            <ns5:Party>
                                <ns5:PartyIdentification>
                                    <ns4:ID schemeID="TCKN">' . $row->tc . '</ns4:ID>
                                </ns5:PartyIdentification>
                                <ns5:PostalAddress>
                                    ' . $row->billing_address . '
                                    <ns4:CitySubdivisionName>' . $row->bigCity . '</ns4:CitySubdivisionName>
                                    <ns4:CityName>İstanbul</ns4:CityName>
                                    <!--<ns4:PostalZone>34100</ns4:PostalZone>-->
                                    <ns5:Country>
                                        <ns4:Name>Türkiye</ns4:Name>
                                    </ns5:Country>
                                </ns5:PostalAddress>
                                <ns5:Person>
                                    <ns4:FirstName>' . $row->sender_name . '</ns4:FirstName>
                                    <ns4:FamilyName>' . $row->sender_surname . '</ns4:FamilyName>
                                </ns5:Person>
                            </ns5:Party>
                        </ns5:AccountingCustomerParty>
                        ' . $tempDiscountText . '
                        <ns5:TaxTotal>
                            <ns4:TaxAmount currencyID="TRY">' . $row->discountValue . '</ns4:TaxAmount>
                            <ns5:TaxSubtotal>
                                <ns4:TaxableAmount
                                        currencyID="TRY">' . $row->sumPartial . '
                                </ns4:TaxableAmount>
                                <ns4:TaxAmount
                                        currencyID="TRY">' . $row->discountValue . '
                                </ns4:TaxAmount>
                                <ns4:CalculationSequenceNumeric>
                                    1
                                </ns4:CalculationSequenceNumeric>
                                <ns4:Percent>18.0</ns4:Percent>
                                <ns5:TaxCategory>
                                    <ns5:TaxScheme>
                                        <ns4:TaxTypeCode>0015</ns4:TaxTypeCode>
                                    </ns5:TaxScheme>
                                </ns5:TaxCategory>
                            </ns5:TaxSubtotal>
                        </ns5:TaxTotal>
                        <ns5:LegalMonetaryTotal>
                            <ns4:LineExtensionAmount
                                    currencyID="TRY">' . $row->price . '
                            </ns4:LineExtensionAmount>
                            <ns4:TaxExclusiveAmount
                                    currencyID="TRY">' . $row->sumPartial . '
                            </ns4:TaxExclusiveAmount>
                            <ns4:TaxInclusiveAmount
                                    currencyID="TRY">' . $row->sumTotal . '
                            </ns4:TaxInclusiveAmount>
                            <ns4:AllowanceTotalAmount currencyID="TRY">' . $row->discountVal . '</ns4:AllowanceTotalAmount>
                            <ns4:PayableRoundingAmount
                                    currencyID="TRY">0.0
                            </ns4:PayableRoundingAmount>
                            <ns4:PayableAmount
                                    currencyID="TRY">' . $row->sumTotal . '
                            </ns4:PayableAmount>
                        </ns5:LegalMonetaryTotal>
                        <ns5:InvoiceLine>
                            <ns4:ID>1</ns4:ID>
                            <ns4:InvoicedQuantity unitCode="C62">1</ns4:InvoicedQuantity>
                            <ns4:LineExtensionAmount currencyID="TRY">' . $row->price . '</ns4:LineExtensionAmount>
                            <ns5:Item>
                                <ns4:Name>' . $row->products . '</ns4:Name>
                                <ns5:SellersItemIdentification>
                                    <ns4:ID>' . $row->id . '</ns4:ID>
                                </ns5:SellersItemIdentification>
                            </ns5:Item>
                            <!--Optional:-->
                            <ns5:Price>
                                <!--Optional:-->
                                <ns4:PriceAmount currencyID="TRY">' . $row->price . '</ns4:PriceAmount>
                            </ns5:Price>
                            <!--Zero or more repetitions:-->
                        </ns5:InvoiceLine>
                    </ns1:Invoice>

                    <ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                        <ns1:InternetSalesInfo>
                            <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                            <ns1:PaymentMidierName>SANAL POS</ns1:PaymentMidierName>
                            <ns1:PaymentType>KREDIKARTI/BANKAKARTI</ns1:PaymentType>
                            <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                            <ns1:ShipmentInfo>
                                <ns1:SendDate>2016-01-19</ns1:SendDate>
                                <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ ANONİM ŞİRKETİ"/>
                            </ns1:ShipmentInfo>
                        </ns1:InternetSalesInfo>
                    </ns1:EArchiveInvoiceInfo>
                    <ns1:Scenario>eArchive</ns1:Scenario>
                    <ns1:Notification>
                        <ns1:Mailing EnableNotification="true" To="qw@msn.com" BodyXsltIdentifier="qwer"
                                     EmailAccountIdentifier="qwer">
                        </ns1:Mailing>
                    </ns1:Notification>
                </ns1:InvoiceInfo>';
                    $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                    $client = new SoapClient($wsdl, array(
                        'soap_version' => SOAP_1_1,
                        'trace' => true,
                    ));


                    //$args = array(new \SoapVar($xml2, XSD_ANYXML));
//
                    //$header = array( new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2', 'RequestorCredentials'),
                    //    new \SOAPHeader('http://www.w3.org/2000/09/xmldsig#', 'RequestorCredentials'),
                    //    new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'RequestorCredentials'),
                    //    new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'RequestorCredentials'));
                    ////dd($header);
                    //$client->__setSoapHeaders($header);
//
                    //$res  = $client->__soapCall('SaveAsDraft', $args);
                    //dd($res);
                } else {

                    if ($row->payment_type == 'KURUMSAL') {
                        //dd($row->customer_contact_id);
                        $tempCompanyInfo = DB::table('customer_contacts')
                            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                            ->join('company_user_info', 'customers.user_id', '=', 'company_user_info.user_id')
                            ->where('customer_contacts.id', $row->customer_contact_id)
                            ->select('company_name', 'tax_no', 'tax_office', 'billing_address')
                            ->get()[0];

                        $row->tax_no = $tempCompanyInfo->tax_no;
                        $row->company = $tempCompanyInfo->company_name;
                        $row->billing_address = $tempCompanyInfo->billing_address;
                        $row->tax_office = $tempCompanyInfo->tax_office;
                    }
                    $tempSaleDate = explode('.', explode(' ', $row->created_at)[0]);
                    //    $tempCheckBilling = '<ns1:IsEInvoiceUser>
                    //    <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
                    //    <ns1:vknTckn>' . $row->tax_no . '</ns1:vknTckn>
                    //</ns1:IsEInvoiceUser>';
//
                    //    $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                    //    $client = new SoapClient($wsdl, array(
                    //        'soap_version' => SOAP_1_1,
                    //        'trace' => true,
                    //    ));
//
//
                    //    $args = array(new \SoapVar($tempCheckBilling, XSD_ANYXML));
//
                    //    $header = array(new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2', 'RequestorCredentials'),
                    //        new \SOAPHeader('http://www.w3.org/2000/09/xmldsig#', 'RequestorCredentials'),
                    //        new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'RequestorCredentials'),
                    //        new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'RequestorCredentials'));
                    //    //dd($header);
                    //    $client->__setSoapHeaders($header);
//
                    //    $res = $client->__soapCall('IsEInvoiceUser', $args);
//
                    //    if ($res->IsEInvoiceUserResult->Value) {
                    $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                                <ns1:InternetSalesInfo>
                                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                                    <ns1:PaymentMidierName>SANAL POS</ns1:PaymentMidierName>
                                    <ns1:PaymentType>KREDIKARTI/BANKAKARTI</ns1:PaymentType>
                                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                                    <ns1:ShipmentInfo>
                                        <ns1:SendDate>2016-01-20</ns1:SendDate>
                                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                                    </ns1:ShipmentInfo>
                                </ns1:InternetSalesInfo>
                            </ns1:EArchiveInvoiceInfo>
                            <ns1:Scenario>eInvoice</ns1:Scenario>';
                    //    } else {
                    //        $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                    //                <ns1:InternetSalesInfo>
                    //                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                    //                    <ns1:PaymentMidierName>SANAL POS</ns1:PaymentMidierName>
                    //                    <ns1:PaymentType>KREDIKARTI/BANKAKARTI</ns1:PaymentType>
                    //                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                    //                    <ns1:ShipmentInfo>
                    //                        <ns1:SendDate>2016-01-20</ns1:SendDate>
                    //                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                    //                    </ns1:ShipmentInfo>
                    //                </ns1:InternetSalesInfo>
                    //            </ns1:EArchiveInvoiceInfo>
                    //            <ns1:Scenario>eArchive</ns1:Scenario>';
                    //   }

                    //dd($res->IsEInvoiceUserResult->Value);

                    if ($row->discount > 0) {
                        $tempDiscountText = '<ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                İndirim
                            </ns4:AllowanceChargeReason>
                            <ns4:MultiplierFactorNumeric>
                                ' . $row->discount / 100 . '
                            </ns4:MultiplierFactorNumeric>
                            <ns4:Amount currencyID="TRY">
                                ' . $row->discountVal . '
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                ' . $row->price . '
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>';
                    } else {
                        $tempDiscountText = '';
                    }
                    $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->sales_id . '">
                            <ns1:Invoice>
                                <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                                <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                                <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                                <ns4:CopyIndicator>false</ns4:CopyIndicator>
                                <ns4:IssueDate>2016-01-20</ns4:IssueDate>
                                <ns4:IssueTime>15:00:00</ns4:IssueTime>
                                <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                                <ns4:Note>Satış internet üzerinden gerçekleştirilmiştir.</ns4:Note>
                                <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                                <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                                <ns5:DespatchDocumentReference>
				    		        <ns4:ID>A-' . $row->sales_id . '</ns4:ID>
						            <ns4:IssueDate>2016-01-20</ns4:IssueDate>
				                </ns5:DespatchDocumentReference>
                                <ns5:AccountingSupplierParty>
                                    <ns5:Party>
                                        <ns4:WebsiteURI>bloomandfresh.com</ns4:WebsiteURI>
                                        <ns5:PartyIdentification>
                                            <ns4:ID schemeID="VKN">4650412713</ns4:ID>
                                        </ns5:PartyIdentification>
                                        <ns5:PartyName>
                                            <ns4:Name>IF GİRİŞİM VE TEKNOLOJİ A.Ş.</ns4:Name>
                                        </ns5:PartyName>
                                        <ns5:PostalAddress>
                                            <ns4:StreetName>BOYACIÇEŞME SOKAK</ns4:StreetName>
                                            <ns4:BuildingNumber>12/2</ns4:BuildingNumber>
                                            <ns4:CitySubdivisionName>Sarıyer</ns4:CitySubdivisionName>
                                            <ns4:CityName>İSTANBUL</ns4:CityName>
                                            <ns5:Country>
                                                <ns4:Name>Türkiye</ns4:Name>
                                            </ns5:Country>
                                        </ns5:PostalAddress>
                                        <ns5:PartyTaxScheme>
                                            <ns5:TaxScheme>
                                                <ns4:Name>Sarıyer</ns4:Name>
                                            </ns5:TaxScheme>
                                        </ns5:PartyTaxScheme>
                                        <ns5:Contact>
                                            <ns4:Telephone>02122120282</ns4:Telephone>
                                            <ns4:Telefax>02122120292</ns4:Telefax>
                                            <ns4:ElectronicMail>hello@bloomandfresh.com</ns4:ElectronicMail>
                                        </ns5:Contact>
                                    </ns5:Party>
                                </ns5:AccountingSupplierParty>
                                <ns5:AccountingCustomerParty>
                                    <ns5:Party>
                                        <ns5:PartyIdentification>
                                            <ns4:ID schemeID="VKN">' . str_replace(' ', '', str_replace('-', '', $row->tax_no)) . '</ns4:ID>
                                        </ns5:PartyIdentification>
                                        <ns5:PartyName>
                                            <ns4:Name>' . $row->company . '</ns4:Name>
                                        </ns5:PartyName>
                                        <ns5:PostalAddress>
                                            <ns4:StreetName>' . $row->billing_address . '</ns4:StreetName>
                                            <ns4:CitySubdivisionName>' . explode('-', $row->tax_office)[0] . '</ns4:CitySubdivisionName>
                                            <ns4:CityName>İstanbul</ns4:CityName>
                                            <!--<ns4:PostalZone>34100</ns4:PostalZone>-->
                                            <ns5:Country>
                                                <ns4:Name>Türkiye</ns4:Name>
                                            </ns5:Country>
                                        </ns5:PostalAddress>
                                        <ns5:PartyTaxScheme>
                                            <ns5:TaxScheme>
                                                <ns4:Name>' . explode('-', $row->tax_office)[0] . '</ns4:Name>
                                            </ns5:TaxScheme>
                                        </ns5:PartyTaxScheme>
                                    </ns5:Party>
                                </ns5:AccountingCustomerParty>
                                ' . $tempDiscountText . '
                                <ns5:TaxTotal>
                                    <ns4:TaxAmount currencyID="TRY">' . $row->discountValue . '</ns4:TaxAmount>
                                    <ns5:TaxSubtotal>
                                        <ns4:TaxableAmount
                                                currencyID="TRY">' . $row->sumPartial . '
                                        </ns4:TaxableAmount>
                                        <ns4:TaxAmount
                                                currencyID="TRY">' . $row->discountValue . '
                                        </ns4:TaxAmount>
                                        <ns4:CalculationSequenceNumeric>
                                            1
                                        </ns4:CalculationSequenceNumeric>
                                        <ns4:Percent>18.0</ns4:Percent>
                                        <ns5:TaxCategory>
                                            <ns5:TaxScheme>
                                                <ns4:TaxTypeCode>0015</ns4:TaxTypeCode>
                                            </ns5:TaxScheme>
                                        </ns5:TaxCategory>
                                    </ns5:TaxSubtotal>
                                </ns5:TaxTotal>
                                <ns5:LegalMonetaryTotal>
                                    <ns4:LineExtensionAmount
                                            currencyID="TRY">' . $row->price . '
                                    </ns4:LineExtensionAmount>
                                    <ns4:TaxExclusiveAmount
                                            currencyID="TRY">' . $row->sumPartial . '
                                    </ns4:TaxExclusiveAmount>
                                    <ns4:TaxInclusiveAmount
                                            currencyID="TRY">' . $row->sumTotal . '
                                    </ns4:TaxInclusiveAmount>
                                    <ns4:AllowanceTotalAmount currencyID="TRY">' . $row->discountVal . '</ns4:AllowanceTotalAmount>
                                    <ns4:PayableRoundingAmount
                                            currencyID="TRY">0.0
                                    </ns4:PayableRoundingAmount>
                                    <ns4:PayableAmount
                                            currencyID="TRY">' . $row->sumTotal . '
                                    </ns4:PayableAmount>
                                </ns5:LegalMonetaryTotal>
                                <ns5:InvoiceLine>
                                    <ns4:ID>1</ns4:ID>
                                    <ns4:InvoicedQuantity unitCode="C62">1</ns4:InvoicedQuantity>
                                    <ns4:LineExtensionAmount currencyID="TRY">' . $row->price . '</ns4:LineExtensionAmount>
                                    <ns5:Item>
                                        <ns4:Name>' . $row->products . '</ns4:Name>
                                        <ns5:SellersItemIdentification>
                                            <ns4:ID>' . $row->id . '</ns4:ID>
                                        </ns5:SellersItemIdentification>
                                    </ns5:Item>
                                    <!--Optional:-->
                                    <ns5:Price>
                                        <!--Optional:-->
                                        <ns4:PriceAmount currencyID="TRY">' . $row->price . '</ns4:PriceAmount>
                                    </ns5:Price>
                                    <!--Zero or more repetitions:-->
                                </ns5:InvoiceLine>
                            </ns1:Invoice>
                            ' . $tempBillingType . '
                            <ns1:Notification>
                                <ns1:Mailing EnableNotification="true" To="qw@msn.com" BodyXsltIdentifier="qwer"
                                             EmailAccountIdentifier="qwer">
                                </ns1:Mailing>
                            </ns1:Notification>
                        </ns1:InvoiceInfo>';
                    $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                    $client = new SoapClient($wsdl, array(
                        'soap_version' => SOAP_1_1,
                        'trace' => true,
                    ));


                    //$args = array(new \SoapVar($xml2, XSD_ANYXML));
//
                    //$header = array( new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2', 'RequestorCredentials'),
                    //    new \SOAPHeader('http://www.w3.org/2000/09/xmldsig#', 'RequestorCredentials'),
                    //    new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'RequestorCredentials'),
                    //    new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'RequestorCredentials'));
                    ////dd($header);
                    //$client->__setSoapHeaders($header);
//
                    //$res  = $client->__soapCall('SaveAsDraft', $args);
                }

            }

            $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
            $tempQueryString = $tempQueryString . $tempQueryEnd;
            //dd($tempQueryString);
            $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
            $client = new SoapClient($wsdl, array(
                'soap_version' => SOAP_1_1,
                'trace' => true,
            ));


            $args = array(new \SoapVar($tempQueryString, XSD_ANYXML));

            $header = array(new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2', 'RequestorCredentials'),
                new \SOAPHeader('http://www.w3.org/2000/09/xmldsig#', 'RequestorCredentials'),
                new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'RequestorCredentials'),
                new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'RequestorCredentials'));
            //dd($header);
            $client->__setSoapHeaders($header);
            $res = $client->__soapCall('SaveAsDraft', $args);
            dd($res);
        }

        //$wsdl   = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
        //$tempQueryString = $tempQueryString . $tempQueryEnd;
        ////dd($tempQueryString);
        //$wsdl   = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
        //$client = new SoapClient($wsdl, array(
        //    'soap_version' => SOAP_1_1,
        //    'trace' => true,
        //));


        //$args = array(new \SoapVar($tempQueryString, XSD_ANYXML));
//
        //$header = array( new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2', 'RequestorCredentials'),
        //    new \SOAPHeader('http://www.w3.org/2000/09/xmldsig#', 'RequestorCredentials'),
        //    new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'RequestorCredentials'),
        //    new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'RequestorCredentials'));
        ////dd($header);
        //$client->__setSoapHeaders($header);
//
        //$res  = $client->__soapCall('SaveAsDraft', $args);

        /*
        $xml2 = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Uyumsoft" Password="Uyumsoft"/>
            <ns1:invoices>
                <ns1:InvoiceInfo LocalDocumentId="' .  . '">
                    <ns1:Invoice>
                        <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                        <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                        <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                        <ns4:ID>GIB2016000022123</ns4:ID>
                        <ns4:CopyIndicator>false</ns4:CopyIndicator>
                        <ns4:IssueDate>2016-01-11</ns4:IssueDate>
                        <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                        <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                        <ns5:AccountingSupplierParty>
                            <ns5:Party>
                                <ns4:WebsiteURI>Bloomandfresh.com</ns4:WebsiteURI>
                                <ns5:PartyIdentification>
                                    <ns4:ID schemeID="VKN">9000068418</ns4:ID>
                                </ns5:PartyIdentification>
                                <ns5:PartyName>
                                    <ns4:Name>IF GİRİŞİM VE TEKNOLOJİ ANONİM ŞİRKETİ</ns4:Name>
                                </ns5:PartyName>
                                <ns5:PostalAddress>
                                    <ns4:StreetName>BOYACIÇEŞME SOKAK</ns4:StreetName>
                                    <ns4:BuildingNumber>12</ns4:BuildingNumber>
                                    <ns4:CitySubdivisionName>Sarıyer</ns4:CitySubdivisionName>
                                    <ns4:CityName>İSTANBUL</ns4:CityName>
                                    <ns4:District>EMİRGAN</ns4:District>
                                    <ns5:Country>
                                        <ns4:Name>Türkiye</ns4:Name>
                                    </ns5:Country>
                                </ns5:PostalAddress>
                                <ns5:PartyTaxScheme>
                                    <ns5:TaxScheme>
                                        <ns4:Name>Sarıyer</ns4:Name>
                                    </ns5:TaxScheme>
                                </ns5:PartyTaxScheme>
                                <ns5:Contact>
                                    <ns4:Telephone>02122120282</ns4:Telephone>
                                    <ns4:Telefax>02122120292</ns4:Telefax>
                                    <ns4:ElectronicMail>hello@bloomandfresh.com</ns4:ElectronicMail>
                                </ns5:Contact>
                                <ns5:AgentParty/>
                            </ns5:Party>
                        </ns5:AccountingSupplierParty>
                        <ns5:AccountingCustomerParty>
                            <ns5:Party>
                                <ns5:PartyIdentification>
                                    <ns4:ID schemeID="TCKN">11111111112</ns4:ID>
                                </ns5:PartyIdentification>
                                <ns5:PostalAddress>
                                    <ns4:CitySubdivisionName>Beşiktaş</ns4:CitySubdivisionName>
                                    <ns4:CityName>İstanbul</ns4:CityName>
                                    <!--<ns4:PostalZone>34100</ns4:PostalZone>-->
                                    <ns5:Country>
                                        <ns4:Name>Türkiye</ns4:Name>
                                    </ns5:Country>
                                </ns5:PostalAddress>
                                <ns5:Person>
                                    <ns4:FirstName>Pınar</ns4:FirstName>
                                    <ns4:FamilyName>Ambarcıoğlu</ns4:FamilyName>
                                </ns5:Person>
                                <ns5:Contact>
                                    <ns4:Telephone>(212) 925 51515</ns4:Telephone>
                                </ns5:Contact>
                            </ns5:Party>
                        </ns5:AccountingCustomerParty>
                        <ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                Yeni Müşteri İndirimi
                            </ns4:AllowanceChargeReason>
                            <ns4:MultiplierFactorNumeric>
                                0.1
                            </ns4:MultiplierFactorNumeric>
                            <ns4:Amount currencyID="TRY">
                                9.99
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                99.90
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>
                        <ns5:TaxTotal>
                            <ns4:TaxAmount currencyID="TRY">16.18</ns4:TaxAmount>
                            <ns5:TaxSubtotal>
                                <ns4:TaxableAmount
                                        currencyID="TRY">89.91
                                </ns4:TaxableAmount>
                                <ns4:TaxAmount
                                        currencyID="TRY">16.18
                                </ns4:TaxAmount>
                                <ns4:CalculationSequenceNumeric>
                                    1
                                </ns4:CalculationSequenceNumeric>
                                <ns4:Percent>18.0</ns4:Percent>
                                <ns5:TaxCategory>
                                    <ns5:TaxScheme>
                                        <ns4:TaxTypeCode>
                                            0015
                                        </ns4:TaxTypeCode>
                                    </ns5:TaxScheme>
                                </ns5:TaxCategory>
                            </ns5:TaxSubtotal>
                        </ns5:TaxTotal>
                        <ns5:LegalMonetaryTotal>
                            <ns4:LineExtensionAmount
                                    currencyID="TRY">99.90
                            </ns4:LineExtensionAmount>
                            <ns4:TaxExclusiveAmount
                                    currencyID="TRY">89.91
                            </ns4:TaxExclusiveAmount>
                            <ns4:TaxInclusiveAmount
                                    currencyID="TRY">106.09
                            </ns4:TaxInclusiveAmount>
                            <ns4:AllowanceTotalAmount currencyID="TRY">9.99</ns4:AllowanceTotalAmount>
                            <ns4:PayableRoundingAmount
                                    currencyID="TRY">0.0
                            </ns4:PayableRoundingAmount>
                            <ns4:PayableAmount
                                    currencyID="TRY">106.09
                            </ns4:PayableAmount>
                        </ns5:LegalMonetaryTotal>
                        <ns5:InvoiceLine>
                            <ns4:ID>1</ns4:ID>
                            <ns4:InvoicedQuantity unitCode="C62">1</ns4:InvoicedQuantity>
                            <ns4:LineExtensionAmount currencyID="TRY">99.9</ns4:LineExtensionAmount>
                            <ns5:Item>
                                <ns4:Name>Valhalla</ns4:Name>
                                <ns5:SellersItemIdentification>
                                    <ns4:ID>009</ns4:ID>
                                </ns5:SellersItemIdentification>
                            </ns5:Item>
                            <!--Optional:-->
                            <ns5:Price>
                                <!--Optional:-->
                                <ns4:PriceAmount currencyID="TRY">99.90</ns4:PriceAmount>
                            </ns5:Price>
                            <!--Zero or more repetitions:-->
                            <ns5:SubInvoiceLine/>
                        </ns5:InvoiceLine>
                    </ns1:Invoice>

                    <ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                        <ns1:InternetSalesInfo>
                            <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                            <ns1:PaymentMidierName>SANAL POS</ns1:PaymentMidierName>
                            <ns1:PaymentType>KREDIKARTI/BANKAKARTI</ns1:PaymentType>
                            <ns1:PaymentDate>2016-01-07</ns1:PaymentDate>
                        </ns1:InternetSalesInfo>
                    </ns1:EArchiveInvoiceInfo>
                    <ns1:Scenario>eArchive</ns1:Scenario>
                    <ns1:Notification>
                        <ns1:Mailing EnableNotification="true" To="qw@msn.com" BodyXsltIdentifier="qwer"
                                     EmailAccountIdentifier="qwer">
                        </ns1:Mailing>
                    </ns1:Notification>
                </ns1:InvoiceInfo>
            </ns1:invoices>
        </ns1:SaveAsDraft>';
        $wsdl   = "http://efatura-test.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
        $client = new SoapClient($wsdl, array(
            'soap_version' => SOAP_1_1,
            'trace' => true,
        ));


        $args = array(new \SoapVar($xml2, XSD_ANYXML));

        $headerbody = array('UsernameKey'=>array('Username'=>'1111',
            'Password'=>'1111'));

        $header = array( new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2', 'RequestorCredentials'),
                            new \SOAPHeader('http://www.w3.org/2000/09/xmldsig#', 'RequestorCredentials'),
                                new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2', 'RequestorCredentials'),
                                    new \SOAPHeader('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2', 'RequestorCredentials'));
        //dd($header);
        $client->__setSoapHeaders($header);

        $res  = $client->__soapCall('SaveAsDraft', $args);

        //$client = new SoapClient('http://efatura-test.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl');
        ////$array = [ 'Username' => 'Uyumsoft' , 'Password' => 'Uyumsoft'];
        dd($res);*/
    }

}