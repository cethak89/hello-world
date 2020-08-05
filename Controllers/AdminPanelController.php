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

class AdminPanelController extends Controller
{
    public $site_url = 'https://bloomandfresh.com';
    //public  $site_url = 'http://188.166.86.116';
    //public $backend_url = 'http://188.166.86.116:3000';
    public $backend_url = 'https://everybloom.com';

    public function updatePlanningCourier()
    {

        $tempObject = Request::all();

        $tempIds = [];
        $tempOperationPerson = '';
        foreach ($tempObject as $key => $value) {
            if (explode('_', $key)[0] == 'selected') {
                array_push($tempIds, explode('_', $key)[1]);
            } else if (explode('_', $key)[0] == 'plannig') {
                $tempOperationPerson = $value;
            }
        }

        //dd($tempOperationPerson);

        DB::table('sales')->whereIn('id', $tempIds)->update([
            'planning_courier_id' => $tempOperationPerson
        ]);

        return redirect('/admin/deliveries/today');
    }

    public function cardPrintDeliveryInfo(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();

        //dd($request->all());

        $tempObject = $request->all();
        $tempQueryList = [];
        //dd($tempObject);
        foreach ($tempObject as $key => $value) {
            if (explode('_', $key)[0] == 'selected') {
                if (strlen(explode('_', $key)[1]) > 8) {
                    $deliveryList = (object)[];
                } else {
                    $deliveryList = DB::table('sales')
                        ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                        ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                        ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                        //->where('deliveries.wanted_delivery_date' , '>' , $before )
                        //->where('deliveries.wanted_delivery_date' , '<' , $after )
                        ->where('sales.id', '=', explode('_', $key)[1])
                        ->where('sales.payment_methods', '=', 'OK')
                        ->select('sales.id', 'sales.card_message', 'sales.receiver', 'sales.sender', 'customer_contacts.name', 'customer_contacts.surname')
                        ->get()[0];
                }

                array_push($tempQueryList, $deliveryList);
            }
        }

        return view('admin.cardDeliverydocument', compact('tempQueryList'));
    }

    public function deleteFBImage($imageId, $productId)
    {

        AdminPanelController::checkAdmin();
        //$imageId = Request::input('imageId');
        try {

            DB::table('images_social')->where('id', $imageId)->delete();

        } catch (\Exception $e) {
            DB::rollback();
            return "Resim silmede hata!";
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();

        return redirect('/admin/products/detail/' . $productId);
    }

    public function updateTag()
    {
        AdminPanelController::checkAdmin();

        if (Request::hasFile('image')) {
            $siteUrl = $this->backend_url;
            $file = Request::file('image');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];
            $imageId = (string)(rand(0, 1000000));

            $fileMoved = Request::file('image')->move(public_path() . "/productImageUploads/", $imageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('image');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/' . $imageId . "." . $fileExtension,
                'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
                'CacheControl' => 'max-age=31536000'
            ));

            DB::table('tags')->where('id', Request::input('id'))->where('lang_id', 'tr')->update([
                'banner_image' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $imageId . "." . $fileExtension,
            ]);

        }

        DB::table('tags')->where('id', Request::input('id'))->where('lang_id', 'tr')->update([
            'tags_name' => Request::input('tags_name'),
            'description' => Request::input('description'),
            'tag_header' => Request::input('tag_header'),
            'meta_description' => Request::input('meta_description')
        ]);

        return redirect('/admin/tag-list');
    }

    public function tagDetail($id)
    {
        AdminPanelController::checkAdmin();

        $tag = DB::table('tags')->where('id', $id)->where('lang_id', 'tr')->get()[0];

        return view('admin.tagDetail', compact('tag'));

    }

    public function tagList()
    {
        AdminPanelController::checkAdmin();

        $tags = DB::table('tags')->where('lang_id', 'tr')->get();

        return view('admin.tagList', compact('tags'));
    }

    public function updateFlowersCategory()
    {
        AdminPanelController::checkAdmin();

        $tempStatus = 0;
        if (Request::input('active') == 'on') {
            $tempStatus = 1;
        }

        if (Request::hasFile('image')) {
            $siteUrl = $this->backend_url;
            $file = Request::file('image');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];
            $imageId = (string)(rand(0, 1000000));

            $fileMoved = Request::file('image')->move(public_path() . "/productImageUploads/", $imageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('image');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/' . Request::input('url_name') . '_' . $imageId . "." . $fileExtension,
                'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
                'CacheControl' => 'max-age=31536000'
            ));

            DB::table('flowers_page')->where('id', Request::input('id'))->update([
                'image' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . Request::input('url_name') . '_' . $imageId . "." . $fileExtension,
            ]);

        }

        DB::table('flowers_page')->where('id', Request::input('id'))->update([
            'active' => $tempStatus,
            'head' => Request::input('head'),
            'desc' => Request::input('desc'),
            'meta_tittle' => Request::input('meta_tittle'),
            'meta_desc' => Request::input('meta_desc'),
            'url_name' => Request::input('url_name')
        ]);

        DB::table('page_flower_production')->where('page_id', Request::input('id'))->delete();

        if (Request::input('products')) {
            foreach (Request::input('products') as $productId) {
                DB::table('page_flower_production')->insert([
                    'page_id' => Request::input('id'),
                    'product_id' => $productId
                ]);
            }
        }


        return redirect('/admin/flowers-page-list');
    }

    public function detailFlowersPage($id)
    {
        AdminPanelController::checkAdmin();

        $productList = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('products.company_product', '=', '0')
            ->where('product_city.city_id', '=', '1')
            ->where('product_city.active', '=', 1)
            ->select(DB::raw('(select 1 from page_flower_production where products.id = page_flower_production.product_id and page_flower_production.page_id = ' . $id . ' ) as selected'), 'products.name', 'products.id')
            ->get();

        $flowersPage = DB::table('flowers_page')->where('id', $id)->get()[0];

        return view('admin.flowersPageDetail', compact('flowersPage', 'productList'));
    }

    public function flowersPageList()
    {
        AdminPanelController::checkAdmin();

        $flowersPage = DB::table('flowers_page')->get();

        return view('admin.flowersPageList', compact('flowersPage'));
    }

    public function insertFlowersCategory()
    {
        AdminPanelController::checkAdmin();

        if (Request::hasFile('image')) {
            $siteUrl = $this->backend_url;
            $file = Request::file('image');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];
            $imageId = (string)(rand(0, 1000000));

            $fileMoved = Request::file('image')->move(public_path() . "/productImageUploads/", $imageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('image');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/' . Request::input('url_name') . '_' . $imageId . "." . $fileExtension,
                'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
                'CacheControl' => 'max-age=31536000'
            ));
        } else {
            dd('Resim yüklemelisiniz!');
        }

        DB::table('flowers_page')->insert([
            'active' => 0,
            'head' => Request::input('head'),
            'desc' => Request::input('desc'),
            'meta_tittle' => Request::input('meta_tittle'),
            'meta_desc' => Request::input('meta_desc'),
            'url_name' => Request::input('url_name'),
            'image' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . Request::input('url_name') . '_' . $imageId . "." . $fileExtension,
        ]);

        return redirect('/admin/flowers-page-list');
    }

    public function createFlowersPage()
    {
        AdminPanelController::checkAdmin();

        return view('admin.createFlowersPage');
    }

    public function updateDropDownBanner()
    {

        $tempStatus = 0;
        if (Request::input('active') == 'on') {
            $tempStatus = 1;
        }

        if (Request::hasFile('image')) {
            $siteUrl = $this->backend_url;
            $file = Request::file('image');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];
            $imageId = (string)(rand(0, 10000));

            $fileMoved = Request::file('image')->move(public_path() . "/productImageUploads/", $imageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('image');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/' . $imageId . "." . $fileExtension,
                'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
                'CacheControl' => 'max-age=31536000'
            ));

            DB::table('drop_down_banner')->where('id', Request::input('id'))->update([
                'image' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $imageId . "." . $fileExtension,
            ]);
        }

        DB::table('drop_down_banner')->where('id', Request::input('id'))->update([
            'active' => $tempStatus,
            'first_header' => Request::input('first_header'),
            'second_header' => Request::input('second_header'),
            'button_name' => Request::input('button_name'),
            'link_url' => Request::input('link_url')
        ]);

        return redirect('/admin/drop-down-list');
    }

    public function dropDownMenuDetail($id)
    {

        $banner = DB::table('drop_down_banner')->where('id', $id)->get()[0];

        return view('admin.dropDownBannerDetail', compact('banner'));
    }

    public function deleteDropDownBanner()
    {

        DB::table('drop_down_banner')->where('id', Request::input('id'))->delete();

        return redirect('/admin/drop-down-list');
    }

    public function dropDownBannerProduct()
    {

        $activeFlowers = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('products.city_id', 1)
            ->where('products.company_product', 0)
            ->where('product_city.city_id', 1)
            ->where('product_city.activation_status_id', 1)
            ->orderBy('products.name')
            ->select('products.name', 'products.id')
            ->get();

        return view('admin.dropDownBannerCreateProduct', compact('activeFlowers'));

    }

    public function insertDropDownBannerProduct()
    {
        //dd(Request::all());

        $tempStatus = 0;
        if (Request::input('active') == 'on') {
            $tempStatus = 1;
        }

        $tempProductInfo = DB::table('products')
            ->join('tags', 'products.tag_id', '=', 'tags.id')
            ->join('images', 'products.id', '=', 'images.products_id')
            ->where('images.type', 'main')
            ->where('tags.lang_id', 'tr')
            ->where('products.city_id', '1')
            ->where('products.id', Request::input('product'))
            ->select('products.name', 'products.url_parametre', 'tags.tag_ceo', 'products.id', 'images.image_url')
            ->get()[0];

        $tempLink = 'https://bloomandfresh.com/' . $tempProductInfo->tag_ceo . '/' . $tempProductInfo->url_parametre . '-' . $tempProductInfo->id;

        DB::table('drop_down_banner')->insert([
            'active' => $tempStatus,
            'first_header' => 'HAFTANIN POPÜLERİ!',
            'second_header' => $tempProductInfo->name,
            'button_name' => 'Hızlı Gönder!',
            'link_url' => $tempLink,
            'image' => $tempProductInfo->image_url
        ]);

        return redirect('/admin/drop-down-list');
    }

    public function dropDownList()
    {

        $banners = DB::table('drop_down_banner')->get();

        return view('admin.dropDownBannerList', compact('banners'));
    }

    public function insertDropDownBanner()
    {

        $tempStatus = 0;
        if (Request::input('active') == 'on') {
            $tempStatus = 1;
        }

        if (Request::hasFile('image')) {
            $siteUrl = $this->backend_url;
            $file = Request::file('image');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];
            $imageId = (string)(rand(0, 10000));

            $fileMoved = Request::file('image')->move(public_path() . "/productImageUploads/", $imageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('image');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/' . $imageId . "." . $fileExtension,
                'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
                'CacheControl' => 'max-age=31536000'
            ));
        } else {
            dd('Resim yüklemelisiniz!');
        }

        DB::table('drop_down_banner')->insert([
            'active' => $tempStatus,
            'first_header' => Request::input('first_header'),
            'second_header' => Request::input('second_header'),
            'button_name' => Request::input('button_name'),
            'link_url' => Request::input('link_url'),
            'image' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $imageId . "." . $fileExtension,
        ]);

        return redirect('/admin/drop-down-list');
    }

    public function dropDownBanner()
    {

        return view('admin.dropDownBannerCreate');
    }

    public function updateDropDownMenu()
    {
        AdminPanelController::checkAdmin();

        for ($x = 1; $x < 40; $x++) {
            $header = 0;
            $tab = 0;
            if (Request::input('new_tab-' . $x)) {
                $tab = 1;
            }
            if (Request::input('header-' . $x)) {
                $header = 1;
            }

            DB::table('mega_drop_down')->where('id', $x)->update([
                'name' => Request::input('name-' . $x),
                'link' => Request::input('link-' . $x),
                'new_tab' => $tab,
                'header' => $header
            ]);
        }

        return redirect('/admin/mega-drop-down');

    }

    public function megaDropDown()
    {
        AdminPanelController::checkAdmin();

        $columnOne = DB::table('mega_drop_down')->where('segment', 1)->get();
        $columnTwo = DB::table('mega_drop_down')->where('segment', 2)->get();
        $columnThree = DB::table('mega_drop_down')->where('segment', 3)->get();

        return view('admin.megaDropDown', compact('columnOne', 'columnTwo', 'columnThree'));
    }

    public function updatePosType()
    {
        AdminPanelController::checkAdmin();

        DB::table('pos_type')->update([
            'active' => 0
        ]);

        DB::table('pos_type')->where('id', Request::input('posName'))->update([
            'active' => 1
        ]);

        $posTypes = DB::table('pos_type')->get();

        return view('admin.posTypes', compact('posTypes'));

    }

    public function paymentPostPage()
    {
        AdminPanelController::checkAdmin();
        $posTypes = DB::table('pos_type')->get();

        return view('admin.posTypes', compact('posTypes'));
    }

    public function updateCity()
    {
        AdminPanelController::checkAdmin();

        if (Request::input('id') == 0) {
            DB::table('user_city')->where('user_id', \Auth::user()->id)->update([
                'active' => 1
            ]);
        } else {
            DB::table('user_city')->where('user_id', \Auth::user()->id)->update([
                'active' => 0
            ]);
            DB::table('user_city')->where('user_id', \Auth::user()->id)->where('id', Request::input('id'))->update([
                'active' => 1
            ]);
        }


        return response()->json(["status" => 1], 200);
    }

    public function getUserCity()
    {
        AdminPanelController::checkAdmin();
        $data = DB::table('user_city')->join('city_list', 'user_city.city_id', '=', 'city_list.id')->where('user_id', \Auth::user()->id)->where('valid', 1)->orderBy('name')
            ->select('user_city.*', 'city_list.name')->get();

        $tempStatus = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->count();

        return response()->json(["status" => count($data), "data" => $data, 'active' => $tempStatus], 200);
    }

    public function manuelBillingPage()
    {
        $error = '';
        return view('admin.manuelBilling', compact('error'));

    }

    public function sendBillingManually()
    {
        DB::table('sales')->where('id', Request::input('delivery_id'))->update([
            'send_billing' => '0'
        ]);
        //BillingOperation::soapTest(Request::input('delivery_id'));
        $error = 'Oluşan faturayı uyumsoft üzerinden kontrol ediniz!';
        return view('admin.manuelBilling', compact('error'));
    }

    public function makeFlowerReady()
    {

        $nowForQuery = Carbon::now();
        $nowForQuery->hour(00);
        $nowForQuery->minute(00);
        $nowEndForQuery = Carbon::now();
        $nowEndForQuery->hour(23);
        $nowEndForQuery->minute(59);

        $tempSaleId = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $nowForQuery)
            ->where('deliveries.wanted_delivery_limit', '<', $nowEndForQuery)
            ->where('deliveries.status', 1)
            ->where('products.id', Request::input('id'))
            ->orderByRaw('deliveries.wanted_delivery_date')
            ->select('deliveries.id')
            ->get()[0];

        DB::table('deliveries')->where('id', $tempSaleId->id)->update([
            'status' => 6
        ]);


        return response()->json(["status" => Request::input('id')], 200);
    }

    public function flowerReady()
    {
        $nowForQuery = Carbon::now();
        $nowForQuery->hour(00);
        $nowForQuery->minute(00);
        $nowEndForQuery = Carbon::now();
        $nowEndForQuery->hour(23);
        $nowEndForQuery->minute(59);

        $tempFLowerList = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('images', 'products.id', '=', 'images.products_id')
            ->where('type', '=', 'main')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $nowForQuery)
            ->where('deliveries.wanted_delivery_limit', '<', $nowEndForQuery)
            ->where('deliveries.status', 1)
            ->groupBy('products.id')
            ->orderBy('products.name')
            ->selectRaw(' count(*) as totalFlower , products.name, products.id, images.image_url')
            ->get();

        return view('admin.flowerReady', compact('tempFLowerList'));
    }

    public function filterLiveFlowers()
    {
        $tempProducts = Request::input('products');
        $tempQuery = ' 1 = 1 and ';
        //foreach($tempProducts as $product){
        //    $tempQuery = $tempQuery . 'products.id = ' . $product . ' ';
        //}
        //dd(Request::input('products'));

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $nowForQuery = new Carbon(Request::input('startDate'));
        //$nowForQuery->addDay(-1);
        $nowForQuery->hour(00);
        $nowForQuery->minute(00);
        $nowEndForQuery = new Carbon(Request::input('endDate'));
        $nowEndForQuery->hour(23);
        $nowEndForQuery->minute(59);

        $tempFLowerList = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $nowForQuery)
            ->where('deliveries.wanted_delivery_limit', '<', $nowEndForQuery)
            ->where('deliveries.status', 1)
            ->whereRaw($tempWhere)
            ->groupBy('products.id')
            ->orderBy('products.name')
            ->selectRaw(' count(*) as totalFlower , products.name, products.id, 0 as selected')
            ->get();

        $tempLocationHour = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $nowForQuery)
            ->where('deliveries.wanted_delivery_limit', '<', $nowEndForQuery)
            ->where('deliveries.status', 1)
            ->whereRaw($tempWhere)
            ->groupBy(DB::raw('delivery_locations.continent_id, HOUR(wanted_delivery_date),HOUR(wanted_delivery_limit)'))
            ->orderBy(DB::raw('HOUR(wanted_delivery_date)'))
            ->selectRaw('delivery_locations.continent_id, HOUR(wanted_delivery_date) as start_hour,HOUR(wanted_delivery_limit) as end_hour, 0 as count, count(*) as totalRaw')
            ->get();

        foreach ($tempFLowerList as $flower) {
            $flower->locationList = [];
        }

        //dd($tempLocationHour);
        foreach ($tempFLowerList as $flower) {
            ///foreach($tempProducts as $product){
            //    if($product == $flower->id){
            //        $flower->selected = 1;
            //        continue;
            //    }
            //}
            $flower->selected = 1;
            foreach ($tempLocationHour as $key => $location) {
                $tempCount = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->where('sales.payment_methods', '=', 'OK')
                    ->where('deliveries.wanted_delivery_date', '>', $nowForQuery)
                    ->where('deliveries.wanted_delivery_limit', '<', $nowEndForQuery)
                    ->where('deliveries.status', 1)
                    ->where('sales_products.products_id', $flower->id)
                    ->where('delivery_locations.continent_id', $location->continent_id)
                    ->whereRaw($tempWhere)
                    ->whereRaw('HOUR(wanted_delivery_date) = ' . $location->start_hour . ' and HOUR(wanted_delivery_limit) = ' . $location->end_hour)
                    ->count();
                array_push($flower->locationList, (object)['continent_id' => $location->continent_id, 'start_hour' => $location->start_hour, 'end_hour' => $location->end_hour, 'count' => $tempCount]);
            }
        }

        $queryParams = new \Illuminate\Http\Request();

        return view('admin.todayFlowers', compact('tempFLowerList', 'tempLocationHour', 'queryParams'));
    }

    public function liveFlowers()
    {

        $nowForQuery = Carbon::now();
        $nowForQuery->hour(00);
        $nowForQuery->minute(00);
        $nowEndForQuery = Carbon::now();
        $nowEndForQuery->hour(23);
        $nowEndForQuery->minute(59);

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $tempFLowerList = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $nowForQuery)
            ->where('deliveries.wanted_delivery_limit', '<', $nowEndForQuery)
            ->where('deliveries.status', 1)
            ->whereRaw($tempWhere)
            ->groupBy('products.id')
            ->orderBy('products.name')
            ->selectRaw(' count(*) as totalFlower , products.name, products.id, 1 as selected')
            ->get();

        $tempLocationHour = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $nowForQuery)
            ->where('deliveries.wanted_delivery_limit', '<', $nowEndForQuery)
            ->where('deliveries.status', 1)
            ->whereRaw($tempWhere)
            ->groupBy(DB::raw('delivery_locations.continent_id, HOUR(wanted_delivery_date),HOUR(wanted_delivery_limit)'))
            ->orderBy(DB::raw('HOUR(wanted_delivery_date)'))
            ->selectRaw('delivery_locations.continent_id, HOUR(wanted_delivery_date) as start_hour,HOUR(wanted_delivery_limit) as end_hour, 0 as count, 1 as selected, count(*) as totalRaw')
            ->get();


        foreach ($tempFLowerList as $flower) {
            $flower->locationList = [];
        }

        //dd($tempLocationHour);
        foreach ($tempFLowerList as $flower) {
            foreach ($tempLocationHour as $key => $location) {
                $tempCount = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->where('sales.payment_methods', '=', 'OK')
                    ->where('deliveries.wanted_delivery_date', '>', $nowForQuery)
                    ->where('deliveries.wanted_delivery_limit', '<', $nowEndForQuery)
                    ->where('deliveries.status', 1)
                    ->where('sales_products.products_id', $flower->id)
                    ->whereRaw($tempWhere)
                    ->where('delivery_locations.continent_id', $location->continent_id)
                    ->whereRaw('HOUR(wanted_delivery_date) = ' . $location->start_hour . ' and HOUR(wanted_delivery_limit) = ' . $location->end_hour)
                    ->count();
                array_push($flower->locationList, (object)['continent_id' => $location->continent_id, 'start_hour' => $location->start_hour, 'end_hour' => $location->end_hour, 'count' => $tempCount]);
            }
        }

        $queryParams = [];

        return view('admin.todayFlowers', compact('tempFLowerList', 'tempLocationHour', 'queryParams'));

    }

    public function showFilterOperations()
    {
        AdminPanelController::checkAdmin();

        $startDate = Request::input('startDate');
        //$endDate = Request::input('endDate');
        $endDate = new Carbon(Request::input('endDate'));
        $endDate->hour(23);
        $endDate->minute(59);
        $endDate->second(59);

        $tempHours = DB::table('deliveries')->where('wanted_delivery_date', '>', $startDate)->where('wanted_delivery_date', '<', $endDate)
            ->selectRaw(' HOUR(wanted_delivery_date) as start_hour ,HOUR(wanted_delivery_limit) as end_hour')->groupBy(DB::raw(' HOUR(wanted_delivery_date),HOUR(wanted_delivery_limit)'))->orderBy('wanted_delivery_date')->get();
        //dd($tempHours);
        $totalGeneral = 0;

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        foreach ($tempHours as $eachHour) {
            //$today = Carbon::now();
            //$today->hour($eachHour->start_hour);
            //$today->hour(explode(":", $eachHour->start_hour)[0]);
            //$today->minute(00);
            //$today->second(00);
            //$todayEnd = Carbon::now();
            //$todayEnd->hour($eachHour->end_hour);
            //$todayEnd->hour(explode(":", $eachHour->end_hour)[0]);
            //$todayEnd->minute(00);
            //$todayEnd->second(00);
            $deliveryList = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->whereRaw('HOUR(deliveries.wanted_delivery_date) = ' . $eachHour->start_hour)
                ->whereRaw('HOUR(deliveries.wanted_delivery_limit) = ' . $eachHour->end_hour)
                ->whereRaw($tempWhere)
                ->where('deliveries.wanted_delivery_date', '>', $startDate)
                ->where('deliveries.wanted_delivery_limit', '<', $endDate)
                ->groupBy('delivery_locations.continent_id')
                ->groupBy(DB::raw('delivery_locations.continent_id, HOUR(wanted_delivery_date),HOUR(wanted_delivery_limit)'))
                ->orderBy('sales.created_at', 'DESC')
                ->selectRaw(' count(*) as totalRow , delivery_locations.continent_id')
                ->get();

            $eachHour->totalArray = 0;
            foreach ($deliveryList as $delivery) {
                $eachHour->totalArray = $eachHour->totalArray + $delivery->totalRow;
                $totalGeneral = $totalGeneral + $delivery->totalRow;
            }
            //dd($deliveryList);
            $eachHour->countContinent = $deliveryList;
            //dd($deliveryList);
        }
        $queryParams = new \Illuminate\Http\Request();
        //dd($tempHours);
        $boolFilter = true;
        return view('admin.todayOperation', compact('tempHours', 'queryParams', 'boolFilter', 'totalGeneral', 'cityList'));
    }

    public function showTodayOperations()
    {
        AdminPanelController::checkAdmin();
        $now = Carbon::now();
        $nowForQuery = Carbon::now();
        $nowForQuery->hour(00);
        $nowForQuery->minute(00);
        //$nowForQuery->addDay(1);
        $nowEndForQuery = Carbon::now();
        //$nowEndForQuery->addDay(1);
        $nowEndForQuery->hour(23);
        $nowEndForQuery->minute(59);

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $tempHours = DB::table('deliveries')
            ->join('sales', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('wanted_delivery_date', '>', $nowForQuery)->where('wanted_delivery_date', '<', $nowEndForQuery)->whereRaw($tempWhere)
            ->selectRaw(' HOUR(wanted_delivery_date) as start_hour ,HOUR(wanted_delivery_limit) as end_hour')->groupBy('wanted_delivery_date', 'wanted_delivery_limit')->orderBy('wanted_delivery_date')->get();
        foreach ($tempHours as $eachHour) {
            $today = Carbon::now();
            $today->hour($eachHour->start_hour);
            //$today->addDay(1);
            //$today->hour(explode(":", $eachHour->start_hour)[0]);
            $today->minute(00);
            $today->second(00);
            $todayEnd = Carbon::now();
            //$todayEnd->addDay(1);
            $todayEnd->hour($eachHour->end_hour);
            //$todayEnd->hour(explode(":", $eachHour->end_hour)[0]);
            $todayEnd->minute(00);
            $todayEnd->second(00);
            $deliveryList = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.wanted_delivery_date', '=', $today)
                ->where('deliveries.wanted_delivery_limit', '=', $todayEnd)
                ->whereRaw($tempWhere)
                ->groupBy('delivery_locations.continent_id', 'deliveries.status')
                ->orderBy('sales.created_at', 'DESC')
                ->selectRaw(' count(*) as totalRow , delivery_locations.continent_id, deliveries.status')
                ->get();
            $eachHour->totalArray = [0, 0, 0];
            foreach ($deliveryList as $delivery) {
                if ($delivery->status == 1 || $delivery->status == 6) {
                    $eachHour->totalArray[0] = $eachHour->totalArray[0] + $delivery->totalRow;
                } else if ($delivery->status == 2) {
                    $eachHour->totalArray[1] = $eachHour->totalArray[1] + $delivery->totalRow;
                } else if ($delivery->status == 3) {
                    $eachHour->totalArray[2] = $eachHour->totalArray[2] + $delivery->totalRow;
                }
            }
            $eachHour->countContinent = $deliveryList;
            //dd($deliveryList);
        }
        //dd($tempHours);
        $queryParams = [];
        $boolFilter = false;
        $totalGeneral = 0;
        return view('admin.todayOperation', compact('tempHours', 'queryParams', 'boolFilter', 'totalGeneral', 'cityList'));

    }

    public function showTodayCoupon()
    {
        AdminPanelController::checkAdmin();
        $today = Carbon::now();
        //$today->startOfDay();
        $tempRequest = new \Illuminate\Http\Request();
        if ($today->month < 10)
            $tempMonth = 0 . $today->month;
        else
            $tempMonth = $today->month;
        $tempRequest->created_at = $today->year . '-' . $tempMonth . '-' . $today->day;
        $tempRequest->replace([
            'created_at' => $tempRequest->created_at,
            'created_at_end' => $tempRequest->created_at_end,
            'couponName' => '0'
        ]);
        return AdminPanelController::filterCouponPage($tempRequest);
        //redirect('/filterBillingExcel', ['created_at' => $today]);
    }

    public function filterCouponPage(\Illuminate\Http\Request $request)
    {
        $allCoupons = DB::table('marketing_acts')
            ->selectRaw('name')
            ->groupBy('name')->get();
        AdminPanelController::checkAdmin();
        $companyList = [];
        array_push($companyList, (object)['information' => 'Mobilike', 'status' => 'mobilike']);
        array_push($companyList, (object)['information' => 'Itelligence', 'status' => 'itelligence']);
        array_push($companyList, (object)['information' => 'tr.pwc.com', 'status' => 'tr.pwc.com']);
        array_push($companyList, (object)['information' => 'enkaokullari.k12.tr', 'status' => 'enkaokullari.k12.tr']);
        array_push($companyList, (object)['information' => 'seranit.com.tr', 'status' => 'seranit.com.tr']);
        array_push($companyList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        //dd($queryParams->created_at);
        if ($queryParams->created_at_end) {
            $tempDate = logEventController::modifyEndDate($queryParams->created_at_end);
        }
        $queryString = " 1 = 1  ";
        if ($queryParams->created_at) {
            $queryString = $queryString . ' and sales.created_at > ' . " '" . $queryParams->created_at . "' ";
        }
        if ($queryParams->created_at_end) {
            $queryString = $queryString . ' and sales.created_at < ' . " '" . $tempDate . "' ";
        }
        //dd($queryString);
        if (Request::input('billing_active') == "on") {
            //dd(Request::input('billing_active'));
            $queryString = $queryString . ' and billings.userBilling != 0';
        } else
            $queryParams->billing_active = '';

        if (Request::input('payment_type') == "on") {
            //dd(Request::input('billing_active'));
            $queryString = $queryString . ' and sales.payment_type = "Kurumsal" ';
            if (Request::input('CompanyId')) {
                //dd(Request::input('billing_active'));
                if (Request::input('CompanyId') == "Hepsi") {
                    $queryParams->status = 'Hepsi';
                } else {
                    $queryString = $queryString . ' and sales.sender_email like "%' . Request::input('CompanyId') . '%" ';
                    $queryParams->status = Request::input('CompanyId');
                }
            } else
                $queryParams->status = 'Hepsi';
        } else {
            $queryParams->payment_type = '';
            $queryParams->status = 'Hepsi';
        }
        //dd(Request::input('couponName'));
        if (Request::input('couponName') != '0' && Request::input('couponName') != '1' && Request::input('couponName') != '2' && Request::input('couponName')) {
            $queryString = $queryString . ' and marketing_acts.name = "' . Request::input('couponName') . '" ';
        }
        if (Request::input('couponName') == '2' && Request::input('couponName')) {
            $queryString = $queryString . ' and sales.id NOT IN (SELECT sales_id FROM marketing_acts_sales)';
        }

        if (Request::input('couponName') != '0' && Request::input('couponName') != '2' && Request::input('couponName')) {
            $list = DB::table('sales')
                ->join('marketing_acts_sales', 'marketing_acts_sales.sales_id', '=', 'sales.id')
                ->join('marketing_acts', 'marketing_acts.id', '=', 'marketing_acts_sales.marketing_acts_id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->whereRaw($queryString)
                ->where('sales.payment_methods', 'OK')
                //->where('sales.payment_type' , '!=', 'FİYONGO')
                ->where('deliveries.status', '<>', '4')
                ->orderBy('sales.created_at', 'DESC')
                ->select('customers.user_id', 'sales.sender_email', 'sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling',
                    'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
                    'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'sales.sender_mobile', 'products.name as products', 'sales.sender_name', 'sales.sender_surname',
                    'sales.product_price as price', 'products.id')
                ->get();
        } else if ($queryParams->created_at_end || $queryParams->created_at || Request::input('billing_active') == "on" || Request::input('payment_type') == "on") {
            $list = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->whereRaw($queryString)
                ->where('sales.payment_methods', 'OK')
                //->where('sales.payment_type' , '!=', 'FİYONGO')
                ->where('deliveries.status', '<>', '4')
                ->orderBy('sales.created_at', 'DESC')
                ->select('customers.user_id', 'sales.sender_email', 'sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling',
                    'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
                    'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'sales.sender_mobile', 'products.name as products', 'sales.sender_name', 'sales.sender_surname',
                    'sales.product_price as price', 'products.id')
                ->get();
        } else {
            $list = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.payment_methods', 'OK')
                //->where('sales.payment_type' , '!=', 'FİYONGO')
                ->where('deliveries.status', '<>', '4')
                ->orderBy('sales.created_at', 'DESC')
                ->select('customers.user_id', 'sales.sender_email', 'sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.city', 'billings.small_city', 'billings.tc',
                    'billings.userBilling', 'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no',
                    'billings.billing_send', 'billings.billing_surname', 'billings.billing_name', 'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'products.name as products',
                    'sales.sender_name', 'sales.sender_surname', 'sales.product_price as price', 'sales.sender_mobile', 'products.id')
                ->get();
        }

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

                $totalKDV = $totalKDV + $row->discountValue;

                $row->discountValue = number_format($row->discountValue, 2);
                parse_str($row->discountValue);
                $row->discountValue = str_replace('.', ',', $row->discountValue);

                $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                $priceWithDiscount = number_format($priceWithDiscount, 2);

                $tempTotal = $priceWithDiscount;
                parse_str($priceWithDiscount);
                $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                $row->sumTotal = $priceWithDiscount;
                $row->discountName = "";

            } else {
                $row->discountName = $discount[0]->name;
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

            return view('admin.couponPage', compact('list', 'allCoupons', 'cikilotCount', 'cikilotTotalPrice', 'cikilotTotalTax', 'cikilotGeneral', 'cikilotBigGeneral', 'queryParams', 'total', 'totalKDV', 'totalPartial', 'totalDiscount', 'firstPrice', 'count', 'avarageDiscount', 'companyList'));
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

        return view('admin.couponPage', compact('list', 'allCoupons', 'cikilotCount', 'cikilotTotalPrice', 'cikilotTotalTax', 'cikilotGeneral', 'cikilotBigGeneral', 'queryParams', 'total', 'totalKDV', 'totalPartial', 'totalDiscount', 'firstPrice', 'count', 'avarageDiscount', 'companyList'));
    }

    public function couponManagement()
    {
        AdminPanelController::checkAdmin();

        $products = DB::table('marketing_acts')
            ->selectRaw('name, count(*) as totalSum, value')
            ->groupBy('name')->get();

        foreach ($products as $product) {
            $product->usedCoupon = DB::table('marketing_acts')
                ->join('marketing_acts_sales', 'marketing_acts_sales.marketing_acts_id', '=', 'marketing_acts.id')
                ->join('sales', 'sales.id', '=', 'marketing_acts_sales.sales_id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '=', '3')->where('name', $product->name)->count();
            $product->listCoupon = DB::table('marketing_acts')->where('active', 1)->where('name', $product->name)->count();
        }

        return view('admin.couponManagement', compact('products'));
    }

    public function insertMultipleCompany()
    {
        DB::table('company_billing')->insert([
            'company' => Request::input('name'),
            'mobile' => '02122120282',
            'billing_address' => 'billing_address',
            'tax_office' => 'tax_office',
            'tax_no' => 'tax_no',
            'small_city' => 'small_city',
            'city' => 'istanbul'
        ]);
        return redirect('/admin/multipleCompany/general');
    }

    public function createMultipleCompany()
    {
        return view('admin.multipleCompanyListCreate');
    }

    public function getMultipleCompany()
    {
        AdminPanelController::checkAdmin();

        $products = DB::table('company_billing')->get();

        return view('admin.multipleCompanyList', compact('products'));
    }

    public function deleteMultipleCompany()
    {
        DB::table('company_billing')->where('id', Request::input('id'))->delete();

        return redirect('/admin/multipleCompany/general');
    }

    public function updateCompanyFlowerStatus()
    {
        AdminPanelController::checkAdmin();
        DB::table('companies_info')->where('id', Request::input('id'))->update([
            'flower_status' => Request::input('value')
        ]);
        return response()->json(["status" => 1], 200);
    }

    public function showUpMenu()
    {
        $tempUpMenu = DB::table('up_menu')->orderBy('order')->get();

        return view('admin.upMenu', compact('tempUpMenu'));
    }

    public function updateUpMenu()
    {
        DB::table('up_menu')->where('id', Request::input('id'))->update([
            'order' => Request::input('order'),
            'name' => Request::input('name'),
            'url' => Request::input('url'),
            'open_style' => Request::input('open_style')
        ]);

        $tempUpMenu = DB::table('up_menu')->orderBy('order')->get();

        return view('admin.upMenu', compact('tempUpMenu'));
    }

    public static function getCikolatImage($sale_id)
    {
        $tempValue = DB::table('cross_sell')
            ->join('cross_sell_products', 'cross_sell.product_id', '=', 'cross_sell_products.id')
            ->select('cross_sell_products.image')
            ->where('sales_id', $sale_id)->get();
        if (count($tempValue) == 0) {
            return null;
        } else {
            return $tempValue[0]->image;
        }
    }

    public static function getCikolatData($sale_id)
    {
        $tempValue = DB::table('cross_sell')
            ->join('cross_sell_products', 'cross_sell.product_id', '=', 'cross_sell_products.id')
            ->select('cross_sell.discount', 'cross_sell_products.image', 'cross_sell_products.name', 'cross_sell.product_price', 'cross_sell.product_price', 'cross_sell.total_price', 'cross_sell.tax', 'cross_sell_products.id', 'cross_sell_products.desc')
            ->where('sales_id', $sale_id)->get();
        if (count($tempValue) == 0) {
            return null;
        } else {
            return $tempValue[0];
        }
    }

    public function updateCrossSellProduct()
    {
        $tempStatus = 0;
        if (Request::input('status') == 'on') {
            $tempStatus = 1;
        }
        $tempImageId = 'product_' . (string)(rand(0, 1000));
        $pastImageId = DB::table('cross_sell_products')->where('id', Request::input('id'))->get()[0]->image;
        if (Request::hasFile('image')) {
            $siteUrl = $this->backend_url;
            $file = Request::file('image');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];

            $fileMoved = Request::file('image')->move(public_path() . "/productImageUploads/", $tempImageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('image');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/' . $tempImageId . "." . $fileExtension,
                'Body' => fopen(public_path() . "/productImageUploads/" . $tempImageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
                'CacheControl' => 'max-age=31536000'
            ));
            $pastImageId = 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $tempImageId . "." . $fileExtension;
        }

        DB::table('cross_sell_products')->where('id', Request::input('id'))->update([
            'name' => Request::input('name'),
            'desc' => Request::input('desc'),
            'price' => Request::input('price'),
            'status' => $tempStatus,
            'sort_number' => Request::input('sort_number'),
            'image' => $pastImageId
        ]);

        return redirect('/admin/crossSell/products');
    }

    public function deleteCrossSellProduct()
    {
        DB::table('cross_sell_products')->where('id', Request::input('id'))->delete();

        return redirect('/admin/crossSell/products');
    }

    public function insertCrossSellProduct()
    {
        $tempStatus = 0;
        if (Request::input('status') == 'on') {
            $tempStatus = 1;
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        if (count($cityList) > 1 || count($cityList) == 0) {
            dd('Şehir seçmelisiniz');
        }

        $tempImageId = 'product_' . (string)(rand(0, 1000));
        if (Request::hasFile('image')) {
            $siteUrl = $this->backend_url;
            $file = Request::file('image');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];


            $fileMoved = Request::file('image')->move(public_path() . "/productImageUploads/", $tempImageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('image');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/' . $tempImageId . "." . $fileExtension,
                'Body' => fopen(public_path() . "/productImageUploads/" . $tempImageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
                'CacheControl' => 'max-age=31536000'
            ));
        } else {
            dd("Resim yüklemelisin -_-' ");
        }

        $crossSellId = DB::table('cross_sell_products')->insertGetId([
            'name' => Request::input('name'),
            'city_id' => $cityList[0]->city_id,
            'desc' => Request::input('desc'),
            'price' => Request::input('price'),
            'status' => $tempStatus,
            'sort_number' => Request::input('sort_number'),
            'image' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $tempImageId . "." . $fileExtension,
        ]);

        if ($cityList[0]->city_id == 1) {

            $now = Carbon::now();

            $stockId = DB::table('product_stocks')->insertGetId([
                'product_id' => 0,
                'cross_sell_id' => $crossSellId,
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

        return redirect('/admin/crossSell/products');
    }

    public function createCrossProduct()
    {
        return view('admin.crossProductCreate');
    }

    public function getCrossProduct($id)
    {
        AdminPanelController::checkAdmin();

        $product = DB::table('cross_sell_products')->where('id', $id)->get()[0];

        return view('admin.crossProduct', compact('product'));
    }

    public function getCrossProducts()
    {
        AdminPanelController::checkAdmin();

        $cityList = DB::table('user_city')->join('city_list', 'user_city.city_id', '=', 'city_list.id')->where('user_city.user_id', \Auth::user()->id)->where('user_city.active', 1)
            ->where('valid', 1)->select('user_city.city_id', 'city_list.name')->get();
        $tempWhere = ' 1 = 0 ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or cross_sell_products.city_id = ' . $city->city_id;
        }

        $products = DB::table('cross_sell_products')->whereRaw($tempWhere)->get();

        return view('admin.crossProducts', compact('products'));
    }

    public function crossUpdateStatus()
    {

        //if (isset($input['status']))
        //    $input['status'] = 1;
        //else
        //    $input['status'] = 0;
        $tempStatus = 0;
        if (Request::input('status') == 'on') {
            $tempStatus = 1;
        }

        DB::table('cross_sell_options')->update([
            'active' => $tempStatus
        ]);

        return redirect('/admin/crossSell/general');
    }

    public function getCrossGeneral()
    {
        AdminPanelController::checkAdmin();

        $cityList = DB::table('user_city')->join('city_list', 'user_city.city_id', '=', 'city_list.id')->where('user_city.user_id', \Auth::user()->id)->where('user_city.active', 1)
            ->where('valid', 1)->select('user_city.city_id', 'city_list.name')->get();
        $tempWhere = ' 1 = 0 ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or cross_sell_options.city_id = ' . $city->city_id;
        }

        $optionsData = DB::table('cross_sell_options')->whereRaw($tempWhere)->get();

        return view('admin.crossGeneral', compact('optionsData', 'cityList'));
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
                return false;
            }
            else{
                $tempCrossSellData = DB::table('cross_sell')->where('sales_id', $productData->id )->get();

                if( count($tempCrossSellData) > 0 ){
                    if( !generateDataController::isCrossSellAvailable($tempCrossSellData[0]->product_id , $saleCity) ){
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

    public function updatePrimeValues()
    {
        DB::table('primeValues')->update([
            'month_value' => Request::input('month_value'),
            'friday_value' => Request::input('friday_value'),
            'name' => Request::input('name'),
            'description' => Request::input('description'),
            'month_name' => Request::input('month_name'),
            'month_description' => Request::input('month_description')
        ]);

        return redirect('/admin/getPrimeValues');
    }

    public function getPrimePage()
    {
        $primeValue = DB::table('primeValues')->get()[0];
        return view('admin.primeValues', compact('primeValue'));
    }

    public function setPrimeCustomers()
    {
        $id = Request::input('id');
        $user_id = DB::table('customers')->where('id', $id)->get()[0]->user_id;

        DB::table('users')->where('id', $user_id)->update([
            'prime' => '1'
        ]);

        $mailchimp = \App::make('Mailchimp');
        $mailchimp->lists->subscribe('a4242a15fc', array('email' => DB::table('users')->where('id', $user_id)->get()[0]->email), null, 'html', false, true, true, false);

        return response()->json(["status" => 1, "data" => Request::input('id')], 200);
    }

    public function getIndex()
    {
        return view('admin.addPrimeCustomers');
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
            ->join('users', 'users.id', '=', 'customers.user_id')
            ->select('customers.name', 'customers.surname', 'customers.created_at', 'customers.user_id', 'customers.mobile', 'customers.id', 'users.prime', 'users.email',
                DB::raw('(select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) as contactNumber'),
                DB::raw('(select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) as salesNumber'),
                DB::raw('(select status from users where users.id = customers.user_id) as status'),
                DB::raw('users.updated_at')
            )
            ->where('users.created_at', '>', $arrStart)
            ->where('users.created_at', '<', $arrEnd)
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
                'totalNumber' => $totalNumber,
                'prime' => $value->prime
            ]);
        }
        return Datatables::of($tempArray)->make(true);
    }

    public function showPrimeCustomers()
    {
        AdminPanelController::checkAdmin();
        $customers = Customer::orderBy('created_at', 'DESC')
            ->join('users', 'customers.user_id', '=', 'users.id')
            ->where('users.prime', '>', '0')
            ->select('customers.*',
                DB::raw('(select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) as contactNumber'),
                DB::raw('(select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) as salesNumber'),
                DB::raw('(select status from users where users.id = customers.user_id) as status'),
                DB::raw('(select updated_at from users where users.id = customers.user_id) as userLastLogin'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) as email'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) as updated_at'),
                DB::raw('( select (CASE WHEN users.status IS NULL THEN "Mail" ELSE "FB" END) as status from users where users.id = customers.user_id  ) as status')
            )
            ->get();

        return view('admin.primeCustomers', compact('customers'));
    }

    public function removePrime()
    {
        $userId = DB::table('customers')->where('id', Request::input('id'))->get()[0]->user_id;
        DB::table('users')->where('id', $userId)->update([
            'prime' => '0'
        ]);

        $mailchimp = \App::make('Mailchimp');
        $mailchimp->lists->unsubscribe('a4242a15fc', array('email' => DB::table('users')->where('id', $userId)->get()[0]->email), false, false, false);

        return response()->json(["status" => 1, "data" => Request::input('id')], 200);
    }

    public function getDeliveriesCount()
    {
        AdminPanelController::checkAdmin();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $today = Carbon::now();
        $today->startOfDay();

        $todayEnd = Carbon::now();
        $todayEnd->endOfDay();
        $tempSalesCount = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->whereRaw($tempWhere)
            ->count();

        $tempSalesOngoing = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '=', '1')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->whereRaw($tempWhere)
            ->count();

        $tempSalesReady = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '=', '6')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->whereRaw($tempWhere)
            ->count();

        $tempSalesOnWay = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '=', '2')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->whereRaw($tempWhere)
            ->count();

        $tempSalesCompleted = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '=', '3')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->whereRaw($tempWhere)
            ->count();

        $tempSalesCanceled = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '=', '4')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->whereRaw($tempWhere)
            ->count();

        return response()->json([
            "value" => $tempSalesCount,
            "tempSalesOngoing" => $tempSalesOngoing,
            "tempSalesReady" => $tempSalesReady,
            "tempSalesOnWay" => $tempSalesOnWay,
            "tempSalesCompleted" => $tempSalesCompleted,
            "tempSalesCanceled" => $tempSalesCanceled
        ], 200);
    }

    public function getCustomersCount()
    {
        AdminPanelController::checkAdmin();

        $today = Carbon::now();
        $today->startOfDay();

        $tempSalesCount = DB::table('customers')
            ->where('created_at', '>', $today)
            ->count();
        return response()->json(["value" => $tempSalesCount], 200);
    }

    public function getSalesCount()
    {
        AdminPanelController::checkAdmin();

        $today = Carbon::now();
        $today->startOfDay();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $tempSalesCount = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->whereRaw($tempWhere)
            ->where('sales.created_at', '>', $today)
            ->count();
        return response()->json(["value" => $tempSalesCount], 200);
    }

    public function showCustomersNew()
    {
        AdminPanelController::checkAdmin();
        $customers = Customer::orderBy('created_at', 'DESC')
            ->select('customers.*',
                DB::raw('(select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) as contactNumber'),
                DB::raw('(select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) as salesNumber'),
                DB::raw('(select status from users where users.id = customers.user_id) as status'),
                DB::raw('(select updated_at from users where users.id = customers.user_id) as userLastLogin'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) as email'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) as updated_at'),
                DB::raw('( select (CASE WHEN users.status IS NULL THEN "Mail" ELSE "FB" END) as status from users where users.id = customers.user_id  ) as status')
            )
            ->get();
        //dd($customers[0]);
        $anonimNumber = Customer::where('user_id', '=', null)->count();
        $userNumber = User::count();
        $totalNumber = $userNumber + $anonimNumber;

        return view('admin.newCustomers', compact('customers', 'userNumber', 'anonimNumber', 'totalNumber'));
    }


    public function addNewAdminUser()
    {
        DB::table('users')->where('email', Request::get('email'))->update([
            'user_group_id' => 1
        ]);

        $tempData = DB::table('user_rights')->where('user_id', '1')->select('name', 'group_name', 'active', 'user_id', 'name_id')->get();

        DB::table('user_rights')->where('user_id', DB::table('users')->where('email', Request::get('email'))->get()[0]->id)->delete();
        foreach ($tempData as $data) {
            DB::table('user_rights')->insert([
                'name' => $data->name,
                'group_name' => $data->group_name,
                'active' => $data->active,
                'user_id' => DB::table('users')->where('email', Request::get('email'))->get()[0]->id,
                'name_id' => $data->name_id
            ]);
        }

        $tempCity = DB::table('city_list')->get();

        foreach ($tempCity as $city) {

            DB::table('user_city')->insert([
                'user_id' => DB::table('users')->where('email', Request::get('email'))->get()[0]->id,
                'city_id' => $city->id,
                'valid' => 0,
                'active' => 0
            ]);

        }


        return redirect('/admin/user-rights');
    }

    public function addAdminUser()
    {
        return view('admin.addAdminUser');
    }

    public function getUserRights()
    {
        AdminPanelController::checkAdmin();
        $data = DB::table('user_rights')->where('user_id', \Auth::user()->id)->where('active', 1)->get();

        return response()->json(["data" => $data], 200);
    }

    public function deleteAdminUser($userId)
    {
        DB::table('users')->where('id', $userId)->update([
            'user_group_id' => 3
        ]);
        DB::table('user_rights')->where('user_id', $userId)->delete();
        return redirect('/admin/user-rights');
    }

    public function updateUserRights(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $input = $request->all();
        DB::table('user_rights')->where('user_id', $input['user_id'])->where('group_name', 'customers')->update([
            'active' => 0
        ]);
        if (Request::get('customers'))
            foreach ($input['customers'] as $tag) {
                DB::table('user_rights')->where('id', $tag)
                    ->update([
                        'id' => $tag,
                        'active' => 1
                    ]);
            }

        DB::table('user_city')->where('user_id', $input['user_id'])->update([
            'valid' => 0
        ]);
        //dd($input['user_city']);
        if (Request::get('user_city'))
            foreach ($input['user_city'] as $tag) {
                DB::table('user_city')->where('id', $tag)
                    ->update([
                        'valid' => 1,
                        'active' => 1
                    ]);
            }

        DB::table('user_rights')->where('user_id', $input['user_id'])->where('group_name', 'delivery')->update([
            'active' => 0
        ]);
        if (Request::get('deliveries'))
            foreach ($input['deliveries'] as $tag) {
                DB::table('user_rights')->where('id', $tag)
                    ->update([
                        'id' => $tag,
                        'active' => 1
                    ]);
            }

        DB::table('user_rights')->where('user_id', $input['user_id'])->where('group_name', 'product')->update([
            'active' => 0
        ]);
        if (Request::get('products'))
            foreach ($input['products'] as $tag) {
                DB::table('user_rights')->where('id', $tag)
                    ->update([
                        'id' => $tag,
                        'active' => 1
                    ]);
            }

        DB::table('user_rights')->where('user_id', $input['user_id'])->where('group_name', 'site')->update([
            'active' => 0
        ]);
        if (Request::get('sites'))
            foreach ($input['sites'] as $tag) {
                DB::table('user_rights')->where('id', $tag)
                    ->update([
                        'id' => $tag,
                        'active' => 1
                    ]);
            }

        DB::table('user_rights')->where('user_id', $input['user_id'])->where('group_name', 'rapor')->update([
            'active' => 0
        ]);
        if (Request::get('rapor'))
            foreach ($input['rapor'] as $tag) {
                DB::table('user_rights')->where('id', $tag)
                    ->update([
                        'id' => $tag,
                        'active' => 1
                    ]);
            }

        DB::table('user_rights')->where('user_id', $input['user_id'])->where('group_name', 'B2B')->update([
            'active' => 0
        ]);
        if (Request::get('B2B'))
            foreach (Request::get('B2B') as $tag) {
                DB::table('user_rights')->where('id', $tag)
                    ->update([
                        'id' => $tag,
                        'active' => 1
                    ]);
            }

        DB::table('user_rights')->where('user_id', $input['user_id'])->where('group_name', 'studioBloom')->update([
            'active' => 0
        ]);
        if (Request::get('studioBloom'))
            foreach ($input['studioBloom'] as $tag) {
                DB::table('user_rights')->where('id', $tag)
                    ->update([
                        'id' => $tag,
                        'active' => 1
                    ]);
            }

        DB::table('user_rights')->where('user_id', $input['user_id'])->where('group_name', 'user_right')->update([
            'active' => 0
        ]);
        if (Request::get('user_right'))
            foreach ($input['user_right'] as $tag) {
                DB::table('user_rights')->where('id', $tag)
                    ->update([
                        'id' => $tag,
                        'active' => 1
                    ]);
            }
        return redirect('/admin/user-rights');
    }

    public function showCourRoute()
    {
        AdminPanelController::checkAdmin();
        $operationList = DB::table('operation_person')->get();

        $deliveriesByOne = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '=', 2)
            ->select('sales.id', 'deliveries.operation_id', 'deliveries.products',
                'delivery_locations.district', 'sales.sender_name', 'sales.sender_surname', 'sales.geoLocation')
            ->get();

        return view('admin.showCourRoutePage', compact('deliveriesByOne', 'operationList'));
    }

    public function defaultProductReminder()
    {

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or product_reminder.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $myArray = DB::table('product_reminder')
            ->join('products', 'product_reminder.product_id', '=', 'products.id')
            ->groupBy('product_id')->select('product_id', 'name')->get();

        foreach ($myArray as $array) {
            $tempProductAvalibility = DB::table('product_reminder')
                ->join("product_city", function ($join) {
                    $join->on("product_city.product_id", "=", "product_reminder.product_id")
                        ->on("product_city.city_id", "=", "product_reminder.city_id");
                })
                ->where('product_city.activation_status_id', 1)
                ->where('product_city.limit_statu', 0)
                ->where('product_reminder.mail_send', 0)
                ->where('product_city.coming_soon', 0)
                //->join('products', 'product_reminder.product_id', '=', 'products.id')
                ->whereRaw($tempWhere)
                ->where('product_city.product_id', $array->product_id)
                ->count();
            if ($tempProductAvalibility > 0) {
                $array->checked = 'checked';
            } else $array->checked = '';
        }

        $mailList = DB::table('product_reminder')
            ->join("product_city", function ($join) {
                $join->on("product_city.product_id", "=", "product_reminder.product_id")
                    ->on("product_city.city_id", "=", "product_reminder.city_id");
            })
            ->join('products', 'product_reminder.product_id', '=', 'products.id')
            ->where('product_city.activation_status_id', 1)
            ->where('product_city.limit_statu', 0)
            ->where('product_reminder.mail_send', 0)
            ->where('product_city.coming_soon', 0)
            ->where('products.city_id', 1)
            ->whereRaw($tempWhere)
            ->select('product_reminder.id', 'products.id as product_id', 'products.name', 'mail', 'product_reminder.created_at', 'mail_send')->get();

        $mailStatus = 'waiting';

        $topWaitingFlowers = DB::table('product_reminder')
            ->join('products', 'product_reminder.product_id', '=', 'products.id')
            ->where('mail_send', 0)
            ->whereRaw($tempWhere)
            ->groupBy('product_id')
            ->orderBy(DB::raw(" count(*) "), 'DESC')
            ->select('products.name', DB::raw(" count(*) as totalWaiting"))
            ->take(20)
            ->get();

        return view('admin.productReminder', compact('myArray', 'mailList', 'mailStatus', 'topWaitingFlowers'));
    }

    public function updateStudioBloomNote()
    {
        DB::table('studioBloom')->where('id', Request::input('id'))
            ->update([
                'note' => Request::input('note')
            ]);
        return response()->json(["status" => Request::get('id'), "note" => Request::get('note'), "data" => Request::all()], 200);
    }

    public function deleteProductCompany()
    {
        AdminPanelController::checkAdmin();
        $id = Request::input('id');
        try {
            $numberOfSale = count(DB::table('sales_products')
                ->where('products_id', '=', $id)
                ->get());
            if ($numberOfSale > 0) {
                return "Siparis verilmis urunu silemezsiniz!";
            }
            DB::table('products_tags')->where('products_id', '=', $id)->delete();
            Description::where('products_id', '=', $id)->delete();
            Image::where('products_id', '=', $id)->delete();
            Product::destroy([$id]);
        } catch (\Exception $e) {
            DB::rollback();
            return "Siparis verilmis urunu silemezsiniz!";
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();

        return redirect('/admin/CompanyInfo/productCompanyList');
    }

    public function updateProductCompany(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        DB::beginTransaction();
        try {
            $tempAl = Description::where('products_id', $input['id'])->get();
            if (count($tempAl) != 0) {
                Description::where('products_id', '=', $input['id'])->where('lang_id', '=', 'tr')->update(
                    [
                        'landing_page_desc' => $input['landing_page_desc'],
                        'detail_page_desc' => $input['detail_page_desc'],
                        'how_to_title' => $input['how_to_title'],
                        'how_to_detail' => $input['how_to_detail'],
                        'how_to_step1' => $input['how_to_step1'],
                        'how_to_step2' => $input['how_to_step2'],
                        'how_to_step3' => $input['how_to_step3'],
                        'extra_info_1' => $input['extra_info_1'],
                        'extra_info_2' => $input['extra_info_2'],
                        'extra_info_3' => $input['extra_info_3'],
                        'url_title' => $input['url_title'],
                        'img_title' => $input['img_title'],
                        'meta_description' => $input['meta_description']
                    ]
                );

            }

            $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

            foreach ($langList as $lang) {

                if (Description::where('products_id', '=', $input['id'])->where('lang_id', '=', $lang->lang_id)->count() > 0) {
                    Description::where('products_id', '=', $input['id'])->where('lang_id', '=', $lang->lang_id)->update(
                        [
                            'landing_page_desc' => $input['landing_page_desc' . $lang->lang_id],
                            'detail_page_desc' => $input['detail_page_desc' . $lang->lang_id],
                            'how_to_title' => $input['how_to_title' . $lang->lang_id],
                            'how_to_detail' => $input['how_to_detail' . $lang->lang_id],
                            'how_to_step1' => $input['how_to_step1' . $lang->lang_id],
                            'how_to_step2' => $input['how_to_step2' . $lang->lang_id],
                            'how_to_step3' => $input['how_to_step3' . $lang->lang_id],
                            'extra_info_1' => $input['extra_info_1' . $lang->lang_id],
                            'extra_info_2' => $input['extra_info_2' . $lang->lang_id],
                            'extra_info_3' => $input['extra_info_3' . $lang->lang_id],
                            'products_id' => $input['id'],
                            'url_title' => $input['url_title' . $lang->lang_id],
                            'img_title' => $input['img_title' . $lang->lang_id],
                            'meta_description' => $input['meta_description' . $lang->lang_id]
                        ]
                    );
                } else {
                    Description::create(
                        [
                            'landing_page_desc' => $input['landing_page_desc' . $lang->lang_id],
                            'detail_page_desc' => $input['detail_page_desc' . $lang->lang_id],
                            'how_to_title' => $input['how_to_title' . $lang->lang_id],
                            'how_to_detail' => $input['how_to_detail' . $lang->lang_id],
                            'how_to_step1' => $input['how_to_step1' . $lang->lang_id],
                            'how_to_step2' => $input['how_to_step2' . $lang->lang_id],
                            'how_to_step3' => $input['how_to_step3' . $lang->lang_id],
                            'extra_info_1' => $input['extra_info_1' . $lang->lang_id],
                            'extra_info_2' => $input['extra_info_2' . $lang->lang_id],
                            'extra_info_3' => $input['extra_info_3' . $lang->lang_id],
                            'products_id' => $input['id'],
                            'url_title' => $input['url_title' . $lang->lang_id],
                            'img_title' => $input['img_title' . $lang->lang_id],
                            'meta_description' => $input['meta_description' . $lang->lang_id],
                            'lang_id' => $lang->lang_id
                        ]
                    );
                }
            }

            //$input['price'] = floatval(str_replace(',', '.',$input['price']));
            DB::table('products_tags')->where('products_id', '=', $input['id'])->delete();
            foreach ($input['allTags'] as $tag) {
                DB::table('products_tags')
                    ->insert([
                        'tags_id' => $tag,
                        'products_id' => (int)$input['id']
                    ]);
            }

            if (isset($input['limit_statu']))
                $input['limit_statu'] = 1;
            else
                $input['limit_statu'] = 0;


            if (isset($input['coming_soon']))
                $input['coming_soon'] = 1;
            else
                $input['coming_soon'] = 0;

            $input['id'] = (int)$input['id'];
            $product = Product::find($input['id']);
            $product->update($input);

            if (Request::hasFile('img')) {
                $file = Request::file('img');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                $imageId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'main')->get()[0]->id;

                $versionId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'main')->get()[0]->version_id;

                if ($versionId == 0) {
                    $versionId = 1;
                } else {
                    $versionId = $versionId + 1;
                }

                $fileMoved = Request::file('img')->move(public_path() . "/productImageUploads/", $input['image_name'] . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "_" . $versionId . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "_" . $versionId . "." . $fileExtension,
                    'version_id' => $versionId
                ]);
            }

            if (Request::hasFile('imgDetail')) {
                $file = Request::file('imgDetail');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->count() > 0) {
                    $versionId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->get()[0]->version_id;
                } else {
                    $versionId = 0;
                }


                if ($versionId == 0) {
                    $versionId = 1;
                } else {
                    $versionId = $versionId + 1;
                }

                $fileMoved = Request::file('imgDetail')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-detail" . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('imgDetail');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-detail" . "_" . $versionId . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-detail" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->count() > 0) {
                    $imageId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->get()[0]->id;
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-detail" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId
                    ]);
                } else {
                    Image::create([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-detail" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId,
                        'type' => 'detailPhoto',
                        'products_id' => $input['id'],
                    ]);
                }

            }

            $imageList = DB::table('images')
                ->where('products_id', '=', $input['id'])
                ->where('type', '=', 'detailImages')
                ->get();
            for ($y = 0; $y < count($imageList); $y++) {
                if ($y == 0) {
                    if (Request::hasFile('img1')) {
                        $file = Request::file('img1');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img1')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide1' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                            'order_no' => 1
                        ]);
                    }
                } else if ($y == 1) {
                    if (Request::hasFile('img2')) {
                        $file = Request::file('img2');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img2')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide2' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                            'order_no' => 2
                        ]);
                    }
                } else if ($y == 2) {
                    if (Request::hasFile('img3')) {
                        $file = Request::file('img3');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img3')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide3' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                            'order_no' => 3
                        ]);
                    }
                } else if ($y == 3) {
                    if (Request::hasFile('img4')) {
                        $file = Request::file('img4');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img4')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide4' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                            'order_no' => 4
                        ]);
                    }
                } else if ($y == 4) {
                    if (Request::hasFile('img5')) {
                        $file = Request::file('img5');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img5')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide5' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                            'order_no' => 5
                        ]);
                    }
                } else if ($y == 5) {
                    if (Request::hasFile('img6')) {
                        $file = Request::file('img6');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img6')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide6' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                            'order_no' => 6
                        ]);
                    }
                } else if ($y == 6) {
                    if (Request::hasFile('img7')) {
                        $file = Request::file('img7');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img7')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide7' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                            'order_no' => 7
                        ]);
                    }
                } else if ($y == 7) {
                    if (Request::hasFile('img8')) {
                        $file = Request::file('img8');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img8')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide8' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                            'order_no' => 8
                        ]);
                    }
                } else if ($y == 8) {
                    if (Request::hasFile('img9')) {
                        $file = Request::file('img9');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img9')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide9' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                            'order_no' => 9
                        ]);
                    }
                } else if ($y == 9) {
                    if (Request::hasFile('img10')) {
                        $file = Request::file('img10');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img10')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide10' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                            'order_no' => 10
                        ]);
                    }
                }
            }
            if (count($imageList) < 10) {
                if (Request::hasFile('img10')) {
                    $file = Request::file('img10');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img10')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide10' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                        'order_no' => 10
                    ]);
                }
            }

            if (count($imageList) < 9) {
                if (Request::hasFile('img9')) {
                    $file = Request::file('img9');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img9')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide9' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                        'order_no' => 9
                    ]);
                }
            }

            if (count($imageList) < 8) {
                if (Request::hasFile('img8')) {
                    $file = Request::file('img8');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img8')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide8' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                        'order_no' => 8
                    ]);
                }
            }

            if (count($imageList) < 7) {
                if (Request::hasFile('img7')) {
                    $file = Request::file('img7');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img7')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide7' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                        'order_no' => 7
                    ]);
                }
            }

            if (count($imageList) < 6) {
                if (Request::hasFile('img6')) {
                    $file = Request::file('img6');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img6')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide6' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                        'order_no' => 6
                    ]);
                }
            }

            if (count($imageList) < 5) {
                if (Request::hasFile('img5')) {
                    $file = Request::file('img5');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img5')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide5' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                        'order_no' => 5
                    ]);
                }
            }

            if (count($imageList) < 4) {
                if (Request::hasFile('img4')) {
                    $file = Request::file('img4');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id']
                        ])->id;
                    $fileMoved = Request::file('img4')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide4' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                        'order_no' => 4
                    ]);
                }
            }

            if (count($imageList) < 3) {
                if (Request::hasFile('img3')) {
                    $file = Request::file('img3');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id']
                        ])->id;
                    $fileMoved = Request::file('img3')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide3' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                        'order_no' => 3
                    ]);
                }
            }

            if (count($imageList) < 2) {
                if (Request::hasFile('img2')) {
                    $file = Request::file('img2');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id']
                        ])->id;
                    $fileMoved = Request::file('img2')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide2' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                        'order_no' => 2
                    ]);
                }
            }

            if (count($imageList) < 1) {
                if (Request::hasFile('img1')) {
                    $file = Request::file('img1');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img1')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide1' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                        'order_no' => 1
                    ]);
                }
            }

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/CompanyInfo/productCompanyList');
//            \Mail::send('emails.new-issue', array('key' => 'value'), function($message)
//            {
//                $message->to('murat.susanli@ifgirisim.com', 'Bloom & Fresh')->subject('Bloom & Fresh New Product Added!');
//            });

        // File upload
    }

    public function productCompanyDetail($id)
    {
        AdminPanelController::checkAdmin();
        //$products = Product::latest('published_at')->get();
        //$products = Product::where('id' , $id)->get();

        $products = DB::table('products')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->join('product_for_companies', 'products.product_id_for_company', '=', 'product_for_companies.id')
            ->join('companies_info', 'products.company_product', '=', 'companies_info.id')
            //->join('images', 'products.id', '=', 'images.products_id')
            //->join('products_tags', 'products.id', '=', 'products_tags.products_id')
            //->join('tags', 'products_tags.tags_id', '=', 'tags.id')
            ->where('products.id', '=', $id)
            ->where('descriptions.lang_id', '=', 'tr')
            ->select('products.tag_id', 'products.id', 'products.name', 'products.activation_status_id', 'products.limit_statu', 'products.coming_soon', 'products.price', 'products.description', 'products.image_name', 'products.background_color', 'products.second_background_color',
                'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre', 'products.company_product', 'products.product_id_for_company', 'product_for_companies.name as companyProductName', 'companies_info.name as companyName'
                , 'descriptions.how_to_detail', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description'
                , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.lang_id', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3')
            ->get();
        /**
         * getting related tags and adding to flower array
         */

        $descriptionList = DB::table('descriptions')->where('lang_id', '!=', 'tr')
            ->where('products_id', '=', $id)->get();

        $allLang = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

        foreach ($allLang as $lang) {
            $tempLandId = false;
            foreach ($descriptionList as $description) {
                if ($description->lang_id == $lang->lang_id) {
                    $tempLandId = true;
                    break;
                }
            }
            if ($tempLandId == false) {
                //array_push($myArray, (object)[ 'mail' => 'Hepsi' , 'domain' => '0' ]);
                array_push($descriptionList, (object)[
                    'landing_page_desc' => '',
                    'how_to_title' => '',
                    'detail_page_desc' => '',
                    'how_to_detail' => '',
                    'how_to_step1' => '',
                    'how_to_step2' => '',
                    'how_to_step3' => '',
                    'extra_info_1' => '',
                    'extra_info_2' => '',
                    'extra_info_3' => '',
                    'meta_description' => '',
                    'img_title' => '',
                    'url_title' => '',
                    'lang_id' => $lang->lang_id
                ]
                );
            }
        }

        for ($x = 0; $x < count($products); $x++) {
            //$products[$x]->price = str_replace('.', ',',$products[$x]->price);
            $tagList = DB::table('products_tags')
                ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                ->where('products_tags.products_id', '=', $products[$x]->id)
                //->select('tags.tags_name' , 'tags.id')
                ->get();
            $allTag = Tag::where('lang_id', 'tr')->get();
            $tempTagList = [];
            //return $tagList[0]->id;
            foreach ($allTag as $tag) {
                $tag->selected = false;
                foreach ($tagList as $selectedTag) {
                    if ($tag->id == $selectedTag->id) {
                        $tag->selected = true;
                        break;
                    }
                }
            }
            //$tagList=array("a"=>"red","b"=>"green");
            //array_push($tagListTemp,'sdfa');
            //$products[$x]->tags = $tagListTemp;
            //return $tagList;
        }
        /**
         * getting related images and adding  to flower array
         */
        for ($x = 0; $x < count($products); $x++) {
            $imageList = DB::table('images')
                ->where('products_id', '=', $products[$x]->id)
                ->orderBy('type')
                //->select('type', 'image_url')
                ->get();
            $detailListImage = [];

            $products[$x]->DetailImage = '';
            $products[$x]->DetailImageId = '';
            for ($y = 0; $y < count($imageList); $y++) {
                if ($imageList[$y]->type == "main") {
                    $products[$x]->MainImage = $imageList[$y]->image_url;
                    $products[$x]->MainImageId = $imageList[$y]->id;
                } else if ($imageList[$y]->type == "detailImages") {
                    array_push($detailListImage, $imageList[$y]->image_url);

                    if ($y == 0) {
                        $products[$x]->image1 = $imageList[$y]->image_url;
                        $products[$x]->image1Id = $imageList[$y]->id;
                    } else if ($y == 1) {
                        $products[$x]->image2 = $imageList[$y]->image_url;
                        $products[$x]->image2Id = $imageList[$y]->id;
                    } else if ($y == 2) {
                        $products[$x]->image3 = $imageList[$y]->image_url;
                        $products[$x]->image3Id = $imageList[$y]->id;
                    } else if ($y == 3) {
                        $products[$x]->image4 = $imageList[$y]->image_url;
                        $products[$x]->image4Id = $imageList[$y]->id;
                    } else if ($y == 4) {
                        $products[$x]->image5 = $imageList[$y]->image_url;
                        $products[$x]->image5Id = $imageList[$y]->id;
                    } else if ($y == 5) {
                        $products[$x]->image6 = $imageList[$y]->image_url;
                        $products[$x]->image6Id = $imageList[$y]->id;
                    } else if ($y == 6) {
                        $products[$x]->image7 = $imageList[$y]->image_url;
                        $products[$x]->image7Id = $imageList[$y]->id;
                    } else if ($y == 7) {
                        $products[$x]->image8 = $imageList[$y]->image_url;
                        $products[$x]->image8Id = $imageList[$y]->id;
                    } else if ($y == 8) {
                        $products[$x]->image9 = $imageList[$y]->image_url;
                        $products[$x]->image9Id = $imageList[$y]->id;
                    } else if ($y == 9) {
                        $products[$x]->image10 = $imageList[$y]->image_url;
                        $products[$x]->image10Id = $imageList[$y]->id;
                    }
                } else if ($imageList[$y]->type == "detailPhoto") {
                    $products[$x]->DetailImage = $imageList[$y]->image_url;
                    $products[$x]->DetailImageId = $imageList[$y]->id;
                }
            }
            $products[$x]->detailListImage = $detailListImage;
        }
        //dd($products);
        //  return $products;
        return view('admin.detailProductCompany', compact('products', 'allTag', 'descriptionList'));
    }

    public function insertProductCompany(insertProductRequest $request)
    {
        AdminPanelController::checkAdmin();
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        $input['allTags'];
        if (count($input['allTags']) == 0) {
            dd('tag secmek zorundasiniz!!!!');
        }
        if (!Request::input('company'))
            dd('Şirket seçmelisiniz');

        if (DB::table('products')->where('company_product', Request::input('company'))->where('product_id_for_company', Request::input('id'))->count() > 0) {
            dd('Aynı çiçek bu şirket ile bağlanmış durumda.');
        }

        DB::beginTransaction();
        try {
            if (isset($input['activation_status']))
                $input['activation_status_id'] = 1;
            else
                $input['activation_status_id'] = 0;
            //$input['price'] = floatval(str_replace(',', '.',$input['price']));
            //$input['id'] = (int)$input['id'];
            $tempLastNumber = DB::table('products')->orderBy('landing_page_order', 'DESC')->select('landing_page_order')->get()[0]->landing_page_order;
            $insertedProduct = DB::table('products')->insertGetId(
                [
                    'name' => $input['name'],
                    'price' => $input['price'],
                    //'description' => $input['description'],
                    'image_name' => $input['image_name'],
                    'url_parametre' => $input['url_parametre'],
                    'landing_page_order' => $tempLastNumber + 1,
                    'company_product' => Request::input('company'),
                    'product_id_for_company' => Request::input('id'),
                    'tag_id' => Request::input('tag_id')
                ]
            );

            DB::table('products_shops')
                ->insert([
                    'shops_id' => 1,
                    'products_id' => $insertedProduct
                ]);

            $input = $request->all();

            Description::create(
                [
                    'landing_page_desc' => $input['landing_page_desc'],
                    'detail_page_desc' => $input['detail_page_desc'],
                    'how_to_title' => $input['how_to_title'],
                    'how_to_detail' => $input['how_to_detail'],
                    'how_to_step1' => $input['how_to_step1'],
                    'how_to_step2' => $input['how_to_step2'],
                    'how_to_step3' => $input['how_to_step3'],
                    'extra_info_1' => $input['extra_info_1'],
                    'extra_info_2' => $input['extra_info_2'],
                    'extra_info_3' => $input['extra_info_3'],
                    'products_id' => $insertedProduct,
                    'url_title' => $input['url_title'],
                    'img_title' => $input['img_title'],
                    'meta_description' => $input['meta_description'],
                    'lang_id' => 'tr'
                ]
            );

            foreach ($input['allTags'] as $tag) {
                DB::table('products_tags')
                    ->insert([
                        'tags_id' => $tag,
                        'products_id' => $insertedProduct
                    ]);
            }
            //dd(Request::file('img'));
            if (Request::file('img')) {
                $file = Request::file('img');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('img');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'main',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension
                    ])->id;
                $fileMoved = Request::file('img')->move(public_path() . "/productImageUploads/", $input['image_name'] . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            } else {
                Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'main',
                        'image_url' => Request::input('img')
                    ]);
            }

            if (Request::file('imgDetail')) {
                $file = Request::file('imgDetail');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('imgDetail');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailPhoto',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension
                    ])->id;
                $fileMoved = Request::file('imgDetail')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-detail" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('imgDetail');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-detail" . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-detail" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-detail" . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            } else {
                Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailPhoto',
                        'image_url' => Request::input('imgDetail')
                    ]);
            }

            $tempProductImage = DB::table('images_for_companies')->where('products_id', Request::input('id'))->where('type', 'detailImages')->get();

            foreach ($tempProductImage as $selected) {
                Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $selected->image_url,
                        'order_no' => $selected->order_no
                    ]);
            }

            if (Request::file('img1')) {

                //$filename = $file->getClientOriginalName();
                $file = Request::file('img1');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 1
                    ])->id;
                $fileMoved = Request::file('img1')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide1' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('img2')) {
                $file = Request::file('img2');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 2
                    ])->id;
                $fileMoved = Request::file('img2')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide2' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('img3')) {
                $file = Request::file('img3');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 3
                    ])->id;
                $fileMoved = Request::file('img3')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide3' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('img4')) {
                $file = Request::file('img4');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 4
                    ])->id;
                $fileMoved = Request::file('img4')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide4' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension
                ]);
            }

            if (Request::file('img5')) {
                $file = Request::file('img5');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 5
                    ])->id;
                $fileMoved = Request::file('img5')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide5' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension
                ]);
            }

            if (Request::file('img6')) {
                $file = Request::file('img6');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 6
                    ])->id;
                $fileMoved = Request::file('img6')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide6' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension
                ]);
            }

            if (Request::file('img7')) {
                $file = Request::file('img7');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 7
                    ])->id;
                $fileMoved = Request::file('img7')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide7' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension
                ]);
            }

            if (Request::file('img8')) {
                $file = Request::file('img8');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 8
                    ])->id;
                $fileMoved = Request::file('img8')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide8' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension
                ]);
            }

            if (Request::file('img9')) {
                $file = Request::file('img9');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 9
                    ])->id;
                $fileMoved = Request::file('img9')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide9' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension
                ]);
            }

            if (Request::file('img10')) {
                $file = Request::file('img10');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 10
                    ])->id;
                $fileMoved = Request::file('img10')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide10' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension
                ]);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/CompanyInfo/productCompanyList');
    }

    public function createProductCompany()
    {

        $companyList = DB::table('companies_info')->get();
        $products = DB::table('product_for_companies')
            ->join('descriptions_for_companies', 'product_for_companies.id', '=', 'descriptions_for_companies.products_id')
            ->where('descriptions_for_companies.lang_id', '=', 'tr')
            ->select('product_for_companies.tag_id', 'product_for_companies.id', 'product_for_companies.name', 'activation_status_id', 'product_for_companies.price', 'product_for_companies.description', 'product_for_companies.image_name', 'product_for_companies.background_color', 'product_for_companies.second_background_color',
                'descriptions_for_companies.landing_page_desc', 'descriptions_for_companies.how_to_title', 'descriptions_for_companies.detail_page_desc', 'product_for_companies.url_parametre'
                , 'descriptions_for_companies.how_to_detail', 'descriptions_for_companies.how_to_step1', 'descriptions_for_companies.how_to_step2', 'descriptions_for_companies.how_to_step3', 'descriptions_for_companies.meta_description'
                , 'descriptions_for_companies.img_title', 'descriptions_for_companies.url_title', 'descriptions_for_companies.lang_id', 'descriptions_for_companies.extra_info_1', 'descriptions_for_companies.extra_info_2', 'descriptions_for_companies.extra_info_3')
            ->get();

        for ($x = 0; $x < count($products); $x++) {
            //$products[$x]->price = str_replace('.', ',',$products[$x]->price);
            $tagList = DB::table('products_tags_for_companies')
                ->join('tags', 'products_tags_for_companies.tags_id', '=', 'tags.id')
                ->where('products_tags_for_companies.products_id', '=', $products[$x]->id)
                ->get();
            $allTag = Tag::where('lang_id', 'tr')->get();
            $tempTagList = [];
            foreach ($allTag as $tag) {
                $tag->selected = false;
                foreach ($tagList as $selectedTag) {
                    if ($tag->id == $selectedTag->id) {
                        $tag->selected = true;
                        break;
                    }
                }
            }
        }
        /**
         * getting related images and adding  to flower array
         */
        for ($x = 0; $x < count($products); $x++) {
            $imageList = DB::table('images_for_companies')
                ->where('products_id', '=', $products[$x]->id)
                ->orderBy('type')
                //->select('type', 'image_url')
                ->get();
            $detailListImage = [];

            $products[$x]->DetailImage = '';
            $products[$x]->DetailImageId = '';
            for ($y = 0; $y < count($imageList); $y++) {
                if ($imageList[$y]->type == "main") {
                    $products[$x]->MainImage = $imageList[$y]->image_url;
                    $products[$x]->MainImageId = $imageList[$y]->id;
                } else if ($imageList[$y]->type == "detailImages") {
                    array_push($detailListImage, $imageList[$y]->image_url);

                    if ($y == 0) {
                        $products[$x]->image1 = $imageList[$y]->image_url;
                        $products[$x]->image1Id = $imageList[$y]->id;
                    } else if ($y == 1) {
                        $products[$x]->image2 = $imageList[$y]->image_url;
                        $products[$x]->image2Id = $imageList[$y]->id;
                    } else if ($y == 2) {
                        $products[$x]->image3 = $imageList[$y]->image_url;
                        $products[$x]->image3Id = $imageList[$y]->id;
                    } else if ($y == 3) {
                        $products[$x]->image4 = $imageList[$y]->image_url;
                        $products[$x]->image4Id = $imageList[$y]->id;
                    } else if ($y == 4) {
                        $products[$x]->image5 = $imageList[$y]->image_url;
                        $products[$x]->image5Id = $imageList[$y]->id;
                    } else if ($y == 5) {
                        $products[$x]->image6 = $imageList[$y]->image_url;
                        $products[$x]->image6Id = $imageList[$y]->id;
                    } else if ($y == 6) {
                        $products[$x]->image7 = $imageList[$y]->image_url;
                        $products[$x]->image7Id = $imageList[$y]->id;
                    } else if ($y == 7) {
                        $products[$x]->image8 = $imageList[$y]->image_url;
                        $products[$x]->image8Id = $imageList[$y]->id;
                    } else if ($y == 8) {
                        $products[$x]->image9 = $imageList[$y]->image_url;
                        $products[$x]->image9Id = $imageList[$y]->id;
                    } else if ($y == 9) {
                        $products[$x]->image10 = $imageList[$y]->image_url;
                        $products[$x]->image10Id = $imageList[$y]->id;
                    }
                } else if ($imageList[$y]->type == "detailPhoto") {
                    $products[$x]->DetailImage = $imageList[$y]->image_url;
                    $products[$x]->DetailImageId = $imageList[$y]->id;
                }
            }
            $products[$x]->detailListImage = $detailListImage;
        }
        //dd($products);
        //  return $products;
        return view('admin.createProductCompany', compact('products', 'allTag', 'companyList'));
    }

    public function storeCompanyProduct(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        //$input['price'] = floatval(str_replace(',', '.',$input['price']));
        if (isset($input['activation_status']))
            $input['activation_status_id'] = 1;
        else
            $input['activation_status_id'] = 0;

        if (isset($input['limit_statu']))
            $input['limit_statu'] = 1;
        else
            $input['limit_statu'] = 0;

        if (isset($input['coming_soon']))
            $input['coming_soon'] = 1;
        else
            $input['coming_soon'] = 0;

        if (isset($input['id'])) {
            $input['id'] = (int)$input['id'];
            $product = Product::find($input['id']);
            if ($product->activation_status_id == 1 && $product->limit_statu == 0 && $product->coming_soon == 0) {
                if (($input['limit_statu'] == 1 || $input['coming_soon'] == 1) && $input['activation_status_id'] == 1) {
                    $before = Carbon::now();
                    DB::table('flowers_accessibility')->insert([
                        'flowers_name' => $product->name,
                        'close_time' => $before
                    ]);
                }
            } else if ($product->activation_status_id == 1 && ($product->limit_statu == 1 || $product->coming_soon == 1)) {
                if (($input['limit_statu'] == 0 && $input['coming_soon'] == 0) && $input['activation_status_id'] == 1) {
                    $before = Carbon::now();
                    DB::table('flowers_accessibility')->where('flowers_name', $product->name)
                        ->whereNull('open_time')
                        ->update([
                            'open_time' => $before
                        ]);
                }
            }
            $product->update($input);
        } else {
            $this->validate($request, ['img' => 'required']);
        }

        return AdminPanelController::showProductCompanyList(0);
    }

    public function showProductCompanyListAll()
    {
        AdminPanelController::checkAdmin();
        return AdminPanelController::showProductCompanyList(0);
    }

    public function showProductCompanyList($id)
    {
        AdminPanelController::checkAdmin();
        $companyList = DB::table('companies_info')->get();
        $productList = DB::table('product_for_companies')
            ->get();
        //$products = Product::latest('published_at')->get();
        $products = DB::table('products')
            ->join('companies_info', 'products.company_product', '=', 'companies_info.id')
            ->where('products.company_product', '>', 0)
            ->orderBy('landing_page_order')->select('products.*', 'companies_info.name as companyName', 'companies_info.id as companies_info_id')->get();
        foreach ($products as $product) {
            $imageList = Image::where('products_id', '=', $product->id)->where('type', '=', 'main')->get();
            $product->mainImage = $imageList[0]->image_url;

            $numberOfSale = count(DB::table('sales_products')
                ->join('sales', 'sales_products.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->where('products_id', '=', $product->id)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '!=', '4')
                ->get());
            $product->saleCount = $numberOfSale;
        }
        return view('admin.productRelatedCompany', compact('products', 'id', 'productList', 'companyList'));
    }

    public function updateCompanyProduct(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        DB::beginTransaction();
        try {
            $tempAl = DB::table('descriptions_for_companies')->where('products_id', $input['id'])->get();
            if (count($tempAl) != 0) {
                DB::table('descriptions_for_companies')->where('products_id', '=', $input['id'])->where('lang_id', '=', 'tr')->update(
                    [
                        'landing_page_desc' => $input['landing_page_desc'],
                        'detail_page_desc' => $input['detail_page_desc'],
                        'how_to_title' => $input['how_to_title'],
                        'how_to_detail' => $input['how_to_detail'],
                        'how_to_step1' => $input['how_to_step1'],
                        'how_to_step2' => $input['how_to_step2'],
                        'how_to_step3' => $input['how_to_step3'],
                        'extra_info_1' => $input['extra_info_1'],
                        'extra_info_2' => $input['extra_info_2'],
                        'extra_info_3' => $input['extra_info_3'],
                        'url_title' => $input['url_title'],
                        'img_title' => $input['img_title'],
                        'meta_description' => $input['meta_description']
                    ]
                );
            }

            //$input['price'] = floatval(str_replace(',', '.',$input['price']));
            DB::table('products_tags_for_companies')->where('products_id', '=', $input['id'])->delete();
            foreach ($input['allTags'] as $tag) {
                DB::table('products_tags_for_companies')
                    ->insert([
                        'tags_id' => $tag,
                        'products_id' => (int)$input['id']
                    ]);
            }

            $input['id'] = (int)$input['id'];
            //$product = Product::find($input['id']);
            //$product->update($input);

            DB::table('product_for_companies')->where('id', $input['id'])->update(
                [
                    'name' => $input['name'],
                    'price' => $input['price'],
                    'activation_status_id' => 0,
                    'image_name' => $input['image_name'],
                    'url_parametre' => $input['url_parametre'],
                    'landing_page_order' => 1000,
                    'tag_id' => $input['tag_id']
                ]
            );

            if (Request::hasFile('img')) {
                $file = Request::file('img');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                $imageId = DB::table('images_for_companies')->where('products_id', '=', $input['id'])->where('type', '=', 'main')->get()[0]->id;

                $versionId = DB::table('images_for_companies')->where('products_id', '=', $input['id'])->where('type', '=', 'main')->get()[0]->version_id;

                if ($versionId == 0) {
                    $versionId = 1;
                } else {
                    $versionId = $versionId + 1;
                }

                $fileMoved = Request::file('img')->move(public_path() . "/productImageUploads/", $input['image_name'] . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "_" . $versionId . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "_" . $versionId . "." . $fileExtension,
                    'version_id' => $versionId
                ]);
            }

            if (Request::hasFile('imgDetail')) {
                $file = Request::file('imgDetail');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                if (DB::table('images_for_companies')->where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->count() > 0) {
                    $versionId = DB::table('images_for_companies')->where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->get()[0]->version_id;
                } else {
                    $versionId = 0;
                }


                if ($versionId == 0) {
                    $versionId = 1;
                } else {
                    $versionId = $versionId + 1;
                }

                $fileMoved = Request::file('imgDetail')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-detail" . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('imgDetail');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-detail" . "_" . $versionId . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-detail" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                if (DB::table('images_for_companies')->where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->count() > 0) {
                    $imageId = DB::table('images_for_companies')->where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->get()[0]->id;
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-detail" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId
                    ]);
                } else {
                    DB::table('images_for_companies')->insert([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-detail" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId,
                        'type' => 'detailPhoto',
                        'products_id' => $input['id'],
                    ]);
                }

            }

            $imageList = DB::table('images_for_companies')
                ->where('products_id', '=', $input['id'])
                ->where('type', '=', 'detailImages')
                ->get();
            for ($y = 0; $y < count($imageList); $y++) {
                if ($y == 0) {
                    if (Request::hasFile('img1')) {
                        $file = Request::file('img1');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img1')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide1' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                            'order_no' => 1
                        ]);
                    }
                } else if ($y == 1) {
                    if (Request::hasFile('img2')) {
                        $file = Request::file('img2');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img2')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide2' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                            'order_no' => 2
                        ]);
                    }
                } else if ($y == 2) {
                    if (Request::hasFile('img3')) {
                        $file = Request::file('img3');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img3')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide3' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                            'order_no' => 3
                        ]);
                    }
                } else if ($y == 3) {
                    if (Request::hasFile('img4')) {
                        $file = Request::file('img4');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img4')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide4' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                            'order_no' => 4
                        ]);
                    }
                } else if ($y == 4) {
                    if (Request::hasFile('img5')) {
                        $file = Request::file('img5');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img5')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide5' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                            'order_no' => 5
                        ]);
                    }
                } else if ($y == 5) {
                    if (Request::hasFile('img6')) {
                        $file = Request::file('img6');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img6')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide6' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                            'order_no' => 6
                        ]);
                    }
                } else if ($y == 6) {
                    if (Request::hasFile('img7')) {
                        $file = Request::file('img7');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img7')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide7' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                            'order_no' => 7
                        ]);
                    }
                } else if ($y == 7) {
                    if (Request::hasFile('img8')) {
                        $file = Request::file('img8');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img8')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide8' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                            'order_no' => 8
                        ]);
                    }
                } else if ($y == 8) {
                    if (Request::hasFile('img9')) {
                        $file = Request::file('img9');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img9')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide9' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                            'order_no' => 9
                        ]);
                    }
                } else if ($y == 9) {
                    if (Request::hasFile('img10')) {
                        $file = Request::file('img10');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img10')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide10' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        DB::table('images_for_companies')->where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                            'order_no' => 10
                        ]);
                    }
                }
            }
            if (count($imageList) < 10) {
                if (Request::hasFile('img10')) {
                    $file = Request::file('img10');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ]);
                    $fileMoved = Request::file('img10')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide10' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                        'order_no' => 10
                    ]);
                }
            }

            if (count($imageList) < 9) {
                if (Request::hasFile('img9')) {
                    $file = Request::file('img9');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ]);
                    $fileMoved = Request::file('img9')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide9' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                        'order_no' => 9
                    ]);
                }
            }

            if (count($imageList) < 8) {
                if (Request::hasFile('img8')) {
                    $file = Request::file('img8');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ]);
                    $fileMoved = Request::file('img8')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide8' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                        'order_no' => 8
                    ]);
                }
            }

            if (count($imageList) < 7) {
                if (Request::hasFile('img7')) {
                    $file = Request::file('img7');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ]);
                    $fileMoved = Request::file('img7')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide7' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                        'order_no' => 7
                    ]);
                }
            }

            if (count($imageList) < 6) {
                if (Request::hasFile('img6')) {
                    $file = Request::file('img6');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ]);
                    $fileMoved = Request::file('img6')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide6' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                        'order_no' => 6
                    ]);
                }
            }

            if (count($imageList) < 5) {
                if (Request::hasFile('img5')) {
                    $file = Request::file('img5');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ]);
                    $fileMoved = Request::file('img5')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide5' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                        'order_no' => 5
                    ]);
                }
            }

            if (count($imageList) < 4) {
                if (Request::hasFile('img4')) {
                    $file = Request::file('img4');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id']
                        ]);
                    $fileMoved = Request::file('img4')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide4' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                        'order_no' => 4
                    ]);
                }
            }

            if (count($imageList) < 3) {
                if (Request::hasFile('img3')) {
                    $file = Request::file('img3');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id']
                        ]);
                    $fileMoved = Request::file('img3')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide3' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                        'order_no' => 3
                    ]);
                }
            }

            if (count($imageList) < 2) {
                if (Request::hasFile('img2')) {
                    $file = Request::file('img2');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id']
                        ]);
                    $fileMoved = Request::file('img2')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide2' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                        'order_no' => 2
                    ]);
                }
            }

            if (count($imageList) < 1) {
                if (Request::hasFile('img1')) {
                    $file = Request::file('img1');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = DB::table('images_for_companies')->insertGetId(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ]);
                    $fileMoved = Request::file('img1')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide1' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                        'order_no' => 1
                    ]);
                }
            }

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/CompanyInfo/productList');
//            \Mail::send('emails.new-issue', array('key' => 'value'), function($message)
//            {
//                $message->to('murat.susanli@ifgirisim.com', 'Bloom & Fresh')->subject('Bloom & Fresh New Product Added!');
//            });

        // File upload
    }

    public function showCompanyProductDetail($id)
    {
        AdminPanelController::checkAdmin();

        $products = DB::table('product_for_companies')
            ->join('descriptions_for_companies', 'product_for_companies.id', '=', 'descriptions_for_companies.products_id')
            ->where('product_for_companies.id', '=', $id)
            ->where('descriptions_for_companies.lang_id', '=', 'tr')
            ->select('product_for_companies.tag_id', 'product_for_companies.id', 'product_for_companies.name', 'activation_status_id', 'product_for_companies.price', 'product_for_companies.description', 'product_for_companies.image_name', 'product_for_companies.background_color', 'product_for_companies.second_background_color',
                'descriptions_for_companies.landing_page_desc', 'descriptions_for_companies.how_to_title', 'descriptions_for_companies.detail_page_desc', 'product_for_companies.url_parametre'
                , 'descriptions_for_companies.how_to_detail', 'descriptions_for_companies.how_to_step1', 'descriptions_for_companies.how_to_step2', 'descriptions_for_companies.how_to_step3', 'descriptions_for_companies.meta_description'
                , 'descriptions_for_companies.img_title', 'descriptions_for_companies.url_title', 'descriptions_for_companies.lang_id', 'descriptions_for_companies.extra_info_1', 'descriptions_for_companies.extra_info_2', 'descriptions_for_companies.extra_info_3')
            ->get();

        $descriptionList = DB::table('descriptions_for_companies')->where('lang_id', '!=', 'tr')
            ->where('products_id', '=', $id)->get();

        //$allLang = DB::table('bnf_languages')->where('lang_id' , '!=' , 'tr')->get();

        //foreach($allLang as $lang){
        //    $tempLandId = false;
        //    foreach($descriptionList as $description){
        //        if($description->lang_id == 3->lang_id ){
        //            $tempLandId = true;
        //            break;
        //        }
        //    }
        //    if($tempLandId == false){
        //        //array_push($myArray, (object)[ 'mail' => 'Hepsi' , 'domain' => '0' ]);
        //        array_push($descriptionList , (object) [
        //                'landing_page_desc' => '',
        //                'how_to_title' => '',
        //                'detail_page_desc' => '',
        //                'how_to_detail' => '',
        //                'how_to_step1' => '',
        //                'how_to_step2' => '',
        //                'how_to_step3' => '',
        //                'extra_info_1' => '',
        //                'extra_info_2' => '',
        //                'extra_info_3' => '',
        //                'meta_description' => '',
        //                'img_title' => '',
        //                'url_title' => '',
        //                'lang_id' => $lang->lang_id
        //            ]
        //        );
        //    }
        //}

        for ($x = 0; $x < count($products); $x++) {
            //$products[$x]->price = str_replace('.', ',',$products[$x]->price);
            $tagList = DB::table('products_tags_for_companies')
                ->join('tags', 'products_tags_for_companies.tags_id', '=', 'tags.id')
                ->where('products_tags_for_companies.products_id', '=', $products[$x]->id)
                ->get();
            $allTag = Tag::where('lang_id', 'tr')->get();
            $tempTagList = [];
            foreach ($allTag as $tag) {
                $tag->selected = false;
                foreach ($tagList as $selectedTag) {
                    if ($tag->id == $selectedTag->id) {
                        $tag->selected = true;
                        break;
                    }
                }
            }
        }
        /**
         * getting related images and adding  to flower array
         */
        for ($x = 0; $x < count($products); $x++) {

            $imageList = DB::table('images_for_companies')
                ->where('products_id', '=', $products[$x]->id)
                ->orderBy('type')
                //->select('type', 'image_url')
                ->get();
            $detailListImage = [];

            $products[$x]->DetailImage = '';
            $products[$x]->DetailImageId = '';
            for ($y = 0; $y < count($imageList); $y++) {
                if ($imageList[$y]->type == "main") {
                    $products[$x]->MainImage = $imageList[$y]->image_url;
                    $products[$x]->MainImageId = $imageList[$y]->id;
                } else if ($imageList[$y]->type == "detailImages") {
                    array_push($detailListImage, $imageList[$y]->image_url);

                    if ($y == 0) {
                        $products[$x]->image1 = $imageList[$y]->image_url;
                        $products[$x]->image1Id = $imageList[$y]->id;
                    } else if ($y == 1) {
                        $products[$x]->image2 = $imageList[$y]->image_url;
                        $products[$x]->image2Id = $imageList[$y]->id;
                    } else if ($y == 2) {
                        $products[$x]->image3 = $imageList[$y]->image_url;
                        $products[$x]->image3Id = $imageList[$y]->id;
                    } else if ($y == 3) {
                        $products[$x]->image4 = $imageList[$y]->image_url;
                        $products[$x]->image4Id = $imageList[$y]->id;
                    } else if ($y == 4) {
                        $products[$x]->image5 = $imageList[$y]->image_url;
                        $products[$x]->image5Id = $imageList[$y]->id;
                    } else if ($y == 5) {
                        $products[$x]->image6 = $imageList[$y]->image_url;
                        $products[$x]->image6Id = $imageList[$y]->id;
                    } else if ($y == 6) {
                        $products[$x]->image7 = $imageList[$y]->image_url;
                        $products[$x]->image7Id = $imageList[$y]->id;
                    } else if ($y == 7) {
                        $products[$x]->image8 = $imageList[$y]->image_url;
                        $products[$x]->image8Id = $imageList[$y]->id;
                    } else if ($y == 8) {
                        $products[$x]->image9 = $imageList[$y]->image_url;
                        $products[$x]->image9Id = $imageList[$y]->id;
                    } else if ($y == 9) {
                        $products[$x]->image10 = $imageList[$y]->image_url;
                        $products[$x]->image10Id = $imageList[$y]->id;
                    }
                } else if ($imageList[$y]->type == "detailPhoto") {
                    $products[$x]->DetailImage = $imageList[$y]->image_url;
                    $products[$x]->DetailImageId = $imageList[$y]->id;
                }
            }
            $products[$x]->detailListImage = $detailListImage;
        }
        //dd($products);
        //  return $products;
        return view('admin.detailCompanyProduct', compact('products', 'allTag', 'descriptionList'));
    }

    public function createCompanyInfoUser()
    {
        if (Request::input('isAdmin'))
            $tempAdmin = 1;
        else
            $tempAdmin = 0;
        $tempUserInfo = DB::table('users')->where('email', Request::input('email'))->get();
        if (count($tempUserInfo) == 0)
            dd('Kullanici bulunamadi');
        else {
            DB::table('users')->where('email', Request::input('email'))->update([
                'company_info_id' => Request::input('company'),
                'company_user' => 1,
                'companyAdmin' => $tempAdmin
            ]);
            $tempcompany = DB::table('companies_info')->where('id', Request::input('company'))->get()[0];
            DB::table('company_user_info')->insert([
                'user_id' => $tempUserInfo[0]->id,
                'company_name' => $tempcompany->name,
                'company_desc' => ' ',
                'tax_no' => ' ',
                'tax_office' => ' ',
                'billing_address' => ' '
            ]);
        }
        return AdminPanelController::companyInfoUserList();
    }

    public function companyInfoAddUser()
    {
        $companyList = DB::table('companies_info')->get();
        return view('admin.createCompanyUserInfo', compact('companyList'));
    }

    public function companyInfoUserList()
    {
        $tempCompanyUsers = DB::table('companies_info')->join('users', 'companies_info.id', '=', 'users.company_info_id')
            ->select('users.id', 'users.email', 'users.name', 'users.surname', 'companies_info.name as company_name', 'users.companyAdmin')
            ->get();

        foreach ($tempCompanyUsers as $user) {
            $user->saleCount = DB::table('sales')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('users', 'customers.user_id', '=', 'users.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '<>', '4')
                ->where('users.id', $user->id)
                ->select('sales.id')
                ->count();
        }

        return view('admin.companyUserList', compact('tempCompanyUsers'));
    }

    public function createCompanyInfoPage()
    {
        return view('admin.createCompanyInfo');
    }

    public function companyInfoPageList()
    {
        $companyList = DB::table('companies_info')->get();

        foreach ($companyList as $company) {
            $company->userCount = DB::table('users')->where('company_info_id', $company->id)->count();
            $company->saleCount = DB::table('sales')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('users', 'customers.user_id', '=', 'users.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '<>', '4')
                ->where('users.company_info_id', $company->id)
                ->select('sales.id')
                ->count();
        }

        return view('admin.companyInfoList', compact('companyList'));
    }

    public function createCompanyInfo()
    {
        DB::table('companies_info')->insert([
            'name' => Request::input('name'),
            'description' => Request::input('description')
        ]);
        return AdminPanelController::companyInfoPageList();
    }

    public function getCustomerSales($customerId)
    {
        $tempSales = DB::table('customers')->join('customer_contacts', 'customers.id', '=', 'customer_contacts.customer_id')
            ->join('sales', 'customer_contacts.id', '=', 'sales.customer_contact_id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('customers.id', '=', $customerId)
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->select('deliveries.id', 'sales.created_at', 'deliveries.products', 'delivery_locations.district', 'customer_contacts.name', 'customer_contacts.surname', 'sales.sender_name', 'sales.sender_surname')
            ->orderBy('sales.created_at', 'DESC')
            ->get();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($tempSales as $sale) {
            $requestDate = new Carbon($sale->created_at);
            $sale->created_at = $requestDate->formatLocalized('%Y %a %d %b');
        }

        return response()->json($tempSales);
    }

    public function getCustomerContacts($customerId)
    {

        $contactList = CustomerContact::where('customer_list', '=', 1)->where('customer_id', $customerId)->orderBy('created_at', 'DESC')->get();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        //foreach($contactList as $sale){
        //    $requestDate = new Carbon($sale->created_at);
        //    $sale->created_at = $requestDate->formatLocalized('%Y %a %d %b');
        //}

        return response()->json($contactList);
    }

    public function showFiyongoSale()
    {
        $fiyongoSales = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('billings', 'sales.id', '=', 'billings.sales_id')
            ->join('marketing_acts_sales', 'sales.id', '=', 'marketing_acts_sales.sales_id')
            ->join('marketing_acts', 'marketing_acts_sales.marketing_acts_id', '=', 'marketing_acts.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->select('marketing_acts.value', 'deliveries.products', 'sales.product_price', 'sales.created_at', 'sales.id')->get();

        return view('admin.fiyongoSales', compact('fiyongoSales'));
    }

    public function showStudioBloomRequest()
    {
        $myArray = [];
        array_push($myArray, (object)['hour' => '00', 'val' => '00']);
        array_push($myArray, (object)['hour' => '01', 'val' => '01']);
        array_push($myArray, (object)['hour' => '02', 'val' => '02']);
        array_push($myArray, (object)['hour' => '03', 'val' => '03']);
        array_push($myArray, (object)['hour' => '04', 'val' => '04']);
        array_push($myArray, (object)['hour' => '05', 'val' => '05']);
        array_push($myArray, (object)['hour' => '06', 'val' => '06']);
        array_push($myArray, (object)['hour' => '07', 'val' => '07']);
        array_push($myArray, (object)['hour' => '08', 'val' => '08']);
        array_push($myArray, (object)['hour' => '09', 'val' => '09']);
        array_push($myArray, (object)['hour' => '10', 'val' => '10']);
        array_push($myArray, (object)['hour' => '11', 'val' => '11']);
        array_push($myArray, (object)['hour' => '12', 'val' => '12']);
        array_push($myArray, (object)['hour' => '13', 'val' => '13']);
        array_push($myArray, (object)['hour' => '14', 'val' => '14']);
        array_push($myArray, (object)['hour' => '15', 'val' => '15']);
        array_push($myArray, (object)['hour' => '16', 'val' => '16']);
        array_push($myArray, (object)['hour' => '17', 'val' => '17']);
        array_push($myArray, (object)['hour' => '18', 'val' => '18']);
        array_push($myArray, (object)['hour' => '19', 'val' => '19']);
        array_push($myArray, (object)['hour' => '20', 'val' => '20']);
        array_push($myArray, (object)['hour' => '21', 'val' => '21']);
        array_push($myArray, (object)['hour' => '22', 'val' => '22']);
        array_push($myArray, (object)['hour' => '23', 'val' => '23']);
        return view('admin.studioBloomAdd', compact('myArray'));
    }

    public function getStudioBloomListDetail($id)
    {
        $requestList = DB::table('studioBloom')->where('id', $id)->get();

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $deliveryDate = new Carbon($requestList[0]->wanted_date);
        $requestList[0]->dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);

        return view('admin.studioBloomAddWork', compact('requestList'));
    }

    public function insertStudioBloomRequest()
    {

        $couponId = str_random(30);
        $tempCouponExist = DB::table('studioBloom')->where('id', $couponId)->get();
        while (count($tempCouponExist) > 0) {
            $couponId = str_random(30);
            $tempCouponExist = DB::table('studioBloom')->where('id', $couponId)->get();
        }

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        $wantedDeliveryDateStart = new Carbon(Request::input('wanted_delivery_date'));
        $wantedDeliveryDateLimit = new Carbon(Request::input('wanted_delivery_date'));
        //dd(Request::input('wanted_delivery_date_1'));
        $wantedDeliveryDateStart->hour(Request::input('wanted_delivery_date_1'));
        $wantedDeliveryDateStart->minute(0);
        $wantedDeliveryDateStart->second(0);

        $wantedDeliveryDateLimit->hour(Request::input('wanted_delivery_date_2'));
        $wantedDeliveryDateLimit->minute(0);
        $wantedDeliveryDateLimit->second(0);

        $today = Carbon::now();

        DB::table('studioBloom')->insert([
            'id' => $couponId,
            'email' => Request::input('email'),
            'customer_name' => Request::input('customer_name'),
            'contact_name' => Request::input('contact_name'),
            'flower_name' => Request::input('flower_name'),
            'flower_desc' => Request::input('flower_desc'),
            'continent_id' => Request::input('continent_id'),
            'district' => Request::input('district'),
            'receiver_address' => Request::input('receiver_address'),
            'wanted_date' => $wantedDeliveryDateStart,
            'wanted_delivery_limit' => $wantedDeliveryDateLimit,
            'price' => Request::input('price'),
            'note' => Request::input('note'),
            'status' => 'Ödeme Bekleniyor',
            'created_at' => $today
        ]);
        if (Request::get('name') == "") {
            $nameSurName = Request::get('customer_name');
        } else
            $nameSurName = Request::get('name');
        $nameSurName = logEventController::splitNameSurname($nameSurName);
        DB::table('studio_billings')->insert([
            'billing_send' => 0,
            'billing_name' => $nameSurName[0],
            'billing_surname' => $nameSurName[1],
            'city' => 'İstanbul',
            'small_city' => Request::input('district'),
            'company' => null,
            'billing_address' => '',
            'tax_office' => null,
            'tax_no' => null,
            'billing_type' => 1,
            'tc' => '11111111111',
            'userBilling' => 0,
            'created_at' => $today,
            'sales_id' => $couponId
        ]);

        return redirect('/admin/studioBloom/updateDetail/' . $couponId);
    }

    public function getStudioBloomListRemove($id)
    {
        DB::table('studioBloom')->where('id', $id)->delete();
        return redirect('/admin/studioBloomList');
    }

    public function getStudioBloomList()
    {
        $requestList = DB::table('studioBloom')->orderBy('created_at', 'DESC')->get();

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($requestList as $element) {
            $element->tempUrl = $this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . $element->id;
            $deliveryDate = new Carbon($element->wanted_date);
            $element->dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
        }

        return view('admin.studioBloomAddWork', compact('requestList'));
    }

    public function showTodayExcel()
    {
        $today = Carbon::now();
        //$today->startOfDay();
        $tempRequest = new \Illuminate\Http\Request();
        if ($today->month < 10)
            $tempMonth = 0 . $today->month;
        else
            $tempMonth = $today->month;
        $tempRequest->created_at = $today->year . '-' . $tempMonth . '-' . $today->day;
        $tempRequest->replace([
            'created_at' => $tempRequest->created_at,
            'created_at_end' => $tempRequest->created_at_end,
            'category' => [1, 2, 3],
            'sub_category' => [11, 12, 13, 14, 15, 16, 17, 21, 22, 23, 24, 25, 31, 32, 33]
        ]);
        return AdminPanelController::filterBillingExcel($tempRequest);
        //redirect('/filterBillingExcel', ['created_at' => $today]);
    }

    public function showWaitingBilling()
    {
        $deliveryList = DB::table('sales')->select('sales.id', 'deliveries.delivery_date as created_at', 'sales.sender_name', 'sales.sender_surname', 'billings.billing_type',
            'billings.billing_name', 'billings.billing_surname', 'billings.company', 'billings.tax_no', 'billings.billing_type', 'billings.tc')
            ->join('billings', 'sales.id', '=', 'billings.sales_id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            //->where('billings.userBilling' , 1 )
            ->where('deliveries.status', 3)
            ->where('send_billing_mail', 0)
            ->where('payment_type', '!=', 'KURUMSAL')
            ->where('sales.created_at', '>', '2016-03-18 09:00:00')->orderBy('deliveries.delivery_date', 'DESC')->get();

        return view('admin.waitingBillingList', compact('deliveryList'));
    }

    public function sendBillingMail(\Illuminate\Http\Request $request)
    {
        $tempObject = $request->all();
        $tempIds = [];
        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                array_push($tempIds, (object)['id' => explode('_', $key)[2], 'key' => explode('_', $value)]);
            }
        }
        foreach ($tempIds as $id) {
            ini_set("soap.wsdl_cache_enabled", "0");
            $tempSaleInfo = DB::table('sales')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->where('sales.id', $id->id)->select('billing_number', 'sender_email', 'sender_name', 'sales.id', 'customer_contacts.name', 'sum_total', 'sales_products.products_id', 'deliveries.products', 'sales.created_at',
                    'customer_contacts.surname', 'sales.sender_mobile', 'receiver_address', 'deliveries.wanted_delivery_limit', 'deliveries.wanted_delivery_date')->get()[0];

            $tempCheckBilling2 = '<ns1:GetOutboxInvoicePdf>
                    <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
                    <ns1:invoiceId>' . $tempSaleInfo->billing_number . '</ns1:invoiceId>
                </ns1:GetOutboxInvoicePdf>';

            $wsdl2 = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
            $client2 = new SoapClient($wsdl2, array(
                'soap_version' => SOAP_1_1,
                'trace' => true,
            ));
            ini_set("soap.wsdl_cache_enabled", "0");
            $args2 = array(new \SoapVar($tempCheckBilling2, XSD_ANYXML));

            //dd($client2->__getFunctions());
            $res2 = $client2->__soapCall("GetOutboxInvoicePdf", $args2);
            $testTemp = base64_encode($res2->GetOutboxInvoicePdfResult->Value->Data);
            //dd($res2);
            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');

            $created = new Carbon($tempSaleInfo->wanted_delivery_limit);

            $deliveryDate = new Carbon($tempSaleInfo->created_at);
            $dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);

            $wantedDeliveryDate = new Carbon($tempSaleInfo->wanted_delivery_date);
            $wantedDeliveryDateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . '00' . ' - ' . str_pad($created->hour, 2, '0', STR_PAD_LEFT) . ':' . '00';

            $tempCikolat = AdminPanelController::getCikolatData($tempSaleInfo->id);

            if ($tempCikolat) {
                $tempCikolatName = "Ekstra: " . $tempCikolat->name . "<br>";
                $tempSaleInfo->sum_total = number_format(floatval(str_replace(',', '.', $tempSaleInfo->sum_total)) + floatval(str_replace(',', '.', $tempCikolat->total_price)), 2);
                $tempSaleInfo->sum_total = str_replace('.', ',', $tempSaleInfo->sum_total);
            } else {
                $tempCikolatName = "";
            }

            \MandrillMail::messages()->sendTemplate('v2_BNF_Siparis_Fatura_gonderim', null, array(
                'html' => '<p>Example HTML content</p>',
                'text' => 'Siparişiniz başarıyla verilmiştir.',
                'subject' => 'Bloom And Fresh - Fatura Talebiniz',
                'from_email' => 'teknik@bloomandfresh.com',
                'from_name' => 'Bloom And Fresh',
                'to' => array(
                    array(
                        'email' => $tempSaleInfo->sender_email,
                        'type' => 'to'
                    )
                ),
                'merge' => true,
                'merge_language' => 'mailchimp',
                'global_merge_vars' => array(
                    array(
                        'name' => 'FNAME',
                        'content' => ucwords(strtolower($tempSaleInfo->sender_name)),
                    ), array(
                        'name' => 'SALEID',
                        'content' => $tempSaleInfo->id,
                    ), array(
                        'name' => 'CNTCNAME',
                        'content' => ucwords(strtolower($tempSaleInfo->name)),
                    ), array(
                        'name' => 'CNTCLNAME',
                        'content' => ucwords(strtolower($tempSaleInfo->surname)),
                    ), array(
                        'name' => 'CNTADD',
                        'content' => $tempSaleInfo->receiver_address
                    ), array(
                        'name' => 'WANTEDDATE',
                        'content' => $wantedDeliveryDateInfo
                    ), array(
                        'name' => 'PRICE',
                        'content' => $tempSaleInfo->sum_total
                    ), array(
                        'name' => 'PIMAGE',
                        'content' => DB::table('images')->where('type', 'main')->where('products_id', $tempSaleInfo->products_id)->get()[0]->image_url
                    ), array(
                        'name' => 'PRNAME',
                        'content' => $tempSaleInfo->products
                    ), array(
                        'name' => 'ORDERDATE',
                        'content' => $dateInfo
                    ), array(
                        'name' => 'EKSTRA_URUN_NAME',
                        'content' => $tempCikolatName
                    )
                ),
                'attachments' => array(
                    array(
                        'type' => 'application/pdf',
                        'name' => 'Fatura.pdf',
                        'content' => $testTemp
                    )
                )
            ));

            DB::table('sales')->where('id', $id->id)->update([
                'send_billing_mail' => 1
            ]);
        }
        return redirect('/admin/waitingBilling');
    }

    public function showTomorrowDeliveryDocument()
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $before = Carbon::now();
        $after = Carbon::now();
        $before->addDay(1);
        $before->hour(0);
        $after->addDay(1);
        $after->hour(23);

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $deliveryList = DB::table('sales')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('deliveries.wanted_delivery_date', '>', $before)
            ->where('deliveries.wanted_delivery_date', '<', $after)
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->whereRaw($tempWhere)
            ->select('sales.id', 'deliveries.products', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit', 'sales.created_at', 'delivery_locations.continent_id'
                , DB::raw("'0' as studio"), 'sales.receiver_address as address', 'customer_contacts.name', 'customer_contacts.surname', 'sales.receiver_mobile as mobile', 'delivery_locations.district')
            ->get();

        $tempStudioBloom = DB::table('studioBloom')
            ->where('status', 'Ödeme Yapıldı')
            ->where('wanted_date', '>', $before)
            ->where('wanted_date', '<', $after)
            ->where('delivery_status', '!=', '4')
            ->select(
                'contact_name as name',
                'contact_surname as surname',
                'customer_name',
                'customer_surname',
                'district',
                'receiver_address as address',
                'id',
                'wanted_date as wanted_delivery_date',
                'flower_name as products',
                'wanted_delivery_limit',
                'delivery_status as status',
                'created_at',
                'customer_mobile',
                'continent_id'
            )->get();
        foreach ($tempStudioBloom as $studio) {
            $studio->studio = 1;
            array_unshift($deliveryList, (object)$studio);
        }

        foreach ($deliveryList as $delivery) {
            //$limitDate = new Carbon($delivery->wanted_delivery_limit);
            $limitDateInfo = new Carbon($delivery->wanted_delivery_date);
            $limitDateInfoL = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = ' ' . $limitDateInfo->hour . ':00 - ' . $limitDateInfoL->hour . ':00 ' . $limitDateInfo->formatLocalized('%A %d %B %Y');
            $delivery->dateInfo = $dateInfo;
        }

        $deliveryHourList = [];

        $queryParams = [];

        $queryParams = (object)['deliveryHour' => "Hepsi"];

        array_push($deliveryHourList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($deliveryHourList, (object)['information' => '9-13', 'status' => '9:00:00']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00']);

        return view('admin.deliveryDocumentList', compact('deliveryList', 'queryParams', 'deliveryHourList'));
    }

    public function updateExternalDeliveryHours()
    {
        AdminPanelController::checkAdmin();
        $hourList = DB::table('speacial_day_hours')->where('day_number', Request::input('id'))->get();
        foreach ($hourList as $hour) {
            $tempActive = false;
            if (Request::input('active_' . $hour->id)) {
                $tempActive = true;
            }

            DB::table('speacial_day_hours')->where('id', $hour->id)->update([
                'start_hour' => Request::input('start_' . $hour->id),
                'end_hour' => Request::input('end_' . $hour->id),
                'active' => $tempActive
            ]);
        }
        return AdminPanelController::showSelectedExternalDeliveryHours(0, "");
    }

    public function showExternalDeliveryHours()
    {
        AdminPanelController::checkAdmin();
        return AdminPanelController::showSelectedExternalDeliveryHours(0, "");
    }

    public function showSelectedExternalDeliveryHours($id, $continent_id)
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        $dayListEu = DB::table('speacial_delivery_hours')->where('continent_id', 'Avrupa')->orderBy('delivery_date')->get();
        foreach ($dayListEu as $day) {
            $now = new Carbon($day->delivery_date);
            $hoursList = DB::table('speacial_day_hours')->where('day_number', $day->id)->get();
            $day->hours = $hoursList;

            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }
        $dayList = DB::table('speacial_delivery_hours')->where('continent_id', 'Asya')->orderBy('delivery_date')->get();
        foreach ($dayList as $day) {
            $now = new Carbon($day->delivery_date);
            $hoursList = DB::table('speacial_day_hours')->where('day_number', $day->id)->get();
            $day->hours = $hoursList;

            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }
        $dayListOyaka = DB::table('speacial_delivery_hours')->where('continent_id', 'Avrupa-2')->orderBy('delivery_date')->get();
        foreach ($dayListOyaka as $day) {
            $now = new Carbon($day->delivery_date);
            $hoursList = DB::table('speacial_day_hours')->where('day_number', $day->id)->get();
            $day->hours = $hoursList;

            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }
        $dayListAsia2 = DB::table('speacial_delivery_hours')->where('continent_id', 'Asya-2')->orderBy('delivery_date')->get();
        foreach ($dayListAsia2 as $day) {
            $now = new Carbon($day->delivery_date);
            $hoursList = DB::table('speacial_day_hours')->where('day_number', $day->id)->get();
            $day->hours = $hoursList;

            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }
        $dayListEu3 = DB::table('speacial_delivery_hours')->where('continent_id', 'Avrupa-3')->orderBy('delivery_date')->get();
        foreach ($dayListEu3 as $day) {
            $now = new Carbon($day->delivery_date);
            $hoursList = DB::table('speacial_day_hours')->where('day_number', $day->id)->get();
            $day->hours = $hoursList;

            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }

        $dayListAnkara1 = DB::table('speacial_delivery_hours')->where('continent_id', 'Ankara-1')->orderBy('delivery_date')->get();
        foreach ($dayListAnkara1 as $day) {
            $now = new Carbon($day->delivery_date);
            $hoursList = DB::table('speacial_day_hours')->where('day_number', $day->id)->get();
            $day->hours = $hoursList;

            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }

        $dayListAnkara2 = DB::table('speacial_delivery_hours')->where('continent_id', 'Ankara-2')->orderBy('delivery_date')->get();
        foreach ($dayListAnkara2 as $day) {
            $now = new Carbon($day->delivery_date);
            $hoursList = DB::table('speacial_day_hours')->where('day_number', $day->id)->get();
            $day->hours = $hoursList;

            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }

        $myArray = [];
        array_push($myArray, (object)['hour' => '00', 'val' => '00:00']);
        array_push($myArray, (object)['hour' => '01', 'val' => '01:00']);
        array_push($myArray, (object)['hour' => '02', 'val' => '02:00']);
        array_push($myArray, (object)['hour' => '03', 'val' => '03:00']);
        array_push($myArray, (object)['hour' => '04', 'val' => '04:00']);
        array_push($myArray, (object)['hour' => '05', 'val' => '05:00']);
        array_push($myArray, (object)['hour' => '06', 'val' => '06:00']);
        array_push($myArray, (object)['hour' => '07', 'val' => '07:00']);
        array_push($myArray, (object)['hour' => '08', 'val' => '08:00']);
        array_push($myArray, (object)['hour' => '09', 'val' => '09:00']);
        array_push($myArray, (object)['hour' => '10', 'val' => '10:00']);
        array_push($myArray, (object)['hour' => '11', 'val' => '11:00']);
        array_push($myArray, (object)['hour' => '12', 'val' => '12:00']);
        array_push($myArray, (object)['hour' => '13', 'val' => '13:00']);
        array_push($myArray, (object)['hour' => '14', 'val' => '14:00']);
        array_push($myArray, (object)['hour' => '15', 'val' => '15:00']);
        array_push($myArray, (object)['hour' => '16', 'val' => '16:00']);
        array_push($myArray, (object)['hour' => '17', 'val' => '17:00']);
        array_push($myArray, (object)['hour' => '18', 'val' => '18:00']);
        array_push($myArray, (object)['hour' => '19', 'val' => '19:00']);
        array_push($myArray, (object)['hour' => '20', 'val' => '20:00']);
        array_push($myArray, (object)['hour' => '21', 'val' => '21:00']);
        array_push($myArray, (object)['hour' => '22', 'val' => '22:00']);
        array_push($myArray, (object)['hour' => '23', 'val' => '23:00']);
        return view('admin.testExternak', compact('dayListOyaka', 'dayList', 'dayListEu', 'myArray', 'id', 'continent_id', 'dayListAsia2', 'dayListEu3', 'dayListAnkara1', 'dayListAnkara2'));
    }

    public function completeFailSales(\Illuminate\Http\Request $request)
    {
        $deliveryDate = Carbon::now();
        $input = $request->all();
        //dd($input);
        //$tempPrice = Request::input('price');

        $productData = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.id', Request::input('id'))
            ->select('sales_products.products_id', 'delivery_locations.city_id', 'sales.id')
            ->get()[0];

        if( $productData->city_id == 3 ){
            $productData->city_id = 1;
        }

        if (generateDataController::isProductAvailable($productData->products_id, $productData->city_id)) {

            $tempCrossSellData = DB::table('cross_sell')->where('sales_id', $productData->id )->get();

            if( count($tempCrossSellData) > 0 ){

                if( generateDataController::isCrossSellAvailable($tempCrossSellData[0]->product_id , $productData->city_id) ){

                    generateDataController::setCrossSellCountOneLess($tempCrossSellData[0]->product_id, $productData->city_id);

                    $productCrossSellData = DB::table('cross_sell_products')->where('id', $tempCrossSellData[0]->product_id )->where('city_id', $productData->city_id)->get();

                    if( $productCrossSellData[0]->product_id > 0 ){
                        $productStockData = DB::table('product_stocks')->where('product_id', $productCrossSellData[0]->product_id )->where('city_id', $productData->city_id)->get()[0];
                    }
                    else{
                        $productStockData = DB::table('product_stocks')->where('cross_sell_id', $tempCrossSellData[0]->product_id )->where('city_id', $productData->city_id)->get()[0];
                    }

                    generateDataController::logStock( 'CROSS-SELL SİPARİŞ ONAY', $productStockData->id, $productData->id, $productStockData->count + 1, $productStockData->count, \Auth::user()->id );

                }
                else {
                    dd('Cross-sell ürün stok durumu uygun olmadığından sipariş onay işlemi gerçekleşemez!');
                }

            }

            generateDataController::setProductCountOneLess($productData->products_id, $productData->city_id);

            $productStockData = DB::table('product_stocks')->where('product_id', $productData->products_id )->where('city_id', $productData->city_id)->get()[0];

            generateDataController::logStock( 'SİPARİŞ ONAY', $productStockData->id, $productData->id, $productStockData->count + 1, $productStockData->count, \Auth::user()->id );

        }
        else{
            dd('Ürün stok durumu uygun olmadığından sipariş onay işlemi gerçekleşemez!');
        }

        if (Request::input('coupon_ids')) {

            DB::table('marketing_acts_sales')->insert([
                'sales_id' => Request::input('id'),
                'marketing_acts_id' => Request::input('coupon_ids')
            ]);

            DB::table('marketing_acts')->where('id', Request::input('coupon_ids'))->where('long_term', 0)->update([
                'valid' => 0,
                'used' => 1
            ]);

        } else {
        }

        $tempPrice = Request::input('price_coupon');

        DB::table('sales')->where('id', Request::input('id'))->update([
            'payment_methods' => 'OK',
            'created_at' => $deliveryDate,
            'updated_at' => $deliveryDate,
            'payment_type' => 'EFT',
            'sum_total' => $tempPrice
        ]);

        return redirect('/admin/deliveries/detail/' . DB::table('deliveries')->where('sales_id', Request::input('id'))->get()[0]->id);
    }

    public function showCompleteFail()
    {
        $deliveryDate = Carbon::now();
        $deliveryDate->addDay(-1);
        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('sales.created_at', '>', $deliveryDate)
            ->where(function ($query) {
                $query->orwhere('sales.payment_methods', '=', null)
                    ->orwhere('sales.payment_methods', '!=', 'OK');
            })
            ->select('sales.id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.created_at as date', 'customers.user_id', 'sales.sender_mobile', 'sales.sender_email', 'sales.payment_methods', 'deliveries.products', 'sales.sales_ip', 'products.product_type'
                , 'sales.admin_not', 'sales.sale_fail_visibility', 'sales.sum_total', 'customers.id as customer_id', 'sales.product_price', 'sales.taxType',
                DB::raw('(select sales.id from sales sl2 where sl2.sender_name = sales.sender_name and sl2.sender_surname = sales.sender_surname and sl2.payment_methods = "OK" and sl2.created_at >  sales.created_at and DAYOFMONTH( sl2.created_at) = DAYOFMONTH( sales.created_at)  LIMIT 1 ) as complete')
            )
            ->orderBy('sales.created_at', 'DESC')
            ->get();

        foreach ($deliveryList as $delivery) {
            $priceWithDiscount = $delivery->product_price;
            $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);

            $tempKDVMultiplier = 118;

            if ($delivery->product_type == 2) {
                $tempKDVMultiplier = 108;
            }

            if( $delivery->taxType == 2 ){
                $tempKDVMultiplier = 108;
                if ($delivery->product_type == 2) {
                    $tempKDVMultiplier = 101;
                }
                else if($delivery->product_type == 3){
                    $tempKDVMultiplier = 118;
                }
            }

            $tempCoupon = DB::table('marketing_acts')
                ->select('marketing_acts.value', 'marketing_acts.id', 'marketing_acts.name')
                ->join('customers_marketing_acts', 'marketing_acts.id', '=', 'customers_marketing_acts.marketing_acts_id')
                ->where('customers_marketing_acts.customers_id', $delivery->customer_id)
                ->where('marketing_acts.valid', 1)
                ->orderBy('marketing_acts.value', 'DESC')->get();


            $tempCikolat = AdminPanelController::getCikolatData($delivery->id);

            foreach ($tempCoupon as $coupon) {
                $coupon->coupon_id = $coupon->id;
                $coupon->coupon_value = $coupon->value;
                $coupon->priceWithoutCoupon = floatval(floatval($priceWithDiscount) * $tempKDVMultiplier / 100);
                $coupon->priceWithCoupon = floatval(floatval($priceWithDiscount) * $tempKDVMultiplier / 100);
                $coupon->priceWithCoupon = floatval($coupon->priceWithCoupon) * (100 - floatval($coupon->coupon_value)) / 100;

                if ($tempCikolat) {

                    $tempCikolatData = DB::table('cross_sell')->where('sales_id', $delivery->id)->get()[0];

                    $tempValue = str_replace(',', '.', $tempCikolatData->total_price);

                    $coupon->extra_priceWithoutCoupon = $coupon->priceWithoutCoupon + $tempValue;
                    $coupon->extra_priceWithCoupon = $coupon->priceWithCoupon + $tempValue;

                } else {
                    $coupon->extra_priceWithoutCoupon = $coupon->priceWithoutCoupon;
                    $coupon->extra_priceWithCoupon = $coupon->priceWithCoupon;
                }
            }

            $delivery->couponList = $tempCoupon;

            $delivery->coupon_id = 0;

            if (count($tempCoupon) != 0) {
                $delivery->coupon_id = $tempCoupon[0]->id;
                $delivery->coupon_value = $tempCoupon[0]->value;
                $delivery->priceWithoutCoupon = floatval(floatval($priceWithDiscount) * $tempKDVMultiplier / 100);
                $delivery->priceWithCoupon = floatval(floatval($priceWithDiscount) * $tempKDVMultiplier / 100);
                $delivery->priceWithCoupon = floatval($delivery->priceWithCoupon) * (100 - floatval($delivery->coupon_value)) / 100;
            } else {
                $delivery->priceWithoutCoupon = floatval(floatval($priceWithDiscount) * $tempKDVMultiplier / 100);
                $delivery->priceWithCoupon = $delivery->priceWithoutCoupon;
                $delivery->coupon_value = 0;
            }


            $delivery->extra_priceWithoutCoupon = $delivery->priceWithoutCoupon;
            $delivery->extra_priceWithCoupon = $delivery->priceWithCoupon;

            $firstPrice = number_format($delivery->priceWithoutCoupon, 2);
            $firstPrice = str_replace('.', '!', $firstPrice);
            $firstPrice = str_replace(',', '.', $firstPrice);
            $firstPrice = str_replace('!', ',', $firstPrice);
            $delivery->priceWithoutCoupon = $firstPrice;

            $firstPrice = number_format($delivery->priceWithCoupon, 2);
            $firstPrice = str_replace('.', '!', $firstPrice);
            $firstPrice = str_replace(',', '.', $firstPrice);
            $firstPrice = str_replace('!', ',', $firstPrice);
            $delivery->priceWithCoupon = $firstPrice;

            $delivery->extra_product = '';

            if ($tempCikolat) {
                //$delivery->products = $delivery->products . ' - ' . $tempCikolat->name;
                $delivery->extra_product = $tempCikolat->name;
            }
        }

        return view('admin.eftComplete', compact('deliveryList'));
    }

    public function excelImportPage()
    {
        return view('admin.importExcel');
    }

    public function completeCompanyDeliveryByCourier()
    {
        $deliveryDate = Carbon::now();

        DB::table('sales_company')->where('id', '=', Request::input('tempId'))->update([
            'delivery_date' => $deliveryDate,
            'status' => 3,
            'picker' => Request::input('picker')
        ]);

        return view('admin.completePage');
    }

    public function completeCompanyPage($saleId)
    {
        AdminPanelController::checkAdmin();
        $deliveryList = DB::table('sales_company')
            ->where('status', '=', '2')
            ->where('id', '=', $saleId)->get();

        return view('admin.companyKurye', compact('deliveryList'));
    }

    public function printCompanyDeliveryInfo(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $before = Carbon::now();
        $after = Carbon::now();
        $before->addHour(-12);
        $after->addHour(36);
        $tempObject = $request->all();
        $tempQueryList = [];
        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                $deliveryList = DB::table('sales_company')
                    ->where('wanted_delivery_date', '>', $before)
                    ->where('wanted_delivery_date', '<', $after)
                    ->where('id', '=', explode('_', $key)[1])
                    ->get()[0];

                $limitDate = new Carbon($deliveryList->wanted_delivery_limit);
                $limitDateInfo = new Carbon($deliveryList->wanted_delivery_date);
                $limitDateInfoL = new Carbon($deliveryList->wanted_delivery_limit);
                setlocale(LC_ALL, 'tr_TR.UTF-8');
                $dateInfo = ' ' . $limitDateInfo->hour . ':00 - ' . $limitDateInfoL->hour . ':00 ' . $limitDate->formatLocalized('%A %d %B %Y');
                $deliveryList->dateInfo = $dateInfo;

                array_push($tempQueryList, $deliveryList);
            }
        }
        return view('admin.deliveryCompanyDocument', compact('tempQueryList'));
    }

    public function showCompanyDeliveryDocumentYesterday()
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $before = Carbon::now();
        $after = Carbon::now();
        $after->subDay(1);
        $before->subDay(1);
        $before->startOfDay();
        $after->endOfDay();
        $deliveryList = DB::table('sales_company')
            ->where('wanted_delivery_date', '>', $before)
            ->where('wanted_delivery_date', '<', $after)
            ->where('status', '!=', '4')->get();

        foreach ($deliveryList as $delivery) {
//$limitDate = new Carbon($delivery->wanted_delivery_limit);
            $limitDateInfo = new Carbon($delivery->wanted_delivery_date);
            $limitDateInfoL = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = ' ' . $limitDateInfo->hour . ':00 - ' . $limitDateInfoL->hour . ':00 ' . $limitDateInfo->formatLocalized('%A %d %B %Y');
            $delivery->dateInfo = $dateInfo;
        }

        return view('admin.deliveryCompanyDocumentList', compact('deliveryList'));
    }

    public function showCompanyDeliveryDocumentTomorrow()
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $before = Carbon::now();
        $after = Carbon::now();
        $after->addDay(1);
        $before->addDay(1);
        $before->startOfDay();
        $after->endOfDay();
        $deliveryList = DB::table('sales_company')
            ->where('wanted_delivery_date', '>', $before)
            ->where('wanted_delivery_date', '<', $after)
            ->where('status', '!=', '4')->get();

        foreach ($deliveryList as $delivery) {
//$limitDate = new Carbon($delivery->wanted_delivery_limit);
            $limitDateInfo = new Carbon($delivery->wanted_delivery_date);
            $limitDateInfoL = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = ' ' . $limitDateInfo->hour . ':00 - ' . $limitDateInfoL->hour . ':00 ' . $limitDateInfo->formatLocalized('%A %d %B %Y');
            $delivery->dateInfo = $dateInfo;
        }

        return view('admin.deliveryCompanyDocumentList', compact('deliveryList'));
    }

    public function showCompanyDeliveryDocument()
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $before = Carbon::now();
        $after = Carbon::now();
        $before->addHour(-12);
        $after->addHour(12);
        $deliveryList = DB::table('sales_company')
            ->where('wanted_delivery_date', '>', $before)
            ->where('wanted_delivery_date', '<', $after)
            ->where('status', '!=', '4')->get();

        foreach ($deliveryList as $delivery) {
//$limitDate = new Carbon($delivery->wanted_delivery_limit);
            $limitDateInfo = new Carbon($delivery->wanted_delivery_date);
            $limitDateInfoL = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = ' ' . $limitDateInfo->hour . ':00 - ' . $limitDateInfoL->hour . ':00 ' . $limitDateInfo->formatLocalized('%A %d %B %Y');
            $delivery->dateInfo = $dateInfo;
        }

        return view('admin.deliveryCompanyDocumentList', compact('deliveryList'));
    }

    public function detailCompanyDelivery($id)
    {
        AdminPanelController::checkAdmin();
        $sales = DB::table('sales_company')->where('id', $id)->get();
        $productList = DB::table('products')->get();

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $wantedDeliveryDate = new Carbon($sales[0]->wanted_delivery_date);
        $wantedDeliveryDateEnd = new Carbon($sales[0]->wanted_delivery_limit);
        $dateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
        $sales[0]->wanted_delivery_date = $dateInfo;

        return view('admin.companyDetailDelivery', compact('sales', 'productList'));
    }

    public function setCompanyDeliveryNote()
    {
        AdminPanelController::checkAdmin();
        DB::table('sales_company')->where('id', Request::get('id'))->update([
            'delivery_not' => Request::get('note')
        ]);
        return response()->json(["status" => Request::get('id'), "note" => Request::get('note'), "data" => Request::all()], 200);
    }

    public function updateCompanyDelivery(\Illuminate\Http\Request $request)
    {
        $input = $request->all();
        $dateInfo = "";
        if (DB::table('sales_company')->where('id', $input['id'])->get()[0]->status != '3' && $input['status'] == '3') {
            $limitDate = new Carbon($input['delivery_date']);
            $limitDate->hour($input['delivery_date_hour']);
            $limitDate->minute($input['delivery_date_minute']);
            $dateInfo = $limitDate->formatLocalized('%A %d %b') . ' | ' . str_pad($limitDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($limitDate->minute, 2, '0', STR_PAD_LEFT);
            DB::table('sales_company')->where('id', '=', $input['id'])->update([
                'delivery_date' => $limitDate,
                'status' => $input['status'],
                'picker' => $input['picker']
            ]);
        }
        DB::table('sales_company')->where('id', '=', $input['id'])->update([
            'status' => $input['status']
        ]);
        return response()->json(["success" => 1, "status" => $input['status'], "id" => $input['id'], "picker" => $input['picker'], "date" => $dateInfo], 200);
    }

    public function updateCompanyDeliveryDetail()
    {

//dd(DB::table('sales_company')->where('id', Request::get('id'))->get());

        DB::table('sales_company')->where('id', Request::get('id'))->update([
            'receiver' => Request::get('receiver'),
            'receiver_mobile' => Request::get('receiver_mobile'),
            'delivery_location' => Request::get('delivery_location'),
            'receiver_address' => Request::get('receiver_address'),
            'card_receiver' => Request::get('card_receiver'),
            'card_message' => Request::get('card_message'),
            'card_sender' => Request::get('card_sender'),
            'delivery_not' => Request::get('delivery_not')
        ]);


        return redirect('/admin/company-deliveries/week');

//dd(Request::all());

    }

    public function showAllCompanyDeliveries()
    {
        AdminPanelController::checkAdmin();
        $deliveryList = DB::table('sales_company')
            ->orderBy('wanted_delivery_date')
            ->get();
        $myArray = [];
        $queryParams = [];
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($deliveryList as $delivery) {

            if ($delivery->delivery_date == "0000-00-00 00:00:00") {
                $delivery->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($delivery->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $delivery->deliveryDate = $dateInfo;
            }

            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
        }

        $queryParams = (object)['created_at' => "", 'created_at_end' => "", 'product_name' => "", 'wanted_delivery_date_end' => "", 'wanted_delivery_date' => "", 'delivery_date_end' => "", 'delivery_date' => "", 'status' => "", 'deliveryHour' => "", 'continent_id' => "",
            'status_all' => "on", 'status_making' => "", 'status_ready' => "", 'status_delivering' => "", 'status_delivered' => "", 'status_cancel' => "", 'company_name' => ""
        ];

        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 0]);

        $filterShow = 'none';
        $id = 0;
        $countDelivery = count($deliveryList);
        return view('admin.companyDeliveries', compact('deliveryList', 'id', 'myArray', 'queryParams', 'filterShow', 'countDelivery'));
    }

    public function showWeekCompanyDeliveries()
    {
        $today = Carbon::now();
//$today->startOfDay();
        $tempRequest = new \Illuminate\Http\Request();

        $tempRequest->replace([
            'wanted_delivery_date' => $today->startOfWeek(),
            'wanted_delivery_date_end' => '',
            'delivery_date' => '',
            'delivery_date_end' => '',
            'product_name' => '',
            'company_name' => '',
            'status_all_temp' => 'on'
        ]);
        return AdminPanelController::filterCompanyDeliveries($tempRequest);
//redirect('/filterBillingExcel', ['created_at' => $today]);
    }

    public function filterCompanyDeliveries(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $tempQueryList = [];
        $statusList = [];
        $queryParams->status_all = '';
        $queryParams->status_making = '';
        $queryParams->status_ready = '';
        $queryParams->status_delivering = '';
        $queryParams->status_delivered = '';
        $queryParams->status_cancel = '';

        if (Request::input('status_all') == "on") {
            $statusList = ['1', '2', '3', '4', '6'];
            $queryParams->status_all = 'on';
        }

        if (property_exists($queryParams, 'status_all_temp')) {
            $statusList = ['1', '2', '3', '4', '6'];
            $queryParams->status_all = 'on';
        }

        if (Request::input('status_making') == "on") {
            array_push($statusList, '1');
            $queryParams->status_making = 'on';
        }

        if (Request::input('status_ready') == "on") {
            array_push($statusList, '6');
            $queryParams->status_ready = 'on';
        }

        if (Request::input('status_delivering') == "on") {
            array_push($statusList, '2');
            $queryParams->status_delivering = 'on';
        }

        if (Request::input('status_delivered') == "on") {
            array_push($statusList, '3');
            $queryParams->status_delivered = 'on';
        }

        if (Request::input('status_cancel') == "on") {
            array_push($statusList, '4');
            $queryParams->status_cancel = 'on';
        }

        if ($tempObject['wanted_delivery_date_end']) {
            $tempObject['wanted_delivery_date_end'] = logEventController::modifyEndDate(Request::input('wanted_delivery_date_end'));
        }

        if ($tempObject['delivery_date_end']) {
            $tempObject['delivery_date_end'] = logEventController::modifyEndDate(Request::input('delivery_date_end'));
        }


        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                if ($key == 'wanted_delivery_date' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'wanted_delivery_date_end' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'wanted_delivery_date', 'state' => '<', 'value' => $value]);
                } else if ($key == 'delivery_date' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'delivery_date_end' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'delivery_date', 'state' => '<', 'value' => $value]);
                } else if ($value != "Hepsi" && $value != "" && $key != 'status' && $key != 'deliveryHour' && $value != 'on') {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '=', 'value' => $value]);
                }
            }
        }

        $QueryString = ' 1 = 1';
        foreach ($tempQueryList as $query) {
            $QueryString = $QueryString . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }
//$deliveryList = Delivery::whereRaw($QueryString)->get();

        $deliveryList = DB::table('sales_company')
            ->whereRaw($QueryString)
            ->whereIn('status', $statusList)
            ->orderBy('wanted_delivery_date', 'DESC')
            ->get();

        $id = 0;
        $myArray = [];

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($deliveryList as $delivery) {

            if ($delivery->delivery_date == "0000-00-00 00:00:00") {
                $delivery->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($delivery->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $delivery->deliveryDate = $dateInfo;
            }

            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
        }

        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 0]);

        $filterShow = 'table';
        $countDelivery = count($deliveryList);
        return view('admin.companyDeliveries', compact('deliveryList', 'id', 'myArray', 'queryParams', 'filterShow', 'countDelivery'));
    }

    public function orderAndFilterWithDescCompanyDeliveries(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $tempQueryList = [];
        $statusList = [];
        $queryParams->status_all = '';
        $queryParams->status_making = '';
        $queryParams->status_ready = '';
        $queryParams->status_delivering = '';
        $queryParams->status_delivered = '';
        $queryParams->status_cancel = '';

        if (Request::input('status_all') == "on") {
            $statusList = ['1', '2', '3', '4', '6'];
            $queryParams->status_all = 'on';
        }

        if (Request::input('status_making') == "on") {
            array_push($statusList, '1');
            $queryParams->status_making = 'on';
        }

        if (Request::input('status_ready') == "on") {
            array_push($statusList, '6');
            $queryParams->status_ready = 'on';
        }

        if (Request::input('status_delivering') == "on") {
            array_push($statusList, '2');
            $queryParams->status_delivering = 'on';
        }

        if (Request::input('status_delivered') == "on") {
            array_push($statusList, '3');
            $queryParams->status_delivered = 'on';
        }

        if (Request::input('status_cancel') == "on") {
            array_push($statusList, '4');
            $queryParams->status_cancel = 'on';
        }

        if ($tempObject['wanted_delivery_date_end']) {
            $tempObject['wanted_delivery_date_end'] = logEventController::modifyEndDate(Request::input('wanted_delivery_date_end'));
        }

        if ($tempObject['delivery_date_end']) {
            $tempObject['delivery_date_end'] = logEventController::modifyEndDate(Request::input('delivery_date_end'));
        }

        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                if ($key == 'wanted_delivery_date' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'wanted_delivery_date_end' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'wanted_delivery_date', 'state' => '<', 'value' => $value]);
                } else if ($key == 'delivery_date' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'delivery_date_end' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'delivery_date', 'state' => '<', 'value' => $value]);
                } else if ($key == 'status' && $value != "" && $value != 0) {
                    array_push($tempQueryList, (object)['attribute' => 'status', 'state' => '=', 'value' => $value]);
                } else if ($value != "Hepsi" && $value != "" && $key != 'status' && $key != 'deliveryHour' && $value != 'on' && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '=', 'value' => $value]);
                }
            }
        }

        $QueryString = ' 1 = 1';
        foreach ($tempQueryList as $query) {
            $QueryString = $QueryString . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }
//dd($QueryString);

        if ($tempObject['upOrDown'] == 'up') {
            $deliveryList = DB::table('sales_company')
                ->whereRaw($QueryString)->orderBy($tempObject['orderParameter'])->get();
        } else {
            $deliveryList = DB::table('sales_company')
                ->whereRaw($QueryString)->orderBy($tempObject['orderParameter'], 'DESC')->get();
        }
        $id = 0;
        $myArray = [];

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($deliveryList as $delivery) {

            if ($delivery->delivery_date == "0000-00-00 00:00:00") {
                $delivery->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($delivery->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $delivery->deliveryDate = $dateInfo;
            }
            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
        }

        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 5]);

        $filterShow = 'none';
        $countDelivery = count($deliveryList);
        return view('admin.companyDeliveries', compact('deliveryList', 'id', 'myArray', 'queryParams', 'countDelivery', 'filterShow'));
    }

    public function showTodayCompanyDeliveries()
    {
        AdminPanelController::checkAdmin();

        $id = 0;

        $today = Carbon::now();
        $today->startOfDay();

        $todayEnd = Carbon::now();
        $todayEnd->endOfDay();
        $deliveryList = DB::table('sales_company')
            ->where('wanted_delivery_date', '>', $today)
            ->orderBy('wanted_delivery_date')
            ->get();
        $myArray = [];
        $queryParams = [];
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($deliveryList as $delivery) {

            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
        }

        $queryParams = (object)['created_at' => "", 'created_at_end' => "", 'product_name' => "", 'wanted_delivery_date_end' => explode(' ', $todayEnd)[0], 'wanted_delivery_date' => explode(' ', $today)[0], 'delivery_date_end' => "", 'delivery_date' => "", 'status' => "", 'deliveryHour' => "", 'continent_id' => "",
            'status_all' => "on", 'status_making' => "", 'status_ready' => "", 'status_delivering' => "", 'status_delivered' => "", 'status_cancel' => "", 'company_name' => ""
        ];

        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 0]);

        $countDelivery = count($deliveryList);

        $filterShow = 'none';

        return view('admin.companyDeliveries', compact('deliveryList', 'id', 'myArray', 'queryParams', 'countDelivery', 'filterShow'));
    }

    public function manualDeliveryPage()
    {
        $billingList = DB::table('company_billing')->get();
        $productList = DB::table('products')->get();

        $myArray = [];
        array_push($myArray, (object)['hour' => '00', 'val' => '00']);
        array_push($myArray, (object)['hour' => '01', 'val' => '01']);
        array_push($myArray, (object)['hour' => '02', 'val' => '02']);
        array_push($myArray, (object)['hour' => '03', 'val' => '03']);
        array_push($myArray, (object)['hour' => '04', 'val' => '04']);
        array_push($myArray, (object)['hour' => '05', 'val' => '05']);
        array_push($myArray, (object)['hour' => '06', 'val' => '06']);
        array_push($myArray, (object)['hour' => '07', 'val' => '07']);
        array_push($myArray, (object)['hour' => '08', 'val' => '08']);
        array_push($myArray, (object)['hour' => '09', 'val' => '09']);
        array_push($myArray, (object)['hour' => '10', 'val' => '10']);
        array_push($myArray, (object)['hour' => '11', 'val' => '11']);
        array_push($myArray, (object)['hour' => '12', 'val' => '12']);
        array_push($myArray, (object)['hour' => '13', 'val' => '13']);
        array_push($myArray, (object)['hour' => '14', 'val' => '14']);
        array_push($myArray, (object)['hour' => '15', 'val' => '15']);
        array_push($myArray, (object)['hour' => '16', 'val' => '16']);
        array_push($myArray, (object)['hour' => '17', 'val' => '17']);
        array_push($myArray, (object)['hour' => '18', 'val' => '18']);
        array_push($myArray, (object)['hour' => '19', 'val' => '19']);
        array_push($myArray, (object)['hour' => '20', 'val' => '20']);
        array_push($myArray, (object)['hour' => '21', 'val' => '21']);
        array_push($myArray, (object)['hour' => '22', 'val' => '22']);
        array_push($myArray, (object)['hour' => '23', 'val' => '23']);

        return view('admin.companyInsertDelivery', compact('billingList', 'productList', 'myArray'));
    }

    public function insertCompanyDelivery()
    {
        try {
            $now = Carbon::now();
            DB::beginTransaction();
            $productInfo = Product::where('id', Request::input('product_id'))->get()[0];
            $companyInfo = DB::table('company_billing')->where('id', Request::input('company_id'))->get()[0];

            $wantedDeliveryDateStart = new Carbon(Request::input('wanted_delivery_date'));
            $wantedDeliveryDateLimit = new Carbon(Request::input('wanted_delivery_date'));
//dd(Request::input('wanted_delivery_date_1'));
            $wantedDeliveryDateStart->hour(Request::input('wanted_delivery_date_1'));
            $wantedDeliveryDateStart->minute(0);
            $wantedDeliveryDateStart->second(0);

            $wantedDeliveryDateLimit->hour(Request::input('wanted_delivery_date_2'));
            $wantedDeliveryDateLimit->minute(0);
            $wantedDeliveryDateLimit->second(0);

            DB::table('sales_company')
                ->insert([
                    'total' => Request::input('total_price'),
                    'product_price' => $productInfo->price,
                    'product_id' => Request::input('product_id'),
                    'product_name' => $productInfo->name,
                    'card_sender' => Request::input('card_sender'),
                    'card_message' => Request::input('card_message'),
                    'card_receiver' => Request::input('card_receiver'),
                    'receiver' => Request::input('receiver'),
                    'receiver_mobile' => Request::input('receiver_mobile'),
                    'receiver_address' => Request::input('receiver_address'),
                    'company_name' => $companyInfo->company,
                    'company_mobile' => $companyInfo->mobile,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'delivery_location' => Request::input('delivery_location'),
                    'delivery_not' => Request::input('admin_not'),
                    'wanted_delivery_date' => $wantedDeliveryDateStart,
                    'wanted_delivery_limit' => $wantedDeliveryDateLimit,
                    'status' => '1',
                    'billing_id' => Request::input('company_id')
                ]);
            DB::commit();
            return redirect('/admin/company-deliveries/week');
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function insertCompanySales()
    {
        try {
            Excel::load(Request::file('file'), function ($reader) {
                $tempSalesInfo = $reader->toArray();
                $now = Carbon::now();
                DB::beginTransaction();
                foreach ($tempSalesInfo as $oneSale) {
                    $productInfo = Product::where('id', $oneSale['product_id'])->get()[0];
                    $companyInfo = DB::table('company_billing')->where('id', $oneSale['company_id'])->get()[0];

                    DB::table('sales_company')
                        ->insert([
                            'total' => $oneSale['total_price'],
                            'product_price' => $productInfo->price,
                            'product_id' => $oneSale['product_id'],
                            'product_name' => $productInfo->name,
                            'card_sender' => $oneSale['card_sender'],
                            'card_message' => $oneSale['card_message'],
                            'card_receiver' => $oneSale['card_receiver'],
                            'receiver' => $oneSale['receiver'],
                            'receiver_mobile' => $oneSale['receiver_mobile'],
                            'receiver_address' => $oneSale['receiver_address'],
                            'company_name' => $companyInfo->company,
                            'company_mobile' => $companyInfo->mobile,
                            'created_at' => $now,
                            'updated_at' => $now,
                            'delivery_location' => $oneSale['delivery_location'],
                            'admin_not' => $oneSale['admin_not'],
                            'wanted_delivery_date' => $oneSale['wanted_delivery_date'],
                            'wanted_delivery_limit' => $oneSale['wanted_delivery_limit'],
                            'status' => '1',
                            'billing_id' => $oneSale['company_id']
                        ]);
                }
            });
            DB::commit();
            return redirect('/admin/company-deliveries');
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function updateOrderProductBetween()
    {
        $fromID = Request::input('fromId');
        $toID = Request::input('toPlace');
        DB::table('products')->where('landing_page_order', '>', $toID)->increment('landing_page_order');
        DB::table('products')->where('id', $fromID)->update([
            'landing_page_order' => intval($toID) + 1
        ]);

        return redirect('/admin/orderProduct');
    }

    public function updateOrderProduct()
    {
        try {
            $fromOrder = DB::table('products')->where('id', Request::input('fromId'))->select('landing_page_order')->get()[0]->landing_page_order;
            $toOrder = DB::table('products')->where('id', Request::input('toId'))->select('landing_page_order')->get()[0]->landing_page_order;
            DB::table('products')->where('id', Request::input('fromId'))->update([
                'landing_page_order' => $toOrder
            ]);
            DB::table('products')->where('id', Request::input('toId'))->update([
                'landing_page_order' => $fromOrder
            ]);
            return response()->json(["status" => 1], 200);
        } catch (\Exception $e) {
            logEventController::logErrorToDB('updateOrderProduct', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }

    public function orderLandingProduct()
    {
        $flowerList = DB::table('shops')
            ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
            ->join('products', 'products_shops.products_id', '=', 'products.id')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->where('shops.id', '=', 1)
            ->where('company_product', '=', '0')
            ->where('descriptions.lang_id', '=', 'tr')
            ->where('products.activation_status_id', '=', 1)
            ->where('products.activation_status_id', '=', 1)
            ->select('products.coming_soon', 'products.limit_statu', 'products.id', 'products.name', 'products.price',
                'products.image_name', 'products.background_color', 'products.second_background_color', 'products.landing_page_order')
            ->orderBy('landing_page_order')
            ->get();

        for ($x = 0; $x < count($flowerList); $x++) {
            $tagList = DB::table('products_tags')
                ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                ->where('products_tags.products_id', '=', $flowerList[$x]->id)
                ->where('tags.lang_id', '=', 'tr')
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
            $flowerList[$x]->detailListImage = $detailListImage;
        }
        return view('admin.orderProduct', compact('flowerList'));
    }

    public function updateProductDeliveryTime()
    {
        AdminPanelController::checkAdmin();
        $today = Carbon::now();
        $todayEnd = Carbon::now();
        $tempAvalibilityTime = explode("-", Request::input('avalibility_time'));
        $today->year((float)$tempAvalibilityTime[0]);
        $today->day((float)$tempAvalibilityTime[2]);
        $today->month((float)$tempAvalibilityTime[1]);
//dd($today);
//$tempAvalibilityTime = new Carbon(Request::input('avalibility_time') );
        $today->hour(Request::input('hour'));
        $today->minute(Request::input('minute'));


        $tempAvalibilityTimeEnd = explode("-", Request::input('avalibility_time_end'));
        $todayEnd->year((float)$tempAvalibilityTimeEnd[0]);
        $todayEnd->month((float)$tempAvalibilityTimeEnd[1]);
        $todayEnd->day((float)$tempAvalibilityTimeEnd[2]);
        $todayEnd->hour(Request::input('hour_end'));
        $todayEnd->minute(Request::input('minute_end'));
        DB::table('product_city')->where('product_id', Request::input('id'))->where('city_id', Request::input('city_id'))->update([
            'avalibility_time' => $today,
            'avalibility_time_end' => $todayEnd
        ]);
        return redirect('/admin/product-delivery-time/0/0');
    }

    public function getProductDeliveryTime($id, $city_id)
    {
        AdminPanelController::checkAdmin();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or product_city.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

//$products = Product::latest('published_at')->get();

        $productList = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('product_city.active', 1)
            ->where('product_id_for_company', '0')
            ->whereRaw($tempWhere)
            ->select('products.id', 'products.name', 'product_city.city_id', 'product_city.avalibility_time', 'product_city.avalibility_time_end', 'product_city.activation_status_id')->orderBy('activation_status_id', 'DESC')->get();
        foreach ($productList as $product) {
            $wantedDeliveryDate = new Carbon($product->avalibility_time);
            $product->hour = $wantedDeliveryDate->hour;
            $product->minute = $wantedDeliveryDate->minute;
        }
        return view('admin.productDeliveryTimeNew', compact('id', 'productList', 'city_id'));
    }

    public function showTodayUps()
    {
        AdminPanelController::checkAdmin();

        $id = 0;

        $today = Carbon::now();
        $today->startOfDay();

        $todayEnd = Carbon::now();
        $todayEnd->endOfDay();

        $deliveryList = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.ups', 1)
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address',
                DB::raw("'0' as studio"), 'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->orderBy('sales.created_at', 'DESC')
            ->get();
        $myArray = [];
        $queryParams = [];
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        foreach ($deliveryList as $delivery) {
            $requestDate = new Carbon($delivery->created_at);
            $dateInfo = $requestDate->formatLocalized('%a %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            $delivery->requestDate = $dateInfo;

            if ($delivery->delivery_date == "0000-00-00 00:00:00") {
                $delivery->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($delivery->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $delivery->deliveryDate = $dateInfo;
            }


            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
            $delivery->prime = 0;
            if ($delivery->user_id) {
                if (DB::table('users')->where('id', $delivery->user_id)->where('prime', '>', 0)->count() > 0) {
                    $delivery->prime = 1;
                }
            }
            $tempCikolat = AdminPanelController::getCikolatData($delivery->sale_id);

            if ($tempCikolat) {
                $delivery->cikilot = $tempCikolat->name;
            } else
                $delivery->cikilot = "";
        }

        $queryParams = (object)['operation_name' => "Hepsi", 'created_at' => "", 'created_at_end' => "", 'products' => "", 'wanted_delivery_date_end' => explode(' ', $todayEnd)[0], 'wanted_delivery_date' => explode(' ', $today)[0], 'delivery_date_end' => "", 'delivery_date' => "", 'status' => "", 'deliveryHour' => "", 'continent_id' => "",
            'status_all' => "on", 'status_making' => "", 'status_ready' => "", 'status_delivering' => "", 'status_delivered' => "", 'status_cancel' => "", 'small_city' => "Hepsi"
        ];


        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 0]);

        $deliveryHourList = [];

        array_push($deliveryHourList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($deliveryHourList, (object)['information' => '09-13', 'status' => '09:00:00']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00']);

        $continentList = [];
        $operationList = DB::table('operation_person')->get();
        array_push($operationList, (object)['name' => 'Hepsi']);

        array_push($continentList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($continentList, (object)['information' => 'Avrupa', 'status' => 'Avrupa']);
        array_push($continentList, (object)['information' => 'Asya', 'status' => 'Asya']);
        array_push($continentList, (object)['information' => 'Oyaka', 'status' => 'Avrupa-2']);

        $countDelivery = count($deliveryList);

        $locationList = DB::table('delivery_locations')->groupBy('small_city')->select('small_city')->get();
        array_push($locationList, (object)['small_city' => 'Hepsi']);
//dd($locationList);

        $filterShow = 'none';

        return view('admin.upsSales', compact('operationList', 'deliveryList', 'id', 'myArray', 'queryParams', 'countDelivery', 'filterShow', 'deliveryHourList', 'continentList', 'locationList'));

    }

    public function showTodayDeliveries()
    {
        AdminPanelController::checkAdmin();

        $id = 0;

        $today = Carbon::now();
        $today->startOfDay();

        $todayEnd = Carbon::now();
        $todayEnd->endOfDay();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $deliveryList = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->whereRaw($tempWhere)
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayEnd)
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address',
                'sales.isPrintedDelivery', 'sales.isPrintedNote', 'sales.planning_courier_id',
                DB::raw("'0' as studio"), 'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->orderBy('sales.created_at', 'DESC')
            ->get();
        $myArray = [];
        $queryParams = [];
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        /*
* StudioBloom Siparis Entegrasyonu
*/

        $tempStudioBloom = DB::table('studioBloom')
            ->where('wanted_date', '>', $today)
            ->where('wanted_date', '<', $todayEnd)
            ->where('status', 'Ödeme Yapıldı')
            ->select(
                'contact_name',
                'contact_surname',
                'customer_name',
                'customer_surname',
                'continent_id',
                'district',
                'receiver_address',
                'id as sale_id',
                'id',
                'wanted_date as wanted_delivery_date',
                'flower_name as product_name',
                'flower_name as products',
                'flower_desc',
                'wanted_date',
                'price',
                'note as delivery_not',
                'delivery_status as status',
                'created_at',
                'payment_date',
                'wanted_delivery_limit',
                'delivery_date',
                'picker',
                'operation_name',
                'email',
                DB::raw("'0' as isPrintedDelivery"),
                DB::raw("'0' as isPrintedNote"),
                DB::raw("'0' as planning_courier_id")
            )->get();
        foreach ($tempStudioBloom as $studio) {
            $studio->studio = 1;
            $studio->user_id = 0;
            array_unshift($deliveryList, (object)$studio);
        }

        /*
        * StudioBloom Entegrasyon End
        */

        foreach ($deliveryList as $delivery) {
            $requestDate = new Carbon($delivery->created_at);
            $dateInfo = $requestDate->formatLocalized('%a %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            $delivery->requestDate = $dateInfo;

            if ($delivery->delivery_date == "0000-00-00 00:00:00") {
                $delivery->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($delivery->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $delivery->deliveryDate = $dateInfo;
            }


            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
            $delivery->prime = 0;
            if ($delivery->user_id) {
                if (DB::table('users')->where('id', $delivery->user_id)->where('prime', '>', 0)->count() > 0) {
                    $delivery->prime = 1;
                }
            }

            $tempCikolat = AdminPanelController::getCikolatData($delivery->sale_id);

            if ($tempCikolat) {
                $delivery->cikilot = $tempCikolat->name;
            } else
                $delivery->cikilot = "";

            $delivery->scottyInfo = DB::table('scotty_sales')->where('sale_id', $delivery->sale_id)->get();

        }

        $queryParams = (object)['operation_name' => "Hepsi", 'created_at' => "", 'created_at_end' => "", 'products' => "", 'wanted_delivery_date_end' => explode(' ', $todayEnd)[0], 'wanted_delivery_date' => explode(' ', $today)[0], 'delivery_date_end' => "", 'delivery_date' => "", 'status' => "", 'deliveryHour' => "", 'continent_id' => "",
            'status_all' => "on", 'status_making' => "", 'status_ready' => "", 'status_delivering' => "", 'status_delivered' => "", 'status_cancel' => "", 'small_city' => "Hepsi", 'planning_courier_id' => 'Hepsi'
        ];

        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 0]);

        $deliveryHourList = [];

        array_push($deliveryHourList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($deliveryHourList, (object)['information' => '09-13', 'status' => '09:00:00']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00']);

        $continentList = [];
        $operationList = DB::table('operation_person')->where('active', '=', 1)->orderBy('position')->get();
        array_push($operationList, (object)['name' => 'Hepsi']);

        foreach ($cityList as $city) {
            if ($city->city_id == 1) {
                array_push($continentList, (object)['information' => 'Avrupa', 'status' => 'Avrupa', 'selected' => true]);
                array_push($continentList, (object)['information' => 'Oyaka', 'status' => 'Avrupa-2', 'selected' => true]);
                array_push($continentList, (object)['information' => 'Avrupa-3', 'status' => 'Avrupa-3', 'selected' => true]);
            }
            if ($city->city_id == 2) {
                array_push($continentList, (object)['information' => 'Ankara-1', 'status' => 'Ankara-1', 'selected' => true]);
                array_push($continentList, (object)['information' => 'Ankara-2', 'status' => 'Ankara-2', 'selected' => true]);
            }
            if ($city->city_id == 341) {
                array_push($continentList, (object)['information' => 'Asya', 'status' => 'Asya', 'selected' => true]);
                array_push($continentList, (object)['information' => 'Asya-2', 'status' => 'Asya-2', 'selected' => true]);
            }
        }

        $countDelivery = count($deliveryList);

        $locationList = DB::table('delivery_locations')->groupBy('small_city')->whereRaw($tempWhere)->select('small_city')->get();
        array_push($locationList, (object)['small_city' => 'Hepsi']);
        //dd($locationList);

        $filterShow = 'none';

        return view('admin.deliveries', compact('operationList', 'deliveryList', 'id', 'myArray', 'queryParams', 'countDelivery', 'filterShow', 'deliveryHourList', 'continentList', 'locationList'));
    }

    public function updateInformationDeliveries()
    {
        AdminPanelController::checkAdmin();

        //dd(Request::all());

        if (Request::input('changingVariable') == "Ürün Adı") {

            $productData = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.id' , Request::input('salesId') )
                ->select( 'sales_products.products_id', 'delivery_locations.city_id', 'sales.id' )
                ->get()[0];

            $oldProductId = $productData->products_id;

            $productData->products_id = Request::input('productName');

            if( generateDataController::isProductAvailable($productData->products_id, $productData->city_id) ){

                generateDataController::setProductCountOneLess($productData->products_id, $productData->city_id);

                $productStockData = DB::table('product_stocks')->where('product_id', $productData->products_id )->where('city_id', $productData->city_id)->get()[0];

                generateDataController::logStock( 'DEĞİŞİM', $productStockData->id, $productData->id, $productStockData->count + 1, $productStockData->count, \Auth::user()->id );

                generateDataController::setProductCountOneMore($oldProductId, $productData->city_id);

                $productStockData = DB::table('product_stocks')->where('product_id', $oldProductId )->where('city_id', $productData->city_id)->get()[0];

                generateDataController::logStock( 'DEĞİŞİM', $productStockData->id, $productData->id, $productStockData->count - 1, $productStockData->count, \Auth::user()->id );

            }
            else {
                dd('Sipariş değişimi yapılamaz! İlgili ürünün stok sayısı yetersiz!');
            }

            DB::table('sales_products')->where('sales_id', Request::input('salesId'))->update([
                'products_id' => Request::input('productName')
            ]);

            DB::table('deliveries')->where('sales_id', Request::input('salesId'))->update([
                'products' => DB::table('products')->where('id', Request::input('productName'))->get()[0]->name
            ]);
        } else if (Request::input('changingVariable') == "Gönderen Telefon Numarası") {
            DB::table('sales')->where('id', Request::input('salesId'))->update([
                'sender_mobile' => Request::input('changeVariable')
            ]);
        } else if (Request::input('changingVariable') == "Alıcı Adı") {
            DB::table('customer_contacts')->where('id', DB::table('sales')->where('id', Request::input('salesId'))->select('customer_contact_id')->get()[0]->customer_contact_id)->update([
                'name' => Request::input('changeVariable')
            ]);
        } else if (Request::input('changingVariable') == "Alıcı Soyadı") {
            DB::table('customer_contacts')->where('id', DB::table('sales')->where('id', Request::input('salesId'))->select('customer_contact_id')->get()[0]->customer_contact_id)->update([
                'surname' => Request::input('changeVariable')
            ]);
        } else if (Request::input('changingVariable') == "Alıcı Telefon Numarası") {
            DB::table('sales')->where('id', Request::input('salesId'))->update([
                'receiver_mobile' => Request::input('changeVariable')
            ]);
        } else if (Request::input('changingVariable') == "Sipariş Bölgesi") {
            DB::table('sales')->where('id', Request::input('salesId'))->update([
                'delivery_locations_id' => Request::input('locationName')
            ]);
        } else if (Request::input('changingVariable') == "Adres") {
            DB::table('sales')->where('id', Request::input('salesId'))->update([
                'receiver_address' => Request::input('changeVariable')
            ]);
        } else if (Request::input('changingVariable') == "Ekstra Ürün Adı") {

            $tempCrossSellData = DB::table('cross_sell')->where('sales_id', Request::input('salesId'))->get();

            $productData = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.id', Request::input('salesId'))
                ->select('sales_products.products_id', 'delivery_locations.city_id', 'sales.id')
                ->get()[0];

            $productData->product_id = Request::input('crossSellName');

            //if( count($tempCrossSellData) > 0 ){

            if (generateDataController::isCrossSellAvailable($productData->product_id, $productData->city_id)) {

                generateDataController::setCrossSellCountOneLess($productData->product_id, $productData->city_id);

                $productCrossSellData = DB::table('cross_sell_products')->where('id', $productData->product_id)->where('city_id', $productData->city_id)->get();

                if ($productCrossSellData[0]->product_id > 0) {
                    $productStockData = DB::table('product_stocks')->where('product_id', $productCrossSellData[0]->product_id)->where('city_id', $productData->city_id)->get()[0];
                } else {
                    $productStockData = DB::table('product_stocks')->where('cross_sell_id', $productData->product_id)->where('city_id', $productData->city_id)->get()[0];
                }

                generateDataController::logStock('CROSS-SELL DEĞİŞİM', $productStockData->id, $productData->id, $productStockData->count + 1, $productStockData->count, \Auth::user()->id);

                if (count($tempCrossSellData) > 0) {

                    $oldProductId = $tempCrossSellData[0]->product_id;

                    generateDataController::setCrossSellCountOneMore($oldProductId, $productData->city_id);

                    $productCrossSellData = DB::table('cross_sell_products')->where('id', $oldProductId)->where('city_id', $productData->city_id)->get();

                    if ($productCrossSellData[0]->product_id > 0) {
                        $productStockData = DB::table('product_stocks')->where('product_id', $productCrossSellData[0]->product_id)->where('city_id', $productData->city_id)->get()[0];
                    } else {
                        $productStockData = DB::table('product_stocks')->where('cross_sell_id', $oldProductId)->where('city_id', $productData->city_id)->get()[0];
                    }

                    generateDataController::logStock('CROSS-SELL DEĞİŞİM', $productStockData->id, $productData->id, $productStockData->count - 1, $productStockData->count, \Auth::user()->id);
                }


            } else {
                dd('Sipariş değişimi yapılamaz! İlgili ürünün stok sayısı yetersiz!');
            }

            //}

            if (DB::table('cross_sell')->where('sales_id', Request::input('salesId'))->count() > 0) {

                $tempCrossProduct = DB::table('cross_sell_products')->where('id', Request::input('crossSellName'))->get()[0];
                $tempCrossSellPrice = floatval(str_replace(',', '.', $tempCrossProduct->price));
                $tempCrossSellDiscount = 0;
                $tempCrossSellTax = number_format(floatval($tempCrossSellPrice / 100.0 * 8.0), 2);

                $tempCrossSellPrice = number_format(floatval($tempCrossSellPrice * 108 / 100), 2);

                $tempCrossSellTotal = $tempCrossSellPrice;

                DB::table('cross_sell')->where('sales_id', Request::get('salesId'))->delete();
                DB::table('cross_sell')->insert([
                    'sales_id' => Request::get('salesId'),
                    'product_id' => Request::input('crossSellName'),
                    'product_price' => $tempCrossProduct->price,
                    'discount' => $tempCrossSellDiscount,
                    'tax' => str_replace('.', ',', $tempCrossSellTax),
                    'total_price' => str_replace('.', ',', $tempCrossSellTotal)
                ]);

            } else {
                $tempCrossProduct = DB::table('cross_sell_products')->where('id', Request::input('crossSellName'))->get()[0];
                $tempCrossSellPrice = floatval(str_replace(',', '.', $tempCrossProduct->price));
                $tempCrossSellDiscount = 0;
                $tempCrossSellTax = number_format(floatval($tempCrossSellPrice / 100.0 * 8.0), 2);

                $tempCrossSellPrice = number_format(floatval($tempCrossSellPrice * 108 / 100), 2);

                $tempCrossSellTotal = $tempCrossSellPrice;

                //DB::table('cross_sell')->where('sales_id' , Request::get('salesId'))->delete();
                DB::table('cross_sell')->insert([
                    'sales_id' => Request::get('salesId'),
                    'product_id' => Request::input('crossSellName'),
                    'product_price' => $tempCrossProduct->price,
                    'discount' => $tempCrossSellDiscount,
                    'tax' => str_replace('.', ',', $tempCrossSellTax),
                    'total_price' => str_replace('.', ',', $tempCrossSellTotal)
                ]);
            }
        } else if (Request::input('changingVariable') == "İstenen Teslim Tarihi") {
            $limitDate = new Carbon(Request::input('dateName'));
            $limitDate->hour(explode(":", explode("-", Request::input('dateNameHour'))[0])[0]);
            $limitDate->minute(00);
            $limitDateEnd = new Carbon(Request::input('dateName'));
            $limitDateEnd->hour(explode(":", explode("-", Request::input('dateNameHour'))[1])[0]);
            $limitDateEnd->minute(00);
            DB::table('deliveries')->where('sales_id', Request::input('salesId'))->update([
                'wanted_delivery_date' => $limitDate,
                'wanted_delivery_limit' => $limitDateEnd
            ]);
        } else if (Request::input('changingVariable') == "Kart Alıcı Adı") {
            DB::table('sales')->where('id', Request::input('salesId'))->update([
                'receiver' => Request::input('changeVariable')
            ]);
        } else if (Request::input('changingVariable') == "Kart Mesajı") {
            DB::table('sales')->where('id', Request::input('salesId'))->update([
                'card_message' => Request::input('changeVariable')
            ]);
        } else if (Request::input('changingVariable') == "Kart Gönderen Adı") {
            DB::table('sales')->where('id', Request::input('salesId'))->update([
                'sender' => Request::input('changeVariable')
            ]);
        }

        $deliveryId = DB::table('deliveries')->where('sales_id', Request::input('salesId'))->get()[0]->id;

        return redirect('/admin/deliveries/detail/' . $deliveryId);

    }

    public function getStatistic()
    {
        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);
        $today->second(00);

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $todaySale = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)
            ->count();

        if (count($cityList) > 1) {
            $todaySaleUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.created_at', '>', $today)
                ->where('delivery_locations.city_id', '=', 3)
                ->count();
        } else {
            $todaySaleUps = 0;
        }


        $todaySaleExtra = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)
            ->count();

        $todayUserNumber = Customer::where('customers.created_at', '>', $today)->count();

        $today->setDate($today->year, $today->month, 1);
        $today->hour(00);
        $today->minute(00);
        $today->second(00);
        $thisMonthSale = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)->count();

        if (count($cityList) > 1) {
            $thisMonthSaleUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.created_at', '>', $today)
                ->where('delivery_locations.city_id', '=', 3)
                ->count();
        } else {
            $thisMonthSaleUps = 0;
        }

        $thisMonthSaleExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)->count();

        $monthUserNumber = Customer::where('customers.created_at', '>', $today)->count();

        $today = Carbon::now();
        $today->startOfWeek();
        $today->hour(00);
        $today->minute(00);
        $today->second(00);
        $thisWeekSale = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)->count();

        if (count($cityList) > 1) {
            $thisWeekSaleUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.created_at', '>', $today)
                ->where('delivery_locations.city_id', '=', 3)
                ->count();
        } else {
            $thisWeekSaleUps = 0;
        }

        $thisWeekSaleExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)->count();

        $weekUserNumber = Customer::where('customers.created_at', '>', $today)->count();

        $pastToday = Carbon::now();
        $pastToday->addDay(-7);
        $pastToday->hour(00);
        $pastToday->minute(00);
        $pastToday->second(00);
        $pastUpToday = Carbon::now();
        $pastUpToday->addDay(-6);
        $pastUpToday->hour(00);
        $pastUpToday->minute(00);
        $pastUpToday->second(00);
        $todayPastSale = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastUpToday)
            ->whereRaw($tempWhere)
            ->count();

        if (count($cityList) > 1) {
            $todayPastSaleUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.created_at', '>', $pastToday)
                ->where('sales.created_at', '<', $pastUpToday)
                ->where('delivery_locations.city_id', '=', 3)
                ->count();
        } else {
            $todayPastSaleUps = 0;
        }

        $todayPastSaleExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastUpToday)
            ->whereRaw($tempWhere)
            ->count();

        $pastTodayUserNumber = Customer::where('customers.created_at', '>', $pastToday)
            ->where('customers.created_at', '<', $pastUpToday)->count();

        $pastToday = Carbon::now();
        $pastToday = $pastToday->subMonthsNoOverflow(1);
        $pastToday->startOfMonth();
        $pastTodayTemp = Carbon::now();
        $pastTodayTemp = $pastTodayTemp->subMonthsNoOverflow(1);
        $pastTodayTemp->endOfMonth();
        $pastTodayTemp->hour(23);
        $pastTodayTemp->minute(59);
        $pastTodayTemp->second(59);
        $pastToday->hour(00);
        $pastToday->minute(00);
        $pastToday->second(00);
        $pastMonthSale = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastTodayTemp)
            ->whereRaw($tempWhere)
            ->count();

        if (count($cityList) > 1) {
            $pastMonthSaleUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('sales.created_at', '>', $pastToday)
                ->where('sales.created_at', '<', $pastTodayTemp)
                ->where('delivery_locations.city_id', '=', 3)
                ->count();
        } else {
            $pastMonthSaleUps = 0;
        }

        $pastMonthSaleExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastTodayTemp)
            ->whereRaw($tempWhere)
            ->count();

        $pastMonthUserNumber = Customer::where('customers.created_at', '>', $pastToday)
            ->where('customers.created_at', '<', $pastTodayTemp)->count();

        $pastToday = Carbon::now();
        $pastToday->addDay(-7);
        $pastToday->startOfWeek();
        $pastToday->hour(00);
        $pastToday->minute(00);
        $pastToday->second(00);

        $pastUpTodayToday = Carbon::now();
        $pastUpTodayToday->addDay(-7);
        $pastUpTodayToday->endOfWeek();
        $pastUpTodayToday->hour(23);
        $pastUpTodayToday->minute(59);
        $pastUpTodayToday->second(59);
        $pastWeekSale = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastUpTodayToday)
            ->whereRaw($tempWhere)
            ->count();

        if (count($cityList) > 1) {
            $pastWeekSaleUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.created_at', '>', $pastToday)
                ->where('sales.created_at', '<', $pastUpTodayToday)
                ->where('delivery_locations.city_id', '=', 3)
                ->count();
        } else {
            $pastWeekSaleUps = 0;
        }


        $pastWeekSaleExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastUpTodayToday)
            ->whereRaw($tempWhere)
            ->count();

        $pastWeekUserNumber = Customer::where('customers.created_at', '>', $pastToday)
            ->where('customers.created_at', '<', $pastUpTodayToday)->count();

        ////

        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);
        $today->second(00);
        $todaySaleC = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('products.city_id', '=', 1)
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;

        if (count($cityList) > 1) {
            $todaySaleCUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('products.city_id', '=', 1)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('sales.created_at', '>', $today)
                ->where('delivery_locations.city_id', '=', 3)
                ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;
        } else {
            $todaySaleCUps = 0;
        }

        //dd($todaySaleC);
        //->select(DB::raw(' ROUND(SUM(replace(sum_total, ",", ".")) ) as totalTemp'))->get()[0]->totalTemp;


        $todaySaleCExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(replace(cross_sell.product_price, ",", "."))  as totalTemp'))->get()[0]->totalTemp;

        $today->setDate($today->year, $today->month, 1);
        $today->hour(00);
        $today->minute(00);
        $today->second(00);
        $thisMonthSaleC = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('products.city_id', '=', 1)
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;

        if (count($cityList) > 1) {
            $thisMonthSaleCUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('products.city_id', '=', 1)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('sales.created_at', '>', $today)
                ->where('delivery_locations.city_id', '=', 3)
                ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;
        } else {
            $thisMonthSaleCUps = 0;
        }

        $thisMonthSaleCExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(replace(cross_sell.product_price, ",", "."))  as totalTemp'))->get()[0]->totalTemp;

        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);
        $today->second(00);
        $today->startOfWeek();
        $thisWeekSaleC = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('products.city_id', '=', 1)
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;

        if (count($cityList) > 1) {
            $thisWeekSaleCUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('products.city_id', '=', 1)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('sales.created_at', '>', $today)
                ->where('delivery_locations.city_id', '=', 3)
                ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;
        } else {
            $thisWeekSaleCUps = 0;
        }


        $thisWeekSaleCExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $today)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(replace(cross_sell.product_price, ",", "."))  as totalTemp'))->get()[0]->totalTemp;

        $pastToday = Carbon::now();
        $pastToday->addDay(-7);
        $pastToday->hour(00);
        $pastToday->minute(00);
        $pastToday->second(00);
        $pastUpToday = Carbon::now();
        $pastUpToday->addDay(-6);
        $pastUpToday->hour(00);
        $pastUpToday->minute(00);
        $pastUpToday->second(00);
        $todayPastSaleC = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('products.city_id', '=', 1)
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastUpToday)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;

        if (count($cityList) > 1) {
            $todayPastSaleCUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('products.city_id', '=', 1)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('sales.created_at', '>', $pastToday)
                ->where('sales.created_at', '<', $pastUpToday)
                ->where('delivery_locations.city_id', '=', 3)
                ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;
        } else {
            $todayPastSaleCUps = 0;
        }

        $todayPastSaleCExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastUpToday)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(replace(cross_sell.product_price, ",", "."))  as totalTemp'))->get()[0]->totalTemp;

        $pastToday = Carbon::now();
        $pastToday = $pastToday->subMonthsNoOverflow(1);
        $pastToday->setDate($pastToday->year, $pastToday->month, 1);
        $pastTodayTemp = Carbon::now();
        $pastTodayTemp = $pastTodayTemp->subMonthsNoOverflow(1);
        $pastTodayTemp->endOfMonth();
        $pastTodayTemp->hour(23);
        $pastTodayTemp->minute(59);
        $pastToday->hour(00);
        $pastToday->minute(00);
        $pastToday->second(00);
        $pastMonthSaleC = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('products.city_id', '=', 1)
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastTodayTemp)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;

        if (count($cityList) > 1) {
            $pastMonthSaleCUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('products.city_id', '=', 1)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.created_at', '>', $pastToday)
                ->where('sales.created_at', '<', $pastTodayTemp)
                ->where('delivery_locations.city_id', '=', 3)
                ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;
        } else {
            $pastMonthSaleCUps = 0;
        }

        $pastMonthSaleCExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastTodayTemp)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(replace(cross_sell.product_price, ",", "."))  as totalTemp'))->get()[0]->totalTemp;

        $pastToday = Carbon::now();
        $pastToday->addDay(-7);
        $pastToday->startOfWeek();
        $pastToday->hour(00);
        $pastToday->minute(00);
        $pastToday->second(00);

        $pastUpTodayToday = Carbon::now();
        $pastUpTodayToday->addDay(-7);
        $pastUpTodayToday->endOfWeek();
        $pastUpTodayToday->hour(23);
        $pastUpTodayToday->minute(59);
        $pastUpTodayToday->second(00);

        if (count($cityList) > 1) {
            $pastWeekSaleCUps = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('products.city_id', '=', 1)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '<>', '4')
                ->where('sales.payment_type', '!=', 'COUPON')
                ->where('sales.created_at', '>', $pastToday)
                ->where('sales.created_at', '<', $pastUpTodayToday)
                ->where('delivery_locations.city_id', '=', 3)
                ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;
        } else {
            $pastWeekSaleCUps = 0;
        }

        $pastWeekSaleC = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('products.city_id', '=', 1)
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastUpTodayToday)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(IF( products.product_type = 2 , replace(sum_total, ",", ".")*100/108 , replace(sum_total, ",", ".")*100/118  )) as totalTemp'))->get()[0]->totalTemp;

        $pastWeekSaleCExtra = DB::table('sales')->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where('sales.created_at', '>', $pastToday)
            ->where('sales.created_at', '<', $pastUpTodayToday)
            ->whereRaw($tempWhere)
            ->select(DB::raw(' SUM(replace(cross_sell.product_price, ",", "."))  as totalTemp'))->get()[0]->totalTemp;

        $groupByWeek = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where(DB::raw('year(`sales`.`created_at`)'), '2016')
            ->whereRaw($tempWhere)
            ->groupBy(DB::raw('WEEKOFYEAR(`sales`.`created_at`)'))
            ->select(DB::raw('WEEKOFYEAR(`sales`.`created_at`) as week ,count(*) as countNumber , sum(replace(`sales`.`sum_total` , "," , "."))/118*100 as sumTotal , avg(replace(`sales`.`sum_total` , "," , "."))/118*100 as avgSum '))
            ->get();

        $groupByWeekExtra = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('cross_sell', 'sales.id', '=', 'cross_sell.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->where('sales.payment_type', '!=', 'COUPON')
            ->where(DB::raw('year(`sales`.`created_at`)'), '2016')
            ->whereRaw($tempWhere)
            ->groupBy(DB::raw('WEEKOFYEAR(`sales`.`created_at`)'))
            ->select(DB::raw('WEEKOFYEAR(`sales`.`created_at`) as week ,count(*) as countNumber , sum(replace(`cross_sell`.`total_price` , "," , "."))/118*100 as sumTotal , avg(replace(`cross_sell`.`total_price` , "," , "."))/118*100 as avgSum '))
            ->get();

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($groupByWeek as $week) {
            $tempDate = Carbon::now();
            $tempDate->subWeeks($tempDate->weekOfYear);
            $tempDate->addWeek($week->week);
            $tempDate->subDay($tempDate->dayOfWeek - 1);
            $week->weekString = $tempDate->formatLocalized('%d %B');
        }

        $customerByWeek = DB::table('customers')
            ->where(DB::raw('year(`customers`.`created_at`)'), '2016')
            ->groupBy(DB::raw('WEEKOFYEAR(`customers`.`created_at`)'))
            ->select(DB::raw('WEEKOFYEAR(`customers`.`created_at`) as week ,count(*) as countNumber '))
            ->get();

        //$cityList = [];

        //$todaySaleUps = 1;
        //$thisWeekSaleUps = 1;
        //$thisMonthSaleUps = 1;
        //$todayPastSaleUps = 1;
        //$pastWeekSaleUps = 1;
        //$pastMonthSaleUps = 1;

        //$todaySaleCUps = 0;
        //$thisWeekSaleCUps = 0;
        //$thisMonthSaleCUps = 0;
        //$todayPastSaleCUps = 0;
        //$pastWeekSaleCUps = 0;
        //$pastMonthSaleCUps = 0;

        return view('admin.statistics', compact('groupByWeek', 'todaySale', 'thisWeekSale', 'thisMonthSale', 'todayPastSale', 'pastMonthSale', 'pastWeekSale',
            'todaySaleC', 'thisWeekSaleC', 'thisMonthSaleC', 'todayPastSaleC', 'pastMonthSaleC', 'pastWeekSaleC',
            'todayUserNumber', 'weekUserNumber', 'monthUserNumber', 'pastTodayUserNumber', 'pastWeekUserNumber', 'pastMonthUserNumber', 'customerByWeek'
            , 'todaySaleExtra', 'thisWeekSaleExtra', 'thisMonthSaleExtra', 'todayPastSaleExtra', 'pastMonthSaleExtra', 'pastWeekSaleExtra',
            'todaySaleCExtra', 'thisWeekSaleCExtra', 'thisMonthSaleCExtra', 'todayPastSaleCExtra', 'pastMonthSaleCExtra', 'pastWeekSaleCExtra',
            'todayUserNumber', 'weekUserNumber', 'monthUserNumber', 'pastTodayUserNumber', 'pastWeekUserNumber', 'pastMonthUserNumber', 'customerByWeek', 'cityList',
            'todaySaleUps', 'thisWeekSaleUps', 'thisMonthSaleUps', 'todayPastSaleUps', 'pastWeekSaleUps', 'pastMonthSaleUps',
            'todaySaleCUps', 'thisWeekSaleCUps', 'thisMonthSaleCUps', 'todayPastSaleCUps', 'pastWeekSaleCUps', 'pastMonthSaleCUps', 'cityList'
        ));
    }

    public function updateAllDeliveriesOneTime(\Illuminate\Http\Request $request)
    {
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
                    ->where('deliveries.id', $id->id)
                    ->select('sales_products.products_id', 'customers.user_id as user_id', 'sales.sender_email as email', 'sales.sender_name as FNAME', 'sales.sender_surname as LNAME', 'sales.sum_total as PRICE', 'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME'
                        , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD', 'deliveries.products as PRNAME', 'sales.lang_id', 'sales.id as sale_id')
                    ->get()[0];

                if (!$sales->email) {
                    $sales->email = User::where('id', $sales->user_id)->get()[0]->email;
                }

                $tempMailTemplateName = "siparis_yola_cikti_ekstre_urun";
                if ($sales->lang_id == 'en') {
                    $tempMailTemplateName = "siparis_yola_cikti_en";
                }

                $tempMailSubjectName = " Yola Çıkıyor!";
                if ($sales->lang_id == 'en') {
                    $tempMailSubjectName = " Has Just Left The Buillding";
                }

                $tempCikolat = AdminPanelController::getCikolatData($sales->sale_id);

                if ($tempCikolat) {
                    $tempCikolatDesc = "Yanında da " . $tempCikolat->name . " var.";
                } else {
                    $tempCikolatDesc = "";
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
                        ), array(
                            'name' => 'EKSTRA_URUN_NOTE',
                            'content' => $tempCikolatDesc
                        )
                    )
                ));
                Delivery::where('id', '=', $id->id)->update([
                    'status' => 2,
                    'operation_id' => $tempOperationInfo->id,
                    'operation_name' => $tempOperationInfo->name
                ]);
            }

        }

        return redirect('/admin/delivery-on-way');
    }

    public function updateAllDeliveriesOneTimeFromDelivery(\Illuminate\Http\Request $request)
    {
        $tempObject = $request->all();
        $tempIds = [];
        $tempOperationPerson = '';
        foreach ($tempObject as $key => $value) {
            if (explode('_', $key)[0] == 'selected') {
                array_push($tempIds, (object)['id' => explode('_', $key)[1], 'key' => explode('_', $value)]);
            } else if (explode('_', $key)[0] == 'status2') {
                $tempOperationPerson = explode('_', $key)[1];
            }
        }

        //dd($tempOperationPerson);

        $tempOperationInfo = DB::table('operation_person')->where('id', $tempOperationPerson)->get()[0];

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
                    ->select('deliveries.id as delivery_id', 'sales_products.products_id', 'customers.user_id as user_id', 'sales.sender_email as email', 'sales.sender_name as FNAME', 'sales.sender_surname as LNAME', 'sales.sum_total as PRICE', 'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME'
                        , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD', 'deliveries.products as PRNAME', 'sales.lang_id')
                    ->get()[0];

                if (!$sales->email) {
                    $sales->email = User::where('id', $sales->user_id)->get()[0]->email;
                }

                $tempMailTemplateName = "siparis_yola_cikti_ekstre_urun";
                if ($sales->lang_id == 'en') {
                    $tempMailTemplateName = "siparis_yola_cikti_en";
                }

                $tempMailSubjectName = " Yola Çıkıyor!";
                if ($sales->lang_id == 'en') {
                    $tempMailSubjectName = " Has Just Left The Buillding";
                }

                $tempCikolat = AdminPanelController::getCikolatData($id->id);

                if ($tempCikolat) {
                    $tempCikolatDesc = "Yanında da " . $tempCikolat->name . " var.";
                } else {
                    $tempCikolatDesc = "";
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
                        ), array(
                            'name' => 'EKSTRA_URUN_NOTE',
                            'content' => $tempCikolatDesc
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

        return redirect('/admin/deliveries/today');
    }

    public function updateAllCompanyDeliveriesOneTime(\Illuminate\Http\Request $request)
    {
        $tempObject = $request->all();
        $tempIds = [];
        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                array_push($tempIds, (object)['id' => explode('_', $key)[2], 'key' => explode('_', $value)]);
            }
        }

        foreach ($tempIds as $id) {

            DB::table('sales_company')->where('id', '=', $id->id)->update([
                'status' => 2
            ]);
        }

        return redirect('/admin/company-delivery-on-way');
    }

    public function getCompanyDeliveryOnWay()
    {
        AdminPanelController::checkAdmin();
        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);

        $todayLimit = Carbon::now();
        $todayLimit->hour(23);
        $todayLimit->minute(59);
        $deliveryList = DB::table('sales_company')
            ->where('wanted_delivery_date', '>', $today)
            ->where('wanted_delivery_date', '<', $todayLimit)
            ->where(function ($query) {
                $query->orwhere('status', '=', '1')
                    ->orwhere('status', '=', '6');
            })
            ->orderBy('wanted_delivery_date')
            ->orderBy('delivery_location')
            ->get();

        foreach ($deliveryList as $delivery) {
            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
        }

        return view('admin.companyDeliveryOnWay', compact('deliveryList'));
    }

    public function getStudioDeliveryOnWay()
    {
        AdminPanelController::checkAdmin();
        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);

        $todayLimit = Carbon::now();
        $todayLimit->hour(23);
        $todayLimit->minute(59);
        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayLimit)
            ->where(function ($query) {
                $query->orwhere('deliveries.status', '=', '1')
                    ->orwhere('deliveries.status', '=', '6');
            })
            ->select('deliveries.id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'sales.receiver_address', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit',
                DB::raw("'0' as studio"), 'deliveries.products', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->orderBy('deliveries.wanted_delivery_date')
            ->orderBy('delivery_locations.continent_id')
            ->get();

        $tempStudioBloom = DB::table('studioBloom')
            ->where('status', 'Ödeme Yapıldı')
            ->where('wanted_date', '>', $today)
            ->where('wanted_date', '<', $todayLimit)
            ->where(function ($query) {
                $query->orwhere('delivery_status', '=', '1')
                    ->orwhere('delivery_status', '=', '6');
            })
            ->select(
                'contact_name as name',
                'contact_surname as surname',
                'customer_name',
                'customer_surname',
                'district',
                'receiver_address',
                'id',
                'wanted_date as wanted_delivery_date',
                'flower_name as products',
                'wanted_delivery_limit',
                'delivery_status as status',
                'created_at',
                'customer_mobile',
                'note as delivery_not',
                'continent_id'
            )->get();
        foreach ($tempStudioBloom as $studio) {
            $studio->studio = 1;
            array_unshift($deliveryList, (object)$studio);
        }

        foreach ($deliveryList as $delivery) {
            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
        }

        $operationList = DB::table('operation_person')->get();

        return view('admin.deliveryOnWay', compact('deliveryList', 'operationList'));
    }

    public function getDeliveryOnWay()
    {
        AdminPanelController::checkAdmin();
        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $todayLimit = Carbon::now();
        $todayLimit->hour(23);
        $todayLimit->minute(59);
        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->whereRaw($tempWhere)
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_date', '<', $todayLimit)
            ->where(function ($query) {
                $query->orwhere('deliveries.status', '=', '1')
                    ->orwhere('deliveries.status', '=', '6');
            })
            ->select('deliveries.id', 'sales.id as sale_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'sales.receiver_address', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit',
                DB::raw("'0' as studio"), 'deliveries.products', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->orderBy('deliveries.wanted_delivery_date')
            ->orderBy('delivery_locations.continent_id')
            ->get();

        $tempStudioBloom = DB::table('studioBloom')
            ->where('status', 'Ödeme Yapıldı')
            ->where('wanted_date', '>', $today)
            ->where('wanted_date', '<', $todayLimit)
            ->where(function ($query) {
                $query->orwhere('delivery_status', '=', '1')
                    ->orwhere('delivery_status', '=', '6');
            })
            ->select(
                'contact_name as name',
                'contact_surname as surname',
                'customer_name',
                'customer_surname',
                'district',
                'receiver_address',
                'id',
                'id as sale_id',
                'wanted_date as wanted_delivery_date',
                'flower_name as products',
                'wanted_delivery_limit',
                'delivery_status as status',
                'created_at',
                'customer_mobile',
                'note as delivery_not',
                'continent_id'
            )->get();
        foreach ($tempStudioBloom as $studio) {
            $studio->studio = 1;
            array_unshift($deliveryList, (object)$studio);
        }

        foreach ($deliveryList as $delivery) {
            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;

            $tempCikolat = AdminPanelController::getCikolatData($delivery->sale_id);

            if ($tempCikolat) {
                $delivery->products = $delivery->products . ' - ' . $tempCikolat->name;
            }

        }

        $operationList = DB::table('operation_person')->get();

        return view('admin.deliveryOnWay', compact('deliveryList', 'operationList'));
    }

    public function getDeliveryAccessibility()
    {
        AdminPanelController::checkAdmin();
        $messages = DB::table('flowers_accessibility')->get();
        $messages2 = DB::table('delivery_hours_accessibility')->get();
        return view('admin.deliveryAccessibility', compact('messages', 'messages2'));
    }

    public function updateSelectedFailSale()
    {
        AdminPanelController::checkAdmin();
        Sale::where('id', Request::input('id'))->update([
            'sale_fail_visibility' => Request::input('value')
        ]);
        return response()->json(["status" => 1], 200);
    }

    public function sendReminderProduct(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $tempQueryList = [];
        $tempFlowerArray = [];
        foreach ($tempObject as $key => $value) {
            if ($key != '_token' && $key != 'flowers') {
                array_push($tempQueryList, (object)['attribute' => $value]);
            } else if ($key == 'flowers') {
                $tempValue = str_replace(" ", "", $value);
                $tempFlowerArray = explode('_', $tempValue);
            }
        }

        //dd($tempQueryList);

        foreach ($tempQueryList as $mail) {
            $tempMail = explode('ω', $mail->attribute)[0];
            //$tempName = explode( 'ü' , $mail->attribute)[1];
            $tempProductId = explode('ω', $mail->attribute)[2];
            DB::table('product_reminder')
                ->where('mail_send', false)->where('product_id', $tempProductId)->where('mail', $tempMail)->update([
                    'mail_send' => 1
                ]);
            $productName = DB::table('products')
                ->join('images', 'products.id', '=', 'images.products_id')
                ->where('images.type', 'main')
                ->where('products.id', $tempProductId)->select('name', 'image_url')->get()[0];;
            $tempMailTemplateName = "v2_BNF_Product_Reminder";
            //if($langId == 'en'){
            //    $tempMailTemplateName = "boarding_eng";
            //}

            $tempMailSubjectName = $productName->name . " siparişini şimdi verebilirsin.";
            //if($langId == 'en'){
            //    $tempMailSubjectName = ", Stylish Flowers Is Waiting For You";
            //}

            \MandrillMail::messages()->sendTemplate($tempMailTemplateName, null, array(
                'html' => '<p>Example HTML content</p>',
                'text' => 'Bloomandfresh dünyasına hoşgeldiniz',
                'subject' => ucwords(strtolower(Request::get('name'))) . $tempMailSubjectName,
                'from_email' => 'hello@bloomandfresh.com',
                'from_name' => 'Bloom And Fresh',
                'to' => array(
                    array(
                        'email' => $tempMail,
                        'type' => 'to'
                    )
                ),
                'merge' => true,
                'merge_language' => 'mailchimp',
                'global_merge_vars' => array(
                    array(
                        'name' => 'product_id',
                        'content' => $tempProductId,
                    ), array(
                        'name' => 'PRNAME',
                        'content' => $productName->name,
                    )
                , array(
                        'name' => 'IMAGE',
                        'content' => $productName->image_url,
                    )
                )
            ));
        }

        return redirect('/admin/product-reminder-default');
    }

    public function filterReminderProduct(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $tempQueryList = [];
        $tempStatus = '';
        $tempValue = '';
        $queryStringTemp = ' 1 = 1 ';
        //foreach ($tempObject as $key => $value) {
        //    if ($key != '_token') {
        //        if (explode( '_' , $key)[0] == 'status' && $value == "on") {
        //            if(explode( '_' , $key)[1] == '0' && $value == "on" ){
        //                $tempQueryList = [];
        //                array_push($tempQueryList, (object)['attribute' => 'product_reminder.product_id', 'state' => '!=', 'value' => '0']);
        //                break;
        //            }
        //            else
        //            array_push($tempQueryList, (object)['attribute' => 'product_reminder.product_id', 'state' => '=', 'value' => explode( '_' , $key)[1]]);
        //        }
        //    }
        //}
        if (count($tempObject) > 2)
            foreach ($tempObject['products'] as $key => $value) {
                array_push($tempQueryList, (object)['attribute' => 'product_reminder.product_id', 'state' => '=', 'value' => $value]);
            }
        //dd($tempQueryList);

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or product_reminder.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';


        if (Request::input('button_id') == 'waiting') {
            $queryStringTemp = $queryStringTemp . ' and mail_send = ' . '0';
        } else if (Request::input('button_id') == 'sent') {
            $queryStringTemp = $queryStringTemp . ' and mail_send = ' . '1';
        } else if (Request::input('button_id') == 'all') {
            //$queryStringTemp = $queryStringTemp . ' mail_send = ' . '1';
        }

        $queryString = "  ( 1 = 0   ";
        foreach ($tempQueryList as $query) {
            $queryString = $queryString . ' or product_reminder.product_id ' . $query->state . " '" . $query->value . "' ";
        }
        $queryString = $queryString . ' ) ';

        $myArray = DB::table('product_reminder')
            ->join('products', 'product_reminder.product_id', '=', 'products.id')
            ->whereRaw($tempWhere)
            ->groupBy('product_id')->select('product_id', 'name')->get();


        $mailList = DB::table('product_reminder')
            ->join('products', 'product_reminder.product_id', '=', 'products.id')
            ->whereRaw($queryString)
            ->whereRaw($tempWhere)
            ->whereRaw($queryStringTemp)
            ->select('product_reminder.id', 'products.id as product_id', 'products.name', 'mail', 'product_reminder.created_at', 'mail_send')->get();

        //array_push($myArray, (object)['name' => 'Hepsi', 'product_id' => '0' , 'checked' => '']);

        foreach ($myArray as $array) {
            $array->checked = '';
            foreach ($tempQueryList as $query) {
                if ($query->value == $array->product_id) {
                    $array->checked = 'checked';
                }
            }
        }

        $topWaitingFlowers = DB::table('product_reminder')
            ->join('products', 'product_reminder.product_id', '=', 'products.id')
            ->where('mail_send', 0)
            ->whereRaw($tempWhere)
            ->groupBy('product_id')
            ->orderBy(DB::raw(" count(*) "), 'DESC')
            ->select('products.name', DB::raw(" count(*) as totalWaiting"))
            ->take(20)
            ->get();

        $mailStatus = Request::input('button_id');

        return view('admin.productReminder', compact('myArray', 'mailList', 'mailStatus', 'topWaitingFlowers'));
    }

    public function showReminderProduct()
    {
        $myArray = DB::table('product_reminder')
            ->join('products', 'product_reminder.product_id', '=', 'products.id')
            ->groupBy('product_id')->select('product_id', 'name')->get();

        foreach ($myArray as $array) {
            if (DB::table('product_reminder')->where('mail_send', 0)->where('product_id', $array->product_id)->count() > 0) {
                $array->checked = '1';
            }
        }

        $mailList = DB::table('product_reminder')
            ->join('products', 'product_reminder.product_id', '=', 'products.id')
            ->where('mail_send', 0)->select('product_reminder.id', 'products.id as product_id', 'products.name', 'mail', 'product_reminder.created_at', 'mail_send')->get();

        //array_push($myArray, (object)['name' => 'Hepsi', 'product_id' => '0' , 'checked' => 'checked']);

        $mailStatus = 'waiting';

        return view('admin.productReminder', compact('myArray', 'mailList', 'mailStatus'));

    }

    public function deleteLogReceiver()
    {
        DB::table('log_receiver')->where('id', Request::input('id'))->delete();
        return redirect('/admin/get-fail-receiver');
    }

    public function LogsReceiver()
    {
        $customers = DB::table('log_receiver')
            ->join('customers', 'customers.id', '=', 'log_receiver.customer_id')
            ->select('log_receiver.*', 'customers.name as customer_name', 'customers.surname as customer_surname')
            ->orderBy('log_receiver.created_at', 'DESC')->get();
        return view('admin.failReceiverInfo', compact('customers'));
    }

    public function showProductCoupon($couponId)
    {
        AdminPanelController::checkAdmin();
        $coupons = DB::table('product_coupon')->orderBy('created_at', 'DESC')->get();
        $id = $couponId;

        $flowers = Product::where('activation_status_id', 1)->select('id', 'name')->get();

        $couponFlowers = DB::table('productList_coupon')
            ->join('products', 'productList_coupon.product_id', '=', 'products.id')
            ->select('products.id', 'products.name')
            ->where('coupon_id', $couponId)->get();

        foreach ($coupons as $coupon) {
            $coupon->flowers = DB::table('productList_coupon')
                ->join('products', 'productList_coupon.product_id', '=', 'products.id')
                ->select('products.id', 'products.name')
                ->where('coupon_id', $coupon->id)->get();
        }

        foreach ($flowers as $flower) {
            $flower->selected = false;
            foreach ($couponFlowers as $couponFlower) {
                if ($flower->id == $couponFlower->id) {
                    $flower->selected = true;
                    break;
                }
            }
        }
        return view('admin.productCouponList', compact('coupons', 'id', 'flowers'));
    }

    public function userRights()
    {
        AdminPanelController::checkAdmin();

        $userRights = DB::table('user_rights')->get();
        $userList = DB::table('users')->where('user_group_id', 1)->get();
        $userCity = DB::table('user_city')->join('city_list', 'user_city.city_id', '=', 'city_list.id')
            ->select('user_city.*', 'city_list.name')->get();

        return view('admin.userRight', compact('userList', 'userRights', 'userCity'));
    }

    public function updateProductCoupon(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $input = $request->all();

        if (isset($input['valid']))
            $input['valid'] = 1;
        else
            $input['valid'] = 0;

        DB::table('product_coupon')->where('id', $input['id'])->update([
            'name' => $input['name'],
            'type' => 2,
            'value' => $input['value'],
            'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $input['value'] . '_indirim.png',
            'expired_date' => $input['expiredDate'],
            'description' => $input['description']
        ]);

        DB::table('marketing_acts')->where('product_coupon', $input['id'])->update([
            'name' => $input['name'],
            'description' => $input['description'],
            'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $input['value'] . '_indirim.png',
            'type' => 2,
            'value' => $input['value'],
            'expiredDate' => $input['expiredDate']
        ]);

        DB::table('productList_coupon')->where('coupon_id', $input['id'])->delete();

        foreach ($input['allTags'] as $tag) {
            DB::table('productList_coupon')
                ->insert([
                    'coupon_id' => $input['id'],
                    'product_id' => $tag
                ]);
        }

        return redirect('/admin/productCoupons');
    }

    public function deleteProductCoupon()
    {
        AdminPanelController::checkAdmin();
        $id = Request::input('id');
        DB::table('product_coupon')->where('id', $id)->delete();
        DB::table('productList_coupon')->where('coupon_id', $id)->delete();

        DB::table('marketing_acts')->where('product_coupon', $id)->delete();

        return redirect('/admin/productCoupons');
    }

    public function createProductCoupon()
    {
        AdminPanelController::checkAdmin();

        $now = Carbon::now();
        $now = str_replace(' ', 'T', $now);

        $flowers = Product::where('activation_status_id', 1)->select('id', 'name')->get();

        return view('admin.productCoupon', compact('now', 'flowers'));
    }

    public function productCoupons()
    {
        AdminPanelController::checkAdmin();
        //$coupons = DB::table('company_coupon')->orderBy('count')->get();
        $id = 0;
        $coupons = DB::table('product_coupon')->orderBy('created_at', 'DESC')->get();

        foreach ($coupons as $coupon) {
            $coupon->flowers = DB::table('productList_coupon')
                ->join('products', 'productList_coupon.product_id', '=', 'products.id')
                ->select('products.id', 'products.name')
                ->where('coupon_id', $coupon->id)->get();
        }
        return view('admin.productCouponList', compact('coupons', 'id'));
    }

    public function storeProductCoupon(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $input = $request->all();

        if (isset($input['valid']))
            $input['valid'] = 1;
        else
            $input['valid'] = 0;

        $companyCouponId = DB::table('product_coupon')->insertGetId([
            'name' => $input['name'],
            'type' => 2,
            'value' => $input['value'],
            'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $input['value'] . '_indirim.png',
            'expired_date' => $input['expiredDate'],
            'description' => $input['description']
        ]);

        foreach ($input['allTags'] as $tag) {
            DB::table('productList_coupon')
                ->insert([
                    'coupon_id' => $companyCouponId,
                    'product_id' => $tag,
                    'limit_start' => $input['startDate'],
                    'limit_end' => $input['endDate']
                ]);
        }

        $customerList = Customer::whereNotNull('user_id')->get();

        foreach ($customerList as $customer) {
            $couponId = str_random(20);
            $tempCouponExist = DB::table('marketing_acts')->where('publish_id', $couponId)->get();
            while (count($tempCouponExist) > 0) {
                $couponId = str_random(20);
                $tempCouponExist = DB::table('marketing_acts')->where('publish_id', $couponId)->get();
            }

            $marketingActId = MarketingAct::create(
                [
                    'publish_id' => $couponId,
                    'name' => $input['name'],
                    'description' => $input['description'],
                    'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $input['value'] . '_indirim.png',
                    'type' => 2,
                    'value' => $input['value'],
                    'active' => true,
                    'valid' => 1,
                    'expiredDate' => $input['expiredDate'],
                    'used' => false,
                    'administrator_id' => 1,
                    'long_term' => 0,
                    'product_coupon' => $companyCouponId
                ]
            )->id;

            DB::table('customers_marketing_acts')->insert(
                array(
                    'marketing_acts_id' => $marketingActId,
                    'customers_id' => $customer->id,
                )
            );
        }
        return redirect('/admin/productCoupons');
    }

    public function completePage($saleId)
    {
        AdminPanelController::checkAdmin();
        $deliveryList = DB::table('sales')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '=', '2')
            ->where('sales.id', '=', $saleId)
            ->select('sales.id', 'deliveries.products', DB::raw("'0' as studio")
                , 'customer_contacts.name', 'customer_contacts.surname', 'delivery_locations.district')->get();


        if (count($deliveryList) == 0) {
            $deliveryList = DB::table('studioBloom')
                ->where('status', 'Ödeme Yapıldı')
                ->where('id', 'like', '%' . $saleId . '%')
                ->select(
                    'contact_name as name',
                    'contact_surname as surname',
                    'id',
                    'flower_name as products',
                    'district', DB::raw("'1' as studio")
                )->get();
        }

        if (count($deliveryList) == 0) {
            dd('Teslimat aşamasında olmayan çiçek! Önce teslimata çıkarmalısın -_-');
        }

        return view('admin.kurye', compact('deliveryList'));
    }

    public function getDeliveryLocations()
    {
        AdminPanelController::checkAdmin();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' 1 = 0 ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }

        $tempAreaList = DB::table('delivery_locations')->whereRaw($tempWhere)->groupBy('continent_id')->select('continent_id')->get();

        $tempLocations = DB::table('delivery_locations')->whereRaw($tempWhere)->get();
        return view('admin.deliveryLocations', compact('tempLocations', 'tempAreaList'));
    }

    public function updateDeliveryLocation()
    {
        $tempActive = 0;
        if (Request::input('active')) {
            $tempActive = 1;
        }
        DB::table('delivery_locations')->where('id', Request::input('location_id'))->update(
            [
                'small_city' => Request::input('small_city'),
                'district' => Request::input('small_city') . ' - ' . Request::input('district'),
                'continent_id' => Request::input('continent_id'),
                'active' => $tempActive
            ]
        );
        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' 1 = 0 ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }

        $tempAreaList = DB::table('delivery_locations')->whereRaw($tempWhere)->groupBy('continent_id')->select('continent_id')->get();

        $tempLocations = DB::table('delivery_locations')->whereRaw($tempWhere)->get();
        return view('admin.deliveryLocations', compact('tempLocations', 'tempAreaList'));
    }

    public function aboutUsDetail($id)
    {
        $aboutUsPeople = DB::table('about_us_people')->where('id', $id)->get()[0];
        return view('admin.about_usDetail', compact('aboutUsPeople'));
    }

    public function insertAboutUsPeople()
    {
        return view('admin.addAboutUs');
    }

    public function insertNewAboutUs()
    {
        DB::table('about_us_people')->insert(
            [
                'name' => Request::input('name'),
                'tittre' => Request::input('tittre'),
                'linked_url' => Request::input('linked_url'),
                'order' => Request::input('order'),
                'image_url' => Request::input('image_url')
            ]
        );
        $aboutUsPeople = DB::table('about_us_people')->get();
        return view('admin.about_us', compact('aboutUsPeople'));
    }

    public function updateAboutUs()
    {
        DB::table('about_us_people')->where('id', Request::input('id'))->update(
            [
                'name' => Request::input('name'),
                'tittre' => Request::input('tittre'),
                'linked_url' => Request::input('linked_url'),
                'order' => Request::input('order'),
                'image_url' => Request::input('image_url')
            ]
        );
        $aboutUsPeople = DB::table('about_us_people')->get();
        return view('admin.about_us', compact('aboutUsPeople'));
    }

    public function insertDeliveryLocation()
    {
        $tempActive = 0;
        if (Request::input('active')) {
            $tempActive = 1;
        }

        $cityList = DB::table('user_city')->join('city_list', 'user_city.city_id', '=', 'city_list.id')
            ->where('user_id', \Auth::user()->id)->where('user_city.active', 1)->where('valid', 1)->select('city_list.id', 'city_list.name')->get();

        foreach ($cityList as $city) {
            DB::table('delivery_locations')->insert(
                [
                    'city' => $city->name,
                    'city_id' => $city->id,
                    'shop_id' => 1,
                    'small_city' => Request::input('small_city'),
                    'district' => Request::input('small_city') . ' - ' . Request::input('district'),
                    'continent_id' => Request::input('continent_id'),
                    'active' => $tempActive
                ]
            );
        }

        return redirect('/admin/delivery-locations');
    }

    public function getAboutUsPeople()
    {
        $aboutUsPeople = DB::table('about_us_people')->get();
        return view('admin.about_us', compact('aboutUsPeople'));
    }

    public function deleteAboutUs()
    {
        DB::table('about_us_people')->where('id', Request::input('id'))->delete();
        $aboutUsPeople = DB::table('about_us_people')->get();
        return view('admin.about_us', compact('aboutUsPeople'));
    }

    public function addDeliveryLocation()
    {
        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' 1 = 0 ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempAreaList = DB::table('delivery_locations')->whereRaw($tempWhere)->groupBy('continent_id')->select('continent_id')->get();

        return view('admin.addDeliveryLocation', compact('tempAreaList'));
    }

    public function failSaleChecked()
    {
        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);
        $today->subDay(7);

        $page = 1;

        if (Request::input('page')) {
            $page = Request::input('page');
        }

        $tempSkip = ($page - 1) * 20;

        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            //->where('sales.payment_methods', '!=', 'OK')
            ->whereRaw(' ( 1 = 0 or sales.payment_methods != "OK" or sales.payment_methods is null )  ')
            ->where('sales.sale_fail_visibility', '=', '0')
            ->where('sales.created_at', '>', $today)
            //->orWhere(function ($query) {
            //    $today = Carbon::now();
            //    $today->hour(00);
            //    $today->minute(00);
            //    $today->subDay(5);
            //    $query->where('sales.payment_methods', '=', null)
            //        ->where('sales.sale_fail_visibility', '=', '0')
            //        ->where('sales.created_at','>' ,$today);
            //})
            ->select('sales.id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'delivery_locations.district', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit',
                'sales.created_at as date', 'customers.user_id', 'sales.sender_mobile', 'sales.sender_email', 'sales.payment_methods', 'deliveries.products', 'sales.sales_ip', 'sales.card_message'
                , 'sales.admin_not', 'sales.sale_fail_visibility', 'sales.sum_total',
                DB::raw('(select count(*) from sales sl2 where sl2.sender_name = sales.sender_name and sl2.sender_surname = sales.sender_surname and sl2.payment_methods = "OK" and sl2.created_at >  sales.created_at and DAYOFMONTH( sl2.created_at) = DAYOFMONTH( sales.created_at)  ) as complete')
            )
            ->orderBy('sales.created_at', 'DESC')
            ->take(20)
            ->skip($tempSkip)
            ->get();

        //dd($deliveryList);

        foreach ($deliveryList as $delivery) {
            $delivery->cikilot = AdminPanelController::getCikolatImage($delivery->id);

            $delivery->isBank = DB::table('is_bank_log')->where('sale_id', $delivery->id)->where('code', '!=', '0000')->get();
            $delivery->garantiLog = [];


        }

        $id = 0;

        $pageCount = 7;
        $pageNumber = $page;

        return view('admin.failsales', compact('deliveryList', 'id', 'pageCount', 'pageNumber'));
    }

    public function failSaleUnchecked()
    {
        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '!=', 'OK')
            ->where('sales.sale_fail_visibility', '=', '1')
            ->orWhere(function ($query) {
                $query->where('sales.payment_methods', '=', null)
                    ->where('sales.sale_fail_visibility', '=', '1');
            })
            ->select('sales.id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.created_at as date', 'customers.user_id', 'sales.sender_mobile', 'sales.sender_email', 'sales.payment_methods', 'deliveries.products', 'sales.sales_ip'
                , 'sales.admin_not', 'sales.sale_fail_visibility', 'sales.sum_total',
                DB::raw('(select sales.id from sales sl2 where sl2.sender_name = sales.sender_name and sl2.sender_surname = sales.sender_surname and sl2.payment_methods = "OK" and sl2.created_at >  sales.created_at and DAYOFMONTH( sl2.created_at) = DAYOFMONTH( sales.created_at)  LIMIT 1 ) as complete')

            )
            ->orderBy('sales.created_at', 'DESC')
            ->get();

        $id = 0;

        return view('admin.failsales', compact('deliveryList', 'id'));
    }

    public function failSale()
    {
        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '!=', 'OK')
            ->orWhere('sales.payment_methods', '=', null)
            ->select('sales.id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.created_at as date', 'customers.user_id', 'sales.sender_mobile', 'sales.sender_email', 'sales.payment_methods', 'deliveries.products', 'sales.sales_ip'
                , 'sales.admin_not', 'sales.sale_fail_visibility', 'sales.sum_total',
                DB::raw('(select sales.id from sales sl2 where sl2.sender_name = sales.sender_name and sl2.sender_surname = sales.sender_surname and sl2.payment_methods = "OK" and sl2.created_at >  sales.created_at and DAYOFMONTH( sl2.created_at) = DAYOFMONTH( sales.created_at)  LIMIT 1 ) as complete')
            )
            ->orderBy('sales.created_at', 'DESC')
            ->get();

        $id = 0;

        return view('admin.failsales', compact('deliveryList', 'id'));
    }

    public function failSaleUpdate($id)
    {
        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '!=', 'OK')
            ->where('sales.sale_fail_visibility', '=', '0')
            ->orWhere(function ($query) {
                $query->where('sales.payment_methods', '=', null)
                    ->where('sales.sale_fail_visibility', '=', '0');
            })
            ->select('sales.id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.created_at as date', 'customers.user_id', 'sales.sender_mobile', 'sales.sender_email', 'sales.payment_methods', 'deliveries.products', 'sales.sales_ip'
                , 'sales.admin_not', 'sales.sale_fail_visibility', 'sales.sum_total',
                DB::raw('(select sales.id from sales sl2 where sl2.sender_name = sales.sender_name and sl2.sender_surname = sales.sender_surname and sl2.payment_methods = "OK" and sl2.created_at >  sales.created_at and DAYOFMONTH( sl2.created_at) = DAYOFMONTH( sales.created_at)  LIMIT 1 ) as complete')
            )
            ->orderBy('sales.created_at', 'DESC')
            ->get();

        return view('admin.failsales', compact('deliveryList', 'id'));
    }

    public function updateSales()
    {

        $tempVar = Request::input('sale_fail_visibility');

        if (isset($tempVar))
            $tempVar = 1;
        else
            $tempVar = 0;

        Sale::where('id', Request::input('id'))->update([
            'admin_not' => Request::input('admin_not'),
            'sale_fail_visibility' => $tempVar
        ]);

        return redirect('/admin/get-fail-sale-unchecked');

        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '!=', 'OK')
            ->where('sales.sale_fail_visibility', '=', '0')
            ->orWhere(function ($query) {
                $query->where('sales.payment_methods', '=', null)
                    ->where('sales.sale_fail_visibility', '=', '0');
            })
            ->select('sales.id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.created_at as date', 'customers.user_id', 'sales.sender_mobile', 'sales.sender_email', 'sales.payment_methods', 'deliveries.products', 'sales.sales_ip'
                , 'sales.admin_not', 'sales.sale_fail_visibility', 'sales.sum_total',
                DB::raw('(select sales.id from sales sl2 where sl2.sender_name = sales.sender_name and sl2.sender_surname = sales.sender_surname and sl2.payment_methods = "OK" and sl2.created_at >  sales.created_at and DAYOFMONTH( sl2.created_at) = DAYOFMONTH( sales.created_at)  LIMIT 1 ) as complete')
            )
            ->orderBy('sales.created_at', 'DESC')
            ->get();

        $id = 0;

        return view('admin.failsales', compact('deliveryList', 'id'));
    }

    public function orderWithDescCompany($attribute)
    {
        AdminPanelController::checkAdmin();
        $coupons = DB::table('company_coupon')->orderBy($attribute, 'DESC')->get();
        $id = 0;
        return view('admin.companyCouponList', compact('coupons', 'id'));
    }

    public function orderWithCompany($attribute)
    {
        AdminPanelController::checkAdmin();
        $coupons = DB::table('company_coupon')->orderBy($attribute)->get();
        $id = 0;
        return view('admin.companyCouponList', compact('coupons', 'id'));
    }

    public function completeStudioDeliveryByCourier()
    {
        $flag = false;
        try {

            $deliveryDate = Carbon::now();

            DB::table('studioBloom')->where('id', '=', Request::input('tempId'))->update([
                'delivery_date' => $deliveryDate,
                'delivery_status' => 3,
                'picker' => Request::input('picker')
            ]);
            $flag = true;
            DB::table('error_logs')->insert([
                'method_name' => 'BillingOperation_Operasyon',
                'error_code' => 'log',
                'error_message' => Request::input('tempId'),
                'type' => 'WS',
                'related_variable' => 'billing'
            ]);
            //BillingOperation::studioBillingSend(Request::input('tempId'));
            return view('admin.completePage');
        } catch (\Exception $e) {
            if ($flag) {
                return view('admin.completePage');
            } else
                return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
    }

    public function completeDeliveryByCourier()
    {
        $flag = false;
        if (DB::table('deliveries')
                ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->where('sales.id', Request::input('tempId'))->where('deliveries.status', 2)->count() == 0) {
            return view('admin.completePage');
        }
        try {
            if (DB::table('deliveries')
                    ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->where('sales.id', Request::input('tempId'))
                    ->select('deliveries.id as deliveryId', 'sales_products.products_id', 'deliveries.wanted_delivery_date', 'deliveries.created_at as orderDate', 'sales.id as id', 'customers.user_id as user_id', 'sales.sender_email as email', 'sales.sender_name as FNAME', 'sales.sender_surname as LNAME', 'sales.sum_total as PRICE', 'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME'
                        , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD', 'deliveries.products as PRNAME', 'deliveries.wanted_delivery_limit')
                    ->count() == 0) {
                dd('Teslimat aşamasında olmayan çiçek! Önce teslimata çıkarmalısın -_-');
            }
            $sales = DB::table('deliveries')
                ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->where('sales.id', Request::input('tempId'))
                ->select('deliveries.id as deliveryId', 'sales_products.products_id', 'deliveries.wanted_delivery_date', 'deliveries.created_at as orderDate', 'sales.id as id', 'customers.user_id as user_id', 'sales.sender_email as email', 'sales.sender_name as FNAME', 'sales.sender_surname as LNAME', 'sales.sum_total as PRICE', 'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME'
                    , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD', 'deliveries.products as PRNAME', 'deliveries.wanted_delivery_limit')
                ->get()[0];

            if (!$sales->email) {
                $sales->email = User::where('id', $sales->user_id)->get()[0]->email;
            }

            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');

            $created = new Carbon($sales->wanted_delivery_limit);

            $requestDeliveryDate = new Carbon($sales->orderDate);
            $requestDateInfo = $requestDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($requestDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDeliveryDate->minute, 2, '0', STR_PAD_LEFT);

            $deliveryDate = Carbon::now();

            $dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);

            $wantedDeliveryDate = new Carbon($sales->wanted_delivery_date);
            $wantedDeliveryDateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . '00' . ' - ' . str_pad($created->hour, 2, '0', STR_PAD_LEFT) . ':' . '00';

            $tempCikolat = AdminPanelController::getCikolatData($sales->id);

            if ($tempCikolat) {
                $tempCikolatDesc = "ve " . $tempCikolat->name;
                $tempCikolatName = "Ekstra: " . $tempCikolat->name . "<br>";
                $sales->PRICE = floatval(str_replace(',', '.', $sales->PRICE)) + floatval(str_replace(',', '.', $tempCikolat->total_price));
                $sales->PRICE = str_replace('.', ',', $sales->PRICE);
            } else {
                $tempCikolatDesc = "";
                $tempCikolatName = "";
            }

            \MandrillMail::messages()->sendTemplate('siparis_teslim_alindi_ekstre_urun', null, array(
                'html' => '<p>Example HTML content</p>',
                'text' => 'Siparişiniz başarıyla teslim edilmistir',
                'subject' => ucwords(strtolower($sales->FNAME)) . ', Bloom And Fresh - ' . $sales->PRNAME . ' Teslim Edildi!',
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
                        'name' => 'TAKETIME',
                        'content' => $dateInfo,
                    ), array(
                        'name' => 'PRICE',
                        'content' => $sales->PRICE,
                    ), array(
                        'name' => 'PRNAME',
                        'content' => $sales->PRNAME
                    ), array(
                        'name' => 'SALEID',
                        'content' => $sales->id
                    ), array(
                        'name' => 'ORDERDATE',
                        'content' => $requestDateInfo
                    ), array(
                        'name' => 'WANTEDDATE',
                        'content' => $wantedDeliveryDateInfo
                    ), array(
                        'name' => 'PIMAGE',
                        'content' => DB::table('images')->where('type', 'main')->where('products_id', $sales->products_id)->get()[0]->image_url
                    ), array(
                        'name' => 'PICKER',
                        'content' => ucwords(strtolower(Request::input('picker')))
                    ), array(
                        'name' => 'EKSTRA_URUN_NOTE',
                        'content' => $tempCikolatDesc
                    ), array(
                        'name' => 'EKSTRA_URUN_NAME',
                        'content' => $tempCikolatName
                    )
                )
            ));
            Delivery::where('id', '=', $sales->deliveryId)->update([
                'delivery_date' => $deliveryDate,
                'status' => 3,
                'picker' => Request::input('picker')
            ]);
            $flag = true;
            DB::table('error_logs')->insert([
                'method_name' => 'BillingOperation_Operasyon',
                'error_code' => 'log',
                'error_message' => $sales->id,
                'type' => 'WS',
                'related_variable' => 'billing'
            ]);
            BillingOperation::soapTest($sales->id);
            return view('admin.completePage');
        } catch (\Exception $e) {
            if ($flag) {
                return view('admin.completePage');
            } else
                return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
    }

    public function showKurye()
    {
        $deliveryList = DB::table('sales')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '=', '2')
            ->select('sales.id', 'deliveries.products'
                , 'customer_contacts.name', 'customer_contacts.surname', 'delivery_locations.district')->get();

        return view('admin.kurye', compact('deliveryList'));
    }

    public function showBillings()
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
            $billing->orderTime = $now;
            $billing->time = $now->formatLocalized('%d %B');
        }
        $queryParams = [];
        return view('admin.testReminderList', compact('reminderList', 'queryParams'));
    }

    public function showNewsletters()
    {
        $reminderList = DB::table('newsletters')
            ->select(DB::raw('(select users.id from users where newsletters.email = users.email ) as userOrNot'), 'created_at', 'email')
            ->orderBy('created_at', 'DESC')->get();
        $queryParams = [];
        $total = count($reminderList);
        $user = DB::table('newsletters')->join('users', 'newsletters.email', '=', 'users.email')->count();
        $notUser = $total - $user;
        return view('admin.newsLetter', compact('reminderList', 'queryParams', 'total', 'user', 'notUser'));
    }

    public function orderNewsletters(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        if ($tempObject['upOrDown'] == 'up') {

            $reminderList = DB::table('newsletters')
                ->select(DB::raw('(select users.id from users where newsletters.email = users.email ) as userOrNot'), 'created_at', 'email')
                ->orderBy($tempObject['orderParameter'])->get();

        } else {

            $reminderList = DB::table('newsletters')
                ->select(DB::raw('(select users.id from users where newsletters.email = users.email ) as userOrNot'), 'created_at', 'email')
                ->orderBy($tempObject['orderParameter'], 'DESC')->get();
        }

        $queryParams = [];
        $total = count($reminderList);
        $user = DB::table('newsletters')->join('users', 'newsletters.email', '=', 'users.email')->count();
        $notUser = $total - $user;
        return view('admin.newsLetter', compact('reminderList', 'queryParams', 'total', 'user', 'notUser'));
    }

    public function sortReminders(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        if ($tempObject['upOrDown'] == 'up') {

            if ($tempObject['orderParameter'] == 'reminder_date') {
                $reminderList = DB::table('reminders')
                    ->join('customers', 'reminders.customers_id', '=', 'customers.id')
                    ->select('reminders.created_at', 'reminders.name as receiver_name', 'reminders.description', 'reminders.reminder_day', 'reminders.reminder_month', 'customers.name', 'customers.surname')
                    ->orderBy('reminder_month')
                    ->orderBy('reminder_day')
                    ->get();
            } else {
                $reminderList = DB::table('reminders')
                    ->join('customers', 'reminders.customers_id', '=', 'customers.id')
                    ->select('reminders.created_at', 'reminders.name as receiver_name', 'reminders.description', 'reminders.reminder_day', 'reminders.reminder_month', 'customers.name', 'customers.surname')
                    ->orderBy($tempObject['orderParameter'])
                    ->get();
            }
        } else {
            if ($tempObject['orderParameter'] == 'reminder_date') {
                $reminderList = DB::table('reminders')
                    ->join('customers', 'reminders.customers_id', '=', 'customers.id')
                    ->select('reminders.created_at', 'reminders.name as receiver_name', 'reminders.description', 'reminders.reminder_day', 'reminders.reminder_month', 'customers.name', 'customers.surname')
                    ->orderBy('reminder_month', 'DESC')
                    ->orderBy('reminder_day', 'DESC')
                    ->get();
            } else {
                $reminderList = DB::table('reminders')
                    ->join('customers', 'reminders.customers_id', '=', 'customers.id')
                    ->select('reminders.created_at', 'reminders.name as receiver_name', 'reminders.description', 'reminders.reminder_day', 'reminders.reminder_month', 'customers.name', 'customers.surname')
                    ->orderBy($tempObject['orderParameter'], 'DESC')
                    ->get();
            }

        }
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($reminderList as $billing) {
            $now = Carbon::now();
            $now->month($billing->reminder_month);
            $now->day($billing->reminder_day);
            $billing->time = $now->formatLocalized('%d %B');
        }

        $queryParams = [];

        return view('admin.reminderList', compact('reminderList', 'queryParams'));
    }

    public function deleteCustomer()
    {
        //$user_id = Request::input('user_id');
        $id = Request::input('id');

        $contactList = CustomerContact::where('customer_id', $id)->get();

        DB::table('customers_marketing_acts')->where('customers_id', $id);
        CustomerBilling::where('customers_id', $id)->delete();

        foreach ($contactList as $contact) {
            $saleList = Sale::where('customer_contact_id', $contact->id)->get();
            foreach ($saleList as $sale) {
                DB::table('marketing_acts_sales')->where('sales_id', $sale->id)->delete();
                Billing::where('sales_id', $sale->id)->delete();
                Delivery::where('sales_id', $sale->id)->delete();
                Sale::where('id', $sale->id)->delete();
            }
            CustomerContact::where('id', $contact->id)->delete();
        }

        $userInfo = User::where('id', Customer::where('id', Request::input('id'))->get()[0]->user_id)->get();

        if (count($userInfo) > 0) {

            DB::table('passwords_email')->where('users_id', $userInfo[0]->id)->delete();
            Newsletter::where('email', User::where('id', $userInfo[0]->id)->get()[0]->email)->delete();
            Reminder::where('customers_id', $id)->delete();
            Customer::where('id', $id)->delete();
            User::where('id', $userInfo[0]->id)->delete();
        } else {
            Customer::where('id', $id)->delete();
        }
        return response()->json(["status" => 1, "data" => $id], 200);
        //return redirect('/admin/customers');
    }

    public function filterBillingExcel(\Illuminate\Http\Request $request)
    {
        $companyList = [];
        array_push($companyList, (object)['information' => 'Mobilike', 'status' => 'mobilike']);
        array_push($companyList, (object)['information' => 'Itelligence', 'status' => 'itelligence']);
        array_push($companyList, (object)['information' => 'tr.pwc.com', 'status' => 'tr.pwc.com']);
        array_push($companyList, (object)['information' => 'enkaokullari.k12.tr', 'status' => 'enkaokullari.k12.tr']);
        array_push($companyList, (object)['information' => 'seranit.com.tr', 'status' => 'seranit.com.tr']);
        array_push($companyList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        //dd($queryParams->created_at);
        if ($queryParams->created_at_end) {
            $tempDate = logEventController::modifyEndDate($queryParams->created_at_end);
        }
        $queryString = " 1 = 1  ";
        if ($queryParams->created_at) {
            $queryString = $queryString . ' and sales.created_at > ' . " '" . $queryParams->created_at . "' ";
        }
        if ($queryParams->created_at_end) {
            $queryString = $queryString . ' and sales.created_at < ' . " '" . $tempDate . "' ";
        }
        //dd($queryString);
        if (Request::input('billing_active') == "on") {
            //dd(Request::input('billing_active'));
            $queryString = $queryString . ' and billings.userBilling != 0';
        } else
            $queryParams->billing_active = '';

        if (Request::input('payment_type') == "on") {
            //dd(Request::input('billing_active'));
            $queryString = $queryString . ' and sales.payment_type = "Kurumsal" ';
            if (Request::input('CompanyId')) {
                //dd(Request::input('billing_active'));
                if (Request::input('CompanyId') == "Hepsi") {
                    $queryParams->status = 'Hepsi';
                } else {
                    $queryString = $queryString . ' and sales.sender_email like "%' . Request::input('CompanyId') . '%" ';
                    $queryParams->status = Request::input('CompanyId');
                }
            } else
                $queryParams->status = 'Hepsi';
        } else {
            $queryParams->payment_type = '';
            $queryParams->status = 'Hepsi';
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0   ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }

        if (count($cityList) > 1) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = 3 ';
        }

        $tempWhere = $tempWhere . ' ) ';

        if ($queryParams->created_at_end || $queryParams->created_at || Request::input('billing_active') == "on" || Request::input('payment_type') == "on") {
            $list = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->whereRaw($queryString)
                ->whereRaw($tempWhere)
                ->where('sales.payment_methods', 'OK')
                ->whereIn('products.product_type_sub', $queryParams->sub_category)
                ->whereIn('products.product_type', $queryParams->category)
                //->where('sales.payment_type' , '!=', 'FİYONGO')
                ->where('deliveries.status', '<>', '4')
                ->orderBy('sales.created_at', 'DESC')
                ->select('customers.user_id', 'sales.sender_email', 'sales.taxType' , 'sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling',
                    'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
                    'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'delivery_locations.city as real_city', 'sales.sender_mobile', 'products.name as products', 'sales.sender_name', 'sales.sender_surname', 'products.product_type_sub',
                    'sales.product_price as price', 'products.id', 'products.product_type', 'delivery_locations.city_id', 'sales.IsTroyCard', 'sales.paymentAmount')
                ->get();
        } else {
            $list = DB::table('sales2')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.payment_methods', 'OK')
                //->where('sales.payment_type' , '!=', 'FİYONGO')
                ->where('deliveries.status', '<>', '4')
                ->orderBy('sales.created_at', 'DESC')
                ->select('customers.user_id', 'sales.sender_email', 'sales.payment_type', 'sales.taxType', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.city', 'delivery_locations.city as real_city', 'billings.small_city', 'billings.tc',
                    'billings.userBilling', 'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'delivery_locations.city_id',
                    'billings.billing_send', 'billings.billing_surname', 'billings.billing_name', 'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'products.name as products', 'products.product_type_sub',
                    'sales.sender_name', 'sales.sender_surname', 'sales.product_price as price', 'sales.sender_mobile', 'products.id', 'products.product_type', 'sales.IsTroyCard', 'sales.paymentAmount')
                ->get();
        }

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

            $flowerTax = 108;
            $chocolate = 101;
            $giftBox = 118;

            if( $row->taxType == 1 ){
                $flowerTax = 118;
                $chocolate = 108;
                $giftBox = 118;
            }

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
                    $row->discountValue = floatval(floatval($priceWithDiscount) * ( $chocolate - 100 ) / 100);
                }
                else if ($row->product_type == 3) {
                    $row->discountValue = floatval(floatval($priceWithDiscount) * ( $giftBox - 100 ) / 100);
                }
                else {
                    $row->discountValue = floatval(floatval($priceWithDiscount) * ( $flowerTax - 100 ) / 100);
                }

                $totalKDV = $totalKDV + $row->discountValue;

                $row->discountValue = number_format($row->discountValue, 2);
                parse_str($row->discountValue);
                $row->discountValue = str_replace('.', ',', $row->discountValue);

                if ($row->product_type == 2) {
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * $chocolate / 100);
                }
                else if ($row->product_type == 3) {
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * $giftBox / 100);
                }
                else {
                    $priceWithDiscount = floatval(floatval($priceWithDiscount) * $flowerTax / 100);
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
                        $row->discountValue = floatval(floatval($priceWithDiscount) * ( $chocolate - 100 ) / 100);
                        $priceWithDiscount = floatval(floatval($priceWithDiscount) * $chocolate / 100) - floatval($discount[0]->value);
                    }
                    else if ($row->product_type == 3) {
                        $row->discountValue = floatval(floatval($priceWithDiscount) * ( $giftBox - 100 ) / 100);
                        $priceWithDiscount = floatval(floatval($priceWithDiscount) * $giftBox / 100) - floatval($discount[0]->value);
                    }
                    else {
                        $row->discountValue = floatval(floatval($priceWithDiscount) * ( $flowerTax - 100 ) / 100);
                        $priceWithDiscount = floatval(floatval($priceWithDiscount) * $flowerTax / 100) - floatval($discount[0]->value);
                    }

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
                        $row->discountValue = floatval(floatval($tempPriceWithDiscount) * ( $chocolate - 100 ) / 100);
                    }
                    else if ($row->product_type == 3) {
                        $row->discountValue = floatval(floatval($tempPriceWithDiscount) * ( $giftBox - 100 ) / 100);
                    }
                    else {
                        $row->discountValue = floatval(floatval($tempPriceWithDiscount) * ( $flowerTax - 100 ) / 100);
                    }
                }

                $totalKDV = $totalKDV + $row->discountValue;
                $row->discountValue = number_format($row->discountValue, 2);
                parse_str($row->discountValue);
                $row->discountValue = str_replace('.', ',', $row->discountValue);

                if ($discount[0]->type == 2) {
                    if ($row->product_type == 2) {
                        $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * $chocolate / 100);
                    }
                    else if ($row->product_type == 3) {
                        $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * $giftBox / 100);
                    }
                    else {
                        $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * $flowerTax / 100);
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

            if ($row->IsTroyCard == 1) {

                $cikilotGeneral = $cikilotGeneral - 30;

                $row->cikilotTotalGeneral = number_format(floatval(str_replace(',', '.', $row->cikilotTotalGeneral)) - 30, 2);
                $row->cikilotTotalGeneral = str_replace('.', ',', $row->cikilotTotalGeneral);


            }

            //$total = $total + $row->sumTotal;
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

        $cikilotBigGeneral = number_format(floatval($cikilotGeneral), 2) + $total;
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

    public function showBillingExcel()
    {
        $companyList = [];
        array_push($companyList, (object)['information' => 'Mobilike', 'status' => 'mobilike']);
        array_push($companyList, (object)['information' => 'Itelligence', 'status' => 'itelligence']);
        array_push($companyList, (object)['information' => 'tr.pwc.com', 'status' => 'tr.pwc.com']);
        array_push($companyList, (object)['information' => 'enkaokullari.k12.tr', 'status' => 'enkaokullari.k12.tr']);
        array_push($companyList, (object)['information' => 'seranit.com.tr', 'status' => 'seranit.com.tr']);
        array_push($companyList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        $list = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('billings', 'sales.id', '=', 'billings.sales_id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('sales.payment_methods', 'OK')
            ->where('deliveries.status', '<>', '4')
            ->where('sales.payment_type', '!=', 'FİYONGO')
            ->orderBy('sales.created_at', 'DESC')
            ->select('customers.user_id', 'sales.payment_type', 'sales.sender_email', 'sales.device', 'billings.billing_surname', 'billings.billing_name', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.userBilling',
                'billings.small_city', 'billings.city', 'billings.tc', 'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no',
                'billings.billing_send', 'billings.billing_name', 'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'products.name as products', 'sales.sender_name',
                'sales.sender_surname', 'sales.product_price as price', 'sales.sender_mobile', 'products.id', 'products.product_type')
            ->get();

        $count = 0;
        $firstPrice = 0;
        $totalDiscount = 0;
        $totalPartial = 0;
        $totalKDV = 0;
        $total = 0;

        foreach ($list as $row) {
            $count++;
            $tempVal = str_replace(',', '.', $row->price);
            $firstPrice = $firstPrice + floatval($tempVal);
            $discount = DB::table('marketing_acts_sales')
                ->join('marketing_acts', 'marketing_acts_sales.marketing_acts_id', '=', 'marketing_acts.id')
                ->where('sales_id', $row->sales_id)->get();

            if ($row->billing_type == 1 && $row->userBilling == 0) {
                $row->name = $row->sender_name . ' ' . $row->sender_surname;
                $districtTemp = DeliveryLocation::where('id', $row->delivery_locations_id)->get()[0]->district;
                $row->bigCity = explode("-", $districtTemp)[0];
                $row->smallCity = explode("-", $districtTemp)[1];
                //$row->address = DeliveryLocation::where('id' , $row->delivery_locations_id )->get()[0]->district;
                $row->address2 = $row->city;
            } else if ($row->billing_type == 1 && $row->userBilling == 1) {
                $row->name = $row->billing_name . ' ' . $row->billing_surname;
                $row->bigCity = $row->city;
                $row->smallCity = $row->small_city;
                //$row->address = DeliveryLocation::where('id' , $row->delivery_locations_id )->get()[0]->district;
                $row->address2 = $row->billing_address;
                $row->tax_office = $row->tc;
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

                if ($row->product_type == 2) {
                    $row->discountValue = floatval(floatval($tempPriceWithDiscount) * 8 / 100);
                } else {
                    $row->discountValue = floatval(floatval($tempPriceWithDiscount) * 18 / 100);
                }

                $totalKDV = $totalKDV + $row->discountValue;
                $row->discountValue = number_format($row->discountValue, 2);
                parse_str($row->discountValue);
                $row->discountValue = str_replace('.', ',', $row->discountValue);

                if ($row->product_type == 2) {
                    $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 108 / 100);
                } else {
                    $priceWithDiscount = floatval(floatval($tempPriceWithDiscount) * 118 / 100);
                }

                $priceWithDiscount = number_format($priceWithDiscount, 2);
                $tempTotal = $priceWithDiscount;
                parse_str($priceWithDiscount);
                $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                $row->sumTotal = $priceWithDiscount;

            }
            $total = $total + $tempTotal;
        }

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


        $queryParams = [];
        $queryParams = (object)['created_at' => "", 'created_at_end' => "", "billing_active" => false, "payment_type" => false, 'status' => 'Hepsi'];

        return view('admin.billingExport', compact('list', 'queryParams', 'total', 'totalKDV', 'totalPartial', 'totalDiscount', 'firstPrice', 'count', 'avarageDiscount', 'companyList'));
    }

    public function showAllMessages()
    {
        AdminPanelController::checkAdmin();
        $messages = DB::table('messages')->orderBy('created_at', 'DESC')->get();
        return view('admin.messages', compact('messages'));
    }

    public function deleteCompanyCoupon()
    {
        AdminPanelController::checkAdmin();
        $id = Request::input('id');
        DB::table('company_coupon')->where('id', $id)->delete();
        return redirect('/admin/companyCoupons');
    }

    public function companyCoupons()
    {
        AdminPanelController::checkAdmin();
        //$coupons = DB::table('company_coupon')->orderBy('count')->get();
        $id = 0;
        $coupons = DB::table('company_coupon')->orderBy('mail')->get();
        return view('admin.companyCouponList', compact('coupons', 'id'));
    }

    public function showCompanyCoupon($couponId)
    {
        AdminPanelController::checkAdmin();
        $coupons = DB::table('company_coupon')->orderBy('mail')->get();
        $id = $couponId;
        return view('admin.companyCouponList', compact('coupons', 'id'));
    }

    public function createCompanyCoupon()
    {
        AdminPanelController::checkAdmin();
        $now = Carbon::now();
        $now = $now->day(1);
        $now = $now->month(1);
        $now = $now->year(2016);
        $now = $now->hour(0);
        $now = $now->minute(0);
        $now = $now->second(0);
        $now = str_replace(' ', 'T', $now);
        return view('admin.companyCoupon', compact('now'));
    }

    public function storeCompanyCoupon(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $input = $request->all();

        if (isset($input['valid']))
            $input['valid'] = 1;
        else
            $input['valid'] = 0;

        $companyCouponId = DB::table('company_coupon')->insertGetId([
            'name' => $input['name'],
            'type' => $input['type'],
            'value' => $input['value'],
            'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $input['value'] . '_indirim.png',
            'expiredDate' => $input['expiredDate'],
            'valid' => $input['valid'],
            'mail' => $input['mail'],
            'description' => $input['description'],
            'group_name' => $input['group'],
            'count' => 0
        ]);

        $customerList = User::where('email', 'LIKE', '%@' . $input['mail'] . '%')->get();

        foreach ($customerList as $customer) {
            $publish_id = str_random(20);
            while (count(MarketingAct::where('publish_id', $publish_id)->get()) != 0) {
                $publish_id = str_random(20);
            }
            $marketingActId = MarketingAct::create(
                [
                    'publish_id' => $publish_id,
                    'name' => $input['name'],
                    'description' => $input['description'],
                    'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $input['value'] . '_indirim.png',
                    'type' => $input['type'],
                    'value' => $input['value'],
                    'active' => true,
                    'valid' => 1,
                    'expiredDate' => $input['expiredDate'],
                    'used' => false,
                    'administrator_id' => 1,
                    'long_term' => 1
                ]
            )->id;

            DB::table('company_coupon')->where('id', $companyCouponId)->increment('count');

            DB::table('customers_marketing_acts')->insert(
                array(
                    'marketing_acts_id' => $marketingActId,
                    'customers_id' => Customer::where('user_id', $customer->id)->get()[0]->id
                )
            );
        }

        $coupons = DB::table('company_coupon')->orderBy('count')->get();
        $id = 0;
        return view('admin.companyCouponList', compact('coupons', 'id'));
    }

    public function updateCompanyCoupon(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $input = $request->all();

        if (isset($input['valid']))
            $input['valid'] = 1;
        else
            $input['valid'] = 0;

        DB::table('company_coupon')->where('id', $input['id'])->update([
            'name' => $input['name'],
            'type' => $input['type'],
            'value' => $input['value'],
            'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $input['value'] . '_indirim.png',
            'expiredDate' => $input['expiredDate'],
            'valid' => $input['valid'],
            'mail' => $input['mail'],
            'group_name' => $input['group_name'],
            'description' => $input['description']
        ]);
        $coupons = DB::table('company_coupon')->orderBy('mail')->get();
        $id = 0;
        return view('admin.companyCouponList', compact('coupons', 'id'));
    }

    public function deleteBanner()
    {
        DB::table('landingBanner')->where('id', Request::input('id'))->delete();
        return AdminPanelController::showBanner(0);
    }

    public function showBanners()
    {
        return AdminPanelController::showBanner(0);
    }

    public function showBanner($id)
    {
        $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();
        $bannerList = DB::table('landingBanner')->orderBy('order_number')->get();
        $descriptionList = DB::table('banner_description')
            ->where('banner_id', '=', $id)->get();

        //$allLang = DB::table('bnf_languages')->where('lang_id' , '!=' , 'tr')->get();

        foreach ($langList as $lang) {
            $tempLandId = false;
            foreach ($descriptionList as $description) {
                if ($description->lang_id == $lang->lang_id) {
                    $tempLandId = true;
                    break;
                }
            }
            if ($tempLandId == false) {
                //array_push($myArray, (object)[ 'mail' => 'Hepsi' , 'domain' => '0' ]);
                array_push($descriptionList, (object)[
                    'banner_id' => '',
                    'content' => '',
                    'lang_id' => $lang->lang_id
                ]
                );
            }
        }
        return view('admin.banner', compact('bannerList', 'id', 'descriptionList'));
    }

    public function createBannerPage()
    {
        $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();
        $mobile = false;
        return view('admin.createBanner', compact('langList', 'mobile'));
    }

    public function createMobileBannerPage()
    {
        $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();
        $mobile = true;
        return view('admin.createBanner', compact('langList', 'mobile'));
    }

    public function createMobileBanner(\Illuminate\Http\Request $request)
    {
        if (Request::input('active') == 'on') {
            $active = 1;
        } else {
            $active = 0;
        }

        $fontColor = "#3b454d";
        $backGroundColor = "#fff";

        if (Request::input('font_color') != "") {
            $fontColor = Request::input('font_color');
        }

        if (Request::input('background_color') != "") {
            $backGroundColor = Request::input('background_color');
        }

        if ($active == 1) {
            DB::table('landingBanner')->where('mobile', 1)->update([
                'active' => 0
            ]);
        }

        $bannerID = DB::table('landingBanner')->insertGetId([
            'active' => $active,
            'url' => Request::input('url'),
            'img_url' => '',
            'header' => Request::input('header'),
            'order_number' => 0,
            'font_color' => $fontColor,
            'background_color' => $backGroundColor,
            'mobile' => 1
        ]);


        $input = $request->all();
        $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

        foreach ($langList as $lang) {
            DB::table('banner_description')->insert([
                'banner_id' => $bannerID,
                'lang_id' => $lang->lang_id,
                'content' => $input['header' . $lang->lang_id]
            ]);
        }

        if (Request::hasFile('img_url')) {
            $file = Request::file('img_url');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];

            $fileMoved = Request::file('img_url')->move(public_path() . "/bannerImages/", $bannerID . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $this->site_url)[1] . '/banners/' . $bannerID . "." . $fileExtension,
                //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                'Body' => fopen(public_path() . "/bannerImages/" . $bannerID . "." . $fileExtension, 'r'),
                'ACL' => 'public-read-write',
                'CacheControl' => 'max-age=2996000'
            ));
            DB::table('landingBanner')->where('id', $bannerID)->update([
                'img_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $this->site_url)[1] . "/banners/" . $bannerID . "." . $fileExtension
            ]);
        }
        $bannerList = DB::table('landingBanner')->orderBy('order_number')->get();
        $id = 0;
        return view('admin.banner', compact('bannerList', 'id'));
    }

    public function createBanner(\Illuminate\Http\Request $request)
    {
        if (Request::input('active') == 'on') {
            $active = 1;
        } else {
            $active = 0;
        }

        $fontColor = "#3b454d";
        $backGroundColor = "#fff";

        if (Request::input('font_color') != "") {
            $fontColor = Request::input('font_color');
        }

        if (Request::input('background_color') != "") {
            $backGroundColor = Request::input('background_color');
        }

        $bannerID = DB::table('landingBanner')->insertGetId([
            'active' => $active,
            'url' => Request::input('url'),
            'img_url' => '',
            'header' => Request::input('header'),
            'order_number' => Request::input('order_number'),
            'font_color' => $fontColor,
            'background_color' => $backGroundColor,
            'mobile' => 0
        ]);

        $input = $request->all();
        $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

        foreach ($langList as $lang) {
            DB::table('banner_description')->insert([
                'banner_id' => $bannerID,
                'lang_id' => $lang->lang_id,
                'content' => $input['header' . $lang->lang_id]
            ]);
        }

        if (Request::hasFile('img_url')) {
            $file = Request::file('img_url');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];

            $fileMoved = Request::file('img_url')->move(public_path() . "/bannerImages/", $bannerID . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $this->site_url)[1] . '/banners/' . $bannerID . "." . $fileExtension,
                //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                'Body' => fopen(public_path() . "/bannerImages/" . $bannerID . "." . $fileExtension, 'r'),
                'ACL' => 'public-read-write',
                'CacheControl' => 'max-age=2996000'
            ));
            DB::table('landingBanner')->where('id', $bannerID)->update([
                'img_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $this->site_url)[1] . "/banners/" . $bannerID . "." . $fileExtension
            ]);
        }
        $bannerList = DB::table('landingBanner')->orderBy('order_number')->get();
        $id = 0;
        return view('admin.banner', compact('bannerList', 'id'));
    }

    public function updateBanners(\Illuminate\Http\Request $request)
    {
        $input = $request->all();
        if (Request::input('active') == 'on') {
            $active = 1;
        } else {
            $active = 0;
        }
        if (Request::hasFile('img_url')) {
            $file = Request::file('img_url');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];

            $fileMoved = Request::file('img_url')->move(public_path() . "/bannerImages/", Request::input('id') . "." . $fileExtension);

            $versionId = DB::table('landingBanner')->where('id', Request::input('id'))->get()[0]->version_id;

            if ($versionId == 0) {
                $versionId = 1;
            } else {
                $versionId = $versionId + 1;
            }

            $s3 = \AWS::get('s3');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $this->site_url)[1] . '/banners/' . Request::input('id') . "_" . $versionId . "." . $fileExtension,
                //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                'Body' => fopen(public_path() . "/bannerImages/" . Request::input('id') . "." . $fileExtension, 'r'),
                'ACL' => 'public-read-write',
                'CacheControl' => 'max-age=2996000'
            ));

            DB::table('landingBanner')->where('id', Request::input('id'))->update([
                'img_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $this->site_url)[1] . "/banners/" . Request::input('id') . "_" . $versionId . "." . $fileExtension,
                'version_id' => $versionId
            ]);
        }

        if (Request::input('mobile') == 1 && $active == 1) {
            DB::table('landingBanner')->where('mobile', 1)->update([
                'active' => 0
            ]);
        }


        DB::table('landingBanner')->where('id', Request::input('id'))->update([
            'active' => $active,
            'url' => Request::input('url'),
            'header' => Request::input('header'),
            'order_number' => Request::input('order_number'),
            'font_color' => Request::input('font_color'),
            'background_color' => Request::input('background_color')
        ]);


        $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

        foreach ($langList as $lang) {

            if (DB::table('banner_description')->where('banner_id', '=', Request::input('id'))->where('lang_id', '=', $lang->lang_id)->count() > 0) {
                DB::table('banner_description')->where('banner_id', '=', Request::input('id'))->where('lang_id', '=', $lang->lang_id)->update(
                    [
                        'content' => $input['header' . $lang->lang_id]
                    ]
                );
            } else {
                DB::table('banner_description')->where('banner_id', '=', Request::input('id'))->where('lang_id', '=', $lang->lang_id)->insert(
                    [
                        'banner_id' => Request::input('id'),
                        'lang_id' => $lang->lang_id,
                        'content' => $input['header' . $lang->lang_id]
                    ]
                );
            }
        }

        $bannerList = DB::table('landingBanner')->orderBy('order_number')->get();
        $id = 0;
        return view('admin.banner', compact('bannerList', 'id'));
    }

    public function checkAdmin()
    {
        if (\Auth::user()->user_group_id == 3) {
            \Auth::logout();
            dd('yetkiniz yok');
        }
    }

    public function logOut()
    {
        \Auth::logout();
        return redirect('/');
    }

    public function orderCoupons()
    {
        AdminPanelController::checkAdmin();
        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);
        if (Request::input('upOrDown') == 'up') {
            $coupons = MarketingAct::orderBy(Request::input('orderParameter'))->where('name', '!=', '10% Tanışma İndirimi')->where('created_at', '>', $today)->get();
        } else {
            $coupons = MarketingAct::orderBy(Request::input('orderParameter'), 'DESC')->where('name', '!=', '10% Tanışma İndirimi')->where('created_at', '>', $today)->get();
        }
        $id = 0;
        return view('admin.coupons', compact('coupons', 'id'));
    }

    public function updateDeliveryHours()
    {
        AdminPanelController::checkAdmin();
        //dd(Request::all());

        $tempDate = '';

        $tempContinentName = '';

        $hourList = DB::table('dayHours')->where('day_number', Request::input('id'))->get();

        if (count($hourList) > 0) {
            $tempDayNumber = DB::table('delivery_hours')->where('id', $hourList[0]->day_number)->get()[0]->day_number;
        }

        $tempHourList = $hourList;

        foreach ($hourList as $key => $hour) {
            $tempActive = false;
            if (Request::input('active_' . $hour->id)) {
                $tempActive = true;
            }

            $tempHourList[$key]->hourString = $hour->start_hour . '-' . $hour->end_hour;
            $tempHourList[$key]->hourData = 'Pasif';

            if ($tempActive) {
                $tempHourList[$key]->hourData = $hour->start_hour . '-' . $hour->end_hour;
                $tempHourList[$key]->hourData = 'Aktif';
            }

            $now = Carbon::now();
            if ($now->dayOfWeek > $tempDayNumber) {
                $now->addDay(7 - $now->dayOfWeek + $tempDayNumber);
            } else {
                $now->addDay($tempDayNumber - $now->dayOfWeek);
            }
            //dd($now);
            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');
            $tempDay = $now->formatLocalized('%A');

            $tempDate = $now->formatLocalized('%A %d %B');
            //dd($hour);

            if ($hour->active == 1 && $tempActive == false) {
                DB::table('delivery_hours_accessibility')->insert([
                    'close_time' => $now,
                    'time_segment' => Request::input('start_' . $hour->id) . ' - ' . Request::input('end_' . $hour->id) . ' - ' . $tempDay,
                    'delivery_location' => DB::table('delivery_hours')->where('id', Request::input('id'))->get()[0]->continent_id
                ]);
            }

            if ($hour->active == 0 && $tempActive == true) {
                $tempTime = Request::input('start_' . $hour->id) . ' - ' . Request::input('end_' . $hour->id) . ' - ' . $tempDay;

                DB::table('delivery_hours_accessibility')
                    ->where('time_segment', $tempTime)
                    ->where('delivery_location', DB::table('delivery_hours')->where('id', Request::input('id'))->get()[0]->continent_id)
                    ->whereNull('open_time')
                    ->update([
                        'open_time' => $now
                    ]);
            }

            DB::table('dayHours')->where('id', $hour->id)->update([
                'start_hour' => Request::input('start_' . $hour->id),
                'end_hour' => Request::input('end_' . $hour->id),
                'active' => $tempActive
            ]);

            $tempContinentName = DB::table('delivery_hours')->where('id', $hour->day_number)->get()[0]->continent_id;

            DB::table('delivery_hours_log')->insert([
                'continent_name' => $tempContinentName,
                'day_hour_id' => $hour->id,
                'status' => $tempActive,
                'user_id' => \Auth::user()->id
            ]);

        }

        //dd($tempHourList);

        \MandrillMail::messages()->sendTemplate('delivery_time_changes', null, array(
            'html' => '<p>Example HTML content</p>',
            'text' => '!!Hatalı Siparişlerde Artış!!',
            'subject' => "Teslim saatleri güncellendi!",
            'from_email' => 'teknik@bloomandfresh.com',
            'from_name' => 'Bloom And Fresh',
            'to' => array(
                array(
                    'email' => 'omer@bloomandfresh.com',
                    'type' => 'to'
                ),
                array(
                    'email' => 'murat@bloomandfresh.com',
                    'type' => 'to'
                ),
                array(
                    'email' => 'hello@bloomandfresh.com',
                    'type' => 'to'
                ),
                array(
                    'email' => 'hakancetinh@gmail.com',
                    'type' => 'to'
                )
            ),
            'merge' => true,
            'merge_language' => 'mailchimp',
            'global_merge_vars' => array(
                array(
                    'name' => 'location',
                    'content' => $tempContinentName,
                ), array(
                    'name' => 'date',
                    'content' => $tempDate,
                ), array(
                    'name' => 'hour1',
                    'content' => $hourList[0]->hourString,
                ), array(
                    'name' => 'hour1Data',
                    'content' => $hourList[0]->hourData,
                ), array(
                    'name' => 'hour2',
                    'content' => $hourList[1]->hourString,
                ), array(
                    'name' => 'hour2Data',
                    'content' => $hourList[1]->hourData,
                ), array(
                    'name' => 'hour3',
                    'content' => $hourList[2]->hourString,
                ), array(
                    'name' => 'hour3Data',
                    'content' => $hourList[2]->hourData,
                ), array(
                    'name' => 'user',
                    'content' => \Auth::user()->email,
                )
            )
        ));

        return redirect('/admin/deliveryHours');
        //return AdminPanelController::showSelectedDeliveryHours(0 , "");
    }

    public function showDeliveryHours()
    {
        AdminPanelController::checkAdmin();
        return AdminPanelController::showSelectedDeliveryHours(0, "");
    }

    public function showSelectedDeliveryHours($id, $continent_id)
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' 1 = 0 ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_hours.city_id = ' . $city->city_id;
        }

        //dd($cityList);

        $tempContinentList = DB::table('delivery_hours')->whereRaw($tempWhere)->groupBy('continent_id')->select('continent_id')->get();

        foreach ($tempContinentList as $continent) {
            $continent->days = DB::table('delivery_hours')->where('continent_id', $continent->continent_id)->get();
            foreach ($continent->days as $day) {
                $now = Carbon::now();
                $hoursList = DB::table('dayHours')->where('day_number', $day->id)->get();
                $day->hours = $hoursList;

                if ($now->dayOfWeek > $day->day_number) {
                    $now->addDay(7 - $now->dayOfWeek + $day->day_number);
                } else {
                    $now->addDay($day->day_number - $now->dayOfWeek);
                }
                $day->date = $now->formatLocalized('%A %d %B');
                $day->number = $now->dayOfYear;
            }
        }

        //dd($tempContinentList);

        /*
        $dayListEu = DB::table('delivery_hours')->where('continent_id' , 'Avrupa' )->get();
        foreach($dayListEu as $day){
            $now = Carbon::now();
            $hoursList =  DB::table('dayHours')->where( 'day_number' , $day->id )->get();
            $day->hours = $hoursList;

            if($now->dayOfWeek > $day->day_number){
                $now->addDay( 7 - $now->dayOfWeek + $day->day_number );
            }
            else{
                $now->addDay( $day->day_number - $now->dayOfWeek);
            }
            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }
        usort($dayListEu, function ($a, $b) {
            return $a->number - $b->number;
        });
        $dayList = DB::table('delivery_hours')->where('continent_id' , 'Asya' )->get();
        foreach($dayList as $day){
            $now = Carbon::now();
            $hoursList =  DB::table('dayHours')->where( 'day_number' , $day->id )->get();
            $day->hours = $hoursList;

            if($now->dayOfWeek > $day->day_number){
                $now->addDay( 7 - $now->dayOfWeek + $day->day_number );
            }
            else{
                $now->addDay( $day->day_number - $now->dayOfWeek);
            }
            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }
        usort($dayList, function ($a, $b) {
            return $a->number - $b->number;
        });


        $dayListAsya2 = DB::table('delivery_hours')->where('continent_id' , 'Asya-2' )->get();
        foreach($dayListAsya2 as $day){
            $now = Carbon::now();
            $hoursList =  DB::table('dayHours')->where( 'day_number' , $day->id )->get();
            $day->hours = $hoursList;

            if($now->dayOfWeek > $day->day_number){
                $now->addDay( 7 - $now->dayOfWeek + $day->day_number );
            }
            else{
                $now->addDay( $day->day_number - $now->dayOfWeek);
            }
            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }
        usort($dayListAsya2, function ($a, $b) {
            return $a->number - $b->number;
        });

        $dayListAvrupa2 = DB::table('delivery_hours')->where('continent_id' , 'Avrupa-2' )->get();
        foreach($dayListAvrupa2 as $day){
            $now = Carbon::now();
            $hoursList =  DB::table('dayHours')->where( 'day_number' , $day->id )->get();
            $day->hours = $hoursList;

            if($now->dayOfWeek > $day->day_number){
                $now->addDay( 7 - $now->dayOfWeek + $day->day_number );
            }
            else{
                $now->addDay( $day->day_number - $now->dayOfWeek);
            }
            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }
        usort($dayListAvrupa2, function ($a, $b) {
            return $a->number - $b->number;
        });

        $dayListAvrupa3 = DB::table('delivery_hours')->where('continent_id' , 'Avrupa-3' )->get();
        foreach($dayListAvrupa3 as $day){
            $now = Carbon::now();
            $hoursList =  DB::table('dayHours')->where( 'day_number' , $day->id )->get();
            $day->hours = $hoursList;

            if($now->dayOfWeek > $day->day_number){
                $now->addDay( 7 - $now->dayOfWeek + $day->day_number );
            }
            else{
                $now->addDay( $day->day_number - $now->dayOfWeek);
            }
            $day->date = $now->formatLocalized('%A %d %B');
            $day->number = $now->dayOfYear;
        }
        usort($dayListAvrupa3, function ($a, $b) {
            return $a->number - $b->number;
        });
        */
        $myArray = [];
        array_push($myArray, (object)['hour' => '00', 'val' => '00:00']);
        array_push($myArray, (object)['hour' => '01', 'val' => '01:00']);
        array_push($myArray, (object)['hour' => '02', 'val' => '02:00']);
        array_push($myArray, (object)['hour' => '03', 'val' => '03:00']);
        array_push($myArray, (object)['hour' => '04', 'val' => '04:00']);
        array_push($myArray, (object)['hour' => '05', 'val' => '05:00']);
        array_push($myArray, (object)['hour' => '06', 'val' => '06:00']);
        array_push($myArray, (object)['hour' => '07', 'val' => '07:00']);
        array_push($myArray, (object)['hour' => '08', 'val' => '08:00']);
        array_push($myArray, (object)['hour' => '09', 'val' => '09:00']);
        array_push($myArray, (object)['hour' => '10', 'val' => '10:00']);
        array_push($myArray, (object)['hour' => '11', 'val' => '11:00']);
        array_push($myArray, (object)['hour' => '12', 'val' => '12:00']);
        array_push($myArray, (object)['hour' => '13', 'val' => '13:00']);
        array_push($myArray, (object)['hour' => '14', 'val' => '14:00']);
        array_push($myArray, (object)['hour' => '15', 'val' => '15:00']);
        array_push($myArray, (object)['hour' => '16', 'val' => '16:00']);
        array_push($myArray, (object)['hour' => '17', 'val' => '17:00']);
        array_push($myArray, (object)['hour' => '18', 'val' => '18:00']);
        array_push($myArray, (object)['hour' => '19', 'val' => '19:00']);
        array_push($myArray, (object)['hour' => '20', 'val' => '20:00']);
        array_push($myArray, (object)['hour' => '21', 'val' => '21:00']);
        array_push($myArray, (object)['hour' => '22', 'val' => '22:00']);
        array_push($myArray, (object)['hour' => '23', 'val' => '23:00']);
        return view('admin.deliveryHours', compact('tempContinentList', 'myArray', 'id', 'continent_id'));
    }

    public function coupons()
    {
        AdminPanelController::checkAdmin();
        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);

        $coupons = DB::table('marketing_acts')->where('name', '!=', '10% Tanışma İndirimi')->where('created_at', '>', $today)->get();

        //$coupons = MarketingAct::orderBy('created_at' , 'DESC')->where('created_at' , '>' , $today)->get();
        $id = 0;
        return view('admin.coupons', compact('coupons', 'id'));
    }

    public function showDeliveryDocument()
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $before = Carbon::now();
        $after = Carbon::now();
        $before->addHour(-4);
        $after->addHour(11);

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $deliveryList = DB::table('sales')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('deliveries.wanted_delivery_date', '>', $before)
            ->where('deliveries.wanted_delivery_date', '<', $after)
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->whereRaw($tempWhere)
            ->select('sales.id', 'deliveries.products', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit', 'sales.created_at'
                , DB::raw("'0' as studio"), 'sales.receiver_address as address', 'customer_contacts.name', 'customer_contacts.surname', 'delivery_locations.continent_id'
                , 'sales.receiver_mobile as mobile', 'delivery_locations.district')
            ->orderBy('sales.created_at', 'DESC')
            ->get();

        $tempStudioBloom = DB::table('studioBloom')
            ->where('status', 'Ödeme Yapıldı')
            ->where('wanted_date', '>', $before)
            ->where('wanted_date', '<', $after)
            ->where('delivery_status', '!=', '4')
            ->select(
                'contact_name as name',
                'contact_surname as surname',
                'customer_name',
                'customer_surname',
                'district',
                'receiver_address as address',
                'id',
                'wanted_date as wanted_delivery_date',
                'flower_name as products',
                'wanted_delivery_limit',
                'delivery_status as status',
                'created_at',
                'customer_mobile',
                'continent_id'
            )->get();
        foreach ($tempStudioBloom as $studio) {
            $studio->studio = 1;
            array_unshift($deliveryList, (object)$studio);
        }

        foreach ($deliveryList as $delivery) {
            //$limitDate = new Carbon($delivery->wanted_delivery_limit);
            $limitDateInfo = new Carbon($delivery->wanted_delivery_date);
            $limitDateInfoL = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = ' ' . $limitDateInfo->hour . ':00 - ' . $limitDateInfoL->hour . ':00 ' . $limitDateInfo->formatLocalized('%A %d %B %Y');
            $delivery->dateInfo = $dateInfo;

            $tempCikolat = AdminPanelController::getCikolatData($delivery->id);

            if ($tempCikolat) {
                $delivery->products = $delivery->products . ' - ' . $tempCikolat->name;
            }
        }

        $deliveryHourList = [];

        $queryParams = [];

        $queryParams = (object)['deliveryHour' => "Hepsi"];

        array_push($deliveryHourList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($deliveryHourList, (object)['information' => '9-13', 'status' => '9:00:00']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00']);

        return view('admin.deliveryDocumentListNew', compact('deliveryList', 'deliveryHourList', 'queryParams'));
        //return view('admin.deliveryDocumentList' , compact('deliveryList' , 'deliveryHourList' , 'queryParams') );
    }

    public function printDeliveryInfo(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $before = Carbon::now();
        $after = Carbon::now();
        $before->addHour(-4);
        $after->addHour(48);
        $tempObject = $request->all();
        $tempQueryList = [];
        foreach ($tempObject as $key => $value) {
            if (explode('_', $key)[0] == 'selected') {
                if (strlen(explode('_', $key)[1]) > 8) {
                    $deliveryList = DB::table('studioBloom')
                        ->where('status', 'Ödeme Yapıldı')
                        //->where('wanted_date', '>', $before)
                        //->where('wanted_date', '<', $after)
                        ->where('id', '=', explode('_', $key)[1])
                        ->select(
                            'contact_name as name',
                            'contact_surname as surname',
                            'customer_name',
                            'customer_surname',
                            'district',
                            'receiver_address as address',
                            'id',
                            'wanted_date as wanted_delivery_date',
                            'flower_name as products',
                            'wanted_delivery_limit',
                            'delivery_status as status',
                            'created_at',
                            'customer_mobile as mobile',
                            'contact_name as sender_name',
                            'contact_surname as sender_surname',
                            'customer_mobile as sender_mobile',
                            'note as delivery_not'
                        )->get()[0];
                    //foreach($deliveryList as $studio){
                    $deliveryList->studio = 1;
                    $deliveryList->id = substr($deliveryList->id, 0, 9);
                    //array_unshift($tempQueryList, (object)$studio);
                    //}
                } else {
                    $deliveryList = DB::table('sales')
                        ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                        ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                        ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                        //->where('deliveries.wanted_delivery_date' , '>' , $before )
                        //->where('deliveries.wanted_delivery_date' , '<' , $after )
                        ->where('sales.id', '=', explode('_', $key)[1])
                        ->where('sales.payment_methods', '=', 'OK')
                        ->select('deliveries.products', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit'
                            , DB::raw("'0' as studio"), 'sales.receiver_address as address', 'customer_contacts.name', 'customer_contacts.surname', 'sales.receiver_mobile as mobile',
                            'delivery_locations.district', 'sales.sender_name', 'sales.sender_surname', 'sales.sender_mobile', 'sales.id', 'sales.delivery_not'
                        )
                        ->get()[0];


                    DB::table('sales')->where('sales.id', '=', explode('_', $key)[1])->update([
                        'isPrintedDelivery' => 1
                    ]);

                }

                $limitDate = new Carbon($deliveryList->wanted_delivery_limit);
                $limitDateInfo = new Carbon($deliveryList->wanted_delivery_date);
                $limitDateInfoL = new Carbon($deliveryList->wanted_delivery_limit);
                setlocale(LC_ALL, 'tr_TR.UTF-8');
                $dateInfo = ' ' . $limitDateInfo->hour . ':00 - ' . $limitDateInfoL->hour . ':00 ' . $limitDate->formatLocalized('%A %d %B %Y');
                $deliveryList->dateInfo = $dateInfo;

                $tempCikolat = AdminPanelController::getCikolatData($deliveryList->id);

                if ($tempCikolat) {
                    $deliveryList->cikolatName = $tempCikolat->name;
                } else {
                    $deliveryList->cikolatName = "";
                }

                array_push($tempQueryList, $deliveryList);
            }
        }

        return view('admin.deliverydocument', compact('tempQueryList'));
    }

    public function createCoupon()
    {
        AdminPanelController::checkAdmin();
        $now = Carbon::now();
        $now = $now->addDay(10);
        $now = str_replace(' ', 'T', $now);
        return view('admin.createMarketActivity', compact('now'));
    }

    public function showSaleDocument()
    {
        AdminPanelController::checkAdmin();
        return view('admin.deliverydocument');
    }

    public function showCoupon($couponId)
    {
        AdminPanelController::checkAdmin();
        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);
        $coupons = MarketingAct::orderBy('created_at', 'DESC')->where('created_at', '>', $today)->get();
        $id = $couponId;
        return view('admin.coupons', compact('coupons', 'id'));
    }

    public function storeCoupon(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $input = $request->all();
        if (isset($input['valid']))
            $input['valid'] = 1;
        else
            $input['valid'] = 0;
        if (isset($input['used']))
            $input['used'] = 1;
        else
            $input['used'] = 0;
        MarketingAct::where('id', $input['id'])->update([
            'publish_id' => $input['publish_id'],
            'name' => $input['name'],
            'type' => $input['type'],
            'value' => $input['value'],
            'valid' => $input['valid'],
            'expiredDate' => $input['expiredDate'],
            'used' => $input['used'],
        ]);
        return redirect('/admin/coupons');
    }

    public function deleteCoupon()
    {
        AdminPanelController::checkAdmin();
        $id = Request::input('id');
        MarketingAct::where('id', $id)->delete();
        return redirect('/admin/coupons');
    }

    public function insertCoupon(\Illuminate\Http\Request $request)
    {

        AdminPanelController::checkAdmin();

        $userId = \Auth::user();
        $input = $request->all();

        if (isset($input['long_term'])) {
            //dd('Mail adresi bulunamadı.');
            $userInfo = User::where('email', $input['email'])->get();
            if (count($userInfo) == 0) {
                dd('Mail adresi bulunamadı.');
            }
            $input['long_term'] = 1;
        } else
            $input['long_term'] = 0;

        if (isset($input['valid']))
            $input['valid'] = 1;
        else
            $input['valid'] = 0;

        //if (isset($input['long_term']))
        //    $input['long_term'] = 1;
        //else
        //    $input['long_term'] = 0;

        for ($x = 1; $x <= $input['count']; $x++) {
            $publish_id = 'BNF' . str_random(6);
            while (count(MarketingAct::where('publish_id', $publish_id)->get()) != 0) {
                $publish_id = 'BNF' . str_random(6);
            }
            $publish_id = strtoupper($publish_id);
            $id = MarketingAct::create(
                [
                    'publish_id' => $publish_id,
                    'name' => $input['name'],
                    'type' => $input['type'],
                    'value' => $input['value'],
                    'valid' => $input['valid'],
                    'expiredDate' => $input['expiredDate'],
                    'used' => 0,
                    'long_term' => $input['long_term'],
                    'active' => 0,
                    'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $input['value'] . '_indirim.png',
                    'administrator_id' => 1,
                    'description' => $input['description']
                ]
            )->id;
        }


        if ($input['long_term'] == 1) {
            MarketingAct::where('id', $id)->update([
                'active' => 1,
                'image_type' => 'https://d1z5skrvc8vebc.cloudfront.net/coupons/' . $input['value'] . '_indirim.png'
            ]);

            DB::table('customers_marketing_acts')->insert([
                'marketing_acts_id' => $id,
                'customers_id' => Customer::where('user_id', $userInfo[0]->id)->get()[0]->id
            ]);
        }
        $today = Carbon::now();
        $today->hour(00);
        $today->minute(00);
        $coupons = MarketingAct::where('created_at', '>', $today)->get();
        //$coupons = MarketingAct::all();
        $id = 0;
        return view('admin.coupons', compact('coupons', 'id'));
    }

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function login()
    {
        AdminPanelController::checkAdmin();
        //return 'login | forgot password';
        //return view('admin.index');
        return redirect('/auth/login');
    }

    public function index()
    {
        AdminPanelController::checkAdmin();
        return AdminPanelController::show(0);
    }

    public function show($id)
    {
        AdminPanelController::checkAdmin();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or product_city.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';


        //$products = Product::latest('published_at')->get();
        $products = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('product_city.active', 1)
            ->where('company_product', '=', '0')->whereRaw($tempWhere)->orderBy('product_city.landing_page_order')
            ->select('products.id', 'products.name', 'products.price', 'products.photo_url', 'products.name', 'products.name', 'products.description', 'product_city.activation_status_id'
                , 'product_city.limit_statu', 'product_city.coming_soon', 'product_city.landing_page_order', 'product_city.id as product_city_id', 'product_city.city_id as city_id')->get();

        foreach ($products as $product) {
            //$product->price = str_replace('.', ',',$product->price);
            $imageList = Image::where('products_id', '=', $product->id)->where('type', '=', 'main')->get();
            $product->mainImage = $imageList[0]->image_url;

            /*$numberOfSale = count(DB::table('sales_products')
                ->join('sales', 'sales_products.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->where('products_id', '=', $product->id)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '!=', '4')
                ->get());*/

            $product->saleCount = '0';
        }
        //dd($products);
        return view('admin.products', compact('products', 'id', 'cityList'));
    }

    public function index1()
    {
        AdminPanelController::checkAdmin();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or product_city.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        //$products = Product::latest('published_at')->get();
        $products = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('product_city.active', 1)
            ->where('company_product', '=', '0')->whereRaw($tempWhere)
            ->select('products.id', 'products.name', 'products.price', 'products.photo_url', 'products.name', 'products.name', 'products.description', 'product_city.activation_status_id'
                , 'product_city.limit_statu', 'product_city.coming_soon', 'product_city.landing_page_order', 'product_city.id as product_city_id', 'product_city.city_id as city_id')->orderBy('products.name')->get();

        //dd($products);

        foreach ($products as $product) {
            //$product->price = str_replace('.', ',',$product->price);
            $imageList = Image::where('products_id', '=', $product->id)->where('type', '=', 'main')->get();
            $product->mainImage = $imageList[0]->image_url;

        }
        //dd($products);
        return view('admin.products1', compact('products', 'id', 'cityList'));
    }

    public function showCompanyProduct()
    {
        AdminPanelController::checkAdmin();
        return AdminPanelController::showSelectedCompanyProduct(0);
    }

    public function showSelectedCompanyProduct($id)
    {
        AdminPanelController::checkAdmin();
        //$products = Product::latest('published_at')->get();
        $products = DB::table('product_for_companies')->orderBy('landing_page_order')->get();
        foreach ($products as $product) {
            //$product->price = str_replace('.', ',',$product->price);
            $imageList = DB::table('images_for_companies')->where('products_id', '=', $product->id)->where('type', '=', 'main')->get();
            $product->mainImage = $imageList[0]->image_url;
            $product->saleCount = DB::table('sales')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('users', 'customers.user_id', '=', 'users.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->where('products.product_id_for_company', $product->id)
                ->where('deliveries.status', '<>', '4')
                ->select('sales.id')
                ->count();
        }
        //dd($products);
        return view('admin.productCompanyList', compact('products', 'id'));
    }

    public function orderWith($attribute)
    {
        AdminPanelController::checkAdmin();
        $id = 0;

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' 1 = 0 ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or products.city_id = ' . $city->city_id;
        }

        if ($attribute != 'count')
            $products = Product::where('company_product', '=', 0)->whereRaw($tempWhere)->orderBy($attribute)->get();
        else
            $products = Product::where('company_product', '=', 0)->whereRaw($tempWhere)->get();
        foreach ($products as $product) {
            $imageList = Image::where('products_id', '=', $product->id)->where('type', '=', 'main')->get();
            $product->mainImage = $imageList[0]->image_url;

            $numberOfSale = count(DB::table('sales_products')
                ->join('sales', 'sales_products.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->where('products_id', '=', $product->id)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '!=', '4')
                ->get());
            $product->saleCount = $numberOfSale;
        }
        $products = $products->toArray();
        if ($attribute == 'count')
            usort($products, function ($a, $b) {
                return $a['saleCount'] - $b['saleCount'];
            });
        $tempArray = [];
        foreach ($products as $product) {
            array_push($tempArray, (object)$product);
        }
        $products = $tempArray;
        return view('admin.products', compact('products', 'id'));
    }

    public function orderWithDesc($attribute)
    {
        AdminPanelController::checkAdmin();
        $id = 0;
        //$products = Product::latest('published_at')->get();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' 1 = 0 ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or products.city_id = ' . $city->city_id;
        }


        if ($attribute != 'count')
            $products = Product::where('company_product', '=', 0)->whereRaw($tempWhere)->orderBy($attribute, 'DESC')->get();
        else
            $products = Product::where('company_product', '=', 0)->whereRaw($tempWhere)->get();
        foreach ($products as $product) {
            $imageList = Image::where('products_id', '=', $product->id)->where('type', '=', 'main')->get();
            $product->mainImage = $imageList[0]->image_url;

            $numberOfSale = count(DB::table('sales_products')
                ->join('sales', 'sales_products.sales_id', '=', 'sales.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->where('products_id', '=', $product->id)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '!=', '4')
                ->get());
            $product->saleCount = $numberOfSale;
        }
        $products = $products->toArray();
        if ($attribute == 'count')
            usort($products, function ($a, $b) {
                return -$a['saleCount'] + $b['saleCount'];
            });
        $tempArray = [];
        foreach ($products as $product) {
            array_push($tempArray, (object)$product);
        }
        $products = $tempArray;
        //dd($products);

        return view('admin.products', compact('products', 'id'));
    }

    public function createProduct()
    {
        AdminPanelController::checkAdmin();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

        $allTag = Tag::where('lang_id', 'tr')->get();
        foreach ($allTag as $tag) {
            $tag->selected = false;
        }

        $suppliers = DB::table('suppliers')->where('active', 1)->get();

        $allProduct = DB::table('products')->where('city_id', 1)->get();

        return view('admin.createProduct', compact('allTag', 'langList', 'cityList', 'allProduct', 'suppliers'));
    }

    public function createCompanyProduct()
    {
        AdminPanelController::checkAdmin();

        $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

        $allTag = Tag::where('lang_id', 'tr')->get();
        foreach ($allTag as $tag) {
            $tag->selected = false;
        }

        return view('admin.createCompanyProduct', compact('allTag', 'langList'));
    }

    public function insertCompanyProduct(insertProductRequest $request)
    {
        AdminPanelController::checkAdmin();
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        $input['allTags'];
        if (count($input['allTags']) == 0) {
            dd('tag secmek zorundasiniz!!!!');
        }
        DB::beginTransaction();
        try {
            //$input['price'] = floatval(str_replace(',', '.',$input['price']));
            //$input['id'] = (int)$input['id'];
            $insertedProduct = DB::table('product_for_companies')->insertGetId(
                [
                    'name' => $input['name'],
                    'price' => $input['price'],
                    'activation_status_id' => 0,
                    'image_name' => $input['image_name'],
                    'url_parametre' => $input['url_parametre'],
                    'landing_page_order' => 1000,
                    'tag_id' => $input['tag_id']
                ]
            );
            $input = $request->all();

            DB::table('descriptions_for_companies')->insert(
                [
                    'landing_page_desc' => $input['landing_page_desc'],
                    'detail_page_desc' => $input['detail_page_desc'],
                    'how_to_title' => $input['how_to_title'],
                    'how_to_detail' => $input['how_to_detail'],
                    'how_to_step1' => $input['how_to_step1'],
                    'how_to_step2' => $input['how_to_step2'],
                    'how_to_step3' => $input['how_to_step3'],
                    'extra_info_1' => $input['extra_info_1'],
                    'extra_info_2' => $input['extra_info_2'],
                    'extra_info_3' => $input['extra_info_3'],
                    'products_id' => $insertedProduct,
                    'url_title' => $input['url_title'],
                    'img_title' => $input['img_title'],
                    'meta_description' => $input['meta_description'],
                    'lang_id' => 'tr'
                ]
            );

            foreach ($input['allTags'] as $tag) {
                DB::table('products_tags_for_companies')
                    ->insert([
                        'tags_id' => $tag,
                        'products_id' => $insertedProduct
                    ]);
            }
            //dd(Request::file('img'));
            if (Request::file('img')) {
                $file = Request::file('img');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('img');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'main',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension
                    ]);
                $fileMoved = Request::file('img')->move(public_path() . "/productImageUploads/", $input['image_name'] . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            } else {
                dd('resim yuklemeniz gerekmektedir.');
            }

            if (Request::file('imgDetail')) {
                $file = Request::file('imgDetail');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('imgDetail');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailPhoto',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension
                    ]);
                $fileMoved = Request::file('imgDetail')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-detail" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('imgDetail');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-detail" . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-detail" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-detail" . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            } else {
                dd('resim yuklemeniz gerekmektedir.');
            }

            if (Request::file('img1')) {

                //$filename = $file->getClientOriginalName();
                $file = Request::file('img1');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 1
                    ]);
                $fileMoved = Request::file('img1')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide1' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('img2')) {
                $file = Request::file('img2');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 2
                    ]);
                $fileMoved = Request::file('img2')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide2' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('img3')) {
                $file = Request::file('img3');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 3
                    ]);
                $fileMoved = Request::file('img3')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide3' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('img4')) {
                $file = Request::file('img4');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 4
                    ]);
                $fileMoved = Request::file('img4')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide4' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension
                ]);
            }

            if (Request::file('img5')) {
                $file = Request::file('img5');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 5
                    ]);
                $fileMoved = Request::file('img5')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide5' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension
                ]);
            }

            if (Request::file('img6')) {
                $file = Request::file('img6');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 6
                    ]);
                $fileMoved = Request::file('img6')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide6' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension
                ]);
            }

            if (Request::file('img7')) {
                $file = Request::file('img7');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 7
                    ]);
                $fileMoved = Request::file('img7')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide7' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension
                ]);
            }

            if (Request::file('img8')) {
                $file = Request::file('img8');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 8
                    ]);
                $fileMoved = Request::file('img8')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide8' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension
                ]);
            }

            if (Request::file('img9')) {
                $file = Request::file('img9');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 9
                    ]);
                $fileMoved = Request::file('img9')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide9' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension
                ]);
            }

            if (Request::file('img10')) {
                $file = Request::file('img10');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = DB::table('images_for_companies')->insertGetId(
                    [
                        'products_id' => $insertedProduct,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct . "." . $fileExtension,
                        'order_no' => 10
                    ]);
                $fileMoved = Request::file('img10')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide10' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                DB::table('images_for_companies')->where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension
                ]);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/CompanyInfo/productList');
//            \Mail::send('emails.new-issue', array('key' => 'value'), function($message)
//            {
//                $message->to('murat.susanli@ifgirisim.com', 'Bloom & Fresh')->subject('Bloom & Fresh New Product Added!');
//            });

        // File upload
    }

    public function detailProduct($id)
    {
        AdminPanelController::checkAdmin();
        //$products = Product::latest('published_at')->get();
        //$products = Product::where('id' , $id)->get();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or related_products.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $products = DB::table('products')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            //->join('images', 'products.id', '=', 'images.products_id')
            //->join('products_tags', 'products.id', '=', 'products_tags.products_id')
            //->join('tags', 'products_tags.tags_id', '=', 'tags.id')
            ->where('products.id', '=', $id)
            ->where('descriptions.lang_id', '=', 'tr')
            ->select('products.best_seller', 'products.choosen', 'products.tag_id', 'products.id', 'products.name', 'activation_status_id', 'products.price', 'products.old_price', 'products.description', 'products.image_name', 'products.background_color', 'products.second_background_color',
                'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre', 'products.youtube_url', 'products.cargo_sendable', 'products.supplier_id'
                , 'descriptions.how_to_detail', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3', 'descriptions.meta_description', 'products.product_type', 'products.product_type_sub'
                , 'descriptions.img_title', 'descriptions.url_title', 'descriptions.lang_id', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3')
            ->get();

        //dd($products);

        $statusList = DB::table('product_city')
            ->join('city_list', 'product_city.city_id', '=', 'city_list.id')
            ->join('user_city', 'city_list.id', '=', 'user_city.city_id')
            ->where('user_city.user_id', \Auth::user()->id)->where('user_city.valid', 1)
            ->where('product_id', $id)->where('product_city.active', 1)
            ->orderBy('city_list.name')
            ->select('product_city.coming_soon', 'product_city.limit_statu', 'product_city.activation_status_id', 'city_list.name', 'product_city.id', 'city_list.id as city_id')->get();

        $userCities = DB::table('user_city')
            ->join('city_list', 'city_list.id', '=', 'user_city.city_id')
            ->where('user_city.user_id', \Auth::user()->id)
            ->where('user_city.valid', 1)
            ->select('city_list.name', 'user_city.city_id')
            ->get();

        foreach ($userCities as $userCity) {
            $userCity->activeCity = DB::table('product_city')->where('product_id', $id)->where('city_id', $userCity->city_id)->where('active', 1)->count();
        }

        //dd($statusList);

        $allProduct = DB::table('products')->where('company_product', 0)->get();
        /**
         * getting related tags and adding to flower array
         */

        $descriptionList = DB::table('descriptions')->where('lang_id', '!=', 'tr')
            ->where('products_id', '=', $id)->get();

        $allLang = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

        foreach ($allLang as $lang) {
            $tempLandId = false;
            foreach ($descriptionList as $description) {
                if ($description->lang_id == $lang->lang_id) {
                    $tempLandId = true;
                    break;
                }
            }
            if ($tempLandId == false) {
                //array_push($myArray, (object)[ 'mail' => 'Hepsi' , 'domain' => '0' ]);
                array_push($descriptionList, (object)[
                    'landing_page_desc' => '',
                    'how_to_title' => '',
                    'detail_page_desc' => '',
                    'how_to_detail' => '',
                    'how_to_step1' => '',
                    'how_to_step2' => '',
                    'how_to_step3' => '',
                    'extra_info_1' => '',
                    'extra_info_2' => '',
                    'extra_info_3' => '',
                    'meta_description' => '',
                    'img_title' => '',
                    'url_title' => '',
                    'lang_id' => $lang->lang_id
                ]
                );
            }
        }

        $fbImage = DB::table('images_social')->where('products_id', $id)->where('type', '1080Main')->get();
        $fbImage2 = DB::table('images_social')->where('products_id', $id)->where('type', '1080Main_2')->get();
        $fbImage3 = DB::table('images_social')->where('products_id', $id)->where('type', '1080Main_3')->get();
        $fbImage4 = DB::table('images_social')->where('products_id', $id)->where('type', '1080Main_4')->get();
        $fbImage5 = DB::table('images_social')->where('products_id', $id)->where('type', '1080Main_5')->get();
        $fbImage6 = DB::table('images_social')->where('products_id', $id)->where('type', '1080Main_6')->get();
        $fbImage7 = DB::table('images_social')->where('products_id', $id)->where('type', '1080Main_7')->get();
        $fbImage8 = DB::table('images_social')->where('products_id', $id)->where('type', '1080Main_8')->get();
        $fbImage9 = DB::table('images_social')->where('products_id', $id)->where('type', '1080Main_9')->get();
        $fbImage10 = [];

        $suppliers = DB::table('suppliers')->get();

        for ($x = 0; $x < count($products); $x++) {

            $products[$x]->future_delivery_day = DB::table('product_city')->where('product_id', $id)->where('city_id', $cityList[0]->city_id)->get()[0]->future_delivery_day;

            //$products[$x]->price = str_replace('.', ',',$products[$x]->price);
            $tagList = DB::table('products_tags')
                ->join('tags', 'products_tags.tags_id', '=', 'tags.id')
                ->where('products_tags.products_id', '=', $products[$x]->id)
                //->select('tags.tags_name' , 'tags.id')
                ->get();
            $allTag = Tag::where('lang_id', 'tr')->get();
            $tempTagList = [];
            //return $tagList[0]->id;
            foreach ($allTag as $tag) {
                $tag->selected = false;
                foreach ($tagList as $selectedTag) {
                    if ($tag->id == $selectedTag->id) {
                        $tag->selected = true;
                        break;
                    }
                }
            }

            if (count($cityList) == 1) {
                $relatedList = DB::table('related_products')
                    ->join('products', 'related_products.related_product', '=', 'products.id')
                    ->where('related_products.main_product', '=', $products[$x]->id)
                    ->whereRaw($tempWhere)
                    ->select('products.name', 'products.id', 'related_products.city_id')
                    ->get();
            }
            //$tagList=array("a"=>"red","b"=>"green");
            //array_push($tagListTemp,'sdfa');
            //$products[$x]->tags = $tagListTemp;
            //return $tagList;
        }
        /**
         * getting related images and adding  to flower array
         */
        for ($x = 0; $x < count($products); $x++) {
            $imageList = DB::table('images')
                ->where('products_id', '=', $products[$x]->id)
                ->orderBy('type')
                //->select('type', 'image_url')
                ->get();
            $detailListImage = [];

            $products[$x]->DetailImage = '';
            $products[$x]->DetailImageId = '';
            for ($y = 0; $y < count($imageList); $y++) {
                if ($imageList[$y]->type == "main") {
                    $products[$x]->MainImage = $imageList[$y]->image_url;
                    $products[$x]->MainImageId = $imageList[$y]->id;
                } else if ($imageList[$y]->type == "detailImages") {
                    array_push($detailListImage, $imageList[$y]->image_url);

                    if ($y == 0) {
                        $products[$x]->image1 = $imageList[$y]->image_url;
                        $products[$x]->image1Id = $imageList[$y]->id;
                    } else if ($y == 1) {
                        $products[$x]->image2 = $imageList[$y]->image_url;
                        $products[$x]->image2Id = $imageList[$y]->id;
                    } else if ($y == 2) {
                        $products[$x]->image3 = $imageList[$y]->image_url;
                        $products[$x]->image3Id = $imageList[$y]->id;
                    } else if ($y == 3) {
                        $products[$x]->image4 = $imageList[$y]->image_url;
                        $products[$x]->image4Id = $imageList[$y]->id;
                    } else if ($y == 4) {
                        $products[$x]->image5 = $imageList[$y]->image_url;
                        $products[$x]->image5Id = $imageList[$y]->id;
                    } else if ($y == 5) {
                        $products[$x]->image6 = $imageList[$y]->image_url;
                        $products[$x]->image6Id = $imageList[$y]->id;
                    } else if ($y == 6) {
                        $products[$x]->image7 = $imageList[$y]->image_url;
                        $products[$x]->image7Id = $imageList[$y]->id;
                    } else if ($y == 7) {
                        $products[$x]->image8 = $imageList[$y]->image_url;
                        $products[$x]->image8Id = $imageList[$y]->id;
                    } else if ($y == 8) {
                        $products[$x]->image9 = $imageList[$y]->image_url;
                        $products[$x]->image9Id = $imageList[$y]->id;
                    } else if ($y == 9) {
                        $products[$x]->image10 = $imageList[$y]->image_url;
                        $products[$x]->image10Id = $imageList[$y]->id;
                    } else if ($y == 10) {
                        $products[$x]->image11 = $imageList[$y]->image_url;
                        $products[$x]->image11Id = $imageList[$y]->id;
                    } else if ($y == 11) {
                        $products[$x]->image12 = $imageList[$y]->image_url;
                        $products[$x]->image12Id = $imageList[$y]->id;
                    } else if ($y == 12) {
                        $products[$x]->image13 = $imageList[$y]->image_url;
                        $products[$x]->image13Id = $imageList[$y]->id;
                    } else if ($y == 13) {
                        $products[$x]->image14 = $imageList[$y]->image_url;
                        $products[$x]->image14Id = $imageList[$y]->id;
                    } else if ($y == 14) {
                        $products[$x]->image15 = $imageList[$y]->image_url;
                        $products[$x]->image15Id = $imageList[$y]->id;
                    }
                } else if ($imageList[$y]->type == "detailPhoto") {
                    $products[$x]->DetailImage = $imageList[$y]->image_url;
                    $products[$x]->DetailImageId = $imageList[$y]->id;
                } else if ($imageList[$y]->type == "mobile") {
                    $products[$x]->mobileImage = $imageList[$y]->image_url;
                    $products[$x]->mobileImageId = $imageList[$y]->id;
                } else if ($imageList[$y]->type == "landingAnimation") {
                    $products[$x]->landingAnimation = $imageList[$y]->image_url;
                    $products[$x]->landingAnimationId = $imageList[$y]->id;
                } else if ($imageList[$y]->type == "landingAnimation2") {
                    $products[$x]->landingAnimation2 = $imageList[$y]->image_url;
                    $products[$x]->landingAnimation2Id = $imageList[$y]->id;
                }
            }
            $products[$x]->detailListImage = $detailListImage;
        }
        //dd($products);
        //  return $products;
        return view('admin.detailProduct', compact('products', 'allTag', 'suppliers' ,'descriptionList', 'relatedList', 'allProduct', 'statusList', 'userCities', 'cityList', 'fbImage', 'fbImage2', 'fbImage3', 'fbImage4', 'fbImage5', 'fbImage6', 'fbImage7', 'fbImage8', 'fbImage9'));
    }

    public function studioUpdateDeliveries(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $dateInfo = "";
        $input = $request->all();
        if (DB::table('studioBloom')->where('id', $input['id'])->get()[0]->status != '3' && $input['status'] == '3') {
            if (!$input['delivery_date'] || !$input['delivery_date_hour'] || !$input['delivery_date_minute']) {
                dd('Sipariş tarihi girmeden sipariş tamamlandı yapılamaz.');
            }

            $limitDate = new Carbon($input['delivery_date']);
            $limitDate->hour($input['delivery_date_hour']);
            $limitDate->minute($input['delivery_date_minute']);
            DB::table('studioBloom')->where('id', '=', $input['id'])->update([
                'delivery_date' => $limitDate,
                'delivery_status' => $input['status'],
                'picker' => $input['picker']
            ]);
        }

        DB::table('studioBloom')->where('id', '=', $input['id'])->update([
            'delivery_status' => $input['status']
        ]);
        return response()->json(["success" => 1, "status" => $input['status'], "id" => $input['id'], "picker" => $input['picker'], "date" => $dateInfo], 200);
        //return redirect('/admin/deliveries');
    }

    public function updateDelivery(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $dateInfo = "";
        $input = $request->all();
        //dd($input['status']);

        if( Delivery::where('id' , $input['id'])->get()[0]->status == '4' && $input['status'] != '4' ){

            $productData = DB::table('deliveries')
                ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('deliveries.id' , $input['id'])
                ->select( 'sales_products.products_id', 'delivery_locations.city_id', 'sales.id' )
                ->get()[0];

            if( generateDataController::isProductAvailable($productData->products_id, $productData->city_id) ){

                $tempCrossSellData = DB::table('cross_sell')->where('sales_id', $productData->id )->get();

                if( count($tempCrossSellData) > 0 ){

                    if( generateDataController::isCrossSellAvailable($tempCrossSellData[0]->product_id , $productData->city_id) ){

                        generateDataController::setCrossSellCountOneLess($tempCrossSellData[0]->product_id, $productData->city_id);

                        $productCrossSellData = DB::table('cross_sell_products')->where('id', $tempCrossSellData[0]->product_id )->where('city_id', $productData->city_id)->get();

                        if( $productCrossSellData[0]->product_id > 0 ){
                            $productStockData = DB::table('product_stocks')->where('product_id', $productCrossSellData[0]->product_id )->where('city_id', $productData->city_id)->get()[0];
                        }
                        else{
                            $productStockData = DB::table('product_stocks')->where('cross_sell_id', $tempCrossSellData[0]->product_id )->where('city_id', $productData->city_id)->get()[0];
                        }

                        generateDataController::logStock( 'CROSS-SELL İPTAL GERİ ALINDI.', $productStockData->id, $productData->id, $productStockData->count + 1, $productStockData->count, \Auth::user()->id );

                    }
                    else {
                        return response()->json([  ], 500);
                    }

                }

                generateDataController::setProductCountOneLess($productData->products_id, $productData->city_id);

                $productStockData = DB::table('product_stocks')->where('product_id', $productData->products_id )->where('city_id', $productData->city_id)->get()[0];

                generateDataController::logStock( 'İPTAL GERİ ALINDI.', $productStockData->id, $productData->id, $productStockData->count + 1, $productStockData->count, \Auth::user()->id );

            }
            else {
                return response()->json([  ], 500);
            }

        }
        elseif( Delivery::where('id' , $input['id'])->get()[0]->status != '4' && $input['status'] == '4' ){

            $productData = DB::table('deliveries')
                ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('deliveries.id' , $input['id'])
                ->select( 'sales_products.products_id', 'delivery_locations.city_id', 'sales.id' )
                ->get()[0];

            $tempCrossSellData = DB::table('cross_sell')->where('sales_id', $productData->id )->get();

            if( count($tempCrossSellData) > 0 ){

                generateDataController::setCrossSellCountOneMore($tempCrossSellData[0]->product_id, $productData->city_id);

                $productCrossSellData = DB::table('cross_sell_products')->where('id', $tempCrossSellData[0]->product_id )->where('city_id', $productData->city_id)->get();

                if( $productCrossSellData[0]->product_id > 0 ){
                    $productStockData = DB::table('product_stocks')->where('product_id', $productCrossSellData[0]->product_id )->where('city_id', $productData->city_id)->get()[0];
                }
                else{
                    $productStockData = DB::table('product_stocks')->where('cross_sell_id', $tempCrossSellData[0]->product_id )->where('city_id', $productData->city_id)->get()[0];
                }

                generateDataController::logStock( 'CROSS-SELL İPTAL', $productStockData->id, $productData->id, $productStockData->count - 1, $productStockData->count, \Auth::user()->id );

            }

            generateDataController::setProductCountOneMore($productData->products_id, $productData->city_id);

            $productStockData = DB::table('product_stocks')->where('product_id', $productData->products_id )->where('city_id', $productData->city_id)->get()[0];

            generateDataController::logStock( 'İPTAL', $productStockData->id, $productData->id, $productStockData->count - 1, $productStockData->count, \Auth::user()->id );


        }


        if (Delivery::where('id', $input['id'])->get()[0]->status != '3' && $input['status'] == '3') {
            if (!$input['delivery_date'] || !$input['delivery_date_hour'] || !$input['delivery_date_minute']) {
                dd('Sipariş tarihi girmeden sipariş tamamlandı yapılamaz.');
            }

            $sales = DB::table('deliveries')
                ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->where('deliveries.id', $input['id'])
                ->select('sales_products.products_id', 'deliveries.wanted_delivery_date', 'deliveries.created_at as orderDate',
                    'sales.id as id', 'customers.user_id as user_id', 'sales.sender_email as email', 'sales.sender_name as FNAME',
                    'sales.sender_surname as LNAME', 'sales.sum_total as PRICE', 'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME'
                    , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD', 'deliveries.products as PRNAME', 'deliveries.wanted_delivery_limit', 'sales.lang_id')
                ->get()[0];

            if (!$sales->email) {
                $sales->email = User::where('id', $sales->user_id)->get()[0]->email;
            }

            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');

            $created = new Carbon($sales->wanted_delivery_limit);

            $requestDeliveryDate = new Carbon($sales->orderDate);
            $requestDateInfo = $requestDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($requestDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDeliveryDate->minute, 2, '0', STR_PAD_LEFT);


            $deliveryDate = new Carbon($input['delivery_date']);
            $deliveryDate->hour($input['delivery_date_hour']);
            $deliveryDate->minute($input['delivery_date_minute']);
            $dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);

            $wantedDeliveryDate = new Carbon($sales->wanted_delivery_date);
            $wantedDeliveryDateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . '00' . ' - ' . str_pad($created->hour, 2, '0', STR_PAD_LEFT) . ':' . '00';

            $tempMailTemplateName = "siparis_teslim_alindi_ekstre_urun";
            if ($sales->lang_id == 'en') {
                $tempMailTemplateName = "siparis_teslim_edildi_en";
            }

            $tempMailSubjectName = " Teslim Edildi!";
            if ($sales->lang_id == 'en') {
                $tempMailSubjectName = " Is Delivered";
            }

            $tempCikolat = AdminPanelController::getCikolatData($sales->id);

            if ($tempCikolat) {
                $tempCikolatDesc = "ve " . $tempCikolat->name;
                $tempCikolatName = "Ekstra: " . $tempCikolat->name . "<br>";
                $sales->PRICE = floatval(str_replace(',', '.', $sales->PRICE)) + floatval(str_replace(',', '.', $tempCikolat->total_price));
                $sales->PRICE = str_replace('.', ',', $sales->PRICE);
            } else {
                $tempCikolatDesc = "";
                $tempCikolatName = "";
            }

            \MandrillMail::messages()->sendTemplate($tempMailTemplateName, null, array(
                'html' => '<p>Example HTML content</p>',
                'text' => 'Siparişiniz başarıyla teslim edilmistir',
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
                        'name' => 'TAKETIME',
                        'content' => $dateInfo,
                    ), array(
                        'name' => 'PRICE',
                        'content' => $sales->PRICE,
                    ), array(
                        'name' => 'PRNAME',
                        'content' => $sales->PRNAME
                    ), array(
                        'name' => 'SALEID',
                        'content' => $sales->id
                    ), array(
                        'name' => 'ORDERDATE',
                        'content' => $requestDateInfo
                    ), array(
                        'name' => 'WANTEDDATE',
                        'content' => $wantedDeliveryDateInfo
                    ), array(
                        'name' => 'PIMAGE',
                        'content' => DB::table('images')->where('type', 'main')->where('products_id', $sales->products_id)->get()[0]->image_url
                    ), array(
                        'name' => 'PICKER',
                        'content' => ucwords(strtolower($input['picker']))
                    ), array(
                        'name' => 'EKSTRA_URUN_NOTE',
                        'content' => $tempCikolatDesc
                    ), array(
                        'name' => 'EKSTRA_URUN_NAME',
                        'content' => $tempCikolatName
                    )
                )
            ));
            $limitDate = new Carbon($input['delivery_date']);
            $limitDate->hour($input['delivery_date_hour']);
            $limitDate->minute($input['delivery_date_minute']);
            Delivery::where('id', '=', $input['id'])->update([
                'delivery_date' => $limitDate,
                'status' => $input['status'],
                'picker' => $input['picker']
            ]);
            DB::table('error_logs')->insert([
                'method_name' => 'BillingOperation_Admin',
                'error_code' => 'log',
                'error_message' => $sales->id,
                'type' => 'WS',
                'related_variable' => 'billing'
            ]);
            BillingOperation::soapTest($sales->id);
        } else if (Delivery::where('id', $input['id'])->get()[0]->status != '2' && $input['status'] == '2') {
            $sales = DB::table('deliveries')
                ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->where('deliveries.id', $input['id'])
                ->select('sales_products.products_id', 'customers.user_id as user_id', 'sales.sender_email as email', 'sales.sender_name as FNAME', 'sales.sender_surname as LNAME', 'sales.sum_total as PRICE', 'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME'
                    , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD', 'deliveries.products as PRNAME', 'sales.lang_id', 'sales.id as sale_id')
                ->get()[0];

            if (!$sales->email) {
                $sales->email = User::where('id', $sales->user_id)->get()[0]->email;
            }

            $tempMailTemplateName = "siparis_yola_cikti_ekstre_urun";
            if ($sales->lang_id == 'en') {
                $tempMailTemplateName = "siparis_yola_cikti_en";
            }

            $tempMailSubjectName = " Yola Çıkıyor!";
            if ($sales->lang_id == 'en') {
                $tempMailSubjectName = " Has Just Left The Buillding";
            }

            $tempCikolat = AdminPanelController::getCikolatData($sales->sale_id);

            if ($tempCikolat) {
                $tempCikolatDesc = "Yanında da " . $tempCikolat->name . " var.";
            } else {
                $tempCikolatDesc = "";
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
                        'name' => 'ORDERTIME',
                        'content' => $input['delivery_date'],
                    ), array(
                        'name' => 'PRICE',
                        'content' => $sales->PRICE,
                    ), array(
                        'name' => 'PIMAGE',
                        'content' => DB::table('images')->where('type', 'main')->where('products_id', $sales->products_id)->get()[0]->image_url
                    ), array(
                        'name' => 'PRNAME',
                        'content' => $sales->PRNAME
                    ), array(
                        'name' => 'EKSTRA_URUN_NOTE',
                        'content' => $tempCikolatDesc
                    )
                )
            ));

        }
        Delivery::where('id', '=', $input['id'])->update([
            'status' => $input['status']
        ]);
        return response()->json(["success" => 1, "status" => $input['status'], "id" => $input['id'], "picker" => $input['picker'], "date" => $dateInfo], 200);
        //return redirect('/admin/deliveries');
    }

    public function store(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        //$input['price'] = floatval(str_replace(',', '.',$input['price']));
        if (isset($input['activation_status']))
            $input['activation_status_id'] = 1;
        else
            $input['activation_status_id'] = 0;

        if (isset($input['limit_statu']))
            $input['limit_statu'] = 1;
        else
            $input['limit_statu'] = 0;

        if (isset($input['coming_soon']))
            $input['coming_soon'] = 1;
        else
            $input['coming_soon'] = 0;

        if (isset($input['id'])) {
            $input['id'] = (int)$input['id'];
            $product = DB::table('product_city')->where('id', $input['product_city_id'])->get()[0];
            if ($product->activation_status_id == 1 && $product->limit_statu == 0 && $product->coming_soon == 0) {
                if (($input['limit_statu'] == 1 || $input['coming_soon'] == 1) && $input['activation_status_id'] == 1) {
                    $before = Carbon::now();
                    DB::table('flowers_accessibility')->insert([
                        'flowers_name' => $product->name,
                        'close_time' => $before
                    ]);
                }
            } else if ($product->activation_status_id == 1 && ($product->limit_statu == 1 || $product->coming_soon == 1)) {
                if (($input['limit_statu'] == 0 && $input['coming_soon'] == 0) && $input['activation_status_id'] == 1) {
                    $before = Carbon::now();
                    DB::table('flowers_accessibility')->where('flowers_name', $product->name)
                        ->whereNull('open_time')
                        ->update([
                            'open_time' => $before
                        ]);
                }
            }
            DB::table('product_city')->where('id', $input['product_city_id'])->update([
                'activation_status_id' => $input['activation_status_id'],
                'limit_statu' => $input['limit_statu'],
                'coming_soon' => $input['coming_soon']
            ]);
            //$product->update($input);
        } else {
            $this->validate($request, ['img' => 'required']);
            //$product = Product::create($input);
//            \Mail::send('emails.new-issue', array('key' => 'value'), function($message)
//            {
//                $message->to('murat.susanli@ifgirisim.com', 'Bloom & Fresh')->subject('Bloom & Fresh New Product Added!');
//            });
        }
        // File upload
        if (Request::hasFile('img')) {
            $file = Request::file('img');
            $filename = $file->getClientOriginalName();
            $fileExtension = explode(".", $filename)[1];
            $imageId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'main')->get()[0]->id;

            $versionId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'main')->get()[0]->version_id;

            if ($versionId == 0) {
                $versionId = 1;
            } else {
                $versionId = $versionId + 1;
            }

            Image::where('products_id', '=', $input['id'])->where('type', '=', 'main')->update(
                [
                    'image_url' => $siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension
                ]);

            $fileMoved = Request::file('img')->move(public_path() . "/productImageUploads/", $imageId . "." . $fileExtension);
            $s3 = \AWS::get('s3');
            $file = Request::file('img');
            $s3->putObject(array(
                'Bucket' => 'bloomandfresh',
                'Key' => explode("//", $siteUrl)[1] . '/' . $imageId . '_' . $versionId . "." . $fileExtension,
                //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension, 'r'),
                'ACL' => 'public-read',
            ));
            Image::where('id', '=', $imageId)->update([
                'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $imageId . '_' . $versionId . "." . $fileExtension,
                'version_id' => $versionId
            ]);
            //$fileMoved = Request::file('img')->move($siteUrl . "/productImageUploads/", $product->id . ".png");
            //Image::where('products_id' , '=' , $product->id)->where('type' , '=' , 'main')->update([
            //    'image_url' => "/productImageUploads/" . $product->id
            //]);
            //return $fileMoved->getExtension();
        }

        return redirect('/admin/products#row-id-' . $product->id);
    }

    public function updateProduct(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        DB::beginTransaction();

        try {

            DB::table('products')->where('id', $input['id'])->update([
                'supplier_id' => $input['supplier']
            ]);

            if (Request::get('cargo_sendable')) {
                DB::table('products')->where('id', $input['id'])->update([
                    'cargo_sendable' => 1
                ]);
            } else {
                DB::table('products')->where('id', $input['id'])->update([
                    'cargo_sendable' => 0
                ]);
            }

            if (Request::get('best_seller')) {
                DB::table('products')->where('id', $input['id'])->update([
                    'best_seller' => 1
                ]);
            } else {
                DB::table('products')->where('id', $input['id'])->update([
                    'best_seller' => 0
                ]);
            }

            if (Request::get('choosen')) {
                DB::table('products')->where('id', $input['id'])->update([
                    'choosen' => 1
                ]);
            } else {
                DB::table('products')->where('id', $input['id'])->update([
                    'choosen' => 0
                ]);
            }

            if (Request::get('totalCity') > 1) {

                DB::table('product_city')->where('product_id', $input['id'])->update([
                    'active' => 0
                ]);

                foreach (Request::get('allCities') as $selectedCity) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', $selectedCity)->update([
                        'active' => 1
                    ]);
                }
            }
            //dd(Request::get('activationId_1'));
            if (Request::get('activationTemp_1')) {
                if (Request::get('activationId_1')) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 1)->update([
                        'activation_status_id' => 1
                    ]);
                } else {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 1)->update([
                        'activation_status_id' => 0
                    ]);
                }
            }

            if (Request::get('activationTemp_2')) {
                if (Request::get('activationId_2')) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 2)->update([
                        'activation_status_id' => 1
                    ]);
                } else {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 2)->update([
                        'activation_status_id' => 0
                    ]);
                }
            }

            if (Request::get('activationTemp_341')) {
                if (Request::get('activationId_341')) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 341)->update([
                        'activation_status_id' => 1
                    ]);
                } else {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 341)->update([
                        'activation_status_id' => 0
                    ]);
                }
            }

            if (Request::get('limitTemp_1')) {
                if (Request::get('limitId_1')) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 1)->update([
                        'limit_statu' => 1
                    ]);
                } else {

                    $productStockData = DB::table('product_stocks')->where('product_id', $input['id'])->where('city_id', 1)->get();
                    $productStatusData = DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 1)->get();

                    if (count($productStockData) > 0 && count($productStatusData) > 0 ) {
                        if ($productStockData[0]->count == 0 && $productStatusData[0]->limit_statu == 1) {
                            dd('Ürünün tükenikliğini kaldırmadan Stok Yönetimden Stok adedi girmelisin!');
                        }
                    }

                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 1)->update([
                        'limit_statu' => 0
                    ]);
                }
            }

            if (Request::get('limitTemp_2')) {
                if (Request::get('limitId_2')) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 2)->update([
                        'limit_statu' => 1
                    ]);
                } else {

                    $productStockData = DB::table('product_stocks')->where('product_id', $input['id'])->where('city_id', 2)->get();
                    $productStatusData = DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 2)->get();

                    if (count($productStockData) > 0 && count($productStatusData) > 0 ) {
                        if ($productStockData[0]->count == 0 && $productStatusData[0]->limit_statu == 1) {
                            dd('Ürünün tükenikliğini kaldırmadan Stok Yönetimden Stok adedi girmelisin!');
                        }
                    }

                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 2)->update([
                        'limit_statu' => 0
                    ]);
                }
            }

            if (Request::get('limitTemp_341')) {
                if (Request::get('limitId_341')) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 341)->update([
                        'limit_statu' => 1
                    ]);
                } else {

                    $productStockData = DB::table('product_stocks')->where('product_id', $input['id'])->where('city_id', 341)->get();
                    $productStatusData = DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 341)->get();

                    if (count($productStockData) > 0 && count($productStatusData) > 0 ) {
                        if ($productStockData[0]->count == 0 && $productStatusData[0]->limit_statu == 1) {
                            dd('Ürünün tükenikliğini kaldırmadan Stok Yönetimden Stok adedi girmelisin!');
                        }
                    }

                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 341)->update([
                        'limit_statu' => 0
                    ]);
                }
            }


            if (Request::get('soonTemp_1')) {
                if (Request::get('soonId_1')) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 1)->update([
                        'coming_soon' => 1
                    ]);
                } else {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 1)->update([
                        'coming_soon' => 0
                    ]);
                }
            }

            if (Request::get('soonTemp_2')) {
                if (Request::get('soonId_2')) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 2)->update([
                        'coming_soon' => 1
                    ]);
                } else {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 2)->update([
                        'coming_soon' => 0
                    ]);
                }
            }

            if (Request::get('soonTemp_341')) {
                if (Request::get('soonId_341')) {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 341)->update([
                        'coming_soon' => 1
                    ]);
                } else {
                    DB::table('product_city')->where('product_id', $input['id'])->where('city_id', 341)->update([
                        'coming_soon' => 0
                    ]);
                }
            }

            $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

            DB::table('product_city')->where('product_id', $input['id'])->where('city_id', $cityList[0]->city_id)->update([
                'future_delivery_day' => Request::get('future_delivery_day')
            ]);

            if (count($cityList) == 1) {

                DB::table('related_products')->where('main_product', $input['id'])->where('city_id', $cityList[0]->city_id)->delete();

                DB::table('related_products')->insert([
                    'main_product' => $input['id'],
                    'related_product' => $input['related_1'],
                    'order' => 1,
                    'city_id' => $cityList[0]->city_id
                ]);

                DB::table('related_products')->insert([
                    'main_product' => $input['id'],
                    'related_product' => $input['related_2'],
                    'order' => 2,
                    'city_id' => $cityList[0]->city_id
                ]);

                DB::table('related_products')->insert([
                    'main_product' => $input['id'],
                    'related_product' => $input['related_3'],
                    'order' => 3,
                    'city_id' => $cityList[0]->city_id
                ]);

                DB::table('related_products')->insert([
                    'main_product' => $input['id'],
                    'related_product' => $input['related_4'],
                    'order' => 4,
                    'city_id' => $cityList[0]->city_id
                ]);

            }

            $tempAl = Description::where('products_id', $input['id'])->get();
            if (count($tempAl) != 0) {
                Description::where('products_id', '=', $input['id'])->where('lang_id', '=', 'tr')->update(
                    [
                        'landing_page_desc' => $input['landing_page_desc'],
                        'detail_page_desc' => $input['detail_page_desc'],
                        'how_to_title' => $input['how_to_title'],
                        'how_to_detail' => $input['how_to_detail'],
                        'how_to_step1' => $input['how_to_step1'],
                        'how_to_step2' => $input['how_to_step2'],
                        'how_to_step3' => $input['how_to_step3'],
                        'extra_info_1' => $input['extra_info_1'],
                        'extra_info_2' => $input['extra_info_2'],
                        'extra_info_3' => $input['extra_info_3'],
                        'url_title' => $input['url_title'],
                        'img_title' => $input['img_title'],
                        'meta_description' => $input['meta_description']
                    ]
                );
            }

            $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

            foreach ($langList as $lang) {

                if (Description::where('products_id', '=', $input['id'])->where('lang_id', '=', $lang->lang_id)->count() > 0) {
                    Description::where('products_id', '=', $input['id'])->where('lang_id', '=', $lang->lang_id)->update(
                        [
                            'landing_page_desc' => $input['landing_page_desc' . $lang->lang_id],
                            'detail_page_desc' => $input['detail_page_desc' . $lang->lang_id],
                            'how_to_title' => $input['how_to_title' . $lang->lang_id],
                            'how_to_detail' => $input['how_to_detail' . $lang->lang_id],
                            'how_to_step1' => $input['how_to_step1' . $lang->lang_id],
                            'how_to_step2' => $input['how_to_step2' . $lang->lang_id],
                            'how_to_step3' => $input['how_to_step3' . $lang->lang_id],
                            'extra_info_1' => $input['extra_info_1' . $lang->lang_id],
                            'extra_info_2' => $input['extra_info_2' . $lang->lang_id],
                            'extra_info_3' => $input['extra_info_3' . $lang->lang_id],
                            'products_id' => $input['id'],
                            'url_title' => $input['url_title' . $lang->lang_id],
                            'img_title' => $input['img_title' . $lang->lang_id],
                            'meta_description' => $input['meta_description' . $lang->lang_id]
                        ]
                    );
                } else {
                    Description::create(
                        [
                            'landing_page_desc' => $input['landing_page_desc' . $lang->lang_id],
                            'detail_page_desc' => $input['detail_page_desc' . $lang->lang_id],
                            'how_to_title' => $input['how_to_title' . $lang->lang_id],
                            'how_to_detail' => $input['how_to_detail' . $lang->lang_id],
                            'how_to_step1' => $input['how_to_step1' . $lang->lang_id],
                            'how_to_step2' => $input['how_to_step2' . $lang->lang_id],
                            'how_to_step3' => $input['how_to_step3' . $lang->lang_id],
                            'extra_info_1' => $input['extra_info_1' . $lang->lang_id],
                            'extra_info_2' => $input['extra_info_2' . $lang->lang_id],
                            'extra_info_3' => $input['extra_info_3' . $lang->lang_id],
                            'products_id' => $input['id'],
                            'url_title' => $input['url_title' . $lang->lang_id],
                            'img_title' => $input['img_title' . $lang->lang_id],
                            'meta_description' => $input['meta_description' . $lang->lang_id],
                            'lang_id' => $lang->lang_id
                        ]
                    );
                }
            }

            //$input['price'] = floatval(str_replace(',', '.',$input['price']));
            DB::table('products_tags')->where('products_id', '=', $input['id'])->delete();
            foreach ($input['allTags'] as $tag) {
                DB::table('products_tags')
                    ->insert([
                        'tags_id' => $tag,
                        'products_id' => (int)$input['id']
                    ]);
            }

            if (isset($input['activation_status']))
                $input['activation_status_id'] = 1;
            else
                $input['activation_status_id'] = 0;

            if ($input['price'] && $input['old_price']) {
                $tempPrice = $input['old_price'];
                $input['old_price'] = $input['price'];
                $input['price'] = $tempPrice;
            }

            $input['id'] = (int)$input['id'];
            $product = Product::find($input['id']);
            $product->update($input);

            if (Request::hasFile('img')) {
                $file = Request::file('img');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                $imageId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'main')->get()[0]->id;

                $versionId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'main')->get()[0]->version_id;

                if ($versionId == 0) {
                    $versionId = 1;
                } else {
                    $versionId = $versionId + 1;
                }

                $fileMoved = Request::file('img')->move(public_path() . "/productImageUploads/", $input['image_name'] . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "_" . $versionId . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "_" . $versionId . "." . $fileExtension,
                    'version_id' => $versionId
                ]);
            }

            if (Request::hasFile('landingAnimation')) {
                $file = Request::file('landingAnimation');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'landingAnimation')->count() > 0) {
                    $versionId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'landingAnimation')->get()[0]->version_id;
                } else {
                    $versionId = 0;
                }


                if ($versionId == 0) {
                    $versionId = 1;
                } else {
                    $versionId = $versionId + 1;
                }

                $fileMoved = Request::file('landingAnimation')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-animation" . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('landingAnimation');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-animation" . "_" . $versionId . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-animation" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'landingAnimation')->count() > 0) {
                    $imageId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'landingAnimation')->get()[0]->id;
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-animation" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId
                    ]);
                } else {
                    Image::create([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-animation" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId,
                        'type' => 'landingAnimation',
                        'products_id' => $input['id'],
                    ]);
                }
            }

            if (Request::hasFile('landingAnimation2')) {
                $file = Request::file('landingAnimation2');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'landingAnimation2')->count() > 0) {
                    $versionId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'landingAnimation2')->get()[0]->version_id;
                } else {
                    $versionId = 0;
                }


                if ($versionId == 0) {
                    $versionId = 1;
                } else {
                    $versionId = $versionId + 1;
                }

                $fileMoved = Request::file('landingAnimation2')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-animation2" . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('landingAnimation2');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-animation2" . "_" . $versionId . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-animation2" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'landingAnimation2')->count() > 0) {
                    $imageId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'landingAnimation2')->get()[0]->id;
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-animation2" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId
                    ]);
                } else {
                    Image::create([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-animation2" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId,
                        'type' => 'landingAnimation2',
                        'products_id' => $input['id'],
                    ]);
                }
            }

            if (Request::file('1080')) {
                $file = Request::file('1080');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main' . '_' . $input['id'] . '_' . rand(1000, 9999);

                DB::table('images_social')->where('type', '1080Main')->where('products_id', $input['id'])->delete();

                DB::table('images_social')->insert([
                    'type' => '1080Main',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $input['id']
                ]);

                $fileMoved = Request::file('1080')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080Main" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080Main" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_2')) {
                $file = Request::file('1080_2');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_2');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_2' . '_' . $input['id'] . '_' . rand(1000, 9999);

                DB::table('images_social')->where('type', '1080Main_2')->where('products_id', $input['id'])->delete();

                DB::table('images_social')->insert([
                    'type' => '1080Main_2',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $input['id']
                ]);

                $fileMoved = Request::file('1080_2')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_2" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_2');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_2" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_3')) {
                $file = Request::file('1080_3');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_3');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_3' . '_' . $input['id'] . '_' . rand(1000, 9999);

                DB::table('images_social')->where('type', '1080Main_3')->where('products_id', $input['id'])->delete();

                DB::table('images_social')->insert([
                    'type' => '1080Main_3',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $input['id']
                ]);

                $fileMoved = Request::file('1080_3')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_3" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_3');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_3" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_4')) {
                $file = Request::file('1080_4');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_4');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_4' . '_' . $input['id'] . '_' . rand(1000, 9999);

                DB::table('images_social')->where('type', '1080Main_4')->where('products_id', $input['id'])->delete();

                DB::table('images_social')->insert([
                    'type' => '1080Main_4',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $input['id']
                ]);

                $fileMoved = Request::file('1080_4')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_4" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_4');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_4" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_5')) {
                $file = Request::file('1080_5');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_5');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_5' . '_' . $input['id'] . '_' . rand(1000, 9999);

                DB::table('images_social')->where('type', '1080Main_5')->where('products_id', $input['id'])->delete();

                DB::table('images_social')->insert([
                    'type' => '1080Main_5',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $input['id']
                ]);

                $fileMoved = Request::file('1080_5')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_5" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_5');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_5" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_6')) {
                $file = Request::file('1080_6');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_6');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_6' . '_' . $input['id'] . '_' . rand(1000, 9999);

                DB::table('images_social')->where('type', '1080Main_6')->where('products_id', $input['id'])->delete();

                DB::table('images_social')->insert([
                    'type' => '1080Main_6',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $input['id']
                ]);

                $fileMoved = Request::file('1080_6')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_6" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_6');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_6" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_7')) {
                $file = Request::file('1080_7');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_7');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_7' . '_' . $input['id'] . '_' . rand(1000, 9999);

                DB::table('images_social')->where('type', '1080Main_7')->where('products_id', $input['id'])->delete();

                DB::table('images_social')->insert([
                    'type' => '1080Main_7',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $input['id']
                ]);

                $fileMoved = Request::file('1080_7')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_7" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_7');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_7" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_8')) {
                $file = Request::file('1080_8');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_8');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_8' . '_' . $input['id'] . '_' . rand(1000, 9999);

                DB::table('images_social')->where('type', '1080Main_8')->where('products_id', $input['id'])->delete();

                DB::table('images_social')->insert([
                    'type' => '1080Main_8',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $input['id']
                ]);

                $fileMoved = Request::file('1080_8')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_8" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_8');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_8" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_9')) {
                $file = Request::file('1080_9');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_9');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_9' . '_' . $input['id'] . '_' . rand(1000, 9999);

                DB::table('images_social')->where('type', '1080Main_9')->where('products_id', $input['id'])->delete();

                DB::table('images_social')->insert([
                    'type' => '1080Main_9',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $input['id']
                ]);

                $fileMoved = Request::file('1080_9')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_9" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_9');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_9" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }


            if (Request::file('300')) {
                $file = Request::file('300');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $fileMoved = Request::file('300')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-300" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('300');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/300-300/' . $input['id'] . ".jpg",
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-300" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
            }

            if (Request::file('400')) {
                $file = Request::file('400');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $fileMoved = Request::file('400')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-400" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('400');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/400-400/' . $input['id'] . ".jpg",
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-400" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
            }

            if (Request::file('600')) {
                $file = Request::file('600');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $fileMoved = Request::file('600')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-600" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('600');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/600-600/' . $input['id'] . ".jpg",
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-600" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
            }

            if (Request::hasFile('imgDetail')) {
                $file = Request::file('imgDetail');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->count() > 0) {
                    $versionId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->get()[0]->version_id;
                } else {
                    $versionId = 0;
                }


                if ($versionId == 0) {
                    $versionId = 1;
                } else {
                    $versionId = $versionId + 1;
                }

                $fileMoved = Request::file('imgDetail')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-detail" . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('imgDetail');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-detail" . "_" . $versionId . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-detail" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->count() > 0) {
                    $imageId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'detailPhoto')->get()[0]->id;
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-detail" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId
                    ]);
                } else {
                    Image::create([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-detail" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId,
                        'type' => 'detailPhoto',
                        'products_id' => $input['id'],
                    ]);
                }

            }

            if (Request::hasFile('mobileImg')) {
                $file = Request::file('mobileImg');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'mobile')->count() > 0) {
                    $versionId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'mobile')->get()[0]->version_id;
                } else {
                    $versionId = 0;
                }


                if ($versionId == 0) {
                    $versionId = 1;
                } else {
                    $versionId = $versionId + 1;
                }

                $fileMoved = Request::file('mobileImg')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-mobile" . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('mobileImg');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-mobile" . "_" . $versionId . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-mobile" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                if (Image::where('products_id', '=', $input['id'])->where('type', '=', 'mobile')->count() > 0) {
                    $imageId = Image::where('products_id', '=', $input['id'])->where('type', '=', 'mobile')->get()[0]->id;
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-mobile" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId
                    ]);
                } else {
                    Image::create([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-mobile" . "_" . $versionId . "." . $fileExtension,
                        'version_id' => $versionId,
                        'type' => 'mobile',
                        'products_id' => $input['id'],
                    ]);
                }
            }

            $imageList = DB::table('images')
                ->where('products_id', '=', $input['id'])
                ->where('type', '=', 'detailImages')
                ->get();
            for ($y = 0; $y < count($imageList); $y++) {
                if ($y == 0) {
                    if (Request::hasFile('img1')) {
                        $file = Request::file('img1');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img1')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide1' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                            'order_no' => 1
                        ]);
                    }
                } else if ($y == 1) {
                    if (Request::hasFile('img2')) {
                        $file = Request::file('img2');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img2')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide2' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                            'order_no' => 2
                        ]);
                    }
                } else if ($y == 2) {
                    if (Request::hasFile('img3')) {
                        $file = Request::file('img3');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img3')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide3' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                            'order_no' => 3
                        ]);
                    }
                } else if ($y == 3) {
                    if (Request::hasFile('img4')) {
                        $file = Request::file('img4');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img4')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide4' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                            'order_no' => 4
                        ]);
                    }
                } else if ($y == 4) {
                    if (Request::hasFile('img5')) {
                        $file = Request::file('img5');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img5')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide5' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                            'order_no' => 5
                        ]);
                    }
                } else if ($y == 5) {
                    if (Request::hasFile('img6')) {
                        $file = Request::file('img6');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img6')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide6' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                            'order_no' => 6
                        ]);
                    }
                } else if ($y == 6) {
                    if (Request::hasFile('img7')) {
                        $file = Request::file('img7');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img7')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide7' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                            'order_no' => 7
                        ]);
                    }
                } else if ($y == 7) {
                    if (Request::hasFile('img8')) {
                        $file = Request::file('img8');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img8')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide8' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                            'order_no' => 8
                        ]);
                    }
                } else if ($y == 8) {
                    if (Request::hasFile('img9')) {
                        $file = Request::file('img9');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img9')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide9' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                            'order_no' => 9
                        ]);
                    }
                } else if ($y == 9) {
                    if (Request::hasFile('img10')) {
                        $file = Request::file('img10');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img10')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide10' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                            'order_no' => 10
                        ]);
                    }
                } else if ($y == 10) {
                    if (Request::hasFile('img11')) {
                        $file = Request::file('img11');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img11')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide11' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide11' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide11' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide11' . "." . $fileExtension,
                            'order_no' => 11
                        ]);
                    }
                } else if ($y == 11) {
                    if (Request::hasFile('img12')) {
                        $file = Request::file('img12');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img12')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide12' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide12' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide12' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide12' . "." . $fileExtension,
                            'order_no' => 12
                        ]);
                    }
                } else if ($y == 12) {
                    if (Request::hasFile('img13')) {
                        $file = Request::file('img13');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img13')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide13' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide13' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide13' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide13' . "." . $fileExtension,
                            'order_no' => 13
                        ]);
                    }
                } else if ($y == 13) {
                    if (Request::hasFile('img14')) {
                        $file = Request::file('img14');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img14')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide14' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide14' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide14' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide14' . "." . $fileExtension,
                            'order_no' => 14
                        ]);
                    }
                } else if ($y == 14) {
                    if (Request::hasFile('img15')) {
                        $file = Request::file('img15');
                        $filename = $file->getClientOriginalName();
                        $fileExtension = explode(".", $filename)[1];

                        $fileMoved = Request::file('img15')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide15' . "." . $fileExtension);
                        $s3 = \AWS::get('s3');
                        $file = Request::file('img');
                        $s3->putObject(array(
                            'Bucket' => 'bloomandfresh',
                            'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide15' . "." . $fileExtension,
                            //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                            'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide15' . "." . $fileExtension, 'r'),
                            'ACL' => 'public-read',
                            'CacheControl' => 'max-age=2996000'
                        ));
                        Image::where('id', '=', $imageList[$y]->id)->update([
                            'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide15' . "." . $fileExtension,
                            'order_no' => 15
                        ]);
                    }
                }
            }
            if (count($imageList) < 15) {
                if (Request::hasFile('img15')) {
                    $file = Request::file('img15');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img15')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide15' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide15' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide15' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide15' . "." . $fileExtension,
                        'order_no' => 15
                    ]);
                }
            }
            if (count($imageList) < 14) {
                if (Request::hasFile('img14')) {
                    $file = Request::file('img14');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img14')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide14' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide14' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide14' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide14' . "." . $fileExtension,
                        'order_no' => 14
                    ]);
                }
            }
            if (count($imageList) < 13) {
                if (Request::hasFile('img13')) {
                    $file = Request::file('img13');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img13')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide13' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide13' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide13' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide13' . "." . $fileExtension,
                        'order_no' => 13
                    ]);
                }
            }
            if (count($imageList) < 12) {
                if (Request::hasFile('img12')) {
                    $file = Request::file('img12');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img12')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide12' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide12' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide12' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide12' . "." . $fileExtension,
                        'order_no' => 12
                    ]);
                }
            }
            if (count($imageList) < 11) {
                if (Request::hasFile('img11')) {
                    $file = Request::file('img11');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img11')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide11' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide11' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide11' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide11' . "." . $fileExtension,
                        'order_no' => 11
                    ]);
                }
            }
            if (count($imageList) < 10) {
                if (Request::hasFile('img10')) {
                    $file = Request::file('img10');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img10')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide10' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                        'order_no' => 10
                    ]);
                }
            }

            if (count($imageList) < 9) {
                if (Request::hasFile('img9')) {
                    $file = Request::file('img9');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img9')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide9' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                        'order_no' => 9
                    ]);
                }
            }

            if (count($imageList) < 8) {
                if (Request::hasFile('img8')) {
                    $file = Request::file('img8');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img8')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide8' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                        'order_no' => 8
                    ]);
                }
            }

            if (count($imageList) < 7) {
                if (Request::hasFile('img7')) {
                    $file = Request::file('img7');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img7')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide7' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                        'order_no' => 7
                    ]);
                }
            }

            if (count($imageList) < 6) {
                if (Request::hasFile('img6')) {
                    $file = Request::file('img6');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img6')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide6' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                        'order_no' => 6
                    ]);
                }
            }

            if (count($imageList) < 5) {
                if (Request::hasFile('img5')) {
                    $file = Request::file('img5');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img5')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide5' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                        'order_no' => 5
                    ]);
                }
            }

            if (count($imageList) < 4) {
                if (Request::hasFile('img4')) {
                    $file = Request::file('img4');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id']
                        ])->id;
                    $fileMoved = Request::file('img4')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide4' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                        'order_no' => 4
                    ]);
                }
            }

            if (count($imageList) < 3) {
                if (Request::hasFile('img3')) {
                    $file = Request::file('img3');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id']
                        ])->id;
                    $fileMoved = Request::file('img3')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide3' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                        'order_no' => 3
                    ]);
                }
            }

            if (count($imageList) < 2) {
                if (Request::hasFile('img2')) {
                    $file = Request::file('img2');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id']
                        ])->id;
                    $fileMoved = Request::file('img2')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide2' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                        'order_no' => 2
                    ]);
                }
            }

            if (count($imageList) < 1) {
                if (Request::hasFile('img1')) {
                    $file = Request::file('img1');
                    $filename = $file->getClientOriginalName();
                    $fileExtension = explode(".", $filename)[1];

                    $imageId = Image::create(
                        [
                            'products_id' => $input['id'],
                            'type' => 'detailImages',
                            'image_url' => $siteUrl . "/productImageUploads/" . $input['id'] . "." . $fileExtension
                        ])->id;
                    $fileMoved = Request::file('img1')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide1' . "." . $fileExtension);
                    $s3 = \AWS::get('s3');
                    $file = Request::file('img');
                    $s3->putObject(array(
                        'Bucket' => 'bloomandfresh',
                        'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                        //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                        'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension, 'r'),
                        'ACL' => 'public-read',
                        'CacheControl' => 'max-age=2996000'
                    ));
                    Image::where('id', '=', $imageId)->update([
                        'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                        'order_no' => 1
                    ]);
                }
            }

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();

        $tempValue = '/admin/products';

        if (Request::get('backLink') == '1') {

            //redirect('/admin/products');
        } else {
            $tempValue = '/admin/products-1';
        }

        return redirect($tempValue);
//            \Mail::send('emails.new-issue', array('key' => 'value'), function($message)
//            {
//                $message->to('murat.susanli@ifgirisim.com', 'Bloom & Fresh')->subject('Bloom & Fresh New Product Added!');
//            });

        // File upload

    }

    public function insertProduct(insertProductRequest $request)
    {
        AdminPanelController::checkAdmin();
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        $input['allTags'];
        if (count($input['allTags']) == 0) {
            dd('tag secmek zorundasiniz!!!!');
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        if (count($cityList) > 1 || count($cityList) == 0) {
            dd('Şehir seçmelisiniz');
        }

        DB::beginTransaction();
        try {
            if (isset($input['activation_status']))
                $input['activation_status_id'] = 1;
            else
                $input['activation_status_id'] = 0;
            //$input['price'] = floatval(str_replace(',', '.',$input['price']));
            //$input['id'] = (int)$input['id'];
            $tempLastNumber = DB::table('products')->orderBy('landing_page_order', 'DESC')->select('landing_page_order')->get()[0]->landing_page_order;
            $insertedProduct = Product::create(
                [
                    'name' => $input['name'],
                    'price' => $input['price'],
                    //'description' => $input['description'],
                    'activation_status_id' => $input['activation_status_id'],
                    'image_name' => $input['image_name'],
                    'url_parametre' => $input['url_parametre'],
                    'landing_page_order' => $tempLastNumber + 1,
                    'tag_id' => $input['tag_id'],
                    'city_id' => 1,
                    'product_type' => $input['product_type'],
                    'supplier_id' => $input['supplier']
                ]
            );

            DB::table('products')->where('id', $insertedProduct->id)->update([
                'product_type' => $input['product_type'],
                'product_type_sub' => $input['product_type_sub']
            ]);

            $productStockId = DB::table('product_stocks')->insertGetId([
                'product_id' => $insertedProduct->id,
                'cross_sell_id' => 0,
                'city_id' => 1,
                'count' => 0,
                'future_stock' => 0,
                'active' => 1
            ]);

            $productStockIdAnk = DB::table('product_stocks')->insertGetId([
                'product_id' => $insertedProduct->id,
                'cross_sell_id' => 0,
                'city_id' => 2,
                'count' => 0,
                'future_stock' => 0,
                'active' => 1
            ]);

            $productStockIdIst2 = DB::table('product_stocks')->insertGetId([
                'product_id' => $insertedProduct->id,
                'cross_sell_id' => 0,
                'city_id' => 341,
                'count' => 0,
                'future_stock' => 0,
                'active' => 1
            ]);


            /*DB::table('mail_trigger')->insert([
                'product_stock_id' => $productStockId,
                'under_email' => 0,
                'no_stock' => 0
            ]);*/


            /*DB::table('mail_trigger')->insert([
                'product_stock_id' => $productStockIdIst2,
                'under_email' => 0,
                'no_stock' => 0
            ]);

            DB::table('mail_trigger')->insert([
                'product_stock_id' => $productStockIdAnk,
                'under_email' => 0,
                'no_stock' => 0
            ]);*/


            if ($input['related_1']) {
                DB::table('related_products')->insert([
                    'main_product' => $insertedProduct->id,
                    'related_product' => $input['related_1'],
                    'city_id' => $cityList[0]->city_id,
                    'order' => 1
                ]);
            }

            if ($input['related_2']) {
                DB::table('related_products')->insert([
                    'main_product' => $insertedProduct->id,
                    'related_product' => $input['related_2'],
                    'city_id' => $cityList[0]->city_id,
                    'order' => 2
                ]);
            }

            if ($input['related_3']) {
                DB::table('related_products')->insert([
                    'main_product' => $insertedProduct->id,
                    'related_product' => $input['related_3'],
                    'city_id' => $cityList[0]->city_id,
                    'order' => 3
                ]);
            }

            if ($input['related_4']) {
                DB::table('related_products')->insert([
                    'main_product' => $insertedProduct->id,
                    'related_product' => $input['related_4'],
                    'city_id' => $cityList[0]->city_id,
                    'order' => 4
                ]);
            }

            //$tempCityId = $cityList[0]->city_id;

            DB::table('product_city')->insert([
                'product_id' => $insertedProduct->id,
                'active' => 1,
                'city_id' => 2,
                'landing_page_order' => $tempLastNumber + 1,
                'activation_status_id' => 0
            ]);

            DB::table('product_city')->insert([
                'product_id' => $insertedProduct->id,
                'active' => 1,
                'city_id' => 1,
                'landing_page_order' => $tempLastNumber + 1,
                'activation_status_id' => 0
            ]);

            DB::table('product_city')->insert([
                'product_id' => $insertedProduct->id,
                'active' => 1,
                'city_id' => 341,
                'landing_page_order' => $tempLastNumber + 1,
                'activation_status_id' => 0
            ]);

            DB::table('product_city')->where('product_id', $insertedProduct->id)->where('city_id', $cityList[0]->city_id)->update([
                'activation_status_id' => $input['activation_status_id']
            ]);


            DB::table('products_shops')
                ->insert([
                    'shops_id' => 1,
                    'products_id' => $insertedProduct->id
                ]);

            $input = $request->all();
            $langList = DB::table('bnf_languages')->where('lang_id', '!=', 'tr')->get();

            foreach ($langList as $lang) {
                Description::create(
                    [
                        'landing_page_desc' => $input['landing_page_desc'],
                        'detail_page_desc' => $input['detail_page_desc'],
                        'how_to_title' => $input['how_to_title'],
                        'how_to_detail' => $input['how_to_detail'],
                        'how_to_step1' => $input['how_to_step1'],
                        'how_to_step2' => $input['how_to_step2'],
                        'how_to_step3' => $input['how_to_step3'],
                        'extra_info_1' => $input['extra_info_1'],
                        'extra_info_2' => $input['extra_info_2'],
                        'extra_info_3' => $input['extra_info_3'],
                        'products_id' => $insertedProduct->id,
                        'url_title' => $input['url_title'],
                        'img_title' => $input['img_title'],
                        'meta_description' => $input['meta_description'],
                        'lang_id' => $lang->lang_id
                    ]
                );
            }

            Description::create(
                [
                    'landing_page_desc' => $input['landing_page_desc'],
                    'detail_page_desc' => $input['detail_page_desc'],
                    'how_to_title' => $input['how_to_title'],
                    'how_to_detail' => $input['how_to_detail'],
                    'how_to_step1' => $input['how_to_step1'],
                    'how_to_step2' => $input['how_to_step2'],
                    'how_to_step3' => $input['how_to_step3'],
                    'extra_info_1' => $input['extra_info_1'],
                    'extra_info_2' => $input['extra_info_2'],
                    'extra_info_3' => $input['extra_info_3'],
                    'products_id' => $insertedProduct->id,
                    'url_title' => $input['url_title'],
                    'img_title' => $input['img_title'],
                    'meta_description' => $input['meta_description'],
                    'lang_id' => 'tr'
                ]
            );

            foreach ($input['allTags'] as $tag) {
                DB::table('products_tags')
                    ->insert([
                        'tags_id' => $tag,
                        'products_id' => $insertedProduct->id
                    ]);
            }
            //dd(Request::file('img'));
            if (Request::file('img')) {
                $file = Request::file('img');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('img');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'main',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension
                    ])->id;
                $fileMoved = Request::file('img')->move(public_path() . "/productImageUploads/", $input['image_name'] . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            } else {
                dd('resim yuklemeniz gerekmektedir.');
            }

            if (Request::file('imgDetail')) {
                $file = Request::file('imgDetail');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('imgDetail');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailPhoto',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension
                    ])->id;
                $fileMoved = Request::file('imgDetail')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-detail" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('imgDetail');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-detail" . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-detail" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-detail" . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            } else {
                dd('resim yuklemeniz gerekmektedir.');
            }

            if (Request::file('mobileImage')) {
                $file = Request::file('mobileImage');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('mobileImage');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'mobile',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension
                    ])->id;
                $fileMoved = Request::file('mobileImage')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-mobile" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('mobileImage');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-mobile" . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-mobile" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-mobile" . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            } else {
                dd('mobil resim yuklemeniz gerekmektedir.');
            }

            if (Request::file('300')) {
                $file = Request::file('300');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $fileMoved = Request::file('300')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-300" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('300');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/300-300/' . $insertedProduct->id . ".jpg",
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-300" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
            }

            if (Request::file('400')) {
                $file = Request::file('400');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $fileMoved = Request::file('400')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-400" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('400');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/400-400/' . $insertedProduct->id . ".jpg",
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-400" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
            }

            if (Request::file('600')) {
                $file = Request::file('600');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $fileMoved = Request::file('600')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-600" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('600');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/600-600/' . $insertedProduct->id . ".jpg",
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-600" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
            }

            if (Request::file('landingAnimation')) {
                $file = Request::file('landingAnimation');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('landingAnimation');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();
                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'landingAnimation',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension
                    ])->id;
                $fileMoved = Request::file('landingAnimation')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-animation" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('landingAnimation');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . "-animation" . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-animation" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . "-animation" . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('1080')) {
                $file = Request::file('1080');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main' . '_' . $insertedProduct->id . '_' . rand(1000, 9999);

                DB::table('images_social')->insert([
                    'type' => '1080Main',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $insertedProduct->id
                ]);

                $fileMoved = Request::file('1080')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080Main" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080Main" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_2')) {
                $file = Request::file('1080_2');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_2');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_2' . '_' . $insertedProduct->id . '_' . rand(1000, 9999);

                DB::table('images_social')->insert([
                    'type' => '1080Main_2',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $insertedProduct->id
                ]);

                $fileMoved = Request::file('1080_2')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_2" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_2');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_2" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_3')) {
                $file = Request::file('1080_3');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_3');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_3' . '_' . $insertedProduct->id . '_' . rand(1000, 9999);

                DB::table('images_social')->insert([
                    'type' => '1080Main_3',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $insertedProduct->id
                ]);

                $fileMoved = Request::file('1080_3')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_3" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_3');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_3" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_4')) {
                $file = Request::file('1080_4');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_4');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_4' . '_' . $insertedProduct->id . '_' . rand(1000, 9999);

                DB::table('images_social')->insert([
                    'type' => '1080Main_4',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $insertedProduct->id
                ]);

                $fileMoved = Request::file('1080_4')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_4" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_4');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_4" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_5')) {
                $file = Request::file('1080_5');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_5');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_5' . '_' . $insertedProduct->id . '_' . rand(1000, 9999);

                DB::table('images_social')->insert([
                    'type' => '1080Main_5',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $insertedProduct->id
                ]);

                $fileMoved = Request::file('1080_5')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_5" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_5');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_5" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_6')) {
                $file = Request::file('1080_6');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_6');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_6' . '_' . $insertedProduct->id . '_' . rand(1000, 9999);

                DB::table('images_social')->insert([
                    'type' => '1080Main_6',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $insertedProduct->id
                ]);

                $fileMoved = Request::file('1080_6')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_6" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_6');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_6" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_7')) {
                $file = Request::file('1080_7');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_7');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_7' . '_' . $insertedProduct->id . '_' . rand(1000, 9999);

                DB::table('images_social')->insert([
                    'type' => '1080Main_7',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $insertedProduct->id
                ]);

                $fileMoved = Request::file('1080_7')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_7" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_7');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_7" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_8')) {
                $file = Request::file('1080_8');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_8');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_8' . '_' . $insertedProduct->id . '_' . rand(1000, 9999);

                DB::table('images_social')->insert([
                    'type' => '1080Main_8',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $insertedProduct->id
                ]);

                $fileMoved = Request::file('1080_8')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_8" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_8');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_8" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('1080_9')) {
                $file = Request::file('1080_9');
                //$filename = $file->getClientOriginalName();
                $file = Request::file('1080_9');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $fileName = '1080_main_9' . '_' . $insertedProduct->id . '_' . rand(1000, 9999);

                DB::table('images_social')->insert([
                    'type' => '1080Main_9',
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . "1080-1080/" . $fileName . "." . $fileExtension,
                    'products_id' => $insertedProduct->id
                ]);

                $fileMoved = Request::file('1080_9')->move(public_path() . "/productImageUploads/", $input['image_name'] . "-1080_main_9" . "." . $fileExtension);
                //dd($siteUrl . "/productImageUploads/" . $imageId . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('1080_9');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => '/1080-1080/' . $fileName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . "-1080_main_9" . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));

                //return $fileMoved->getExtension();
            }

            if (Request::file('img1')) {

                //$filename = $file->getClientOriginalName();
                $file = Request::file('img1');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 1
                    ])->id;
                $fileMoved = Request::file('img1')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide1' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide1' . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('img2')) {
                $file = Request::file('img2');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 2
                    ])->id;
                $fileMoved = Request::file('img2')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide2' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide2' . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('img3')) {
                $file = Request::file('img3');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 3
                    ])->id;
                $fileMoved = Request::file('img3')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide3' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide3' . "." . $fileExtension
                ]);
                //return $fileMoved->getExtension();
            }

            if (Request::file('img4')) {
                $file = Request::file('img4');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];
                //return $file->guessExtension();

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 4
                    ])->id;
                $fileMoved = Request::file('img4')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide4' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide4' . "." . $fileExtension
                ]);
            }

            if (Request::file('img5')) {
                $file = Request::file('img5');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 5
                    ])->id;
                $fileMoved = Request::file('img5')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide5' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide5' . "." . $fileExtension
                ]);
            }

            if (Request::file('img6')) {
                $file = Request::file('img6');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 6
                    ])->id;
                $fileMoved = Request::file('img6')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide6' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide6' . "." . $fileExtension
                ]);
            }

            if (Request::file('img7')) {
                $file = Request::file('img7');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 7
                    ])->id;
                $fileMoved = Request::file('img7')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide7' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide7' . "." . $fileExtension
                ]);
            }

            if (Request::file('img8')) {
                $file = Request::file('img8');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 8
                    ])->id;
                $fileMoved = Request::file('img8')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide8' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide8' . "." . $fileExtension
                ]);
            }

            if (Request::file('img9')) {
                $file = Request::file('img9');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 9
                    ])->id;
                $fileMoved = Request::file('img9')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide9' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide9' . "." . $fileExtension
                ]);
            }

            if (Request::file('img10')) {
                $file = Request::file('img10');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 10
                    ])->id;
                $fileMoved = Request::file('img10')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide10' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide10' . "." . $fileExtension
                ]);
            }

            if (Request::file('img11')) {
                $file = Request::file('img11');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 11
                    ])->id;
                $fileMoved = Request::file('img11')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide11' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide11' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide11' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide11' . "." . $fileExtension
                ]);
            }

            if (Request::file('img12')) {
                $file = Request::file('img12');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 12
                    ])->id;
                $fileMoved = Request::file('img12')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide12' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide12' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide12' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide12' . "." . $fileExtension
                ]);
            }

            if (Request::file('img13')) {
                $file = Request::file('img13');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 13
                    ])->id;
                $fileMoved = Request::file('img13')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide13' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide13' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide13' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide13' . "." . $fileExtension
                ]);
            }

            if (Request::file('img14')) {
                $file = Request::file('img14');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 14
                    ])->id;
                $fileMoved = Request::file('img14')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide14' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide14' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide14' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide14' . "." . $fileExtension
                ]);
            }

            if (Request::file('img15')) {
                $file = Request::file('img15');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $imageId = Image::create(
                    [
                        'products_id' => $insertedProduct->id,
                        'type' => 'detailImages',
                        'image_url' => $siteUrl . "/productImageUploads/" . $insertedProduct->id . "." . $fileExtension,
                        'order_no' => 15
                    ])->id;
                $fileMoved = Request::file('img15')->move(public_path() . "/productImageUploads/", $input['image_name'] . '-' . 'slide15' . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('img');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/' . $input['image_name'] . '-' . 'slide15' . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['image_name'] . '-' . 'slide15' . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                Image::where('id', '=', $imageId)->update([
                    'image_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/" . $input['image_name'] . '-' . 'slide15' . "." . $fileExtension
                ]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/products');
//            \Mail::send('emails.new-issue', array('key' => 'value'), function($message)
//            {
//                $message->to('murat.susanli@ifgirisim.com', 'Bloom & Fresh')->subject('Bloom & Fresh New Product Added!');
//            });

        // File upload

    }

    public function delete()
    {
        AdminPanelController::checkAdmin();
        $id = Request::input('id');
        try {
            $numberOfSale = count(DB::table('sales_products')
                ->where('products_id', '=', $id)
                ->get());
            if ($numberOfSale > 0) {
                return "Siparis verilmis urunu silemezsiniz!";
            }
            DB::table('products_tags')->where('products_id', '=', $id)->delete();
            Description::where('products_id', '=', $id)->delete();
            Image::where('products_id', '=', $id)->delete();
            Product::destroy([$id]);
        } catch (\Exception $e) {
            DB::rollback();
            return "Siparis verilmis urunu silemezsiniz!";
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();

        return redirect('/admin/products');
    }

    public function showSales()
    {
        AdminPanelController::checkAdmin();
        $sales = DB::table('sales')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->select('sales.id', 'sales.created_at', 'sales.sum_total', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'products.name as product_name', 'delivery_locations.district as location_name')
            ->orderBy('sales.created_at', 'DESC')
            ->get();
        $queryParams = [];
        $queryParams = (object)['created_at' => "", 'created_at_end' => "", 'product_name' => "", 'name' => "", 'sur_name' => "", 'location_name' => ""];
        return view('admin.sales', compact('sales', 'queryParams'));
    }

    public function filterSales(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $tempQueryList = [];
        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                if ($key == 'created_at' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'sales.created_at', 'state' => '>', 'value' => $value]);
                } else if ($key == 'created_at_end' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'sales.created_at', 'state' => '<', 'value' => $value]);
                } else if ($key == 'name' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'sales.sender_name', 'state' => '=', 'value' => $value]);
                } else if ($key == 'sur_name' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'sales.sender_surname', 'state' => '=', 'value' => $value]);
                } else if ($key == 'product_name' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'products.name', 'state' => '=', 'value' => $value]);
                } else if ($key == 'location_name' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'delivery_locations.district', 'state' => '=', 'value' => $value]);
                }
            }
        }

        $QueryString = ' 1 = 1';
        foreach ($tempQueryList as $query) {
            $QueryString = $QueryString . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }

        $sales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->whereRaw($QueryString)
            ->select('sales.id', 'sales.created_at', 'sales.sum_total', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'products.name as product_name', 'delivery_locations.district as location_name')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->get();

        return view('admin.sales', compact('sales', 'queryParams'));

    }

    public function orderAndFilterWithDescSales(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $tempQueryList = [];
        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                if ($key == 'created_at' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'sales.created_at', 'state' => '>', 'value' => $value]);
                } else if ($key == 'created_at_end' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'sales.created_at', 'state' => '<', 'value' => $value]);
                } else if ($key == 'name' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'sales.sender_name', 'state' => '=', 'value' => $value]);
                } else if ($key == 'sur_name' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'sales.sender_surname', 'state' => '=', 'value' => $value]);
                } else if ($key == 'product_name' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'products.name', 'state' => '=', 'value' => $value]);
                }
            }
        }

        $QueryString = ' 1 = 1';
        foreach ($tempQueryList as $query) {
            $QueryString = $QueryString . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }

        if ($tempObject['orderParameter'] == 'name')
            $tempObject['orderParameter'] = 'customers.' . $tempObject['orderParameter'];
        else if ($tempObject['orderParameter'] == 'location_name')
            $tempObject['orderParameter'] = 'delivery_locations.district';

        if ($tempObject['upOrDown'] == 'up') {

            $sales = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->whereRaw($QueryString)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '!=', '4')
                ->select('sales.id', 'sales.created_at', 'sales.sum_total', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                    'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'products.name as product_name', 'delivery_locations.district as location_name')
                ->orderBy($tempObject['orderParameter'])
                ->get();
        } else {
            $sales = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->whereRaw($QueryString)
                ->where('sales.payment_methods', '=', 'OK')
                ->where('deliveries.status', '!=', '4')
                ->select('sales.id', 'sales.created_at', 'sales.sum_total', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                    'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'products.name as product_name', 'delivery_locations.district as location_name')
                ->orderBy($tempObject['orderParameter'], 'DESC')
                ->get();
        }

        return view('admin.sales', compact('sales', 'queryParams'));
    }

    public function orderWithSales($attribute)
    {
        AdminPanelController::checkAdmin();
        if ($attribute == 'name')
            $attribute = 'customers.' . $attribute;

        $sales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->select('sales.id', 'sales.created_at', 'sales.sum_total', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'products.name as product_name', 'delivery_locations.district as location_name')
            ->orderBy($attribute)
            ->get();

        return view('admin.sales', compact('sales'));

    }

    public function orderWithDescSales($attribute)
    {
        AdminPanelController::checkAdmin();

        if ($attribute == 'name')
            $attribute = 'customers.' . $attribute;

        $sales = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.status', '!=', '4')
            ->orderBy('customer_name', 'DESC')
            ->select('sales.id', 'sales.created_at', 'sales.sum_total', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'products.name as product_name', 'delivery_locations.district as location_name')
            ->get();

        return view('admin.sales', compact('sales'));
    }

    public function showCustomers()
    {
        $page = 1;
        $query = 'created_at';
        $order = 'down';
        $tempLimitStart = ($page - 1) * 500;
        AdminPanelController::checkAdmin();

        $tempThisWeek = Carbon::now();

        $customers = Customer::orderBy('created_at', 'DESC')
            ->where('customers.created_at', '>', $tempThisWeek->startOfWeek())
            ->skip($tempLimitStart)
            ->take(500)
            ->select('customers.*',
                DB::raw('(select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) as contactNumber'),
                DB::raw('(select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) as salesNumber'),
                DB::raw('(select status from users where users.id = customers.user_id) as status'),
                DB::raw('(select updated_at from users where users.id = customers.user_id) as userLastLogin'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) as email'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) as updated_at'),
                DB::raw('( select (CASE WHEN users.status IS NULL THEN "Mail" ELSE "FB" END) as status from users where users.id = customers.user_id  ) as status')
            )
            ->get();
        $anonimNumber = DB::table('customers')->where('user_id', '=', null)->where('customers.created_at', '>', $tempThisWeek->startOfWeek())->count();
        $userNumber = DB::table('users')->where('users.created_at', '>', $tempThisWeek->startOfWeek())->count();
        $totalNumber = $userNumber + $anonimNumber;
        $pageNumber = ($totalNumber / 500) + 1;
        $myArray = DB::table('company_coupon')->orderBy('mail')->get();

        array_push($myArray, (object)['mail' => 'Hepsi', 'domain' => '0']);

        $tempWeekString = $tempThisWeek->startOfWeek()->format('Y-m-d');

        $tempWeekStringFinal = explode(" ", $tempWeekString)[0];

        $queryParams = (object)['created_at_end' => "", 'created_at' => $tempWeekStringFinal, 'name' => "", 'surname' => "", 'user_id' => "", 'domain' => "Hepsi", 'domain2' => "", "pagination" => "1", "orderParameter" => "created_at", "upOrDown" => "down"];

        $filterShow = 'none';
        $selectedPage = 1;

        return view('admin.customers', compact('selectedPage', 'customers', 'queryParams', 'userNumber', 'anonimNumber', 'totalNumber', 'myArray', 'filterShow', 'pageNumber', 'query', 'order'));
    }

    public function orderWithCustomers($attribute)
    {
        AdminPanelController::checkAdmin();
        if ($attribute == 'orderCount' || $attribute == 'contactCount') {
            $customers = Customer::orderBy('created_at', 'DESC')->get();
        } else {
            $customers = Customer::orderBy($attribute)->get();
        }

        foreach ($customers as $customer) {

            $contact_list = CustomerContact::where('customer_id', '=', $customer->id)->get();

            $contactNumber = 0;
            if ($customer->user_id) {
                foreach ($contact_list as $contact) {
                    if ($contact->customer_list) {
                        $contactNumber++;
                    }
                }
            }

            $salesNumber = 0;
            foreach ($contact_list as $contact) {
                //if (!$contact->customer_list) {
                //    $salesNumber++;
                //}
                $salesNumber = $salesNumber + DB::table('sales')
                        ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                        ->where('deliveries.status', '!=', '4')->where('customer_contact_id', $contact->id)->where('payment_methods', '=', 'OK')->count();
            }

            $customer->contactNumber = $contactNumber;
            $customer->salesNumber = $salesNumber;

        }

        $customers = $customers->toArray();
        if ($attribute == 'orderCount')
            usort($customers, function ($a, $b) {
                return $a['salesNumber'] - $b['salesNumber'];
            });
        $tempArray = [];
        foreach ($customers as $product) {
            array_push($tempArray, (object)$product);
        }
        $customers = $tempArray;

        $queryParams = [];

        $queryParams = (object)['created_at_end' => "", 'created_at' => "", 'name' => "", 'surname' => "", 'user_id' => ""];


        return view('admin.customers', compact('customers', 'queryParams'));
    }

    public function orderWithDescCustomers($attribute)
    {
        AdminPanelController::checkAdmin();
        if ($attribute == 'orderCount' || $attribute == 'contactCount') {
            $customers = Customer::all();
        } else {
            $customers = Customer::orderBy($attribute, 'DESC')->get();
        }


        foreach ($customers as $customer) {

            $contact_list = CustomerContact::where('customer_id', '=', $customer->id)->get();

            $contactNumber = 0;
            if ($customer->user_id) {
                foreach ($contact_list as $contact) {
                    if ($contact->customer_list) {
                        $contactNumber++;
                    }
                }
            }

            $salesNumber = 0;
            foreach ($contact_list as $contact) {
                //if (!$contact->customer_list) {
                //    $salesNumber++;
                //}
                $salesNumber = $salesNumber + DB::table('sales')
                        ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                        ->where('deliveries.status', '!=', '4')->where('customer_contact_id', $contact->id)->where('payment_methods', '=', 'OK')->count();
            }

            $customer->contactNumber = $contactNumber;
            $customer->salesNumber = $salesNumber;

        }
        $queryParams = [];

        $queryParams = (object)['created_at_end' => "", 'created_at' => "", 'name' => "", 'surname' => "", 'user_id' => ""];


        return view('admin.customers', compact('customers', 'queryParams'));
    }

    public function showCustomerContacts()
    {

        AdminPanelController::checkAdmin();
        $contactList = CustomerContact::where('customer_list', '=', 1)->orderBy('created_at', 'DESC')->get();

        $queryParams = [];

        $queryParams = (object)['created_at' => "", 'created_at_end' => "", 'name' => "", 'wanted_delivery_date_end' => "", 'surname' => "", 'customer_id' => "", 'id' => ""];

        $countList = count($contactList);

        return view('admin.customerContacts', compact('contactList', 'queryParams', 'countList'));

    }

    public function filterCustomers(\Illuminate\Http\Request $request)
    {

        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $tempQueryList = [];

        $userNumber = 0;
        $anonimNumber = 0;
        $totalNumber = 0;


        if ($tempObject['domain'] == 'Hepsi') {
            $tempObject['domain'] = $tempObject['domain2'];
        }

        if ($tempObject['created_at_end']) {
            $tempObject['created_at_end'] = logEventController::modifyEndDate(Request::input('created_at_end'));
        }

        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                if ($key == 'created_at' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'customers.' . $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'created_at_end' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'customers.created_at', 'state' => '<', 'value' => $value]);
                } else if ($key == 'domain' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => '(CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END )', 'state' => 'LIKE', 'value' => '%@' . $value . '%']);
                } else if ($value != "" && $key != 'domain2' && $key != 'orderParameter' && $key != 'upOrDown' && $key != 'pagination') {
                    array_push($tempQueryList, (object)['attribute' => 'customers.' . $key, 'state' => '=', 'value' => $value]);
                }
            }
        }

        $QueryString = ' 1 = 1';
        foreach ($tempQueryList as $query) {
            $QueryString = $QueryString . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }

        //$customers = DB::table('customers')
        //    ->leftJoin('users', 'users.id', '=', 'customers.user_id')
        //    ->select('customers.*')
        //    ->whereRaw($QueryString)->orderBy('customers.created_at' , 'DESC')->get();

        $anonimNumber = Customer::whereNull('user_id')->whereRaw($QueryString)->count();
        $userNumber = Customer::whereNotNull('customers.user_id')->whereRaw($QueryString)->count();
        $totalNumber = $userNumber + $anonimNumber;

        $customers = Customer::orderBy('created_at', 'DESC')
            ->select('customers.*',
                DB::raw('(select count(*) from customer_contacts where customer_contacts.customer_id = customers.id  and customer_contacts.customer_list = 1 ) as contactNumber'),
                DB::raw('(select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) as salesNumber'),
                DB::raw('(select status from users where users.id = customers.user_id) as status'),
                DB::raw('(select updated_at from users where users.id = customers.user_id) as userLastLogin'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) as email'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) as updated_at'),
                DB::raw('( select (CASE WHEN users.status IS NULL THEN "Mail" ELSE "FB" END) as status from users where users.id = customers.user_id  ) as status')
            )
            ->whereRaw($QueryString)
            ->get();

        //$customers = Customer::all();

        //foreach ($customers as $customer) {
//
        //    $contact_list = CustomerContact::where('customer_id', '=', $customer->id)->get();
        //    $totalNumber++;
        //    $contactNumber = 0;
        //    if ($customer->user_id) {
        //        $userNumber++;
        //        foreach ($contact_list as $contact) {
        //            if ($contact->customer_list) {
        //                $contactNumber++;
        //            }
        //        }
        //    }else{
        //        $anonimNumber++;
        //    }
//
        //    $salesNumber = 0;
        //    foreach ($contact_list as $contact) {
        //        //if (!$contact->customer_list) {
        //        //    $salesNumber++;
        //        //}
        //        $salesNumber = $salesNumber + DB::table('sales')
        //                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
        //                ->where('deliveries.status' , '!=' , '4')->where('customer_contact_id' ,  $contact->id)->where('payment_methods', '=', 'OK')->count();
        //    }
//
        //    $customer->status = 'Login';
        //    if(!$customer->email){
        //        $mailNMobile = User::where('id' , $customer->user_id)->get()[0];
        //        $customer->email = $mailNMobile->email;
        //        if($mailNMobile->status == 'FB'){
        //            $customer->status = 'FB';
        //        }
        //    }
//
        //    $customer->contactNumber = $contactNumber;
        //    $customer->salesNumber = $salesNumber;
//
        //}

        $myArray = DB::table('company_coupon')->orderBy('mail')->get();

        array_push($myArray, (object)['mail' => 'Hepsi', 'domain' => '0']);
        $queryParams->orderParameter = 'created_at';
        $queryParams->upOrDown = 'desc';
        $queryParams->pagination = 0;

        $selectedPage = "0";

        $filterShow = 'table';

        $pageNumber = 0;

        return view('admin.customers', compact('customers', 'selectedPage', 'queryParams', 'userNumber', 'anonimNumber', 'totalNumber', 'myArray', 'filterShow', 'pageNumber'));
    }

    public function filterCustomerContacts(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $tempQueryList = [];

        if ($tempObject['created_at_end']) {
            $tempObject['created_at_end'] = logEventController::modifyEndDate(Request::input('created_at_end'));
        }

        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                if ($key == 'created_at' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'created_at_end' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'created_at', 'state' => '<', 'value' => $value]);
                } else if ($value != "") {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '=', 'value' => $value]);
                }
            }
        }
        $QueryString = ' customer_list = 1';
        foreach ($tempQueryList as $query) {
            $QueryString = $QueryString . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }
        $contactList = CustomerContact::whereRaw($QueryString)->orderBy('created_at', 'DESC')->get();

        $countList = count($contactList);

        return view('admin.customerContacts', compact('contactList', 'queryParams', 'countList'));
    }

    public function orderAndFilterWithDescDeliveries(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $tempQueryList = [];
        $tempQueryListStudio = [];
        $statusList = [];
        $queryParams->status_all = '';
        $queryParams->status_making = '';
        $queryParams->status_ready = '';
        $queryParams->status_delivering = '';
        $queryParams->status_delivered = '';
        $queryParams->status_cancel = '';

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        if (Request::input('status_all') == "on") {
            $statusList = ['1', '2', '3', '6'];
            $queryParams->status_all = 'on';
        }

        if (Request::input('status_making') == "on") {
            array_push($statusList, '1');
            $queryParams->status_making = 'on';
        }

        if (Request::input('status_ready') == "on") {
            array_push($statusList, '6');
            $queryParams->status_ready = 'on';
        }

        if (Request::input('status_delivering') == "on") {
            array_push($statusList, '2');
            $queryParams->status_delivering = 'on';
        }

        if (Request::input('status_delivered') == "on") {
            array_push($statusList, '3');
            $queryParams->status_delivered = 'on';
        }

        if (Request::input('status_cancel') == "on") {
            array_push($statusList, '4');
            $queryParams->status_cancel = 'on';
        }

        if ($tempObject['created_at_end']) {
            $tempObject['created_at_end'] = logEventController::modifyEndDate(Request::input('created_at_end'));
        }

        if ($tempObject['wanted_delivery_date_end']) {
            $tempObject['wanted_delivery_date_end'] = logEventController::modifyEndDate(Request::input('wanted_delivery_date_end'));
        }

        if ($tempObject['delivery_date_end']) {
            $tempObject['delivery_date_end'] = logEventController::modifyEndDate(Request::input('delivery_date_end'));
        }

        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                if ($key == 'created_at' && $value != "" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'sales.' . $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'created_at_end' && $value != "" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => 'created_at', 'state' => '<', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'sales.created_at', 'state' => '<', 'value' => $value]);
                } else if ($key == 'wanted_delivery_date' && $value != "" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => 'wanted_date', 'state' => '>', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'wanted_delivery_date_end' && $value != "" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => 'wanted_date', 'state' => '<', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'wanted_delivery_date', 'state' => '<', 'value' => $value]);
                } else if ($key == 'delivery_date' && $value != "" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'delivery_date_end' && $value != "" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => 'delivery_date', 'state' => '<', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'delivery_date', 'state' => '<', 'value' => $value]);
                } else if ($key == 'deliveryHour' && $value != "" && $value != "Hepsi" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => 'hour(wanted_date)', 'state' => '=', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'hour(wanted_delivery_date)', 'state' => '=', 'value' => $value]);
                } else if ($key == 'operation_name' && $value != "" && $value != "Hepsi" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => 'operation_name', 'state' => '=', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'operation_name', 'state' => '=', 'value' => $value]);
                }
                //else if ($key == 'status' && $value != ""  && $value != "0") {
                //    array_push($tempQueryList, (object)['attribute' => 'status', 'state' => '=', 'value' => $value]);
                //}
                else if ($value != "Hepsi" && $value != "" && $key != 'status' && $key != 'deliveryHour' && $value != 'on' && $key != 'continents' && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '=', 'value' => $value]);
                }

                if ($key == 'continent_id' && $value != "" && $value != "Hepsi" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => $key, 'state' => '=', 'value' => $value]);
                }

                if ($key == 'products' && $value != "" && $key != 'orderParameter' && $key != 'upOrDown') {
                    array_push($tempQueryListStudio, (object)['attribute' => 'flower_name', 'state' => '=', 'value' => $value]);
                }
            }
        }

        $QueryString = ' 1 = 1';
        foreach ($tempQueryList as $query) {

            //dd($query);

            $QueryString = $QueryString . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }

        if ($tempObject['upOrDown'] == 'up') {
            $deliveryList = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->whereRaw($tempWhere)
                ->whereRaw($QueryString)->orderBy($tempObject['orderParameter'])
                ->whereIn('deliveries.status', $statusList)
                ->whereIn('delivery_locations.continent_id', $queryParams->continents)
                ->select('customers.user_id', DB::raw("'0' as studio"), 'delivery_locations.continent_id', 'sales.receiver_address', 'products.name as product_name', 'sales.isPrintedDelivery', 'sales.isPrintedNote', 'sales.planning_courier_id',
                    'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.id as sale_id', 'deliveries.*', 'delivery_locations.district', 'sales.delivery_not')->get();
            //$deliveryList = Delivery::whereRaw($QueryString)->orderBy($tempObject['orderParameter'])->get();
        } else {
            $deliveryList = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->whereRaw($tempWhere)
                ->whereRaw($QueryString)->orderBy($tempObject['orderParameter'], 'DESC')
                ->whereIn('deliveries.status', $statusList)
                ->whereIn('delivery_locations.continent_id', $queryParams->continents)
                ->select('customers.user_id', DB::raw("'0' as studio"), 'delivery_locations.continent_id', 'sales.receiver_address', 'products.name as product_name', 'sales.isPrintedDelivery', 'sales.isPrintedNote', 'sales.planning_courier_id',
                    'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.id as sale_id', 'deliveries.*', 'delivery_locations.district', 'sales.delivery_not')->get();
            //$deliveryList = Delivery::whereRaw($QueryString)->orderBy($tempObject['orderParameter'] , 'DESC')->get();
        }
        $id = 0;
        $myArray = [];

        $QueryStringStudio = ' 1 = 1';
        foreach ($tempQueryListStudio as $query) {
            $QueryStringStudio = $QueryStringStudio . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }

        $tempStudioBloom = DB::table('studioBloom')->whereRaw($QueryStringStudio)
            ->whereIn('delivery_status', $statusList)
            ->where('status', 'Ödeme Yapıldı')
            ->select(
                'contact_name',
                'contact_surname',
                'customer_name',
                'customer_surname',
                'continent_id',
                'district',
                'receiver_address',
                'id as sale_id',
                'id',
                'wanted_date as wanted_delivery_date',
                'flower_name as product_name',
                'flower_name as products',
                'flower_desc',
                'wanted_date',
                'price',
                'note as delivery_not',
                'delivery_status as status',
                'created_at',
                'payment_date',
                'wanted_delivery_limit',
                'delivery_date',
                'picker',
                'operation_name',
                'email',
                DB::raw("'0' as isPrintedDelivery"),
                DB::raw("'0' as isPrintedNote"),
                DB::raw("'0' as planning_courier_id")
            )->get();
        foreach ($tempStudioBloom as $studio) {
            $studio->studio = 1;
            $studio->user_id = 1;
            array_unshift($deliveryList, (object)$studio);
        }

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($deliveryList as $delivery) {
            $requestDate = new Carbon($delivery->created_at);
            $dateInfo = $requestDate->formatLocalized('%a %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            $delivery->requestDate = $dateInfo;

            if ($delivery->delivery_date == "0000-00-00 00:00:00") {
                $delivery->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($delivery->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $delivery->deliveryDate = $dateInfo;
            }
            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
            $delivery->prime = 0;
            if ($delivery->user_id) {
                if (DB::table('users')->where('id', $delivery->user_id)->where('prime', '>', 0)->count() > 0) {
                    $delivery->prime = 1;
                }
            }

            $delivery->scottyInfo = DB::table('scotty_sales')->where('sale_id', $delivery->sale_id)->get();

            $tempCikolat = AdminPanelController::getCikolatData($delivery->sale_id);

            if ($tempCikolat) {
                $delivery->cikilot = $tempCikolat->name;
            } else
                $delivery->cikilot = "";
        }

        $deliveryHourList = [];

        array_push($deliveryHourList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($deliveryHourList, (object)['information' => '09-13', 'status' => '09:00:00']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00']);

        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 5]);

        $continentList = [];

        $operationList = DB::table('operation_person')->where('active', '=', 1)->orderBy('position')->get();
        array_push($operationList, (object)['name' => 'Hepsi']);

        foreach ($cityList as $city) {
            if ($city->city_id == 1) {
                array_push($continentList, (object)['information' => 'Avrupa', 'status' => 'Avrupa', 'selected' => false]);
                array_push($continentList, (object)['information' => 'Oyaka', 'status' => 'Avrupa-2', 'selected' => false]);
                array_push($continentList, (object)['information' => 'Avrupa-3', 'status' => 'Avrupa-3', 'selected' => false]);
            }
            if ($city->city_id == 2) {
                array_push($continentList, (object)['information' => 'Ankara-1', 'status' => 'Ankara-1', 'selected' => false]);
                array_push($continentList, (object)['information' => 'Ankara-2', 'status' => 'Ankara-2', 'selected' => false]);
            }
            if ($city->city_id == 341) {
                array_push($continentList, (object)['information' => 'Asya', 'status' => 'Asya', 'selected' => false]);
                array_push($continentList, (object)['information' => 'Asya-2', 'status' => 'Asya-2', 'selected' => false]);
            }
        }

        $tempFlagForAny = true;

        foreach ($continentList as $continent) {
            foreach ($queryParams->continents as $selectedContinet) {
                if ($continent->status == $selectedContinet) {
                    $continent->selected = true;
                    $tempFlagForAny = false;
                }
            }
        }

        if ($tempFlagForAny) {
            foreach ($continentList as $continent) {
                $continent->selected = true;
            }
        }

        $operationList = DB::table('operation_person')->select('name', 'id')->get();
        array_push($operationList, (object)['name' => 'Hepsi']);

        //array_push($continentList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        //array_push($continentList, (object)['information' => 'Avrupa', 'status' => 'Avrupa']);
        //array_push($continentList, (object)['information' => 'Asya', 'status' => 'Asya']);
        //array_push($continentList, (object)['information' => 'Oyaka', 'status' => 'Avrupa-2']);

        $countDelivery = count($deliveryList);

        $locationList = DB::table('delivery_locations')->groupBy('small_city')->select('small_city')->get();
        array_push($locationList, (object)['small_city' => 'Hepsi']);

        $filterShow = 'none';

        return view('admin.deliveries', compact('operationList', 'deliveryList', 'id', 'myArray', 'queryParams', 'countDelivery', 'filterShow', 'deliveryHourList', 'continentList', 'locationList'));
    }

    public function orderAndFilterWithDescCustomers(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $tempQueryList = [];

        $userNumber = 0;
        $anonimNumber = 0;
        $totalNumber = 0;

        if ($tempObject['domain'] == 'Hepsi') {
            $tempObject['domain'] = $tempObject['domain2'];
        }

        if ($tempObject['created_at_end']) {
            $tempObject['created_at_end'] = logEventController::modifyEndDate(Request::input('created_at_end'));
        }

        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                if ($key == 'created_at' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'customers.' . $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'created_at_end' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => 'customers.created_at', 'state' => '<', 'value' => $value]);
                } else if ($key == 'domain' && $value != "") {
                    array_push($tempQueryList, (object)['attribute' => '(CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END )', 'state' => 'LIKE', 'value' => '%@' . $value . '%']);
                } else if ($value != "" && $key != 'orderParameter' && $key != 'upOrDown' && $key != 'domain2' && $key != 'pagination') {
                    array_push($tempQueryList, (object)['attribute' => 'customers.' . $key, 'state' => '=', 'value' => $value]);
                }
            }
        }

        $QueryString = ' 1 = 1';
        $tempLimitStart = (Request::input('pagination') - 1) * 500;
        $selectedPage = Request::input('pagination');
        foreach ($tempQueryList as $query) {
            $QueryString = $QueryString . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }

        $anonimNumber = DB::table('customers')
            ->leftJoin('users', 'users.id', '=', 'customers.user_id')
            ->select('customers.*')
            ->whereRaw($QueryString)->where('customers.user_id', '=', null)->count();

        $userNumber = DB::table('customers')
            ->leftJoin('users', 'users.id', '=', 'customers.user_id')
            ->select('customers.*')
            ->whereRaw($QueryString)->whereNotNull('customers.user_id')->count();
        $totalNumber = $userNumber + $anonimNumber;

        $tempOrder = '';
        if ($tempObject['orderParameter'] == 'orderCount') {
            $tempOrder = DB::raw(' (select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) ');
        } else if ($tempObject['orderParameter'] == 'contactCount') {
            $tempOrder = DB::raw(' (select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) ');
        } else if ($tempObject['orderParameter'] == 'status') {
            $tempOrder = DB::raw(' (select status from users where users.id = customers.user_id)');
        } else if ($tempObject['orderParameter'] == 'email') {
            $tempOrder = DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) ');
        } else if ($tempObject['orderParameter'] == 'updated_at') {
            $tempOrder = DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) ');
        } else {
            $tempOrder = $tempObject['orderParameter'];
        }

        if ($tempObject['upOrDown'] == 'up')
            $customers = Customer::orderBy($tempOrder)
                ->skip($tempLimitStart)
                ->take(500)
                ->select('customers.*',
                    DB::raw('(select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) as contactNumber'),
                    DB::raw('(select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) as salesNumber'),
                    DB::raw('(select status from users where users.id = customers.user_id) as status'),
                    DB::raw('(select updated_at from users where users.id = customers.user_id) as userLastLogin'),
                    DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) as email'),
                    DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) as updated_at'),
                    DB::raw('( select (CASE WHEN users.status IS NULL THEN "Mail" ELSE "FB" END) as status from users where users.id = customers.user_id  ) as status')
                )
                ->whereRaw($QueryString)
                ->get();
        else
            $customers = Customer::orderBy($tempOrder, 'DESC')
                ->skip($tempLimitStart)
                ->take(500)
                ->select('customers.*',
                    DB::raw('(select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) as contactNumber'),
                    DB::raw('(select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) as salesNumber'),
                    DB::raw('(select status from users where users.id = customers.user_id) as status'),
                    DB::raw('(select updated_at from users where users.id = customers.user_id) as userLastLogin'),
                    DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) as email'),
                    DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) as updated_at'),
                    DB::raw('( select (CASE WHEN users.status IS NULL THEN "Mail" ELSE "FB" END) as status from users where users.id = customers.user_id  ) as status')
                )
                ->whereRaw($QueryString)
                ->get();


        //if ($tempObject['orderParameter'] == 'orderCount' || $tempObject['orderParameter'] == 'contactCount' || $tempObject['orderParameter'] == 'status' || $tempObject['orderParameter'] == 'contactNumber'   ) {
        //    //$customers = Customer::whereRaw($QueryString)->get();
//
        //    $customers = DB::table('customers')
        //        ->skip($tempLimitStart)->take(200)
        //        ->leftJoin('users', 'users.id', '=', 'customers.user_id')
        //        ->select('customers.*')
        //        ->whereRaw($QueryString)->get();
//
        //} else {
        //    if($tempObject['upOrDown'] == 'up'){
        //        $customers = DB::table('customers')
        //            ->skip($tempLimitStart)->take(200)
        //            ->leftJoin('users', 'users.id', '=', 'customers.user_id')
        //            ->select('customers.*')
        //            ->whereRaw($QueryString)->orderBy($tempObject['orderParameter'])->get();
//
        //        //$customers = Customer::whereRaw($QueryString)->orderBy($tempObject['orderParameter'])->get();
        //    }else{
        //        $customers = DB::table('customers')
        //            ->skip($tempLimitStart)->take(200)
        //            ->leftJoin('users', 'users.id', '=', 'customers.user_id')
        //            ->select('customers.*')
        //            ->whereRaw($QueryString)->orderBy($tempObject['orderParameter'] , 'DESC')->get();
//
        //        //$customers = Customer::whereRaw($QueryString)->orderBy($tempObject['orderParameter'] , 'DESC')->get();
        //    }
        //}


        //foreach ($customers as $customer) {
//
        //    $contact_list = CustomerContact::where('customer_id', '=', $customer->id)->get();
//
        //    $contactNumber = 0;
        //    if ($customer->user_id) {
        //        foreach ($contact_list as $contact) {
        //            if ($contact->customer_list) {
        //                $contactNumber++;
        //            }
        //        }
        //    }
//
        //    $salesNumber = 0;
        //    foreach ($contact_list as $contact) {
        //        //if (!$contact->customer_list) {
        //        //    $salesNumber++;
        //        //}
        //        $salesNumber = $salesNumber + DB::table('sales')
        //                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
        //                ->where('deliveries.status' , '!=' , '4')->where('customer_contact_id' ,  $contact->id)->where('payment_methods', '=', 'OK')->count();
        //    }
        //    $customer->status = '';
        //    if(!$customer->email){
        //        $mailNMobile = User::where('id' , $customer->user_id)->get()[0];
        //        $customer->updated_at = $mailNMobile->updated_at;
        //        $customer->email = $mailNMobile->email;
        //        if($mailNMobile->status == 'FB'){
        //            $customer->status = 'FB';
        //        }
        //        else{
        //            $customer->status = 'Login';
        //        }
        //    }
//
        //    $customer->contactNumber = $contactNumber;
        //    $customer->salesNumber = $salesNumber;
//
        //}
//
        ////dd($customers);
//
        ////$customers = $customers->toArray();
//
        //if($tempObject['upOrDown'] == 'up'){
        //    if ($tempObject['orderParameter'] == 'orderCount')
        //        usort($customers, function ($a, $b) {
        //            return $a->salesNumber - $b->salesNumber;
        //        });
        //    else if($tempObject['orderParameter'] == 'email'){
        //        usort($customers, function ($a, $b) {
        //            return strcasecmp($a->email, $b->email);
        //        });
        //    }
        //    else if($tempObject['orderParameter'] == 'contactNumber'){
        //        usort($customers, function ($a, $b) {
        //            return strcasecmp($a->contactNumber, $b->contactNumber);
        //        });
        //    }
        //    else if($tempObject['orderParameter'] == 'status'){
        //        usort($customers, function ($a, $b) {
        //            return strcasecmp($a->status, $b->status);
        //            //return $b->email >  $a->email;
        //        });
        //    }
        //    else if($tempObject['orderParameter'] == 'updated_at'){
        //        usort($customers, function ($a, $b) {
        //            return strcasecmp($a->updated_at, $b->updated_at);
        //            //return $b->email >  $a->email;
        //        });
        //    }
        //}else{
        //    if ($tempObject['orderParameter'] == 'orderCount')
        //        usort($customers, function ($a, $b) {
        //            return $b->salesNumber - $a->salesNumber;
        //        });
        //    else if($tempObject['orderParameter'] == 'email'){
        //        usort($customers, function ($a, $b) {
        //            return strcasecmp($b->email, $a->email);
        //            //return $b->email >  $a->email;
        //        });
        //    }
        //    else if($tempObject['orderParameter'] == 'contactNumber'){
        //        usort($customers, function ($a, $b) {
        //            return strcasecmp($b->contactNumber, $a->contactNumber);
        //        });
        //    }
        //    else if($tempObject['orderParameter'] == 'status'){
        //        usort($customers, function ($a, $b) {
        //            return strcasecmp($b->status, $a->status);
        //            //return $b->email >  $a->email;
        //        });
        //    }
        //    else if($tempObject['orderParameter'] == 'updated_at'){
        //        usort($customers, function ($a, $b) {
        //            return strcasecmp($b->updated_at, $a->updated_at);
        //            //return $b->email >  $a->email;
        //        });
        //    }
        //}
//
        //$tempArray = [];
        //foreach ($customers as $product) {
        //    array_push($tempArray, (object)$product);
        //}
        //$customers = $tempArray;

        $myArray = DB::table('company_coupon')->orderBy('mail')->get();
        array_push($myArray, (object)['mail' => 'Hepsi', 'domain' => '0']);
        $query = $tempObject['orderParameter'];
        $order = $tempObject['upOrDown'];
        $filterShow = 'none';
        $pageNumber = ($totalNumber / 500) + 1;
        return view('admin.customers', compact('selectedPage', 'customers', 'queryParams', 'userNumber', 'anonimNumber', 'totalNumber', 'myArray', 'filterShow', 'pageNumber', 'query', 'order'));
    }

    public function showAllDeliveries()
    {
        AdminPanelController::checkAdmin();
        return AdminPanelController::showDeliveries(0);
    }

    public function filterDeliveries(\Illuminate\Http\Request $request)
    {
        AdminPanelController::checkAdmin();
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;
        $tempQueryList = [];
        $tempQueryListStudio = [];
        $statusList = [];
        $queryParams->status_all = '';
        $queryParams->status_making = '';
        $queryParams->status_ready = '';
        $queryParams->status_delivering = '';
        $queryParams->status_delivered = '';
        $queryParams->status_cancel = '';

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        //dd($queryParams->continents);

        if (Request::input('status_all') == "on") {
            $statusList = ['1', '2', '3', '6'];
            $queryParams->status_all = 'on';
        }

        if (Request::input('status_making') == "on") {
            array_push($statusList, '1');
            $queryParams->status_making = 'on';
        }

        if (Request::input('status_ready') == "on") {
            array_push($statusList, '6');
            $queryParams->status_ready = 'on';
        }

        if (Request::input('status_delivering') == "on") {
            array_push($statusList, '2');
            $queryParams->status_delivering = 'on';
        }

        if (Request::input('status_delivered') == "on") {
            array_push($statusList, '3');
            $queryParams->status_delivered = 'on';
        }

        if (Request::input('status_cancel') == "on") {
            array_push($statusList, '4');
            $queryParams->status_cancel = 'on';
        }

        if ($tempObject['created_at_end']) {
            $tempObject['created_at_end'] = logEventController::modifyEndDate(Request::input('created_at_end'));
        }

        if ($tempObject['wanted_delivery_date_end']) {
            $tempObject['wanted_delivery_date_end'] = logEventController::modifyEndDate(Request::input('wanted_delivery_date_end'));
        }

        if ($tempObject['delivery_date_end']) {
            $tempObject['delivery_date_end'] = logEventController::modifyEndDate(Request::input('delivery_date_end'));
        }


        foreach ($tempObject as $key => $value) {
            if ($key != '_token') {
                if ($key == 'created_at' && $value != "") {
                    array_push($tempQueryListStudio, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'sales.' . $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'created_at_end' && $value != "") {
                    array_push($tempQueryListStudio, (object)['attribute' => 'created_at', 'state' => '<', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'sales.created_at', 'state' => '<', 'value' => $value]);
                } else if ($key == 'wanted_delivery_date' && $value != "") {
                    array_push($tempQueryListStudio, (object)['attribute' => 'wanted_date', 'state' => '>', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'wanted_delivery_date_end' && $value != "") {
                    array_push($tempQueryListStudio, (object)['attribute' => 'wanted_date', 'state' => '<', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'wanted_delivery_date', 'state' => '<', 'value' => $value]);
                } else if ($key == 'delivery_date' && $value != "") {
                    array_push($tempQueryListStudio, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '>', 'value' => $value]);
                } else if ($key == 'delivery_date_end' && $value != "") {
                    array_push($tempQueryListStudio, (object)['attribute' => 'delivery_date', 'state' => '<', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'delivery_date', 'state' => '<', 'value' => $value]);
                } else if ($key == 'deliveryHour' && $value != "" && $value != "Hepsi") {
                    array_push($tempQueryListStudio, (object)['attribute' => 'hour(wanted_date)', 'state' => '=', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'hour(wanted_delivery_date)', 'state' => '=', 'value' => $value]);
                } else if ($key == 'operation_name' && $value != "" && $value != "Hepsi") {
                    array_push($tempQueryListStudio, (object)['attribute' => 'operation_name', 'state' => '=', 'value' => $value]);
                    array_push($tempQueryList, (object)['attribute' => 'operation_name', 'state' => '=', 'value' => $value]);
                }
                //else if ($key == 'status' && $value != ""  && $value != "0") {
                //    array_push($tempQueryList, (object)['attribute' => 'status', 'state' => '=', 'value' => $value]);
                //}
                else if ($value != "Hepsi" && $value != "" && $key != 'status' && $key != 'deliveryHour' && $key != 'continents' && $value != 'on') {
                    array_push($tempQueryList, (object)['attribute' => $key, 'state' => '=', 'value' => $value]);
                }

                //if($key == 'continent_id' && $value != ""  && $value != "Hepsi"){
                //    array_push($tempQueryListStudio, (object)['attribute' =>  $key, 'state' => '=', 'value' => $value]);
                //}

                if ($key == 'products' && $value != "") {
                    array_push($tempQueryListStudio, (object)['attribute' => 'flower_name', 'state' => '=', 'value' => $value]);
                }
            }
        }
        $QueryString = ' 1 = 1';
        foreach ($tempQueryList as $query) {
            $QueryString = $QueryString . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }

        $QueryStringStudio = ' 1 = 1';
        foreach ($tempQueryListStudio as $query) {
            $QueryStringStudio = $QueryStringStudio . ' and ' . $query->attribute . ' ' . $query->state . ' ' . "'" . $query->value . "'";
        }
        //$deliveryList = Delivery::whereRaw($QueryString)->get();

        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->whereRaw($tempWhere)
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'products.name as product_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname',
                'sales.isPrintedDelivery', 'sales.isPrintedNote', 'sales.planning_courier_id',
                DB::raw("'0' as studio"), 'sales.id as sale_id', 'deliveries.*', 'sales.receiver_address', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->whereRaw($QueryString)
            ->whereIn('deliveries.status', $statusList)
            ->whereIn('delivery_locations.continent_id', $queryParams->continents)
            ->orderBy('sales.created_at', 'DESC')
            ->get();

        $id = 0;
        $myArray = [];

        $tempStudioBloom = DB::table('studioBloom')->whereRaw($QueryStringStudio)
            ->whereIn('delivery_status', $statusList)
            ->where('status', 'Ödeme Yapıldı')
            ->select(
                'contact_name',
                'contact_surname',
                'customer_name',
                'customer_surname',
                'continent_id',
                'district',
                'receiver_address',
                'id as sale_id',
                'id',
                'wanted_date as wanted_delivery_date',
                'flower_name as product_name',
                'flower_name as products',
                'flower_desc',
                'wanted_date',
                'price',
                'note as delivery_not',
                'delivery_status as status',
                'created_at',
                'payment_date',
                'wanted_delivery_limit',
                'delivery_date',
                'picker',
                'operation_name',
                'email',
                DB::raw("'0' as isPrintedDelivery"),
                DB::raw("'0' as isPrintedNote"),
                DB::raw("'0' as planning_courier_id")
            )->get();
        foreach ($tempStudioBloom as $studio) {
            $studio->user_id = 0;
            $studio->studio = 1;
            array_unshift($deliveryList, (object)$studio);
        }

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($deliveryList as $delivery) {
            $requestDate = new Carbon($delivery->created_at);
            $dateInfo = $requestDate->formatLocalized('%a %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            $delivery->requestDate = $dateInfo;

            if ($delivery->delivery_date == "0000-00-00 00:00:00") {
                $delivery->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($delivery->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $delivery->deliveryDate = $dateInfo;
            }

            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
            $delivery->prime = 0;
            if ($delivery->user_id) {
                if (DB::table('users')->where('id', $delivery->user_id)->where('prime', '>', 0)->count() > 0) {
                    $delivery->prime = 1;
                }
            }

            $tempCikolat = AdminPanelController::getCikolatData($delivery->sale_id);

            if ($tempCikolat) {
                $delivery->cikilot = $tempCikolat->name;
            } else
                $delivery->cikilot = "";

            $delivery->scottyInfo = DB::table('scotty_sales')->where('sale_id', $delivery->sale_id)->get();
        }

        $deliveryHourList = [];

        array_push($deliveryHourList, (object)['information' => 'Hepsi', 'status' => 'Hepsi']);
        array_push($deliveryHourList, (object)['information' => '09-13', 'status' => '09:00:00']);
        array_push($deliveryHourList, (object)['information' => '11-17', 'status' => '11:00:00']);
        array_push($deliveryHourList, (object)['information' => '13-18', 'status' => '13:00:00']);
        array_push($deliveryHourList, (object)['information' => '18-21', 'status' => '18:00:00']);
        array_push($deliveryHourList, (object)['information' => '12-16', 'status' => '12:00:00']);

        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 0]);

        $continentList = [];

        $operationList = DB::table('operation_person')->where('active', '=', 1)->orderBy('position')->get();
        array_push($operationList, (object)['name' => 'Hepsi']);

        foreach ($cityList as $city) {
            if ($city->city_id == 1) {
                array_push($continentList, (object)['information' => 'Avrupa', 'status' => 'Avrupa', 'selected' => false]);
                array_push($continentList, (object)['information' => 'Oyaka', 'status' => 'Avrupa-2', 'selected' => false]);
                array_push($continentList, (object)['information' => 'Avrupa-3', 'status' => 'Avrupa-3', 'selected' => false]);
            }
            if ($city->city_id == 2) {
                array_push($continentList, (object)['information' => 'Ankara-1', 'status' => 'Ankara-1', 'selected' => false]);
                array_push($continentList, (object)['information' => 'Ankara-2', 'status' => 'Ankara-2', 'selected' => false]);
            }
            if ($city->city_id == 341) {
                array_push($continentList, (object)['information' => 'Asya', 'status' => 'Asya', 'selected' => false]);
                array_push($continentList, (object)['information' => 'Asya-2', 'status' => 'Asya-2', 'selected' => false]);
            }
        }

        $tempFlagForAny = true;

        foreach ($continentList as $continent) {
            foreach ($queryParams->continents as $selectedContinet) {
                if ($continent->status == $selectedContinet) {
                    $continent->selected = true;
                    $tempFlagForAny = false;
                }
            }
        }

        if ($tempFlagForAny) {
            foreach ($continentList as $continent) {
                $continent->selected = true;
            }
        }

        $countDelivery = count($deliveryList);

        $locationList = DB::table('delivery_locations')->groupBy('small_city')->whereRaw($tempWhere)->select('small_city')->get();
        array_push($locationList, (object)['small_city' => 'Hepsi']);

        $filterShow = 'table';

        return view('admin.deliveries', compact('operationList', 'deliveryList', 'id', 'myArray', 'queryParams', 'countDelivery', 'filterShow', 'deliveryHourList', 'continentList', 'locationList'));
    }

    public function orderWithDescDeliveries($attribute)
    {
        AdminPanelController::checkAdmin();
        $id = 0;
        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->select('delivery_locations.continent_id', 'sales.receiver_address', 'products.name as product_name', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.id as sale_id', 'deliveries.*', 'delivery_locations.district')
            ->orderBy($attribute, 'DESC')->get();
        $myArray = [];
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        foreach ($deliveryList as $delivery) {
            $requestDate = new Carbon($delivery->created_at);
            $dateInfo = $requestDate->formatLocalized('%A %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            $delivery->requestDate = $dateInfo;

            if ($delivery->delivery_date == "0000-00-00 00:00:00") {
                $delivery->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($delivery->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $delivery->deliveryDate = $dateInfo;
            }

            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
        }
        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 0]);
        return view('admin.deliveries', compact('deliveryList', 'id', 'myArray'));
    }

    public function showDeliveries($id)
    {
        AdminPanelController::checkAdmin();
        $deliveryList = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address',
                DB::raw("'0' as studio"), 'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->orderBy('sales.created_at', 'DESC')
            ->get();
        $myArray = [];
        $queryParams = [];
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        $tempStudioBloom = DB::table('studioBloom')->where('status', 'Ödeme Yapıldı')
            ->select(
                'contact_name',
                'contact_surname',
                'customer_name',
                'customer_surname',
                'continent_id',
                'district',
                'receiver_address',
                'id as sale_id',
                'id',
                'wanted_date as wanted_delivery_date',
                'flower_name as product_name',
                'flower_name as products',
                'flower_desc',
                'wanted_date',
                'price',
                'note as delivery_not',
                'delivery_status as status',
                'created_at',
                'payment_date',
                'wanted_delivery_limit',
                'delivery_date',
                'picker',
                'operation_name',
                'email'
            )->get();
        foreach ($tempStudioBloom as $studio) {
            $studio->studio = 1;
            $studio->user_id = 1;
            array_unshift($deliveryList, (object)$studio);
        }

        foreach ($deliveryList as $delivery) {
            $requestDate = new Carbon($delivery->created_at);
            $dateInfo = $requestDate->formatLocalized('%a %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
            $delivery->requestDate = $dateInfo;

            if ($delivery->delivery_date == "0000-00-00 00:00:00") {
                $delivery->deliveryDate = "----";
            } else {
                $deliveryDate = new Carbon($delivery->delivery_date);
                $dateInfo = $deliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);
                $delivery->deliveryDate = $dateInfo;
            }


            $wantedDeliveryDate = new Carbon($delivery->wanted_delivery_date);
            $wantedDeliveryDateEnd = new Carbon($delivery->wanted_delivery_limit);
            $dateInfo = $wantedDeliveryDate->formatLocalized('%a %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
            $delivery->wantedDeliveryDate = $dateInfo;
            $delivery->prime = 0;
            if ($delivery->user_id) {
                if (DB::table('users')->where('id', $delivery->user_id)->where('prime', '>', 0)->count() > 0) {
                    $delivery->prime = 1;
                }
            }

            $tempCikolat = AdminPanelController::getCikolatData($delivery->sale_id);

            if ($tempCikolat) {
                $delivery->cikilot = $tempCikolat->name;
            } else
                $delivery->cikilot = "";
        }

        $queryParams = (object)['operation_name' => "Hepsi", 'created_at' => "", 'created_at_end' => "", 'products' => "", 'wanted_delivery_date_end' => "", 'wanted_delivery_date' => "", 'delivery_date_end' => "", 'delivery_date' => "", 'status' => "", 'deliveryHour' => "", 'continent_id' => "",
            'status_all' => "on", 'status_making' => "", 'status_ready' => "", 'status_delivering' => "", 'status_delivered' => "", 'status_cancel' => "", 'small_city' => "Hepsi"
        ];

        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 0]);

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

        $operationList = DB::table('operation_person')->get();
        array_push($operationList, (object)['name' => 'Hepsi']);

        $countDelivery = count($deliveryList);

        $locationList = DB::table('delivery_locations')->groupBy('small_city')->select('small_city')->get();
        array_push($locationList, (object)['small_city' => 'Hepsi']);

        $filterShow = 'none';

        return view('admin.deliveries', compact('operationList', 'deliveryList', 'id', 'myArray', 'queryParams', 'countDelivery', 'filterShow', 'deliveryHourList', 'continentList', 'locationList'));
    }

    public function deleteDetailImage($imageId, $productId)
    {

        AdminPanelController::checkAdmin();
        //$imageId = Request::input('imageId');
        try {
            Image::destroy([$imageId]);
        } catch (\Exception $e) {
            DB::rollback();
            return "Resim silmede hata!";
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();

        return redirect('/admin/products/detail/' . $productId);

    }

    public function detailSale($id)
    {
        AdminPanelController::checkAdmin();
        $sales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('billings', 'sales.id', '=', 'billings.sales_id')
            ->select('sales.created_at', 'sales.card_message', 'sales.delivery_notification', 'sales.receiver', 'sales.sender', 'sales.sum_total', 'sales.sum_total', 'sales.delivery_notification',
                'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_mobile as contact_mobile', 'sales.receiver_address as contact_address',
                'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'sales.sender_mobile as customer_mobile', 'sales.sender_email as customer_email',
                'products.name as product_name', 'deliveries.operation_name',
                'delivery_locations.district as location_name', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit',
                'billings.billing_send', 'billings.city', 'billings.small_city', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_type'
            )
            ->where('sales.id', $id)
            ->get();

        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $requestDate = new Carbon($sales[0]->created_at);
        $dateInfo = $requestDate->formatLocalized('%A %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
        $sales[0]->created_at = $dateInfo;

        $wantedDeliveryDate = new Carbon($sales[0]->wanted_delivery_date);
        $wantedDeliveryDateEnd = new Carbon($sales[0]->wanted_delivery_limit);
        $dateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . $wantedDeliveryDate->hour . ' - ' . $wantedDeliveryDateEnd->hour;
        $sales[0]->wanted_delivery_date = $dateInfo;

        $locationList = DB::table('delivery_locations')->get();

        $productList = DB::table('products')->get();

        return view('admin.detailSale', compact('sales', 'locationList', 'productList'));
    }

    public function detailDelivery($id)
    {
        AdminPanelController::checkAdmin();
        $id = Delivery::where('id', $id)->get()[0]->sales_id;
        $sales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('billings', 'sales.id', '=', 'billings.sales_id')
            ->select('sales.created_at', 'sales.card_message', 'sales.delivery_notification', 'sales.receiver', 'sales.sender', 'sales.sum_total', 'sales.id as salesId ',
                'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_mobile as contact_mobile', 'sales.receiver_address as contact_address',
                'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'sales.sender_mobile as customer_mobile', 'sales.sender_email as customer_email',
                'products.name as product_name', 'sales.delivery_not', 'deliveries.wanted_delivery_date as wanted_delivery_date_temp', 'deliveries.operation_name', 'deliveries.picker',
                'delivery_locations.district as location_name', 'deliveries.wanted_delivery_date', 'deliveries.wanted_delivery_limit', 'delivery_locations.city_id', 'delivery_locations.city_id',
                'billings.billing_send', 'billings.city', 'billings.small_city', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_type', 'sales.IsTroyCard'
            )
            ->where('sales.id', $id)
            ->get();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $requestDate = new Carbon($sales[0]->created_at);
        $dateInfo = $requestDate->formatLocalized('%A %d %b') . ' | ' . str_pad($requestDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($requestDate->minute, 2, '0', STR_PAD_LEFT);
        $sales[0]->created_at = $dateInfo;
        //$sales[0]->wanted_delivery_date_temp = $sales[0]->wanted_delivery_date;
        $wantedDeliveryDate = new Carbon($sales[0]->wanted_delivery_date);
        $wantedDeliveryDateEnd = new Carbon($sales[0]->wanted_delivery_limit);
        $dateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
        $sales[0]->wanted_delivery_date = $dateInfo;

        $locationList = DB::table('delivery_locations')->where('city_id', $sales[0]->city_id)->get();

        $productList = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->where('product_city.city_id', $sales[0]->city_id)->orderBy('products.name')->select('products.*')->get();

        $crossSellProductList = DB::table('cross_sell_products')->where('city_id', $sales[0]->city_id)->orderBy('name')->get();

        $deliveryHourList = [];

        $tempCikolat = AdminPanelController::getCikolatData($sales[0]->salesId);

        if ($tempCikolat) {
            $sales[0]->cikolatName = $tempCikolat->name;
            $sales[0]->cikolatImage = $tempCikolat->image;
            $sales[0]->sum_total = str_replace('.', ',', floatval(str_replace(',', '.', $sales[0]->sum_total)) + floatval(str_replace(',', '.', $tempCikolat->total_price)));
        } else {
            $sales[0]->cikolatName = '';
            $sales[0]->cikolatImage = '';
        }

        if ($sales[0]->IsTroyCard) {

            $sales[0]->sum_total = str_replace('.', ',', number_format(floatval(str_replace(',', '.', $sales[0]->sum_total)) - 30.00, 2));

        }

        array_push($deliveryHourList, (object)['information' => ' 09 - 13', 'status' => '09:00:00-13:00:00']);
        array_push($deliveryHourList, (object)['information' => ' 11 - 17', 'status' => '11:00:00-17:00:00']);
        array_push($deliveryHourList, (object)['information' => ' 13 - 18', 'status' => '13:00:00-18:00:00']);
        array_push($deliveryHourList, (object)['information' => ' 18 - 21', 'status' => '18:00:00-20:00:00']);
        array_push($deliveryHourList, (object)['information' => ' 12 - 16', 'status' => '12:00:00-16:00:00']);

        return view('admin.detailSale', compact('sales', 'locationList', 'productList', 'deliveryHourList', 'crossSellProductList'));
    }

    public function checkSale2()
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $before = Carbon::now();
        $before->addSecond(-1000);
        $tempText = "";

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $count = DB::table('sales')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('payment_methods', '=', 'OK')
            ->where('sales.created_at', '>', $before)
            ->whereRaw($tempWhere)
            ->count();

        dd($count);

        if ($count > 0) {
            $tempDelivery = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->whereRaw($tempWhere)
                ->select('deliveries.wanted_delivery_date', 'deliveries.products', 'delivery_locations.district')
                ->orderBy('sales.created_at', 'DESC')
                ->limit(1)
                ->get()[0];
            $tempDate = new Carbon($tempDelivery->wanted_delivery_date);
            $tempDelivery->wanted_delivery_date = $tempDate->formatLocalized('%A');

            $today = Carbon::now();
            $today->hour(00);
            $today->minute(00);
            $count = DB::table('sales')
                ->where('payment_methods', '=', 'OK')
                ->where('created_at', '>', $today)
                ->count();
            $tempText = "Heeeyy! Sipariş geldi! Hey ! " . $tempDelivery->products . " " . $tempDelivery->wanted_delivery_date . " günü " . $tempDelivery->district . " bölgesine gidiyor. Sipariş sayısı " . $count;
            //$logs = DB::table('bglogs')->where('created_at', $today)
        }
        //$billing->time = $now->formatLocalized('%d %B');
        return response()->json(["status" => 1, "new" => $count, "readingText" => $tempText], 200);
    }

    public function checkSale()
    {
        AdminPanelController::checkAdmin();
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');
        $before = Carbon::now();
        $before->addSecond(-40);
        $tempText = "";

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or delivery_locations.city_id = ' . $city->city_id;
        }
        $tempWhere = $tempWhere . ' ) ';

        $count = DB::table('sales')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->where('payment_methods', '=', 'OK')
            ->where('sales.created_at', '>', $before)
            ->whereRaw($tempWhere)
            ->count();

        if ($count > 0) {
            $tempDelivery = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                ->where('sales.payment_methods', '=', 'OK')
                ->whereRaw($tempWhere)
                ->select('deliveries.wanted_delivery_date', 'deliveries.products', 'delivery_locations.district')
                ->orderBy('sales.created_at', 'DESC')
                ->limit(1)
                ->get()[0];
            $tempDate = new Carbon($tempDelivery->wanted_delivery_date);
            $tempDelivery->wanted_delivery_date = $tempDate->formatLocalized('%A');

            $today = Carbon::now();
            $today->hour(00);
            $today->minute(00);
            $count = DB::table('sales')
                ->where('payment_methods', '=', 'OK')
                ->where('created_at', '>', $today)
                ->count();
            $tempText = "Heeeyy! Sipariş geldi! Hey ! " . $tempDelivery->products . " " . $tempDelivery->wanted_delivery_date . " günü " . $tempDelivery->district . " bölgesine gidiyor. Sipariş sayısı " . $count;
            //$logs = DB::table('bglogs')->where('created_at', $today)
        }
        //$billing->time = $now->formatLocalized('%d %B');
        return response()->json(["status" => 1, "new" => $count, "readingText" => $tempText], 200);
    }

    public function setDeliveryNote()
    {
        AdminPanelController::checkAdmin();
        DB::table('sales')->where('id', Request::get('id'))->update([
            'delivery_not' => Request::get('note')
        ]);
        return response()->json(["status" => Request::get('id'), "note" => Request::get('note'), "data" => Request::all()], 200);
    }

    public function sendStudioBloomMail($id)
    {
        $tempData = DB::table('studioBloom')->where('id', $id)->where('payment_mail', 0)->select('id', 'customer_name', 'price', 'flower_name', 'flower_desc', 'email')->get()[0];

        \MandrillMail::messages()->sendTemplate('BNF_StudioBloom_PaymentMail', null, array(
            'html' => '<p>Example HTML content</p>',
            'text' => 'Studiobloom Ödeme Bilgilendirme Maili',
            'subject' => 'Studio Bloom Ödeme Bilgilendirme Maili',
            'from_email' => 'hello@bloomandfresh.com',
            'from_name' => 'Studio Bloom',
            'to' => array(
                array(
                    'email' => $tempData->email,
                    'type' => 'to'
                )
            ),
            'merge' => true,
            'merge_language' => 'mailchimp',
            'global_merge_vars' => array(
                array(
                    'name' => 'customer',
                    'content' => ucwords(strtolower($tempData->customer_name))
                ), array(
                    'name' => 'link',
                    'content' => $this->site_url . '/studioBloom-odeme-sayfasi?orderId=' . $tempData->id
                ), array(
                    'name' => 'price',
                    'content' => $tempData->price . ' TL + KDV'
                ), array(
                    'name' => 'product',
                    'content' => $tempData->flower_name
                ), array(
                    'name' => 'desc',
                    'content' => $tempData->flower_desc
                )
            )
        ));

        DB::table('studioBloom')->where('id', $id)->update([
            'payment_mail' => 1
        ]);

        return redirect('/admin/studioBloom/updateDetail/' . $id);
    }

    public function studioBloomUpdateDetail($id)
    {
        AdminPanelController::checkAdmin();

        $tempStudioBloom = DB::table('studioBloom')
            ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
            ->where('studioBloom.id', $id)
            ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.billing_surname', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile')->get()[0];
        return view('admin.studioBloomUpdateDetail', compact('tempStudioBloom'));
    }

    public function updateStudioBloomSale()
    {

        $wantedDeliveryDateStart = new Carbon(Request::input('wanted_delivery_date'));
        $wantedDeliveryDateLimit = new Carbon(Request::input('wanted_delivery_date'));
        //dd(Request::input('wanted_delivery_date_1'));
        $wantedDeliveryDateStart->hour(Request::input('wanted_delivery_date_1'));
        $wantedDeliveryDateStart->minute(0);
        $wantedDeliveryDateStart->second(0);

        $wantedDeliveryDateLimit->hour(Request::input('wanted_delivery_date_2'));
        $wantedDeliveryDateLimit->minute(0);
        $wantedDeliveryDateLimit->second(0);

        $nameSurName = logEventController::splitNameSurname(Request::get('billing_name'));
        $tempStatus = DB::table('studioBloom')->where('id', Request::input('id'))->get()[0]->status;

        if (Request::input('status') == 'Ödeme Bekleniyor') {
            $paymentDate = null;
        } else {
            $paymentDate = new Carbon(Request::input('payment_date'));
            $paymentDate->hour(Request::input('payment_date_hour'));
            $paymentDate->minute(Request::input('payment_date_min'));
            $paymentDate->second(0);
        }

        DB::table('studioBloom')->where('id', Request::input('id'))->update([
            'email' => Request::input('email'),
            'customer_name' => Request::input('customer_name'),
            'contact_name' => Request::input('contact_name'),
            'flower_name' => Request::input('flower_name'),
            'flower_desc' => Request::input('flower_desc'),
            'continent_id' => Request::input('continent_id'),
            'district' => Request::input('district'),
            'receiver_address' => Request::input('receiver_address'),
            'wanted_date' => $wantedDeliveryDateStart,
            'wanted_delivery_limit' => $wantedDeliveryDateLimit,
            'price' => Request::input('price'),
            'note' => Request::input('note'),
            'customer_mobile' => Request::input('customer_mobile'),
            'picker' => Request::input('picker'),
            'operation_name' => Request::input('operation_name'),
            'status' => Request::input('status'),
            'payment_type' => Request::input('payment_type'),
            'payment_date' => $paymentDate
        ]);
        DB::table('studio_billings')->where('sales_id', Request::input('id'))->update([
            'billing_send' => Request::has('billing_send'),
            'billing_name' => $nameSurName[0],
            'billing_surname' => $nameSurName[1],
            'city' => Request::input('city'),
            'small_city' => Request::input('small_city'),
            'company' => Request::input('company'),
            'billing_address' => Request::input('billing_address'),
            'tax_office' => Request::input('tax_office'),
            'tax_no' => Request::input('tax_no'),
            'billing_type' => Request::input('billing_type'),
            'tc' => Request::input('tc'),
            'userBilling' => Request::has('billing_send')
        ]);
        if ($tempStatus != 'Ödeme Yapıldı' && Request::input('status') == 'Ödeme Yapıldı') {
            BillingOperation::studioBillingSend(Request::input('id'));
        }
        return redirect('/admin/studioBloomList');
    }

    public function setStudioDeliveryNote()
    {
        AdminPanelController::checkAdmin();
        DB::table('studioBloom')->where('id', Request::get('id'))->update([
            'note' => Request::get('note')
        ]);
        return response()->json(["status" => Request::get('id'), "note" => Request::get('note'), "data" => Request::all()], 200);
    }

//    public function staticContents($id)
//    {
//        $staticContents = StaticContent::all();
//
//        //return $staticContents;
//        return view('admin.static-content', compact('staticContents', 'id'));
//    }
//
//    public function storeContent( \Illuminate\Http\Request $request)
//    {
//        $this->validate( $request, [ 'name' => 'required|min:3|max:30', 'content' => 'required'] );
//
//        $input = $request->all();
//
//        if(isset($input['id'])) {
//            $input['id'] = (int)$input['id'];
//            $sc = StaticContent::find($input['id']);
//            $sc->update($input);
//        }
//        else {
//            $sc = StaticContent::create($input);
//        }
//
//        return redirect('/static-contents/0#row-id-'.$sc->id);
//    }
//
//    public function deleteContent()
//    {
//        $id = Request::input('id');
//
//        //dd($id);
//
//        StaticContent::destroy([$id]);
//        return redirect('/static-contents/0');
//    }
}
/*public function showCustomersNew()
    {
        AdminPanelController::checkAdmin();
        $customers = Customer::orderBy('created_at', 'DESC')
            ->select('customers.*',
                DB::raw('(select count(*) from customer_contacts where customer_contacts.customer_id = customers.id and customer_contacts.customer_list = 1 ) as contactNumber'),
                DB::raw('(select count(*) from sales
                inner join deliveries on sales.id = deliveries.sales_id
                inner join customer_contacts on sales.customer_contact_id = customer_contacts.id
                where deliveries.status != 4 and customer_contacts.customer_id = customers.id and sales.payment_methods = "OK"
                ) as salesNumber'),
                DB::raw('(select status from users where users.id = customers.user_id) as status'),
                DB::raw('(select updated_at from users where users.id = customers.user_id) as userLastLogin'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.email ELSE (select email from users where users.id = customers.user_id) END ) as email'),
                DB::raw(' (CASE  WHEN customers.user_id IS NULL THEN customers.updated_at ELSE (select updated_at from users where users.id = customers.user_id) END ) as updated_at'),
                DB::raw('( select (CASE WHEN users.status IS NULL THEN "Mail" ELSE "FB" END) as status from users where users.id = customers.user_id  ) as status')
            )
            ->get();
        //dd($customers[0]);
        $anonimNumber = Customer::where('user_id', '=', null)->count();
        $userNumber = User::count();
        $totalNumber = $userNumber + $anonimNumber;

        retur*/