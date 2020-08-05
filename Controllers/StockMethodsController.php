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
use SoapClient;
//use Illuminate\Http\Request;

use App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Request;
use DB;
use Excel;
use Redirect;
use App\Http\Requests\insertProductRequest;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use SimpleXMLElement;
use stdClass;
use Datatables;

class StockMethodsController extends Controller
{

    public function tempCrossSellMail(){

        /*$sendingMails = DB::table('product_stock_mails')->where('is_mail_sent', 0)->get();

        foreach ( $sendingMails as $mail ){

            $tempProductStock = DB::table('product_stocks')->where('id', $mail->product_stock_id )->get();

            if( $tempProductStock[0]->product_id ){

                $productData = DB::table('products')
                    ->join('images', 'products.id','=', 'images.products_id')
                    ->whereRaw('images.type = "Main" and products.id =  "' . $tempProductStock[0]->product_id . '"' )
                    ->select('products.name', 'images.image_url')->get()[0];

            }
            else{
                $productData = DB::table('cross_sell_products')
                    ->where('id', $tempProductStock[0]->cross_sell_id)
                    ->select('cross_sell_products.name', 'cross_sell_products.image as image_url')->get()[0];
            }

            if( $tempProductStock[0]->city_id == 1 ){
                $tempCityString = 'İstanbul';
            }
            else{
                $tempCityString = 'Ankara';
            }

            if( $mail->under_mail == 1 ){

                \MandrillMail::messages()->sendTemplate('less_then _5', null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => '!!Hatalı Siparişlerde Artış!!',
                    'subject' => "Stok sayısı 5'ten aza indi!",
                    'from_email' => 'teknik@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => 'siparis@bloomandfresh.com',
                            'type' => 'to'
                        ),
                        array(
                            'email' => 'ipek@bloomandfresh.com',
                            'type' => 'to'
                        )
                    ),
                    'merge' => true,
                    'merge_language' => 'mailchimp',
                    'global_merge_vars' => array(
                        array(
                            'name' => 'product_image',
                            'content' => $productData->image_url,
                        ), array(
                            'name' => 'product_name',
                            'content' => $productData->name,
                        ), array(
                            'name' => 'city_name',
                            'content' => $tempCityString,
                        ), array(
                            'name' => 'count',
                            'content' => $tempProductStock[0]->count,
                        ), array(
                            'name' => 'product_id',
                            'content' => $tempProductStock[0]->product_id,
                        )
                    )
                ));

            }
            else if( $mail->no_stock == 1 ){

                \MandrillMail::messages()->sendTemplate('product_stock_0', null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => '!!Hatalı Siparişlerde Artış!!',
                    'subject' => "Stok sayısı 0'a düştü!",
                    'from_email' => 'teknik@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => 'siparis@bloomandfresh.com',
                            'type' => 'to'
                        ),
                        array(
                            'email' => 'ipek@bloomandfresh.com',
                            'type' => 'to'
                        )
                    ),
                    'merge' => true,
                    'merge_language' => 'mailchimp',
                    'global_merge_vars' => array(
                        array(
                            'name' => 'product_image',
                            'content' => $productData->image_url,
                        ), array(
                            'name' => 'product_name',
                            'content' => $productData->name,
                        ), array(
                            'name' => 'city_name',
                            'content' => $tempCityString,
                        ), array(
                            'name' => 'product_id',
                            'content' => $tempProductStock[0]->product_id,
                        )
                    )
                ));

            }

            DB::table('product_stock_mails')->where('id', $mail->id )->update([
                'is_mail_sent' => 1
            ]);

        }*/

    }

    public function productStockActiveCrossSell($id){

        $stockInfo = DB::table('product_stocks')
            ->join('cross_sell_products', 'product_stocks.cross_sell_id', '=', 'cross_sell_products.id')
            ->where('cross_sell_products.id', $id )
            ->where('product_stocks.city_id', 1 )
            ->select('cross_sell_products.name', 'product_stocks.count', 'product_stocks.active', 'product_stocks.id', 'product_stocks.future_stock')->get()[0];

        return view('admin.stockProductActiveCrossSell', compact('stockInfo'));

    }

