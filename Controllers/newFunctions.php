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

use App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Request;
use DB;
use Excel;
use Redirect;
use SoapClient;
use App\Http\Requests\insertProductRequest;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use SimpleXMLElement;
use stdClass;
use Datatables;

class newFunctions extends Controller
{
    public $site_url = 'https://bloomandfresh.com';
    //public  $site_url = 'http://188.166.86.116';
    //public $backend_url = 'http://188.166.86.116:3000';
    public $backend_url = 'https://everybloom.com';

    public function updatePlanningCourierAjax(){
        $tempIds = Request::input('saleIds');

        DB::table('sales')->whereIn('id', $tempIds )->update([
            'planning_courier_id' => Request::input('courId')
        ]);
    }

    public function produceByCategory(){

        if (Request::get('date') == 'lastweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subWeek();
            $endDate = $endDate->subWeek();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();

            $endDate = $endDate->addDays(1);
            $startDate = $startDate->addDays(1);

        } elseif (Request::get('date') == 'yesterday') {
            $startDate = Carbon::yesterday();
            $endDate = Carbon::yesterday();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
            $startDate = $startDate->subDays(7);
            $endDate = $endDate->subDays(7);
        } elseif (Request::get('date') == 'today') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
        } elseif (Request::get('date') == 'thisweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();

            $endDate = $endDate->addDays(1);
            $startDate = $startDate->addDays(1);

        } elseif (Request::get('date') == 'thismonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } elseif (Request::get('date') == 'lastmonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $endDate = $endDate->subMonths(1);
            $startDate = $startDate->subMonths(1);
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } else {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subYears(20);
            $endDate = $endDate->addYears(20);
        }

        $start_date = Request::get('start_date');
        $end_date = Request::get('end_date');
        $date = Request::get('date');

        if( Request::get('date') ){
            $start_date = explode( ' ', $startDate )[0];
            $end_date = explode( ' ', $endDate )[0];
        }

        if( Request::get('start_date') && !Request::get('date') ){
            $startDate = Request::get('start_date');
        }

        if( Request::get('end_date') && !Request::get('date') ){
            $endDate = new Carbon(Request::get('end_date'));
            $endDate->endOfDay();
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }

        if( count($cityList) == 2 ){
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = 3 ';
        }

        $tempWhere = $tempWhere . ' ) ';

        $sub_categories = DB::table('products')
            ->where('products.company_product', '=', 0)
            ->groupBy('products.product_type_sub')
            ->select('products.product_type_sub', DB::raw(' AVG(product_type) as category '))->orderBy('product_type_sub')->get();

        $total = 0;
        $flowerKesme = 0;
        $flowerToprak = 0;
        $flowerTotal = 0;
        $chocolateTotal = 0;
        $packageTotal = 0;
        $totalCrossSell = 0;

        foreach ($sub_categories as $product) {

            $numberOfCrossSell = count(DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
                ->join('cross_sell_products', 'cross_sell.product_id', '=', 'cross_sell_products.id')
                ->join('products', 'cross_sell_products.product_id', '=', 'products.id')
                ->where('products.product_type_sub', '=', $product->product_type_sub)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.wanted_delivery_date', '>', $startDate)
                ->where('deliveries.wanted_delivery_date', '<', $endDate)
                ->where('deliveries.status', '!=', '4')
                ->whereRaw($tempWhere)
                ->get());

            $numberOfSale = count(DB::table('sales_products')
                ->join('sales', 'sales_products.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('products.product_type_sub', '=', $product->product_type_sub)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.wanted_delivery_date', '>', $startDate)
                ->where('deliveries.wanted_delivery_date', '<', $endDate)
                ->where('deliveries.status', '!=', '4')
                ->whereRaw($tempWhere)
                ->get());
            $product->crossSell = $numberOfCrossSell;
            $product->saleCount = $numberOfSale;
            $total = $total + $numberOfSale;
            $totalCrossSell = $totalCrossSell + $numberOfCrossSell;

            if( $product->product_type_sub == 11 )
            {
                $flowerKesme = $flowerKesme + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 12 )
            {
                $flowerKesme = $flowerKesme + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 13 )
            {
                $flowerToprak = $flowerToprak + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 14 )
            {
                $flowerToprak = $flowerToprak + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 15 )
            {
                $flowerToprak = $flowerToprak + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 16 )
            {
                $flowerKesme = $flowerKesme + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 17 )
            {
                $flowerKesme = $flowerKesme + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 21 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 22 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 23 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 24 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 25 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 31 )
            {
                $packageTotal = $packageTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 32 )
            {
                $packageTotal = $packageTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 33 )
            {
                $packageTotal = $packageTotal + $numberOfSale;
            }
        }

        //dd($sub_categories);

        /*usort($sub_categories, function ($a, $b) {
            return -$a->saleCount + $b->saleCount;
        });*/
        $tempArray = [];
        foreach ($sub_categories as $product) {

            if( $product->saleCount > 0 ){
                array_push($tempArray, (object)$product);
            }

        }
        $sub_categories = $tempArray;

        $groupByDayFlowerToprakli = [];
        $groupByDayFlowerKesme = [];
        $groupByDayChocolate = [];
        $groupByDayBox = [];
        $totalSales = [];

        if (Request::get('date') == 'thisweek') {

            $start_date_temp = Carbon::now();
            $start_date_temp = $start_date_temp->addWeek(1)->startOfWeek();
            //($start_date_temp);
            $start_date_temp = $start_date_temp->subYears(1)->addWeek(1);

            $tempListOfWeek = DB::table('sales')
                ->where('sales.created_at', '>', $start_date_temp )
                ->groupBy(DB::raw('WEEKOFYEAR(`sales`.`created_at`)'))
                ->orderBy('sales.created_at')
                ->select( DB::raw(' YEAR(`sales`.`created_at`) as year, WEEKOFYEAR(`sales`.`created_at`) as week, count(*) as total '))->get();


            foreach ( $tempListOfWeek as $week ){

                $tempFlowerToprakliCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' ( products.product_type_sub = 13 or products.product_type_sub = 14 or products.product_type_sub = 15 )')
                    ->whereRaw(' WEEKOFYEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->week . ' and YEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->year  )
                    ->count();

                $tempFlowerKesmeCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->whereRaw($tempWhere)
                    ->where('deliveries.status', '!=', '4')
                    ->whereRaw(' ( products.product_type_sub = 11 or products.product_type_sub = 12 or products.product_type_sub = 16 or products.product_type_sub = 17 ) ')
                    ->whereRaw(' WEEKOFYEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->week . ' and YEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->year  )
                    ->count();

                $tempChocolateCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->where('products.product_type', '=', '2')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' WEEKOFYEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->week . ' and YEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->year  )
                    ->count();