    public function productStockHistoryCrossSell($id){

        $stockInfo = DB::table('product_stocks')
            ->join('cross_sell_products', 'product_stocks.cross_sell_id', '=', 'cross_sell_products.id')
            ->where('cross_sell_products.id', $id )
            ->where('product_stocks.city_id', 1 )
            ->select('cross_sell_products.name', 'product_stocks.count', 'product_stocks.active', 'product_stocks.id', 'product_stocks.cross_sell_id', 'product_stocks.product_id')->get()[0];

        $stockInfo->crossSellId = 0;

        $processes = DB::table('product_stock_user_log')
            ->join('users', 'product_stock_user_log.user_id', '=', 'users.id')
            ->join('product_stocks', 'product_stock_user_log.product_stock_id', '=', 'product_stocks.id')
            ->where('product_stocks.cross_sell_id', $id )
            ->where('product_stocks.city_id', 1 )
            ->select('product_stock_user_log.*', 'users.name', 'users.surname', 'users.user_group_id', 'sale_id', 'old_value', 'new_value')->orderBy('product_stock_user_log.created_at','DESC')->get();

        return view('admin.stockProductHistory', compact('stockInfo', 'processes'));

    }

    public function updateProductStockCountCross(){

        $now = Carbon::now();

        $exStockData = DB::table('product_stocks')->where('id', Request::input('id') )->get()[0];

        if( Request::input('typeCount') == 1 ){
            DB::table('product_stocks')->where('id', Request::input('id') )->increment('count', Request::input('count'));
            //$tempCountTypeString = 'arttırdı.';
        }
        else{
            DB::table('product_stocks')->where('id', Request::input('id') )->decrement('count', Request::input('count'));
            //$tempCountTypeString = 'azalttı.';
        }

        DB::table('product_stocks')->where('id', Request::input('id') )->update([
            'updated_at' => $now
        ]);

        //$tempString = $exStockData->count . ' olan stok sayısını ' . Request::input('count') . ' ' . $tempCountTypeString;

        /*DB::table('product_stock_user_log')->insert([
            'user_id' => \Auth::user()->id,
            'product_stock_id' => Request::input('id'),
            'type' => $tempString,
            'comment' => Request::input('comment')
        ]);*/

        //$mailData = DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->get()[0];

        $activeStockData = DB::table('product_stocks')->where('id', Request::input('id') )->get()[0];

        generateDataController::logStock( Request::input('comment'), $activeStockData->id, '', $exStockData->count, $activeStockData->count, \Auth::user()->id );

        if( $activeStockData->count == 0 ){

            generateDataController::setEmailFunction( $activeStockData->id , 0, 1);

            DB::table('cross_sell_products')->where('id', $activeStockData->cross_sell_id )->where('city_id', $activeStockData->city_id )->update([
                'status' => 0
            ]);

        }
        else if( $activeStockData->count < 5 && $exStockData->count > 4 ){
            generateDataController::setEmailFunction( $activeStockData->id , 1, 0);
        }

        /*if( $mailData->under_email == 0 && $activeStockData->count > 5  ){
            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'under_email' => 1
            ]);
        }*/

        /*if( $mailData->no_stock == 0 && $activeStockData->count > 0  ){
            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'no_stock' => 1
            ]);
        }*/

        return redirect('/showProductStock/active');
    }

    public function productStockModifyCrossSell($id){

        $stockInfo = DB::table('product_stocks')
            ->join('cross_sell_products', 'product_stocks.cross_sell_id', '=', 'cross_sell_products.id')
            ->where('cross_sell_products.id', $id )
            ->select('cross_sell_products.name', 'product_stocks.count', 'product_stocks.active', 'product_stocks.id')->get()[0];

        return view('admin.stockProductModifyCross', compact('stockInfo'));

    }

    public function generateCrossSellStock(){

        $crossSellProducts = DB::table('product_stocks')->where('cross_sell_id', '>',  0)->where('product_id',  0)->get();

        $now = Carbon::now();

        foreach ( $crossSellProducts as $product ){

            /*DB::table('product_stocks')->insert([
                'product_id' => 0,
                'cross_sell_id' => $product->id,
                'city_id' => 1,
                'count' => 0,
                'active' => 0
            ]);*/

            DB::table('mail_trigger')->insert([
                'product_stock_id' => $product->id,
                'under_email' => 0,
                'no_stock' => 0,
                'updated_at' => $now
            ]);

        }

    }