                $tempBoxCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->where('products.product_type', '=', '3')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' WEEKOFYEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->week . ' and YEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->year  )
                    ->count();

                array_push($groupByDayFlowerToprakli, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempFlowerToprakliCount
                ]);

                array_push($groupByDayFlowerKesme, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempFlowerKesmeCount
                ]);

                array_push($groupByDayChocolate, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempChocolateCount
                ]);

                array_push($groupByDayBox, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempBoxCount
                ]);

                array_push($totalSales, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempBoxCount + $tempFlowerToprakliCount + $tempChocolateCount + $tempFlowerKesmeCount
                ]);

            }

        }

        if (Request::get('date') == 'thismonth') {

            $start_date_temp = Carbon::now();
            //$start_date_temp = $start_date_temp->addWeek(1)->startOfWeek();
            //($start_date_temp);
            $start_date_temp = $start_date_temp->subYears(2);

            $tempListOfWeek = DB::table('sales')
                ->where('sales.created_at', '>', $start_date_temp )
                ->groupBy(DB::raw(' YEAR(`sales`.`created_at`) ,MONTH(`sales`.`created_at`)'))
                ->orderBy('sales.created_at')
                ->select( DB::raw(' YEAR(`sales`.`created_at`) as year, MONTH(`sales`.`created_at`) as month '))->get();

            //dd($tempListOfWeek);

            foreach ( $tempListOfWeek as $week ){

                $tempFlowerToprakliCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' ( products.product_type_sub = 13 or products.product_type_sub = 14 or products.product_type_sub = 15 )')
                    ->whereRaw(' MONTH(`deliveries`.`wanted_delivery_date`) = ' . $week->month . ' and YEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->year  )
                    ->count();

                $tempFlowerKesmeCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->whereRaw($tempWhere)
                    ->where('deliveries.status', '!=', '4')
                    ->whereRaw(' ( products.product_type_sub = 11 or products.product_type_sub = 12 or products.product_type_sub = 16 or products.product_type_sub = 17 ) ')
                    ->whereRaw(' MONTH(`deliveries`.`wanted_delivery_date`) = ' . $week->month . ' and YEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->year  )
                    ->count();

                $tempChocolateCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->where('products.product_type', '=', '2')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' MONTH(`deliveries`.`wanted_delivery_date`) = ' . $week->month . ' and YEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->year  )
                    ->count();

                $tempBoxCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->where('products.product_type', '=', '3')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' MONTH(`deliveries`.`wanted_delivery_date`) = ' . $week->month . ' and YEAR(`deliveries`.`wanted_delivery_date`) = ' . $week->year  )
                    ->count();

                array_push($groupByDayFlowerToprakli, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempFlowerToprakliCount
                ]);

                array_push($groupByDayFlowerKesme, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempFlowerKesmeCount
                ]);

                array_push($groupByDayChocolate, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempChocolateCount
                ]);

                array_push($groupByDayBox, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempBoxCount
                ]);

                array_push($totalSales, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempBoxCount + $tempFlowerToprakliCount + $tempChocolateCount + $tempFlowerKesmeCount
                ]);

            }

            //dd($groupByDayFlowerKesme);
            //dd($groupByDayChocolate);

        }

        return view('admin.produceByCategories', compact('sub_categories', 'total', 'start_date', 'end_date', 'date', 'flowerKesme', 'flowerToprak', 'flowerTotal', 'chocolateTotal', 'packageTotal', 'totalCrossSell','totalSales', 'groupByDayFlowerToprakli' , 'groupByDayFlowerKesme', 'groupByDayChocolate', 'groupByDayBox'));

    }

    public function salesByCategory(){

        if (Request::get('date') == 'lastweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subWeek();
            $endDate = $endDate->subWeek();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();

        } elseif (Request::get('date') == 'yesterday') {
            $startDate = Carbon::yesterday();
            $endDate = Carbon::yesterday();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
            $startDate = $startDate->subDays(7);
            $endDate = $endDate->subDays(7);
        } elseif (Request::get('date') == 'today') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
        } elseif (Request::get('date') == 'thisweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();

        } elseif (Request::get('date') == 'thismonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } elseif (Request::get('date') == 'lastmonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $endDate = $endDate->subMonths(1);
            $startDate = $startDate->subMonths(1);
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } else {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subYears(20);
            $endDate = $endDate->addYears(20);
        }

        $start_date = Request::get('start_date');
        $end_date = Request::get('end_date');
        $date = Request::get('date');

        if( Request::get('date') ){
            $start_date = explode( ' ', $startDate )[0];
            $end_date = explode( ' ', $endDate )[0];
        }

        if( Request::get('start_date') && !Request::get('date') ){
            $startDate = Request::get('start_date');
        }

        if( Request::get('end_date') && !Request::get('date') ){
            $endDate = new Carbon(Request::get('end_date'));
            $endDate->endOfDay();
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }

        if( count($cityList) == 2 ){
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = 3 ';
        }

        $tempWhere = $tempWhere . ' ) ';

        $sub_categories = DB::table('products')
            ->where('products.company_product', '=', 0)
            ->groupBy('products.product_type_sub')
            ->select('products.product_type_sub', DB::raw(' AVG(product_type) as category '))->orderBy('product_type_sub')->get();

        $total = 0;
        $flowerKesme = 0;
        $flowerToprak = 0;
        $flowerTotal = 0;
        $chocolateTotal = 0;
        $packageTotal = 0;
        $totalCrossSell = 0;

        foreach ($sub_categories as $product) {

            $numberOfCrossSell = count(DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
                ->join('cross_sell_products', 'cross_sell.product_id', '=', 'cross_sell_products.id')
                ->join('products', 'cross_sell_products.product_id', '=', 'products.id')
                ->where('products.product_type_sub', '=', $product->product_type_sub)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.created_at', '>', $startDate)
                ->where('sales.created_at', '<', $endDate)
                ->where('deliveries.status', '!=', '4')
                ->whereRaw($tempWhere)
                ->get());

            $numberOfSale = count(DB::table('sales_products')
                ->join('sales', 'sales_products.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('products.product_type_sub', '=', $product->product_type_sub)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.created_at', '>', $startDate)
                ->where('sales.created_at', '<', $endDate)
                ->where('deliveries.status', '!=', '4')
                ->whereRaw($tempWhere)
                ->get());
            $product->crossSell = $numberOfCrossSell;
            $product->saleCount = $numberOfSale;
            $total = $total + $numberOfSale;
            $totalCrossSell = $totalCrossSell + $numberOfCrossSell;

            if( $product->product_type_sub == 11 )
            {
                $flowerKesme = $flowerKesme + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 12 )
            {
                $flowerKesme = $flowerKesme + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 13 )
            {
                $flowerToprak = $flowerToprak + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 14 )
            {
                $flowerToprak = $flowerToprak + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 15 )
            {
                $flowerToprak = $flowerToprak + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 16 )
            {
                $flowerKesme = $flowerKesme + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 17 )
            {
                $flowerKesme = $flowerKesme + $numberOfSale;
                $flowerTotal = $flowerTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 21 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 22 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 23 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 24 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 25 )
            {
                $chocolateTotal = $chocolateTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 31 )
            {
                $packageTotal = $packageTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 32 )
            {
                $packageTotal = $packageTotal + $numberOfSale;
            }
            elseif( $product->product_type_sub == 33 )
            {
                $packageTotal = $packageTotal + $numberOfSale;
            }
        }

        //dd($sub_categories);

        /*usort($sub_categories, function ($a, $b) {
            return -$a->saleCount + $b->saleCount;
        });*/
        $tempArray = [];
        foreach ($sub_categories as $product) {

            if( $product->saleCount > 0 ){
                array_push($tempArray, (object)$product);
            }

        }
        $sub_categories = $tempArray;

        $groupByDayFlowerToprakli = [];
        $groupByDayFlowerKesme = [];
        $groupByDayChocolate = [];
        $groupByDayBox = [];
        $totalSales = [];

        if (Request::get('date') == 'today') {

            /*$start_date_temp = Carbon::now();
            $start_date_temp = $start_date_temp->startOfDay();
            $start_date_temp = $start_date_temp->addDays(-60);

            $groupByDayFlowers = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.payment_methods', 'OK')
                ->where('deliveries.status', '!=', '4')
                ->where('products.product_type', '=', '1')
                ->where('sales.created_at', '>', $start_date_temp )
                ->groupBy(DB::raw('DAYOFYEAR(`sales`.`created_at`)'))
                ->select( DB::raw(' DAYOFYEAR(`sales`.`created_at`) dayNumber ,DATE_ADD( "2018-12-31" , INTERVAL DAYOFYEAR(`sales`.`created_at`) DAY)  as day ,count(*) as countNumber '))
                ->get();

            $groupByDayChocolate = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.payment_methods', 'OK')
                ->where('deliveries.status', '!=', '4')
                ->where('products.product_type', '=', '2')
                ->where('sales.created_at', '>', $start_date_temp )
                ->groupBy(DB::raw('DAYOFYEAR(`sales`.`created_at`)'))
                ->select( DB::raw(' DAYOFYEAR(`sales`.`created_at`) dayNumber ,DATE_ADD( "2018-12-31" , INTERVAL DAYOFYEAR(`sales`.`created_at`) DAY)  as day ,count(*) as countNumber '))
                ->get();

            $groupByDayBox = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.payment_methods', 'OK')
                ->where('deliveries.status', '!=', '4')
                ->where('products.product_type', '=', '3')
                ->where('sales.created_at', '>', $start_date_temp )
                ->groupBy(DB::raw('DAYOFYEAR(`sales`.`created_at`)'))
                ->select( DB::raw(' DAYOFYEAR(`sales`.`created_at`) dayNumber ,DATE_ADD( "2018-12-31" , INTERVAL DAYOFYEAR(`sales`.`created_at`) DAY)  as day ,count(*) as countNumber '))
                ->get();
            */
            //dd($groupByDay);

        }

        if (Request::get('date') == 'thisweek') {

            $start_date_temp = Carbon::now();
            $start_date_temp = $start_date_temp->addWeek(1)->startOfWeek();
            //($start_date_temp);
            $start_date_temp = $start_date_temp->subYears(1)->addWeek(1);

            $tempListOfWeek = DB::table('sales')
                ->where('sales.created_at', '>', $start_date_temp )
                ->groupBy(DB::raw('WEEKOFYEAR(`sales`.`created_at`)'))
                ->orderBy('sales.created_at')
                ->select( DB::raw(' YEAR(`sales`.`created_at`) as year, WEEKOFYEAR(`sales`.`created_at`) as week, count(*) as total '))->get();


            //dd($tempListOfWeek);

            foreach ( $tempListOfWeek as $week ){

                $tempFlowerToprakliCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' ( products.product_type_sub = 13 or products.product_type_sub = 14 or products.product_type_sub = 15 )')
                    ->whereRaw(' WEEKOFYEAR(`sales`.`created_at`) = ' . $week->week . ' and YEAR(`sales`.`created_at`) = ' . $week->year  )
                    ->count();

                $tempFlowerKesmeCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->whereRaw($tempWhere)
                    ->where('deliveries.status', '!=', '4')
                    ->whereRaw(' ( products.product_type_sub = 11 or products.product_type_sub = 12  or products.product_type_sub = 16 or products.product_type_sub = 17 ) ')
                    ->whereRaw(' WEEKOFYEAR(`sales`.`created_at`) = ' . $week->week . ' and YEAR(`sales`.`created_at`) = ' . $week->year  )
                    ->count();

                $tempChocolateCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->where('products.product_type', '=', '2')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' WEEKOFYEAR(`sales`.`created_at`) = ' . $week->week . ' and YEAR(`sales`.`created_at`) = ' . $week->year  )
                    ->count();

                $tempBoxCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->where('products.product_type', '=', '3')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' WEEKOFYEAR(`sales`.`created_at`) = ' . $week->week . ' and YEAR(`sales`.`created_at`) = ' . $week->year  )
                    ->count();

                array_push($groupByDayFlowerToprakli, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempFlowerToprakliCount
                ]);

                array_push($groupByDayFlowerKesme, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempFlowerKesmeCount
                ]);

                array_push($groupByDayChocolate, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempChocolateCount
                ]);

                array_push($groupByDayBox, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempBoxCount
                ]);

                array_push($totalSales, (object)[
                    'day' => $week->week,
                    'year' => $week->year,
                    'countNumber' => $tempBoxCount + $tempFlowerToprakliCount + $tempChocolateCount + $tempFlowerKesmeCount
                ]);

            }

            //dd($groupByDayFlowerKesme);
            //dd($groupByDayChocolate);

        }



        if (Request::get('date') == 'thismonth') {

            $start_date_temp = Carbon::now();
            //$start_date_temp = $start_date_temp->addWeek(1)->startOfWeek();
            //($start_date_temp);
            $start_date_temp = $start_date_temp->subYears(2);

            $tempListOfWeek = DB::table('sales')
                ->where('sales.created_at', '>', $start_date_temp )
                ->groupBy(DB::raw(' YEAR(`sales`.`created_at`) ,MONTH(`sales`.`created_at`)'))
                ->orderBy('sales.created_at')
                ->select( DB::raw(' YEAR(`sales`.`created_at`) as year, MONTH(`sales`.`created_at`) as month '))->get();

            //dd($tempListOfWeek);

            foreach ( $tempListOfWeek as $week ){

                $tempFlowerToprakliCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' ( products.product_type_sub = 13 or products.product_type_sub = 14 or products.product_type_sub = 15 )')
                    ->whereRaw(' MONTH(`sales`.`created_at`) = ' . $week->month . ' and YEAR(`sales`.`created_at`) = ' . $week->year  )
                    ->count();

                $tempFlowerKesmeCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->whereRaw($tempWhere)
                    ->where('deliveries.status', '!=', '4')
                    ->whereRaw(' ( products.product_type_sub = 11 or products.product_type_sub = 12 or products.product_type_sub = 16 or products.product_type_sub = 17 ) ')
                    ->whereRaw(' MONTH(`sales`.`created_at`) = ' . $week->month . ' and YEAR(`sales`.`created_at`) = ' . $week->year  )
                    ->count();

                $tempChocolateCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->where('products.product_type', '=', '2')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' MONTH(`sales`.`created_at`) = ' . $week->month . ' and YEAR(`sales`.`created_at`) = ' . $week->year  )
                    ->count();

                $tempBoxCount = DB::table('sales')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('products', 'sales_products.products_id', '=', 'products.id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('deliveries.status', '!=', '4')
                    ->where('products.product_type', '=', '3')
                    ->whereRaw($tempWhere)
                    ->whereRaw(' MONTH(`sales`.`created_at`) = ' . $week->month . ' and YEAR(`sales`.`created_at`) = ' . $week->year  )
                    ->count();

                array_push($groupByDayFlowerToprakli, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempFlowerToprakliCount
                ]);

                array_push($groupByDayFlowerKesme, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempFlowerKesmeCount
                ]);

                array_push($groupByDayChocolate, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempChocolateCount
                ]);

                array_push($groupByDayBox, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempBoxCount
                ]);

                array_push($totalSales, (object)[
                    'month' => $week->month,
                    'year' => $week->year,
                    'countNumber' => $tempBoxCount + $tempFlowerToprakliCount + $tempChocolateCount + $tempFlowerKesmeCount
                ]);

            }

            //dd($groupByDayFlowerKesme);
            //dd($groupByDayChocolate);

        }

        return view('admin.salesByCategories', compact('sub_categories', 'total', 'start_date', 'totalSales' ,'end_date', 'date', 'flowerKesme', 'flowerToprak', 'flowerTotal', 'chocolateTotal', 'packageTotal', 'totalCrossSell', 'groupByDayFlowerToprakli' , 'groupByDayFlowerKesme', 'groupByDayChocolate', 'groupByDayBox'));

    }

    public function produceByProduct(){

        if (Request::get('date') == 'lastweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subWeek();
            $endDate = $endDate->subWeek();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();

            $endDate = $endDate->addDays(1);
            $startDate = $startDate->addDays(1);

        } elseif (Request::get('date') == 'yesterday') {
            $startDate = Carbon::yesterday();
            $endDate = Carbon::yesterday();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
            $startDate = $startDate->subDays(7);
            $endDate = $endDate->subDays(7);
        } elseif (Request::get('date') == 'today') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
        } elseif (Request::get('date') == 'thisweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();

            $endDate = $endDate->addDays(1);
            $startDate = $startDate->addDays(1);

            //dd($startDate);

        } elseif (Request::get('date') == 'thismonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } elseif (Request::get('date') == 'lastmonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $endDate = $endDate->subMonths(1);
            $startDate = $startDate->subMonths(1);
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } else {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subYears(20);
            $endDate = $endDate->addYears(20);
        }

        $category = Request::get('category');
        $sub_category = Request::get('sub_category');
        $start_date = Request::get('start_date');
        $end_date = Request::get('end_date');
        $date = Request::get('date');

        if( Request::get('category') ){
            $categoryList = [Request::get('category')];
        }
        else{
            $categoryList = ['1', '2', '3'];
        }

        if( Request::get('sub_category') ){
            $subCategoryList = [Request::get('sub_category')];
        }
        else{
            $subCategoryList = [ '0', '11', '12','13', '14', '15', '16', '17' ,'21', '22', '23', '24' , '25' , '31' , '32', '33' ];
        }

        if( Request::get('date') ){
            $start_date = explode( ' ', $startDate )[0];
            $end_date = explode( ' ', $endDate )[0];
        }

        if( Request::get('start_date') && !Request::get('date') ){
            $startDate = Request::get('start_date');
        }

        if( Request::get('end_date') && !Request::get('date') ){
            $endDate = new Carbon(Request::get('end_date'));
            $endDate->endOfDay();
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }

        if( count($cityList) == 2 ){
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = 3 ';
        }

        $tempWhere = $tempWhere . ' ) ';

        $products = DB::table('products')
            ->join('images', 'products.id', '=', 'images.products_id')
            ->where('products.company_product', '=', 0)
            ->whereIn('products.product_type', $categoryList)
            ->whereIn('products.product_type_sub', $subCategoryList)
            ->where('type', '=', 'main')->select('products.name', 'products.id')->get();

        $total = 0;

        foreach ($products as $product) {

            $numberOfSale = count(DB::table('sales_products')
                ->join('sales', 'sales_products.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('products_id', '=', $product->id)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.wanted_delivery_date', '>', $startDate)
                ->where('deliveries.wanted_delivery_date', '<', $endDate)
                ->where('deliveries.status', '!=', '4')
                ->whereRaw($tempWhere)
                ->get());
            $product->saleCount = $numberOfSale;
            $total = $total + $numberOfSale;
        }


        usort($products, function ($a, $b) {
            return -$a->saleCount + $b->saleCount;
        });
        $tempArray = [];
        foreach ($products as $product) {

            if( $product->saleCount > 0 ){
                array_push($tempArray, (object)$product);
            }

        }
        $products = $tempArray;

        return view('admin.produceByProduct', compact('products', 'total', 'category', 'sub_category', 'start_date', 'end_date', 'date'));

    }

    public function salesByProduct()
    {
        if (Request::get('date') == 'lastweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subWeek();
            $endDate = $endDate->subWeek();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();
        } elseif (Request::get('date') == 'yesterday') {
            $startDate = Carbon::yesterday();
            $endDate = Carbon::yesterday();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
            $startDate = $startDate->subDays(7);
            $endDate = $endDate->subDays(7);
        } elseif (Request::get('date') == 'today') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
        } elseif (Request::get('date') == 'thisweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();
        } elseif (Request::get('date') == 'thismonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } elseif (Request::get('date') == 'lastmonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $endDate = $endDate->subMonths(1);
            $startDate = $startDate->subMonths(1);
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } else {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subYears(20);
            $endDate = $endDate->addYears(20);
        }

        $category = Request::get('category');
        $sub_category = Request::get('sub_category');
        $start_date = Request::get('start_date');
        $end_date = Request::get('end_date');
        $date = Request::get('date');

        if( Request::get('date') ){
            $start_date = explode( ' ', $startDate )[0];
            $end_date = explode( ' ', $endDate )[0];
        }

        if( Request::get('category') ){
            $categoryList = [Request::get('category')];
        }
        else{
            $categoryList = ['1', '2', '3'];
        }

        if( Request::get('sub_category') ){
            $subCategoryList = [Request::get('sub_category')];
        }
        else{
            $subCategoryList = [ '0', '11', '12','13', '14', '15','16', '17' ,'21', '22', '23', '24', '25' , '31' , '32', '33' ];
        }

        if( Request::get('start_date') && !Request::get('date') ){
            $startDate = Request::get('start_date');
        }

        if( Request::get('end_date') && !Request::get('date') ){
            $endDate = new Carbon(Request::get('end_date'));
            $endDate->endOfDay();
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }

        if( count($cityList) == 2 ){
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = 3 ';
        }

        $tempWhere = $tempWhere . ' ) ';

        $products = DB::table('products')
            ->join('images', 'products.id', '=', 'images.products_id')
            ->where('products.company_product', '=', 0)
            ->whereIn('products.product_type', $categoryList)
            ->whereIn('products.product_type_sub', $subCategoryList)
            ->where('type', '=', 'main')->select('products.name', 'products.id')->get();

        $total = 0;

        foreach ($products as $product) {

            $numberOfSale = count(DB::table('sales_products')
                ->join('sales', 'sales_products.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('products_id', '=', $product->id)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.created_at', '>', $startDate)
                ->where('sales.created_at', '<', $endDate)
                ->where('deliveries.status', '!=', '4')
                ->whereRaw($tempWhere)
                ->get());
            $product->saleCount = $numberOfSale;
            $total = $total + $numberOfSale;
        }


        usort($products, function ($a, $b) {
            return -$a->saleCount + $b->saleCount;
        });
        $tempArray = [];
        foreach ($products as $product) {

            if( $product->saleCount > 0 ){
                array_push($tempArray, (object)$product);
            }

        }
        $products = $tempArray;

        return view('admin.salesByProduct', compact('products', 'total', 'category', 'sub_category', 'start_date', 'end_date', 'date'));

    }

    public function crossSellSales(){
        if (Request::get('date') == 'lastweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subWeek();
            $endDate = $endDate->subWeek();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();
        } elseif (Request::get('date') == 'yesterday') {
            $startDate = Carbon::yesterday();
            $endDate = Carbon::yesterday();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
            $startDate = $startDate->subDays(7);
            $endDate = $endDate->subDays(7);
        } elseif (Request::get('date') == 'today') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
        } elseif (Request::get('date') == 'thisweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();
        } elseif (Request::get('date') == 'thismonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } elseif (Request::get('date') == 'lastmonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $endDate = $endDate->subMonths(1);
            $startDate = $startDate->subMonths(1);
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } else {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subYears(20);
            $endDate = $endDate->addYears(20);
        }

        $start_date = Request::get('start_date');
        $end_date = Request::get('end_date');
        $date = Request::get('date');

        if( Request::get('date') ){
            $start_date = explode( ' ', $startDate )[0];
            $end_date = explode( ' ', $endDate )[0];
        }

        if( Request::get('start_date') && !Request::get('date') ){
            $startDate = Request::get('start_date');
        }

        if( Request::get('end_date') && !Request::get('date') ){
            $endDate = new Carbon(Request::get('end_date'));
            $endDate->endOfDay();
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }

        if( count($cityList) == 2 ){
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = 3 ';
        }

        $tempWhere = $tempWhere . ' ) ';

        $products = DB::table('cross_sell_products')
            ->select('cross_sell_products.name', 'cross_sell_products.price', 'cross_sell_products.id')->get();

        $total = 0;

        foreach ($products as $product) {

            $numberOfSale = DB::table('cross_sell')
                ->join('sales', 'cross_sell.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('cross_sell.product_id', '=', $product->id)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.created_at', '>', $startDate)
                ->where('sales.created_at', '<', $endDate)
                ->where('deliveries.status', '!=', '4')
                ->whereRaw($tempWhere)
                ->count();
            $product->saleCount = $numberOfSale;
            $total = $total + $numberOfSale;
        }

        $sales = DB::table('cross_sell')
            ->join('cross_sell_products', 'cross_sell.product_id', '=', 'cross_sell_products.id')
            ->join('sales', 'cross_sell.sales_id', '=', 'sales.id')
            ->join('sales_products', 'sales_products.sales_id', '=', 'sales.id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.created_at', '>', $startDate)
            ->where('sales.created_at', '<', $endDate)
            ->where('deliveries.status', '!=', '4')
            ->whereRaw($tempWhere)
            ->select('sales.id', 'sales.created_at', 'cross_sell_products.name as crossSell', 'products.name')
            ->get();

        usort($products, function ($a, $b) {
            return -$a->saleCount + $b->saleCount;
        });
        $tempArray = [];
        foreach ($products as $product) {

            if( $product->saleCount > 0 ){
                array_push($tempArray, (object)$product);
            }

        }
        $products = $tempArray;

        return view('admin.CrossSellSales', compact('products', 'total', 'category', 'sub_category', 'start_date', 'end_date', 'date', 'sales'));
    }

    public function StatusUpdateType()
    {

        if (Request::get("secretToken") == 'hx_s2fhJ=0fbKf23KgnKgy2u4wq5') {

            if (Request::get("deliveryState") == 'DELIVERED') {

                DB::table('scotty_sales')->where('sale_id', Request::get("deliveryNumber"))->update([
                    'delivery_date' => Request::get("stateChangeDate")
                ]);
            } else if (Request::get("deliveryState") == 'ON_DELIVERY') {
                DB::table('scotty_sales')->where('sale_id', Request::get("deliveryNumber"))->update([
                    'pick_up_date' => Request::get("stateChangeDate")
                ]);
            }

            //dd(Request::get("deliveryNumber"));

            DB::table('scotty_sales')->where('sale_id', Request::get("deliveryNumber"))->update([
                'scotty_status_name' => Request::get("deliveryState"),
                'live_link' => Request::get("trackingLink")
            ]);

            return response()->json(['status' => 1], 200);
        }

    }

    public function sendScottyRequest()
    {

        $tempAllRequest = Request::all();

        $onlySales = [];

        //dd($tempAllRequest['note_176193']);

        foreach ($tempAllRequest as $key => $sale) {
            if ($key != '_token') {

                if ($sale == 'on') {
                    array_push($onlySales, explode("_", $key)[1]);
                }

            }
        }

        $onlySales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->whereIn('sales.id', $onlySales)
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address', 'customer_contacts.mobile',
                'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->get();

        foreach ($onlySales as $delivery) {

            $tempNote = $tempAllRequest['note_' . $delivery->sale_id];

            //$tempStartDate = '2019-05-22T19:00:00';
            //$tempEndDate = '2019-05-22T19:00:00';

            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');

            $tempStart = Carbon::now();
            $tempStart->addMinute(15);
            $tempStart = $tempStart->format('Y-m-d\TH:i:s');

            $tempEnd = new Carbon($delivery->wanted_delivery_limit);
            $tempEnd->subHours(2);
            //dd($tempEnd);
            $tempEnd = $tempEnd->format('Y-m-d\TH:i:s');

            //dd($tempStart);

            $tempContent = '{ 
                "deliveryNumber": "' . $delivery->sale_id . '",
                "pickUpStartDate": "' . $tempStart . '",
                "deliveryEndDate": "' . $tempEnd . '",
                "description": "' . $tempNote . '",
                "sender": 
                {
                    "name": "Bloom and Fresh",
                    "email": "hello@bloomandfresh.com",
                    "address": "Bloom and Fresh, Emirgan, Boyacıçeşme Sok. No:12, 34467 Sarıyer/İstanbul",
                    "district": "Sarıyer",
                    "city": "İstanbul",
                    "latitude": "",
                    "longitude": "",
                    "phone": "902122120282"
                },
                "receiver": 
                {
                    "name": "' . $delivery->contact_name . ' ' . $delivery->contact_surname . '",
                    "email": "hello@bloomandfresh.com",
                    "address": "' . str_replace("\n", '', $delivery->receiver_address) . '",
                    "district": "' . $delivery->district . '",
                    "city": "İstanbul",
                    "latitude": "",
                    "longitude": "",
                    "phone": "902122120282"
                }
            }';
            //dd($delivery->receiver_address);

            try {

                if (DB::table('scotty_sales')->where('sale_id', $delivery->sale_id)->count() == 0) {

                    $opts = array(
                        'http' => array(
                            'method' => "POST",
                            'header' => "x-api-key: 9TlfZbVnnV9bdBqksX2nclletfqLWc9t" . "\r\n" .
                                "Content-Type: application/json\r\n",
                            'content' => $tempContent
                        )
                    );

                    $context = stream_context_create($opts);

                    $file = file_get_contents('https://package.usescotty.com/api/v1/packages/deliveries', false, $context);

                    DB::table('scotty_sales')->insert([
                        'sale_id' => $delivery->sale_id,
                        'ekstraNote' => $tempNote
                    ]);

                }

            } catch (\Exception $e) {
                dd($e);
                //continue;
            }
        }

        return redirect('/sendScottyDelivery');

    }

    public function scottyFilter()
    {

        $tempStart = Carbon::now();
        $tempStart->startOfDay();

        $tempEnd = '';

        $tempQuery = ' ( 1 = 1  ';

        if (Request::get('created_at')) {

            $tempStart = new Carbon(Request::get('created_at'));
            $tempStart->startOfDay();


            $tempQuery = $tempQuery . ' and deliveries.wanted_delivery_date > "' . $tempStart . '"';
            //dd($tempQuery);
        }

        if (Request::get('created_at_end')) {

            $tempEnd = new Carbon(Request::get('created_at_end'));
            $tempEnd->endOfDay();

            $tempQuery = $tempQuery . ' and deliveries.wanted_delivery_date < "' . $tempEnd . '"';
        }

        if (Request::get('planning_courier_id') != 'Hepsi') {

            $tempQuery = $tempQuery . ' and sales.planning_courier_id = "' . Request::get('planning_courier_id') . '"';
        }

        $tempQuery = $tempQuery . ' ) ';

        //dd($tempQuery);

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $today = Carbon::now();
        $today->startOfDay();

        //

        $salesWithScotty = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->whereRaw($tempWhere)
            ->whereRaw($tempQuery)
            ->where('deliveries.status', '!=', '4')
            ->where('deliveries.status', '!=', '3')
            ->where('deliveries.status', '!=', '2')
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address', 'sales.planning_courier_id',
                'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id');

        $sales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('scotty_sales', 'sales.id', '=', 'scotty_sales.sale_id')
            ->where('sales.payment_methods', '=', 'OK')
            ->whereRaw($tempWhere)
            ->whereRaw($tempQuery)
            //->where('deliveries.status', '!=', '4')
            ->whereRaw(' deliveries.status = 2 ')
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address', 'sales.planning_courier_id',
                'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->union($salesWithScotty)
            ->get();

        //dd($sales);

        //


        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');


        foreach ($sales as $sale) {

            $requestDate = new Carbon($sale->created_at);
            $dateInfo = $requestDate->formatLocalized('%a %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            $sale->requestDate = $dateInfo;

            if ($sale->delivery_date == "0000-00-00 00:00:00") {
                $sale->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($sale->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $sale->deliveryDate = $dateInfo;
            }

            $sale->scottyInfo = DB::table('scotty_sales')->where('sale_id', $sale->sale_id)->get();

            $tempCikolat = AdminPanelController::getCikolatData($sale->sale_id);

            if ($tempCikolat) {
                $sale->cikilot = $tempCikolat->name;
            } else
                $sale->cikilot = "";

            $wantedDeliveryDate = new Carbon($sale->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($sale->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $sale->wantedDeliveryDate = $dateInfo;

            if ($sale->planning_courier_id != 0) {
                $sale->courName = DB::table('operation_person')->where('id', $sale->planning_courier_id)->get()[0]->name;
            } else {
                $sale->courName = '';
            }

        }

        //dd($sales);
        $operationList = DB::table('operation_person')->where('active', '=', 1)->orderBy('position')->get();
        $queryParams = (object)['created_at_end' => "", 'created_at' => "2019-04-22", 'planning_courier_id' => Request::get('planning_courier_id')];

        return view('admin.scottyDeliveries', compact('sales', 'queryParams', 'operationList'));

    }

    public function sendScottyDelivery()
    {

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $today = Carbon::now();
        $today->startOfDay();

        $salesWithScotty = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->whereRaw($tempWhere)
            ->where('deliveries.status', '!=', '4')
            ->where('deliveries.status', '!=', '3')
            ->where('deliveries.status', '!=', '2')
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address', 'sales.planning_courier_id',
                'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id');

        $sales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('scotty_sales', 'sales.id', '=', 'scotty_sales.sale_id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->whereRaw($tempWhere)
            //->where('deliveries.status', '!=', '4')
            //->whereRaw(' ( deliveries.status = 3 or deliveries.status = 2 ) ')
            ->where('deliveries.status', '=', '2')
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address', 'sales.planning_courier_id',
                'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->union($salesWithScotty)
            //->orderBy('deliveries.wanted_delivery_date')
            ->get();

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');


        foreach ($sales as $sale) {

            $requestDate = new Carbon($sale->created_at);
            $dateInfo = $requestDate->formatLocalized('%a %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            $sale->requestDate = $dateInfo;

            if ($sale->delivery_date == "0000-00-00 00:00:00") {
                $sale->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($sale->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $sale->deliveryDate = $dateInfo;
            }

            $sale->scottyInfo = DB::table('scotty_sales')->where('sale_id', $sale->sale_id)->get();

            $tempCikolat = AdminPanelController::getCikolatData($sale->sale_id);

            if ($tempCikolat) {
                $sale->cikilot = $tempCikolat->name;
            } else
                $sale->cikilot = "";

            $wantedDeliveryDate = new Carbon($sale->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($sale->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $sale->wantedDeliveryDate = $dateInfo;

            if ($sale->planning_courier_id != 0) {
                $sale->courName = DB::table('operation_person')->where('id', $sale->planning_courier_id)->get()[0]->name;
            } else {
                $sale->courName = '';
            }

        }

        //dd($sales);
        $operationList = DB::table('operation_person')->where('active', '=', 1)->orderBy('position')->get();
        $queryParams = (object)['created_at_end' => "", 'created_at' => "2019-04-22", 'planning_courier_id' => 'hepsi'];

        return view('admin.scottyDeliveries', compact('sales', 'queryParams', 'operationList'));

    }

    public function manageCategory()
    {
        $allRequest = Request::all();
        //dd();

        foreach ($allRequest as $key => $item) {
            $tempArray = explode("_", $key);

            if (count($tempArray) > 2) {

                $type = $tempArray[1];
                $productId = $tempArray[2];
                $tagId = $tempArray[3];

                if ($type == 'tag') {

                    if ($item == 'true') {

                        if (DB::table('products_tags')->where('tags_id', $tagId)->where('products_id', $productId)->count() == 0) {
                            DB::table('products_tags')->insert([
                                'tags_id' => $tagId,
                                'products_id' => $productId
                            ]);
                        }
                    } else {
                        DB::table('products_tags')->where('tags_id', $tagId)->where('products_id', $productId)->delete();
                    }
                } else {
                    if ($item == 'true') {

                        if (DB::table('page_flower_production')->where('page_id', $tagId)->where('product_id', $productId)->count() == 0) {
                            DB::table('page_flower_production')->insert([
                                'page_id' => $tagId,
                                'product_id' => $productId
                            ]);
                        }
                    } else {
                        DB::table('page_flower_production')->where('page_id', $tagId)->where('product_id', $productId)->delete();
                    }
                }


            }
        }


        return redirect('/admin/manage-flower-category');
    }

    public function manageFlowerCategoryFilterCategory($category_id, $type)
    {

        if ($type == 1) {
            $activeFlowers = DB::table('products')
                ->join('product_city', 'products.id', '=', 'product_city.product_id')
                ->join('tags', 'products.tag_id', '=', 'tags.id')
                ->join('products_tags', 'products.id', '=', 'products_tags.products_id')
                ->where('products.city_id', 1)
                ->where('tags.lang_id', 'tr')
                ->where('products.company_product', 0)
                ->where('product_city.city_id', 1)
                ->where('products_tags.tags_id', $category_id)
                ->where('product_city.activation_status_id', 1)
                ->select('products.name', 'products.id', 'tags.tags_name', 'tags.id as tag_id')
                ->orderBy('products.name')
                ->get();
        } else {
            $activeFlowers = DB::table('products')
                ->join('product_city', 'products.id', '=', 'product_city.product_id')
                ->join('tags', 'products.tag_id', '=', 'tags.id')
                ->join('page_flower_production', 'products.id', '=', 'page_flower_production.product_id')
                ->where('products.city_id', 1)
                ->where('tags.lang_id', 'tr')
                ->where('products.company_product', 0)
                ->where('product_city.city_id', 1)
                ->where('page_flower_production.page_id', $category_id)
                ->where('product_city.activation_status_id', 1)
                ->select('products.name', 'products.id', 'tags.tags_name', 'tags.id as tag_id')
                ->orderBy('products.name')
                ->get();
        }

        foreach ($activeFlowers as $flower) {
            $flower->pageList = DB::table('flowers_page')
                ->where('flowers_page.active', 1)
                ->select('flowers_page.id', 'flowers_page.head', DB::raw(' (select count(*) from page_flower_production where flowers_page.id = page_flower_production.page_id and page_flower_production.product_id = "' . $flower->id . '"  ) as activePage'))
                ->get();

            $flower->tagList = DB::table('tags')
                ->where('tags.lang_id', 'tr')
                ->select('tags.id', 'tags.tags_name', DB::raw(' (select count(*) from products_tags where tags.id = products_tags.tags_id and tags.lang_id = "tr" and products_tags.products_id = "' . $flower->id . '"  ) as activePage'))
                ->get();

        }

        return view('admin.manageFlowerCategory', compact('activeFlowers', 'category_id', 'type'));
    }

    public function manageFlowerCategory()
    {

        $activeFlowers = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->join('tags', 'products.tag_id', '=', 'tags.id')
            ->where('products.city_id', 1)
            ->where('tags.lang_id', 'tr')
            ->where('products.company_product', 0)
            ->where('product_city.city_id', 1)
            ->where('product_city.activation_status_id', 1)
            ->select('products.name', 'products.id', 'tags.tags_name', 'tags.id as tag_id')
            ->orderBy('products.name')
            ->get();

        foreach ($activeFlowers as $flower) {
            $flower->pageList = DB::table('flowers_page')
                ->where('flowers_page.active', 1)
                ->select('flowers_page.id', 'flowers_page.head', DB::raw(' (select count(*) from page_flower_production where flowers_page.id = page_flower_production.page_id and page_flower_production.product_id = "' . $flower->id . '"  ) as activePage'))
                ->get();

            $flower->tagList = DB::table('tags')
                ->where('tags.lang_id', 'tr')
                ->select('tags.id', 'tags.tags_name', DB::raw(' (select count(*) from products_tags where tags.id = products_tags.tags_id and tags.lang_id = "tr" and products_tags.products_id = "' . $flower->id . '"  ) as activePage'))
                ->get();

        }

        $category_id = 0;
        $type = 0;

        return view('admin.manageFlowerCategory', compact('activeFlowers', 'category_id', 'type'));
    }

    public function updateOperationPerson()
    {
        //return response()->json(["status" => Request::all()], 200);

        $tempValue = Request::input('value');

        if ($tempValue == 'true') {
            $tempValue = 1;
        }

        DB::table('operation_person')->where('id', Request::input('operationId'))->update([
            Request::input('data') => $tempValue
        ]);

        return response()->json(["status" => Request::all()], 200);

    }

    public function operationsPerson()
    {

        $operationPerson = DB::table('operation_person')->orderBy('active', 'DESC')->orderBy('position')->get();

        return view('admin.operationModify', compact('operationPerson'));

    }

    public function deleteDailyCoupon($id)
    {

        DB::table('daily_coupons')->where('id', $id)->delete();

        return redirect('/admin/dailyCoupons');
    }

    public function insertDailyCoupon()
    {
        //dd(Request::all());
        //dd(Request::all());

        $tempTimes = explode(" - ", Request::input('timeRange'));
        $tempStartTime = $tempTimes[0];
        $startTime = Carbon::now();
        $startTime->hour(explode(':', explode(" ", $tempStartTime)[1])[0]);
        $startTime->minute(explode(':', explode(" ", $tempStartTime)[1])[1]);
        $startTime->second(00);
        $startTime->year(explode('/', explode(" ", $tempStartTime)[0])[2]);
        $startTime->month(explode('/', explode(" ", $tempStartTime)[0])[1]);
        $startTime->day(explode('/', explode(" ", $tempStartTime)[0])[0]);

        $tempEndTime = $tempTimes[1];
        $endTime = Carbon::now();
        $endTime->hour(explode(':', explode(" ", $tempEndTime)[1])[0]);
        $endTime->minute(explode(':', explode(" ", $tempEndTime)[1])[1]);
        $endTime->second(00);
        $endTime->year(explode('/', explode(" ", $tempEndTime)[0])[2]);
        $endTime->month(explode('/', explode(" ", $tempEndTime)[0])[1]);
        $endTime->day(explode('/', explode(" ", $tempEndTime)[0])[0]);

        DB::table('daily_coupons')->insert([
            'name' => Request::input('name'),
            'description' => Request::input('description'),
            'value' => Request::input('value'),
            'type' => Request::input('type'),
            'code' => Request::input('code'),
            'using_count' => Request::input('using_count'),
            'start_date' => $startTime,
            'end_date' => $endTime,
            'active' => 1
        ]);

        return redirect('/admin/dailyCoupons');
    }

    public function createDailyCoupons()
    {

        return view('admin.createDailyCoupons');
    }

    public function dailyCoupons()
    {
        $coupons = DB::table('daily_coupons')->orderBy('created_at', 'DESC')->get();

        foreach ($coupons as $coupon) {
            $coupon->total = DB::table('marketing_acts')->where('daily_coupon_id', $coupon->id)->count();
            $coupon->used = DB::table('marketing_acts')->where('daily_coupon_id', $coupon->id)->where('used', 1)->count();
        }

        return view('admin.dailyCoupons', compact('coupons'));
    }

    public function updateUser()
    {

        if (DB::table('users')->where('id', '!=', Request::input('id'))->where('email', Request::input('email'))->count() > 0) {

            $user = DB::table('users')->where('id', Request::input('id'))->get();

            if (count($user) == 0) {
                dd('anonim kullanici');
            } else {
                $userInfo = $user[0];
            }

            $userInfo->error = 'Email adresi başka bir kullanıcı tarafından kullanılıyor.';

            return view('admin.userDetail', compact('userInfo'));
        } else {

            DB::table('users')->where('id', Request::input('id'))->update([
                'name' => Request::input('name'),
                'surname' => Request::input('surname'),
                'email' => Request::input('email')
            ]);

            return redirect('/admin/user/' . Request::input('id'));
        }

    }

    public function detailUser($id)
    {

        $user = DB::table('users')->where('id', $id)->get();

        if (count($user) == 0) {
            dd('anonim kullanici');
        } else {
            $userInfo = $user[0];
        }

        $userInfo->error = '';

        return view('admin.userDetail', compact('userInfo'));
    }

    public function goProductDetail($id)
    {

        $product = DB::table('products')->where('id', $id)->get()[0];
        $mainTagId = $product->tag_id;
        $productUrl = $product->url_parametre;

        $tempTagParametre = DB::table('tags')->where('id', $mainTagId)->where('lang_id', 'tr')->get()[0]->tag_ceo;

        return redirect('https://bloomandfresh.com/' . $tempTagParametre . '/' . $productUrl . '-' . $id);
    }

    public function productOrder()
    {

        //dd(Request::get('date'));


        if (Request::get('date') == 'lastweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subWeek();
            $endDate = $endDate->subWeek();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();
        } elseif (Request::get('date') == 'yesterday') {
            $startDate = Carbon::yesterday();
            $endDate = Carbon::yesterday();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
        } elseif (Request::get('date') == 'today') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();
        } elseif (Request::get('date') == 'thisweek') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfWeek();
            $endDate = $endDate->endOfWeek();
        } elseif (Request::get('date') == 'thismonth') {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->startOfMonth();
            $endDate = $endDate->endOfMonth();
        } else {
            $startDate = Carbon::now();
            $endDate = Carbon::now();
            $startDate = $startDate->subYears(20);
            $endDate = $endDate->addYears(20);
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $products = DB::table('products')
            ->join('images', 'products.id', '=', 'images.products_id')
            ->where('products.company_product', '=', 0)
            ->where('type', '=', 'main')->select('products.name', 'products.price', 'products.id', 'images.image_url', 'products.company_product')->get();

        foreach ($products as $product) {

            $numberOfSale = count(DB::table('sales_products')
                ->join('sales', 'sales_products.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('products_id', '=', $product->id)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.created_at', '>', $startDate)
                ->where('sales.created_at', '<', $endDate)
                ->where('deliveries.status', '!=', '4')
                ->whereRaw($tempWhere)
                ->get());
            $product->saleCount = $numberOfSale;
        }


        usort($products, function ($a, $b) {
            return -$a->saleCount + $b->saleCount;
        });
        $tempArray = [];
        foreach ($products as $product) {
            array_push($tempArray, (object)$product);
        }
        $products = $tempArray;

        return view('admin.orderProductCount', compact('products'));
    }

    public function dropLandingPageWithPromo($type, $id, $promo_id)
    {

        $promo_id = explode("_", $promo_id)[1];

        $tempPromo = DB::table('landing_with_promo')->where('id', $promo_id)->get()[0];

        DB::table('landing_with_promo')->where('order', '>', $tempPromo->order)->increment('order');

        if ($type == 'flower') {
            $tempFlower = DB::table('products')->where('id', $id)->get()[0];
            $tempFlowerImage = DB::table('images')->where('products_id', '=', $id)->where('type', 'main')->get()[0]->image_url;

            DB::table('landing_with_promo')->insert([
                'type' => 1,
                'image' => $tempFlowerImage,
                'order' => $tempPromo->order + 1,
                'name' => $tempFlower->name,
                'city_id' => $tempPromo->city_id,
                'product_id' => $id,
                'promo_id' => 0,
                'length' => 1
            ]);

        } else {
            $tempPromoLanding = DB::table('landing_promo')->where('id', $id)->get()[0];

            DB::table('landing_with_promo')->insert([
                'type' => 2,
                'image' => $tempPromoLanding->background_image,
                'order' => $tempPromo->order + 1,
                'name' => $tempPromoLanding->title_small . ' ' . $tempPromoLanding->title,
                'city_id' => $tempPromo->city_id,
                'product_id' => 0,
                'promo_id' => $tempPromoLanding->id,
                'length' => $tempPromoLanding->width
            ]);

        }

        return redirect('/admin/landingPageWithPromo');
    }

    public function changeLandingPageWithPromo($type, $id, $promo_id)
    {

        $tempPromo = DB::table('landing_with_promo')->where('id', $promo_id)->get()[0];

        if ($type == 'flower') {
            $tempFlower = DB::table('products')->where('id', $id)->get()[0];
            $tempFlowerImage = DB::table('images')->where('products_id', '=', $id)->where('type', 'main')->get()[0]->image_url;

            DB::table('landing_with_promo')->insert([
                'type' => 1,
                'image' => $tempFlowerImage,
                'order' => $tempPromo->order,
                'name' => $tempFlower->name,
                'city_id' => $tempPromo->city_id,
                'product_id' => $id,
                'promo_id' => 0,
                'length' => 1
            ]);

            DB::table('landing_with_promo')->where('id', $promo_id)->delete();
        } else {
            $tempPromoLanding = DB::table('landing_promo')->where('id', $id)->get()[0];

            DB::table('landing_with_promo')->insert([
                'type' => 2,
                'image' => $tempPromoLanding->background_image,
                'order' => $tempPromo->order,
                'name' => $tempPromoLanding->title_small . ' ' . $tempPromoLanding->title,
                'city_id' => $tempPromo->city_id,
                'product_id' => 0,
                'promo_id' => $tempPromoLanding->id,
                'length' => $tempPromoLanding->width
            ]);

            DB::table('landing_with_promo')->where('id', $promo_id)->delete();
        }

        return redirect('/admin/landingPageWithPromo');
    }

    public function removeLandingPageWithPromo($id)
    {
        DB::table('landing_with_promo')->where('id', $id)->delete();

        return redirect('/admin/landingPageWithPromo');
    }

    public function updateOrderProductBetweenLanding()
    {
        $fromID = Request::input('fromId');
        $toID = Request::input('toPlace');
        DB::table('landing_with_promo')->where('order', '>', $toID)->increment('order');
        DB::table('landing_with_promo')->where('id', $fromID)->update([
            'order' => intval($toID) + 1
        ]);

        return redirect('/admin/landingPageWithPromo');
    }

    public function updateLandingPromo()
    {
        $fromOrder = DB::table('landing_with_promo')->where('id', Request::input('fromId'))->select('order')->get()[0]->order;
        $toOrder = DB::table('landing_with_promo')->where('id', Request::input('toId'))->select('order')->get()[0]->order;
        DB::table('landing_with_promo')->where('id', Request::input('fromId'))->update([
            'order' => $toOrder
        ]);
        DB::table('landing_with_promo')->where('id', Request::input('toId'))->update([
            'order' => $fromOrder
        ]);
        return response()->json(["status" => 1], 200);
    }

    public function landingPageWithPromo()
    {
        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or product_city.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        if (count($cityList) > 1) {
            dd('Şehir seçmeden işlem yapamazsınız!');
        }

        $flowerList = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            //->join('landing_products_times', 'products.id', '=', 'landing_products_times.product_id')
            ->whereRaw($tempWhere)
            ->where('company_product', '=', '0')
            ->where('product_city.activation_status_id', '=', 1)
            ->where('product_city.active', '=', 1)
            ->select('product_city.coming_soon', 'product_city.limit_statu', 'products.id', 'products.name', 'products.price', 'product_city.landing_page_order',
                DB::raw('(select count(*) from landing_with_promo where products.id = landing_with_promo.product_id and landing_with_promo.city_id = ' . $cityList[0]->city_id . '  ) as landingNumber'))
            ->orderBy('product_city.landing_page_order')
            ->get();

        for ($x = 0; $x < count($flowerList); $x++) {
            $flowerList[$x]->image = DB::table('images')
                ->where('products_id', '=', $flowerList[$x]->id)
                ->where('type', 'main')
                ->select('type', 'image_url')
                ->orderBy('order_no')
                ->get()[0]->image_url;
        }

        $tempWherePromo = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWherePromo = $tempWherePromo . ' or landing_promo_city.city_id = ' . $city->city_id;
        }
        $tempWherePromo = $tempWherePromo . ' ) ';

        $promoList = DB::table('landing_promo')
            ->join('landing_promo_city', 'landing_promo.id', '=', 'landing_promo_city.landing_promo_id')
            ->whereRaw($tempWherePromo)
            ->where('landing_promo_city.active', 1)
            ->select('landing_promo.title', 'landing_promo.temp_name', 'landing_promo.title_small', 'landing_promo.background_image', 'landing_promo.id',
                DB::raw('(select count(*) from landing_with_promo where landing_promo.id = landing_with_promo.promo_id and landing_with_promo.city_id = ' . $cityList[0]->city_id . '  ) as landingNumber'))
            ->get();

        //dd($promoList);

        $tempWherePromo = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWherePromo = $tempWherePromo . ' or landing_with_promo.city_id = ' . $city->city_id;
        }
        $tempWherePromo = $tempWherePromo . ' ) ';

        $landingList = DB::table('landing_with_promo')
            //->leftJoin('product_city', 'landing_with_promo.product_id', '=' , 'product_city.product_id', 'landing_with_promo.city_id', '=', 'product_city.city_id')
            //->leftJoin('landing_products_times', 'landing_with_promo.product_id','=' , 'landing_products_times.product_id', 'landing_with_promo.city_id','=' , 'landing_products_times.city_id')
            ->leftJoin('product_city', function ($join) {
                $join->on('landing_with_promo.product_id', '=', 'product_city.product_id');
                $join->on('landing_with_promo.city_id', '=', 'product_city.city_id');
            })
            ->leftJoin('landing_products_times', function ($join) {
                $join->on('landing_with_promo.product_id', '=', 'landing_products_times.product_id');
                $join->on('landing_with_promo.city_id', '=', 'landing_products_times.city_id');
            })
            ->whereRaw($tempWherePromo)->orderBy('order')
            //->where('product_city.city_id', '=', $cityList[0]->city_id)
            //->where('landing_products_times.city_id', '=', $cityList[0]->city_id)
            ->select('landing_with_promo.*', 'product_city.coming_soon', 'product_city.limit_statu', 'landing_products_times.today', 'landing_products_times.tomorrow', 'landing_products_times.avalibility_time')
            ->get();

        //dd($landingList);

        foreach ($landingList as $flowerOrPromo) {

            if ($flowerOrPromo->product_id != 0) {

                $flowerOrPromo->image = DB::table('images')
                    ->where('products_id', '=', $flowerOrPromo->product_id)
                    ->where('type', 'main')
                    ->select('type', 'image_url')
                    ->orderBy('order_no')
                    ->get()[0]->image_url;

            } else {
                $flowerOrPromo->image = DB::table('landing_promo')
                    ->where('id', '=', $flowerOrPromo->promo_id)
                    ->select('background_image')
                    ->get()[0]->background_image;
            }

        }

        return view('admin.orderLandingWithPromo', compact('flowerList', 'promoList', 'landingList'));
    }

    public function deletePromo()
    {
        DB::table('landing_promo')->where('id', Request::input('id'))->delete();

        return redirect('/admin/listPromo');
    }

    public function updatePromo()
    {
        //AdminPanelController::checkAdmin();

        if (Request::hasFile('background_image')) {
            $siteUrl = $this->backend_url;
            $file = Request::file('background_image');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];
            $imageId = (string)(rand(0, 1000000));

            $fileMoved = Request::file('background_image')->move(public_path() . "/productImageUploads/", $imageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('background_image');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/BNF_promo_' . $imageId . "." . $fileExtension,
                'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
                'CacheControl' => 'max-age=31536000'
            ));

            DB::table('landing_promo')->where('id', Request::input('id'))->update([
                'background_image' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . '/BNF_promo_' . $imageId . "." . $fileExtension,
            ]);

        }

        $tempWidth = 0;

        if (Request::input('type') == 1) {
            $tempWidth = 2;
        } else if (Request::input('type') == 2) {
            $tempWidth = 2;
        } else if (Request::input('type') == 3) {
            $tempWidth = 1;
        }

        DB::table('landing_promo')->where('id', Request::input('id'))->update([
            'type' => Request::input('type'),
            'title' => Request::input('title'),
            'temp_name' => Request::input('temp_name'),
            'width' => $tempWidth,
            'title_small' => Request::input('title_small'),
            'description' => Request::input('description'),
            'button_text' => Request::input('button_text'),
            'border_color' => Request::input('border_color'),
            'background_color' => Request::input('background_color'),
            'text_color' => Request::input('text_color'),
            'button_text_color' => Request::input('button_text_color'),
            'button_background_color' => Request::input('button_background_color'),
            'opacity' => Request::input('opacity'),
            'title_font_size' => Request::input('title_font_size'),
            'title_small_size' => Request::input('title_small_size'),
            'description_size' => Request::input('description_size'),
            'link' => Request::input('link'),
        ]);

        DB::table('landing_promo_city')->where('landing_promo_id', Request::input('id'))->update([
            'active' => 0
        ]);

        foreach (Request::input('allCities') as $city) {
            DB::table('landing_promo_city')->where('landing_promo_id', Request::input('id'))->where('city_id', $city)->update([
                'active' => 1
            ]);
        }

        return redirect('/admin/listPromo');
    }

    public function promoDetail($id)
    {
        $promoDetail = DB::table('landing_promo')->where('id', $id)->get()[0];

        $cityList = DB::table('landing_promo_city')
            ->join('city_list', 'landing_promo_city.city_id', '=', 'city_list.id')
            ->where('landing_promo_id', $id)
            ->select('landing_promo_city.city_id', 'landing_promo_city.active', 'city_list.name')
            ->get();

        return view('admin.promoDetail', compact('promoDetail', 'cityList'));
    }

    public function listPromo()
    {
        $promoList = DB::table('landing_promo')->get();

        return view('admin.promoList', compact('promoList'));
    }

    public function addNewPromoPage()
    {
        //AdminPanelController::checkAdmin();

        return view('admin.createPromoPage');
    }

    public function addNewPromo()
    {
        //AdminPanelController::checkAdmin();

        if (Request::hasFile('background_image')) {
            $siteUrl = $this->backend_url;
            $file = Request::file('background_image');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];
            $imageId = (string)(rand(0, 1000000));

            $fileMoved = Request::file('background_image')->move(public_path() . "/productImageUploads/", $imageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('background_image');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/BNF_promo_' . $imageId . "." . $fileExtension,
                'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
                'CacheControl' => 'max-age=31536000'
            ));
        } else {
            dd('Resim yüklemelisiniz!');
        }

        $tempWidth = 0;

        if (Request::input('type') == 1) {
            $tempWidth = 2;
        } else if (Request::input('type') == 2) {
            $tempWidth = 2;
        } else if (Request::input('type') == 3) {
            $tempWidth = 1;
        }

        $Id = DB::table('landing_promo')->insertGetId([
            'type' => Request::input('type'),
            'active' => 0,
            'width' => $tempWidth,
            'title' => Request::input('title'),
            'temp_name' => Request::input('temp_name'),
            'title_small' => Request::input('title_small'),
            'description' => Request::input('description'),
            'button_text' => Request::input('button_text'),
            'border_color' => Request::input('border_color'),
            'background_color' => Request::input('background_color'),
            'text_color' => Request::input('text_color'),
            'button_text_color' => Request::input('button_text_color'),
            'button_background_color' => Request::input('button_background_color'),
            'opacity' => Request::input('opacity'),
            'title_font_size' => Request::input('title_font_size'),
            'title_small_size' => Request::input('title_small_size'),
            'description_size' => Request::input('description_size'),
            'link' => Request::input('link'),
            'background_image' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . '/BNF_promo_' . $imageId . "." . $fileExtension,
        ]);

        DB::table('landing_promo_city')->insert([
            'landing_promo_id' => $Id,
            'city_id' => 1
        ]);

        DB::table('landing_promo_city')->insert([
            'landing_promo_id' => $Id,
            'city_id' => 2
        ]);

        DB::table('landing_promo_city')->insert([
            'landing_promo_id' => $Id,
            'city_id' => 341
        ]);

        return redirect('/admin/listPromo');
    }

}