    public function updateProductCrossSell(){
        $postData = Request::all();

        $now = Carbon::now();

        foreach ( $postData as $key => $data ){

            $keyPieces = explode("_", $key);

            if( $keyPieces[0] == 'cross' ){

                DB::table('cross_sell_products')->where('id', $keyPieces[1] )->update([
                    'product_id' => $data
                ]);

                if( $data == 0 ){
                    if( DB::table('product_stocks')->where('cross_sell_id', $keyPieces[1] )->where('product_id', 0 )->count() == 0){
                        $stockId = DB::table('product_stocks')->insertGetId([
                            'product_id' => 0,
                            'cross_sell_id' => $keyPieces[1],
                            'city_id' => 1,
                            'count' => 0,
                            'active' => 0
                        ]);

                        DB::table('mail_trigger')->insert([
                            'product_stock_id' => $stockId,
                            'under_email' => 0,
                            'no_stock' => 0,
                            'updated_at' => $now
                        ]);
                    }
                }
                else{
                    if( DB::table('product_stocks')->where('cross_sell_id', $keyPieces[1] )->where('product_id', 0 )->count() > 0){

                        $tempId = DB::table('product_stocks')->where('cross_sell_id', $keyPieces[1] )->where('product_id', 0 )->get()[0]->id;

                        DB::table('product_stocks')->where('cross_sell_id', $keyPieces[1] )->where('product_id', 0 )->delete();

                        DB::table('mail_trigger')->where('product_stock_id' , $tempId )->delete();

                    }
                }

            }

        }

        return redirect('/crossSell-product-merge');
    }

    public function mergeProductCrossSell(){

        $productsChocolate = DB::table('products')->where('product_type', 2)->where('city_id', 1)->orderBy('name')->get();
        $crossSellProducts = DB::table('cross_sell_products')->where('city_id', 1)->orderBy('name')->get();

        return view('admin.mergeProductCrossSell', compact('productsChocolate', 'crossSellProducts'));
    }

    public function generateStockData()
    {
        $allProductCity = DB::table('product_city')->get();
        $now = Carbon::now();

        foreach( $allProductCity as $productCity ) {
            $stockId = DB::table('product_stocks')->insertGetId([
                'product_id' => $productCity->product_id,
                'city_id' => $productCity->city_id,
                'count' => 0,
                'active' => 0,
                'updated_at' => $now
            ]);

            DB::table('mail_trigger')->insert([
                'product_stock_id' => $stockId,
                'under_email' => 0,
                'no_stock' => 0,
                'updated_at' => $now
            ]);
        }
    }

    public function showProductStock($type){

        if( $type == 'active' ){
            $tempWhereType = ' ( 1 = 0 or product_city.activation_status_id = 1 ) ';
        }
        else if( $type == 'passive' ){
            $tempWhereType = ' ( 1 = 0 or product_city.activation_status_id = 0 ) ';
        }
        else{
            $tempWhereType = ' ( 1 = 0 or product_city.activation_status_id = 1 or product_city.activation_status_id = 0 ) ';
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or product_city.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $products = DB::table('products')->join('product_city', 'products.id', '=', 'product_city.product_id')
            //->join('product_stocks', 'product_city.city_id', '=', 'product_stocks.city_id')
            ->join('product_stocks', function ($join) {
                $join->on('product_city.product_id', '=', 'product_stocks.product_id')
                    ->on('product_city.city_id', '=', 'product_stocks.city_id');
            })
            ->where('product_city.active', 1)
            ->where('company_product', '=', '0')
            //->where('product_stocks.product_id', '=', 'products.id')
            ->whereRaw($tempWhere)
            ->whereRaw($tempWhereType)
            //->where('product_city.activation_status_id', 1)
            ->orderBy('product_stocks.active', 'DESC')
            ->orderBy('products.name')
            ->select('products.id', 'products.name', 'product_city.activation_status_id'
                , 'product_city.limit_statu', 'product_city.coming_soon', 'product_city.id as product_city_id', 'product_stocks.count', 'product_stocks.active', 'product_stocks.future_stock',
                DB::raw('(select count(*) from cross_sell_products where cross_sell_products.product_id = products.id ) as crossSellExist') )
            ->get();

        if( $type == 'active' ){
            $tempWhereType = ' ( 1 = 0 or cross_sell_products.status = 1 ) ';
        }
        else if( $type == 'passive' ){
            $tempWhereType = ' ( 1 = 0 or cross_sell_products.status = 0 ) ';
        }
        else{
            $tempWhereType = ' ( 1 = 0 or cross_sell_products.status = 1 or cross_sell_products.status = 0 ) ';
        }

        $crossSellProducts = DB::table('cross_sell_products')
            ->join('product_stocks', 'cross_sell_products.id', '=', 'product_stocks.cross_sell_id')
            ->where('cross_sell_products.product_id', 0)
            ->where('cross_sell_products.city_id', '=', 1)
            ->whereRaw($tempWhereType)
            ->orderBy('product_stocks.active', 'DESC')
            ->orderBy('product_stocks.count', 'DESC')
            ->select('cross_sell_products.id', 'cross_sell_products.name', 'cross_sell_products.status as activation_status_id'
                , DB::raw("'0' as limit_statu") , DB::raw("'0' as coming_soon") , DB::raw("'1' as product_city_id") , 'product_stocks.count', 'product_stocks.active', 'product_stocks.future_stock')
            ->get();


        return view('admin.stockProductList', compact('products', 'type', 'crossSellProducts'));
    }

    public function productStockActive($id){

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        if( count($cityList) > 1 ){
            dd('Şehir seçmeden işlem yapamazsınız!');
        }

        $stockInfo = DB::table('product_stocks')
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->where('product_id', $id )->where('product_stocks.city_id', $cityList[0]->city_id )
            ->select('products.name', 'product_stocks.count', 'product_stocks.active', 'product_stocks.id', 'product_stocks.future_stock')->get()[0];

        return view('admin.stockProductActive', compact('stockInfo'));

    }

    public function updateProductStockStatusCrossSell(){

        if (Request::input('active') == '0' || Request::input('active') == '1') {
            $tempActive = 1;
        } else {
            $tempActive = 0;
        }

        $now = Carbon::now();

        $exStockData = DB::table('product_stocks')->where('id', Request::input('id') )->get()[0];

        //$tempString = '';

        //$mailData = DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->get()[0];

        /*if( $mailData->under_email == 0 && Request::input('count') > 4 ){
            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'under_email' => 1
            ]);
        }

        if( $mailData->no_stock == 0 && Request::input('count') > 0 ){
            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'no_stock' => 1
            ]);
        }*/

        /*if( $exStockData->active && $tempActive == 0 ){
            $tempString = $tempString . 'Stok aktifliği kapatıldı. ';
        }
        else if( $exStockData->active == 0 && $tempActive ){
            $tempString = $tempString . 'Stok aktifliği açıldı. ';

            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'under_email' => 1,
                'no_stock' => 1
            ]);

        }*/

        /*if( $exStockData->count != Request::input('count') ){
            $tempString = $tempString . $exStockData->count . ' olan stok sayısı ' . Request::input('count') . ' olarak değiştirildi. ';
        }*/

        /*DB::table('product_stock_user_log')->insert([
            'user_id' => \Auth::user()->id,
            'product_stock_id' => Request::input('id'),
            'type' => $tempString,
            'comment' => Request::input('comment')
        ]);*/

        if (Request::input('future_stock') == '0' || Request::input('future_stock') == '1') {
            $tempFuture = 1;
        } else {
            $tempFuture = 0;
        }

        DB::table('product_stocks')->where('id', Request::input('id') )->update([
            'count' => Request::input('count'),
            'active' => $tempActive,
            'updated_at' => $now,
            'future_stock' => $tempFuture
        ]);

        $activeStockData = DB::table('product_stocks')->where('id', Request::input('id') )->get()[0];

        generateDataController::logStock( Request::input('comment'), $activeStockData->id, '', $exStockData->count, $activeStockData->count, \Auth::user()->id );

        if( $activeStockData->count == 0 ){

            generateDataController::setEmailFunction( $activeStockData->id , 0, 1);

            DB::table('cross_sell_products')->where('id', $activeStockData->cross_sell_id )->where('city_id', $activeStockData->city_id )->update([
                'status' => 0
            ]);

        }
        else if( $activeStockData->count < 5 && $exStockData->count > 4 ){
            generateDataController::setEmailFunction( $activeStockData->id , 1, 0);
        }


        return redirect('/showProductStock/active');

    }

    public function updateProductStockStatus(){

        if (Request::input('active') == '0' || Request::input('active') == '1') {
            $tempActive = 1;
        } else {
            $tempActive = 0;
        }

        $now = Carbon::now();

        $exStockData = DB::table('product_stocks')->where('id', Request::input('id') )->get()[0];

        //$tempString = '';

        //$mailData = DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->get()[0];

        /*if( $mailData->under_email == 0 && Request::input('count') > 4 ){
            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'under_email' => 1
            ]);
        }

        if( $mailData->no_stock == 0 && Request::input('count') > 0 ){
            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'no_stock' => 1
            ]);
        }*/

        /*if( $exStockData->active && $tempActive == 0 ){
            $tempString = $tempString . 'Stok aktifliği kapatıldı. ';
        }
        else if( $exStockData->active == 0 && $tempActive ){
            $tempString = $tempString . 'Stok aktifliği açıldı. ';

            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'under_email' => 1,
                'no_stock' => 1
            ]);

        }*/

        /*if( $exStockData->count != Request::input('count') ){
            $tempString = $tempString . $exStockData->count . ' olan stok sayısı ' . Request::input('count') . ' olarak değiştirildi. ';
        }*/

        /*DB::table('product_stock_user_log')->insert([
            'user_id' => \Auth::user()->id,
            'product_stock_id' => Request::input('id'),
            'type' => $tempString,
            'comment' => Request::input('comment')
        ]);*/

        if (Request::input('future_stock') == '0' || Request::input('future_stock') == '1') {
            $tempFuture = 1;
        } else {
            $tempFuture = 0;
        }

        DB::table('product_stocks')->where('id', Request::input('id') )->update([
            'count' => Request::input('count'),
            'active' => $tempActive,
            'updated_at' => $now,
            'future_stock' => $tempFuture
        ]);

        $activeStockData = DB::table('product_stocks')->where('id', Request::input('id') )->get()[0];

        generateDataController::logStock( Request::input('comment'), $activeStockData->id, '', $exStockData->count, $activeStockData->count, \Auth::user()->id );

        if( $activeStockData->count == 0 ){

            generateDataController::setEmailFunction( $activeStockData->id , 0, 1);

            DB::table('product_city')->where('product_id', $activeStockData->product_id )->where('city_id', $activeStockData->city_id )->update([
                'limit_statu' => 1
            ]);

            $crossSellData = DB::table('cross_sell_products')->where('product_id', $activeStockData->product_id )->where('city_id', $activeStockData->city_id )->get();

            if( count($crossSellData) > 0 ){
                DB::table('cross_sell_products')->where('product_id', $activeStockData->product_id )->where('city_id', $activeStockData->city_id )->update([
                    'status' => 0
                ]);
            }

        }
        else if( $activeStockData->count < 5 && $exStockData->count > 4 ){
            generateDataController::setEmailFunction( $activeStockData->id , 1, 0);
        }


        return redirect('/showProductStock/active');

    }

    public function productStockModify($id){

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        if( count($cityList) > 1 ){
            dd('Şehir seçmeden işlem yapamazsınız!');
        }

        $stockInfo = DB::table('product_stocks')
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->where('product_id', $id )->where('product_stocks.city_id', $cityList[0]->city_id )
            ->select('products.name', 'product_stocks.count', 'product_stocks.active', 'product_stocks.id')->get()[0];

        return view('admin.stockProductModify', compact('stockInfo'));
    }

    public function updateProductStockCount(){

        $now = Carbon::now();

        $exStockData = DB::table('product_stocks')->where('id', Request::input('id') )->get()[0];

        if( Request::input('typeCount') == 1 ){
            DB::table('product_stocks')->where('id', Request::input('id') )->increment('count', Request::input('count'));
            //$tempCountTypeString = 'arttırdı.';
        }
        else{
            DB::table('product_stocks')->where('id', Request::input('id') )->decrement('count', Request::input('count'));
            //$tempCountTypeString = 'azalttı.';
        }

        DB::table('product_stocks')->where('id', Request::input('id') )->update([
            'updated_at' => $now
        ]);

        //$tempString = $exStockData->count . ' olan stok sayısını ' . Request::input('count') . ' ' . $tempCountTypeString;


        /*DB::table('product_stock_user_log')->insert([
            'user_id' => \Auth::user()->id,
            'product_stock_id' => Request::input('id'),
            'type' => $tempString,
            'comment' => Request::input('comment')
        ]);*/

        //$mailData = DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->get()[0];

        $activeStockData = DB::table('product_stocks')->where('id', Request::input('id') )->get()[0];

        generateDataController::logStock( Request::input('comment'), $activeStockData->id, '', $exStockData->count, $activeStockData->count, \Auth::user()->id );

        if( $activeStockData->count == 0 ){

            generateDataController::setEmailFunction( $activeStockData->id , 0, 1);

            DB::table('product_city')->where('product_id', $activeStockData->product_id )->where('city_id', $activeStockData->city_id )->update([
                'limit_statu' => 1
            ]);

            $crossSellData = DB::table('cross_sell_products')->where('product_id', $activeStockData->product_id )->where('city_id', $activeStockData->city_id )->get();

            if( count($crossSellData) > 0 ){
                DB::table('cross_sell_products')->where('product_id', $activeStockData->product_id )->where('city_id', $activeStockData->city_id )->update([
                    'status' => 0
                ]);
            }

        }
        else if( $activeStockData->count < 5 && $exStockData->count > 4 ){
            generateDataController::setEmailFunction( $activeStockData->id , 1, 0);
        }

        /*if( $mailData->under_email == 0 && $activeStockData->count > 5  ){
            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'under_email' => 1
            ]);
        }

        if( $mailData->no_stock == 0 && $activeStockData->count > 0  ){
            DB::table('mail_trigger')->where('product_stock_id', Request::input('id') )->update([
                'no_stock' => 1
            ]);
        }*/

        return redirect('/showProductStock/active');
    }

    public function productStockHistory($id){

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        if( count($cityList) > 1 ){
            dd('Şehir seçmeden işlem yapamazsınız!');
        }

        $stockInfo = DB::table('product_stocks')
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->where('product_id', $id )->where('product_stocks.city_id', $cityList[0]->city_id )
            ->select('products.name', 'product_stocks.count', 'product_stocks.active', 'product_stocks.id', 'product_stocks.product_id', 'product_stocks.cross_sell_id')->get()[0];

        $stockInfo->crossSellId = DB::table('cross_sell_products')->where('city_id', $cityList[0]->city_id)->where('product_id', $stockInfo->product_id )->count();

        $processes = DB::table('product_stock_user_log')
            ->join('users', 'product_stock_user_log.user_id', '=', 'users.id')
            ->join('product_stocks', 'product_stock_user_log.product_stock_id', '=', 'product_stocks.id')
            ->where('product_stocks.product_id', $id )
            ->where('product_stocks.city_id', $cityList[0]->city_id )
            ->select('product_stock_user_log.*', 'users.name', 'users.surname', 'users.user_group_id', 'sale_id', 'old_value', 'new_value')->orderBy('product_stock_user_log.created_at','DESC')->get();

        return view('admin.stockProductHistory', compact('stockInfo', 'processes'));

    }

    public function testMailTrigger(){

        $salesWithoutStock = DB::table('sales')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=' , 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=' , 'sales_products.sales_id')
            ->where('is_stock_checked', 0)
            ->select('sales.id', 'delivery_locations.city_id', 'sales_products.products_id')->get();

            foreach ( $salesWithoutStock as $sale ){

                if( $sale->city_id == 2 ){
                    $tempCityId = 2;
                }
                else{
                    $tempCityId = 1;
                }

                $tempProductStock = DB::table('product_stocks')->where('product_id', $sale->products_id)->where('city_id', $tempCityId )->get();

                if( count($tempProductStock) > 0 ){
                    //dd($tempProductStock);
                    if( $tempProductStock[0]->active == 0 ){
                        DB::table('sales')->where('id', $sale->id )->update([
                            'is_stock_checked' => 1
                        ]);
                    }
                    else{
                        if( $tempProductStock[0]->count == 0 ){
                            DB::table('product_stock_user_log')->insert([
                                'user_id' => 1,
                                'product_stock_id' => $tempProductStock[0]->id,
                                'type' => 'Ürün stok sayısı 0 iken sipariş alındı',
                                'comment' => 'No comment'
                            ]);
                        }
                        else{
                            DB::table('product_stocks')->where('id', $tempProductStock[0]->id )->decrement('count', 1);

                            $mailData = DB::table('mail_trigger')->where('product_stock_id', $tempProductStock[0]->id )->get()[0];

                            $tempProductStock = DB::table('product_stocks')->where('id', $tempProductStock[0]->id )->get();

                            $stringMain = 'Main';

                            if( count($tempProductStock) > 0 ){
                                if( $tempProductStock[0]->count < 5 && $mailData->under_email == 1 ){

                                    $productData = DB::table('products')
                                        ->join('images', 'products.id','=', 'images.products_id')
                                        ->whereRaw('images.type = "' . $stringMain . '" and products.id =  "' . $tempProductStock[0]->product_id . '"' )
                                        ->select('products.name', 'images.image_url')->get()[0];

                                    if( $tempCityId == 1 ){
                                        $tempCityString = 'İstanbul';
                                    }
                                    else{
                                        $tempCityString = 'Ankara';
                                    }

                                    \MandrillMail::messages()->sendTemplate('less_then _5', null, array(
                                        'html' => '<p>Example HTML content</p>',
                                        'text' => '!!Hatalı Siparişlerde Artış!!',
                                        'subject' => "Stok sayısı 5'ten aza indi!",
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
                                        'global_merge_vars' => array(
                                            array(
                                                'name' => 'product_image',
                                                'content' => $productData->image_url,
                                            ), array(
                                                'name' => 'product_name',
                                                'content' => $productData->name,
                                            ), array(
                                                'name' => 'city_name',
                                                'content' => $tempCityString,
                                            ), array(
                                                'name' => 'count',
                                                'content' => $tempProductStock[0]->count,
                                            ), array(
                                                'name' => 'product_id',
                                                'content' => $tempProductStock[0]->product_id,
                                            )
                                        )
                                    ));

                                    DB::table('mail_trigger')->where('product_stock_id', $tempProductStock[0]->id )->update([
                                        'under_email' => 0
                                    ]);
                                }

                                if( $tempProductStock[0]->count == 0 && $mailData->no_stock == 1 ){

                                    $productData = DB::table('products')
                                        ->join('images', 'products.id','=', 'images.products_id')
                                        ->whereRaw('images.type = "' . $stringMain . '" and products.id =  "' . $tempProductStock[0]->product_id . '"' )
                                        ->select('products.name', 'images.image_url')->get()[0];

                                    if( $tempCityId == 1 ){
                                        $tempCityString = 'İstanbul';
                                    }
                                    else{
                                        $tempCityString = 'Ankara';
                                    }

                                    \MandrillMail::messages()->sendTemplate('product_stock_0', null, array(
                                        'html' => '<p>Example HTML content</p>',
                                        'text' => '!!Hatalı Siparişlerde Artış!!',
                                        'subject' => "Stok sayısı 0'a düştü!",
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
                                        'global_merge_vars' => array(
                                            array(
                                                'name' => 'product_image',
                                                'content' => $productData->image_url,
                                            ), array(
                                                'name' => 'product_name',
                                                'content' => $productData->name,
                                            ), array(
                                                'name' => 'city_name',
                                                'content' => $tempCityString,
                                            ), array(
                                                'name' => 'product_id',
                                                'content' => $tempProductStock[0]->product_id,
                                            )
                                        )
                                    ));

                                    DB::table('mail_trigger')->where('product_stock_id', $tempProductStock[0]->id )->update([
                                        'no_stock' => 0
                                    ]);

                                    DB::table('product_city')->where('city_id', $tempCityId)->where('product_id', $tempProductStock[0]->product_id)->update([
                                        'limit_statu' => '1'
                                    ]);

                                }

                            }

                            DB::table('sales')->where('id', $sale->id )->update([
                                'is_stock_checked' => 1
                            ]);

                        }
                    }
                }
                else{
                    DB::table('sales')->where('id', $sale->id )->update([
                        'is_stock_checked' => 1
                    ]);
                }

            }


    }

}