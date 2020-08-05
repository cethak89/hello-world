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

class upsController extends Controller
{
    public function checkAdmin()
    {
        if (\Auth::user()->user_group_id == 3) {
            \Auth::logout();
            dd('yetkiniz yok');
        }
    }

    public function modifyCities(){

        $cities = DB::table('ups_cities')->get();

        return view('admin.upsCities', compact('cities'));
    }

    public function updateUpsDeliveryStatus(\Illuminate\Http\Request $request){

        $input = $request->all();

        if( Delivery::where('id' , $input['id'])->get()[0]->status == '4' && $input['status'] != '4' ){

            $productData = DB::table('deliveries')
                ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->where('deliveries.id' , $input['id'])
                ->select( 'sales_products.products_id', 'delivery_locations.city_id', 'sales.id' )
                ->get()[0];

            $productData->city_id = 1;

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
            
            $productData->city_id = 1;

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

        Delivery::where('id', '=', $input['id'])->update([
            'status' => $input['status']
        ]);
        return response()->json([ "success" => 1, "status" => $input['status'] , "id" => $input['id'] ], 200);
    }

    public function updateUpsCities(){
        //dd(Request::all());

        $tempAll = Request::all();

        //dd($tempAll);

        DB::table('ups_cities')->where('value', '!=' , 'ist' )->where('value', '!=' , 'ank' )->update([
            'active' => 0
        ]);

        foreach ( $tempAll as $key => $tempField ){

            $tempArray = explode('_', $key);


            if( $tempArray[0] == 'active' ){

                DB::table('ups_cities')->where('id', $tempArray[1] )->update([
                    'active' => 1
                ]);

            }

        }

        return redirect('/admin/ups/modifyCities');
    }

    public function generateUPSCities()
    {
        $dummyCities = [
            (object)[
                "value" => "ist",
                "name" => "İstanbul"
            ],
            (object)[
                "value" => "ank",
                "name" => "Ankara"
            ],
            (object)[
                "value" => "İzmir",
                "name" => "İzmir"
            ],
            (object)[
                "value" => "Bursa",
                "name" => "Bursa"
            ],
            (object)[
                "value" => "Adana",
                "name" => "Adana"
            ],
            (object)[
                "value" => "Adıyaman",
                "name" => "Adıyaman"
            ],
            (object)[
                "value" => "Afyon",
                "name" => "Afyon"
            ],
            (object)[
                "value" => "Ağrı",
                "name" => "Ağrı"
            ],
            (object)[
                "value" => "Aksaray",
                "name" => "Aksaray"
            ],
            (object)[
                "value" => "Amsaya",
                "name" => "Amsaya"
            ],
            (object)[
                "value" => "Antalya",
                "name" => "Antalya"
            ],
            (object)[
                "value" => "Ardahan",
                "name" => "Ardahan"
            ],
            (object)[
                "value" => "Artvin",
                "name" => "Artvin"
            ],
            (object)[
                "value" => "Aydın",
                "name" => "Aydın"
            ],
            (object)[
                "value" => "Balıkesir",
                "name" => "Balıkesir"
            ],
            (object)[
                "value" => "Bartın",
                "name" => "Bartın"
            ],
            (object)[
                "value" => "Batman",
                "name" => "Batman"
            ],
            (object)[
                "value" => "Bayburt",
                "name" => "Bayburt"
            ],
            (object)[
                "value" => "Bilecik",
                "name" => "Bilecik"
            ],
            (object)[
                "value" => "Bingöl",
                "name" => "Bingöl"
            ],
            (object)[
                "value" => "Bitlis",
                "name" => "Bitlis"
            ],
            (object)[
                "value" => "Bolu",
                "name" => "Bolu"
            ],
            (object)[
                "value" => "Burdur",
                "name" => "Burdur"
            ],
            (object)[
                "value" => "Çanakkale",
                "name" => "Çanakkale"
            ],
            (object)[
                "value" => "Çankırı",
                "name" => "Çankırı"
            ],
            (object)[
                "value" => "Çorum",
                "name" => "Çorum"
            ],
            (object)[
                "value" => "Denizli",
                "name" => "Denizli"
            ],
            (object)[
                "value" => "Diyarbakır",
                "name" => "Diyarbakır"
            ],
            (object)[
                "value" => "Düzce",
                "name" => "Düzce"
            ],
            (object)[
                "value" => "Edirne",
                "name" => "Edirne"
            ],
            (object)[
                "value" => "Elazığ",
                "name" => "Elazığ"
            ],
            (object)[
                "value" => "Erzincan",
                "name" => "Erzincan"
            ],
            (object)[
                "value" => "Erzurum",
                "name" => "Erzurum"
            ],
            (object)[
                "value" => "Eskişehir",
                "name" => "Eskişehir"
            ],
            (object)[
                "value" => "Gaziantap",
                "name" => "Gaziantap"
            ],
            (object)[
                "value" => "Giresun",
                "name" => "Giresun"
            ],
            (object)[
                "value" => "Gümüşhane",
                "name" => "Gümüşhane"
            ],
            (object)[
                "value" => "Hakkari",
                "name" => "Hakkari"
            ],
            (object)[
                "value" => "Hatay",
                "name" => "Hatay"
            ],
            (object)[
                "value" => "Iğdır",
                "name" => "Iğdır"
            ],
            (object)[
                "value" => "Isparta",
                "name" => "Isparta"
            ],
            (object)[
                "value" => "Kahramanmaraş",
                "name" => "Kahramanmaraş"
            ],
            (object)[
                "value" => "Karabük",
                "name" => "Karabük"
            ],
            (object)[
                "value" => "Karaman",
                "name" => "Karaman"
            ],
            (object)[
                "value" => "Kars",
                "name" => "Kars"
            ],
            (object)[
                "value" => "Kastamonu",
                "name" => "Kastamonu"
            ],
            (object)[
                "value" => "Kayseri",
                "name" => "Kayseri"
            ],
            (object)[
                "value" => "Kıbrıs",
                "name" => "Kıbrıs"
            ],
            (object)[
                "value" => "Kilis",
                "name" => "Kilis"
            ],
            (object)[
                "value" => "Kırıkkale",
                "name" => "Kırıkkale"
            ],
            (object)[
                "value" => "Kırklareli",
                "name" => "Kırklareli"
            ],
            (object)[
                "value" => "Kırşehir",
                "name" => "Kırşehir"
            ],
            (object)[
                "value" => "Kocaeli",
                "name" => "Kocaeli"
            ],
            (object)[
                "value" => "Konya",
                "name" => "Konya"
            ],
            (object)[
                "value" => "Kütahya",
                "name" => "Kütahya"
            ],
            (object)[
                "value" => "Malatya",
                "name" => "Malatya"
            ],
            (object)[
                "value" => "Manisa",
                "name" => "Manisa"
            ],
            (object)[
                "value" => "Mardin",
                "name" => "Mardin"
            ],
            (object)[
                "value" => "Mersin",
                "name" => "Mersin"
            ],
            (object)[
                "value" => "Muğla",
                "name" => "Muğla"
            ],
            (object)[
                "value" => "Muş",
                "name" => "Muş"
            ],
            (object)[
                "value" => "Nevşehir",
                "name" => "Nevşehir"
            ],
            (object)[
                "value" => "Niğde",
                "name" => "Niğde"
            ],
            (object)[
                "value" => "Ordu",
                "name" => "Ordu"
            ],
            (object)[
                "value" => "Osmaniye",
                "name" => "Osmaniye"
            ],
            (object)[
                "value" => "Rize",
                "name" => "Rize"
            ],
            (object)[
                "value" => "Sakarya",
                "name" => "Sakarya"
            ],
            (object)[
                "value" => "Samsun",
                "name" => "Samsun"
            ],
            (object)[
                "value" => "Şanlıurfa",
                "name" => "Şanlıurfa"
            ],
            (object)[
                "value" => "Siirt",
                "name" => "Siirt"
            ],
            (object)[
                "value" => "Sinop",
                "name" => "Sinop"
            ],
            (object)[
                "value" => "Şırnak",
                "name" => "Şırnak"
            ],
            (object)[
                "value" => "Sivas",
                "name" => "Sivas"
            ],
            (object)[
                "value" => "Tekirdağ",
                "name" => "Tekirdağ"
            ],
            (object)[
                "value" => "Tokat",
                "name" => "Tokat"
            ],
            (object)[
                "value" => "Trabzon",
                "name" => "Trabzon"
            ],
            (object)[
                "value" => "Tunceli",
                "name" => "Tunceli"
            ],
            (object)[
                "value" => "Uşak",
                "name" => "Uşak"
            ],
            (object)[
                "value" => "Van",
                "name" => "Van"
            ],
            (object)[
                "value" => "Yalova",
                "name" => "Yalova"
            ],
            (object)[
                "value" => "Yozgat",
                "name" => "Yozgat"
            ],
            (object)[
                "value" => "Zonguldak",
                "name" => "Zonguldak"
            ]
        ];

        foreach ( $dummyCities as $city ){

            DB::table('ups_cities')->insert([
                'value' => $city->value,
                'name' => $city->name
            ]);

        }

        dd($dummyCities);
    }

    public function statusPage()
    {

        $status = DB::table('ups_active')->where('name', 'active')->get()[0];


        return view('admin.upsStatusPage', compact('status'));
    }

    public function updateUpsStatus()
    {

        //dd(Request::input('status'));

        if (Request::input('status') == '0' || Request::input('status') == '1') {
            $tempStatus = 1;
        } else {
            $tempStatus = 0;
        }

        DB::table('ups_active')->where('id', Request::input('id'))->update([
            'status' => $tempStatus
        ]);

        return redirect('/admin/ups/status');
    }

    public function updateUpsRelatedCity()
    {
        //dd(Request::all());
        DB::table('sales')->where('id', Request::input('id'))->update([
            'related_city_id' => Request::input('relatedId')
        ]);
    }

    public function updateUpsDelivery()
    {


        if (Request::input('changingVariable') == "Ürün Adı") {
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

        return redirect('/admin/ups/detail/' . $deliveryId);
    }

    public function detailUps($id)
    {
        $id = Delivery::where('id', $id)->get()[0]->sales_id;
        $sales = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('billings', 'sales.id', '=', 'billings.sales_id')
            ->select('sales.created_at', 'sales.card_message', 'sales.delivery_notification', 'sales.receiver', 'sales.sender', 'sales.sum_total', 'sales.id as salesId ', 'sales.related_city_id',
                'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_mobile as contact_mobile', 'sales.receiver_address as contact_address',
                'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'sales.sender_mobile as customer_mobile', 'sales.sender_email as customer_email',
                'products.name as product_name', 'sales.delivery_not', 'deliveries.wanted_delivery_date as wanted_delivery_date_temp', 'deliveries.operation_name', 'deliveries.picker',
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
        //$sales[0]->wanted_delivery_date_temp = $sales[0]->wanted_delivery_date;
        $wantedDeliveryDate = new Carbon($sales[0]->wanted_delivery_date);
        $wantedDeliveryDateEnd = new Carbon($sales[0]->wanted_delivery_limit);
        $dateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ' - ' . str_pad($wantedDeliveryDateEnd->hour, 2, '0', STR_PAD_LEFT);
        $sales[0]->wanted_delivery_date = $dateInfo;

        $locationList = DB::table('delivery_locations')->where('continent_id', 'Ups')->orderBy('district')->get();

        $productList = DB::table('products')->orderBy('name')->get();

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

        $sales[0]->ShipmentNoArray = DB::table('ups_sales')->where('sale_id', $id)->get();

        $upsProcess = DB::table('ups_delivery_detail')->where('sale_id', $id)->get();

        array_push($deliveryHourList, (object)['information' => ' 09 - 13', 'status' => '09:00:00-13:00:00']);
        array_push($deliveryHourList, (object)['information' => ' 11 - 17', 'status' => '11:00:00-17:00:00']);
        array_push($deliveryHourList, (object)['information' => ' 13 - 18', 'status' => '13:00:00-18:00:00']);
        array_push($deliveryHourList, (object)['information' => ' 18 - 21', 'status' => '18:00:00-20:00:00']);
        array_push($deliveryHourList, (object)['information' => ' 12 - 16', 'status' => '12:00:00-16:00:00']);

        return view('admin.upsDetail', compact('sales', 'locationList', 'productList', 'deliveryHourList', 'upsProcess'));
    }

    public function updateUpsLocation()
    {
        $tempActive = 0;
        if (Request::input('active')) {
            $tempActive = 1;
        }
        DB::table('delivery_locations')->where('id', Request::input('location_id'))->update(
            [
                'active' => $tempActive
            ]
        );

        DB::table('ups_locations')->where('city_code', DB::table('ups_locations')->where('delivery_location_id', Request::input('location_id'))->get()[0]->city_code)->update([
            'related_city_id' => Request::input('related_city_id')
        ]);

        return redirect('/admin/ups/locations');

    }

    public function getDeliveryLocation()
    {

        $tempLocations = DB::table('delivery_locations')
            ->join('ups_locations', 'delivery_locations.id', '=', 'ups_locations.delivery_location_id')
            ->where('continent_id', 'Ups')
            ->select('delivery_locations.*', 'ups_locations.related_city_id')
            ->get();

        return view('admin.upsLocations', compact('tempLocations'));
    }

    public function sendDelivery(\Illuminate\Http\Request $request)
    {

        $tempObject = $request->all();
        $tempIds = [];

        foreach ($tempObject as $key => $value) {
            if (explode('_', $key)[0] == 'selected') {
                array_push($tempIds, explode('_', $key)[1]);
            }
        }

        ini_set("soap.wsdl_cache_enabled", "0");

        $loginString = '<Login_Type1 xmlns="http://ws.ups.com.tr/wsCreateShipment">
      <CustomerNumber>3018WY</CustomerNumber>
      <UserName>ymuebfRgaJGTTLLuTeS7</UserName>
      <Password>FJTzNTb8nkdeZDHWFU3C?</Password>
    </Login_Type1>';

        $wsdl = "http://ws.ups.com.tr/wsCreateShipment/wsCreateShipment.asmx?wsdl";
        $client = new SoapClient($wsdl, array(
            'soap_version' => SOAP_1_2,
            'trace' => true,
        ));
        $args = array(new \SoapVar($loginString, XSD_ANYXML));

        $loginInfo = $client->__soapCall('Login_Type1', $args);

        foreach ($tempIds as $id) {

            $saleInfo = DB::table('sales')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                ->join('ups_locations', 'delivery_locations.id', '=', 'ups_locations.delivery_location_id')
                ->where('sales.id', $id)
                ->select('sales.sender_name', 'sales.sender_surname', 'customer_contacts.surname as customer_surname', 'customer_contacts.name as customer_name', 'sales.sender_email', 'sales.receiver_mobile', 'sales.sender_name', 'sales.sender_surname', 'sales.receiver_address', 'sales.id', 'products.name', 'ups_locations.area_code', 'ups_locations.city_code')->get()[0];

            ini_set("soap.wsdl_cache_enabled", "0");

            $loginString = '<CreateShipment_Type1 xmlns="http://ws.ups.com.tr/wsCreateShipment">
      <SessionID>' . $loginInfo->Login_Type1Result->SessionID . '</SessionID>
      <ShipmentInfo>
        <ShipperAccountNumber>3018WY</ShipperAccountNumber>
        <ShipperName>BLOOM AND FRESH</ShipperName>
        <ShipperContactName>' . $saleInfo->sender_name . ' ' . $saleInfo->sender_surname . '</ShipperContactName>
        <ShipperAddress>BOYACICESME SOKAK.NO:12 EMIRGAN - SARIYER</ShipperAddress>
        <ShipperCityCode>34</ShipperCityCode>
        <ShipperAreaCode>451</ShipperAreaCode>
        <ShipperPhoneNumber>212 212 02 82</ShipperPhoneNumber>
        <ShipperEMail>info@bloomandfresh.com</ShipperEMail>
        <ConsigneeName>' . $saleInfo->customer_name . ' ' . $saleInfo->customer_surname . '</ConsigneeName>
        <ConsigneeAddress>' . $saleInfo->receiver_address . '</ConsigneeAddress>
        <ConsigneeCityCode>' . $saleInfo->city_code . '</ConsigneeCityCode>
        <ConsigneeAreaCode>' . $saleInfo->area_code . '</ConsigneeAreaCode>
        <ConsigneePhoneNumber>' . $saleInfo->receiver_mobile . '</ConsigneePhoneNumber>
        <ServiceLevel>3</ServiceLevel>
        <PaymentType>2</PaymentType>
        <PackageType>K</PackageType>
        <NumberOfPackages>1</NumberOfPackages>
        <CustomerReferance>' . $saleInfo->id . '</CustomerReferance>
        <DescriptionOfGoods>' . str_replace('&', '&amp;', $saleInfo->name)  . '</DescriptionOfGoods>
        <DeliveryNotificationEmail>' . $saleInfo->sender_email . '</DeliveryNotificationEmail>
      </ShipmentInfo>
      <ReturnLabelLink>1</ReturnLabelLink>
      <ReturnLabelImage>1</ReturnLabelImage>
    </CreateShipment_Type1>';

            $wsdl = "http://ws.ups.com.tr/wsCreateShipment/wsCreateShipment.asmx?wsdl";
            $client = new SoapClient($wsdl, array(
                'soap_version' => SOAP_1_2,
                'trace' => true,
            ));
            $args = array(new \SoapVar($loginString, XSD_ANYXML));

            $res = $client->__soapCall('CreateShipment_Type1', $args);

            //dd($res);

            if (DB::table('ups_sales')->where('sale_id', $saleInfo->id)->count() > 0) {

                DB::table('deliveries')->where('sales_id', $saleInfo->id )->update([
                    'status' => 1
                ]);

                DB::table('ups_sales')->where('sale_id', $saleInfo->id)->update([
                    'ShipmentNo' => $res->CreateShipment_Type1Result->ShipmentNo,
                    'status' => 0,
                    'status_desc' => '',
                    'LinkForLabelPrinting' => $res->CreateShipment_Type1Result->LinkForLabelPrinting,
                    'BarkodArrayPng' => $res->CreateShipment_Type1Result->BarkodArrayPng->string,
                    'ErrorCode' => $res->CreateShipment_Type1Result->ErrorCode,
                    'ErrorDefinition' => $res->CreateShipment_Type1Result->ErrorDefinition
                ]);

                //dd($res->CreateShipment_Type1Result->BarkodArrayPng->string);
            } else {

                DB::table('deliveries')->where('sales_id', $saleInfo->id )->update([
                    'status' => 1
                ]);

                DB::table('ups_sales')->insert([
                    'sale_id' => $saleInfo->id,
                    'ShipmentNo' => $res->CreateShipment_Type1Result->ShipmentNo,
                    'status' => 0,
                    'status_desc' => '',
                    'LinkForLabelPrinting' => $res->CreateShipment_Type1Result->LinkForLabelPrinting,
                    'BarkodArrayPng' => $res->CreateShipment_Type1Result->BarkodArrayPng->string,
                    'ErrorCode' => $res->CreateShipment_Type1Result->ErrorCode,
                    'ErrorDefinition' => $res->CreateShipment_Type1Result->ErrorDefinition
                ]);
            }
        }

        return redirect('/admin/ups/today');
    }

    public function loginMethod()
    {
        ini_set("soap.wsdl_cache_enabled", "0");

        $loginString = '<Login_Type1 xmlns="http://ws.ups.com.tr/wsCreateShipment">
      <CustomerNumber>3018WY</CustomerNumber>
      <UserName>ymuebfRgaJGTTLLuTeS7</UserName>
      <Password>FJTzNTb8nkdeZDHWFU3C?</Password>
    </Login_Type1>';

        $wsdl = "http://ws.ups.com.tr/wsCreateShipment/wsCreateShipment.asmx?wsdl";
        $client = new SoapClient($wsdl, array(
            'soap_version' => SOAP_1_2,
            'trace' => true,
        ));
        $args = array(new \SoapVar($loginString, XSD_ANYXML));

        $res = $client->__soapCall('Login_Type1', $args);

        dd($res);
    }

    public function printPage(\Illuminate\Http\Request $request)
    {

        $tempObject = $request->all();
        $tempIds = [];

        foreach ($tempObject as $key => $value) {
            if (explode('_', $key)[0] == 'selected') {
                array_push($tempIds, explode('_', $key)[1]);
            }
        }

        $tempQueryList = [];

        foreach ($tempIds as $id) {
            $tempSale = DB::table('ups_sales')->where('sale_id', $id)->get()[0];
            array_push($tempQueryList, $tempSale);
        }

        return view('admin.upsPrint', compact('tempQueryList'));
    }

    public function upsFilter(\Illuminate\Http\Request $request)
    {
        $tempObject = $request->all();
        $queryParams = (object)$tempObject;

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

        //dd($queryParams);

        $tempWhere = ' ( 1 = 1  ';

        if (Request::input('created_at')) {
            $tempWhere = $tempWhere . ' and sales.created_at > "' . Request::input('created_at') . '" ';
        }
        if (Request::input('created_at_end')) {
            $tempDate = new Carbon(Request::input('created_at_end'));
            $tempWhere = $tempWhere . ' and sales.created_at < "' . $tempDate->addDay(1) . '" ';
        }
        if (Request::input('products')) {
            $tempWhere = $tempWhere . ' and products.name like "%' . Request::input('products') . '%" ';
        }
        if (Request::input('wanted_delivery_date')) {
            $tempWhere = $tempWhere . ' and deliveries.wanted_delivery_date > "' . Request::input('wanted_delivery_date') . '" ';
        }
        if (Request::input('wanted_delivery_date_end')) {
            $tempDate = new Carbon(Request::input('wanted_delivery_date_end'));
            $tempWhere = $tempWhere . ' and deliveries.wanted_delivery_limit < "' . $tempDate->addDay(1) . '" ';
        }
        if (Request::input('delivery_date')) {
            $tempWhere = $tempWhere . ' and deliveries.delivery_date > "' . Request::input('delivery_date') . '" ';
        }
        if (Request::input('delivery_date_end')) {
            $tempDate = new Carbon(Request::input('delivery_date_end'));
            $tempWhere = $tempWhere . ' and deliveries.delivery_date < "' . $tempDate->addDay(1) . '" ';
        }

        $tempWhere = $tempWhere . ' )';

        upsController::checkAdmin();

        $id = 0;

        $today = Carbon::now();
        $today->startOfDay();

        $todayEnd = Carbon::now();
        $todayEnd->endOfDay();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        $tempWhereCityId = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhereCityId = $tempWhereCityId . ' or sales.related_city_id = ' . $city->city_id;
        }
        $tempWhereCityId = $tempWhereCityId . ' ) ';

        $deliveryList = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('sales.ups', 1)
            ->whereRaw($tempWhere)
            ->whereRaw($tempWhereCityId)
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address',
                DB::raw("'0' as studio"), 'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->orderBy('sales.created_at', 'DESC')
            ->get();
        $myArray = [];
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        $filteredList = [];

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

            $upsInfo = DB::table('ups_sales')->where('sale_id', $delivery->sale_id)->get();

            if (count($upsInfo) > 0) {
                $delivery->upsStatus = $upsInfo[0]->status;
                $delivery->ups_id = $upsInfo[0]->ShipmentNo;
                $delivery->upsDetail = $upsInfo[0]->status_desc;
            } else {
                $delivery->upsDetail = '';
                $delivery->upsStatus = '';
                $delivery->ups_id = '';
            }

            if (Request::input('status_all') == "on") {

            } else {

                if (Request::input('status_making') == "on") {
                    if (count($upsInfo) == 0 && $delivery->status != 4) {
                        array_push($filteredList, $delivery);
                    }
                }

                if (Request::input('status_ready') == "on") {
                    if (count($upsInfo) > 0) {
                        if ($upsInfo[0]->status == 0 && $delivery->status != 4) {
                            array_push($filteredList, $delivery);
                        }
                    }
                }

                if (Request::input('status_delivering') == "on") {
                    if (count($upsInfo) > 0) {
                        if ($upsInfo[0]->status != 0 && $upsInfo[0]->status != 2 && $delivery->status != 4) {
                            array_push($filteredList, $delivery);
                        }
                    }
                }

                if (Request::input('status_delivered') == "on") {
                    if (count($upsInfo) > 0) {
                        if ($upsInfo[0]->status == 2 && $delivery->status != 4) {
                            array_push($filteredList, $delivery);
                        }
                    }
                }

                if (Request::input('status_cancel') == "on") {
                    if ($delivery->status == 4) {
                        array_push($filteredList, $delivery);
                    }
                }

            }

        }

        if (Request::input('status_all') == "on") {
            $filteredList = $deliveryList;
        }

        /*$queryParams = (object)[
            'created_at' => "",
            'created_at_end' => "",
            'products' => "",
            'wanted_delivery_date_end' => explode(' ' , $todayEnd)[0],
            'wanted_delivery_date' => explode(' ' , $today)[0],
            'delivery_date_end' => "",
            'delivery_date' => "",
            'status' => "" ,
            'status_all' => "on",
            'status_making' => "",
            'status_ready' => "",
            'status_delivering' => "",
            'status_delivered' => "",
            'status_cancel' => ""
        ];*/

        //dd($queryParams);

        array_push($myArray, (object)['information' => 'Hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Hazır.', 'status' => 6]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);

        $countDelivery = count($deliveryList);

        $locationList = DB::table('delivery_locations')->groupBy('small_city')->select('small_city')->get();
        array_push($locationList, (object)['small_city' => 'Hepsi']);
        //dd($locationList);

        $filterShow = 'none';

        return view('admin.upsSales', compact('filteredList', 'id', 'myArray', 'queryParams', 'countDelivery', 'filterShow', 'locationList'));

    }

    public function showTodayUps()
    {
        upsController::checkAdmin();

        $id = 0;

        $today = Carbon::now();
        $today->startOfDay();

        $todayEnd = Carbon::now();
        $todayEnd->endOfDay();

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();

        $tempWhereCityId = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhereCityId = $tempWhereCityId . ' or sales.related_city_id = ' . $city->city_id;
        }
        $tempWhereCityId = $tempWhereCityId . ' ) ';

        $filteredList = DB::table('sales')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
            ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
            ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
            ->where('sales.payment_methods', '=', 'OK')
            ->where('deliveries.wanted_delivery_date', '>', $today)
            ->where('deliveries.wanted_delivery_limit', '<', $todayEnd)
            ->where('sales.ups', 1)
            ->whereRaw($tempWhereCityId)
            ->select('customers.user_id', 'sales.sender_name as customer_name', 'sales.sender_surname as customer_surname', 'customer_contacts.name as contact_name', 'customer_contacts.surname as contact_surname', 'sales.receiver_address',
                DB::raw("'0' as studio"), 'sales.id as sale_id', 'deliveries.*', 'products.name as product_name', 'delivery_locations.district', 'sales.delivery_not', 'delivery_locations.continent_id')
            ->orderBy('sales.created_at', 'DESC')
            ->get();
        $myArray = [];
        $queryParams = [];
        setlocale(LC_TIME, "");
        setlocale(LC_ALL, 'tr_TR.utf8');

        foreach ($filteredList as $delivery) {
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

            $upsInfo = DB::table('ups_sales')->where('sale_id', $delivery->sale_id)->get();

            if (count($upsInfo) > 0) {
                $delivery->upsStatus = $upsInfo[0]->status;
                $delivery->ups_id = $upsInfo[0]->ShipmentNo;
                $delivery->upsDetail = $upsInfo[0]->status_desc;
            } else {
                $delivery->upsDetail = '';
                $delivery->upsStatus = '';
                $delivery->ups_id = '';
            }
        }

        $queryParams = (object)['operation_name' => "Hepsi", 'created_at' => "", 'created_at_end' => "", 'products' => "", 'wanted_delivery_date_end' => explode(' ', $todayEnd)[0], 'wanted_delivery_date' => explode(' ', $today)[0], 'delivery_date_end' => "", 'delivery_date' => "", 'status' => "", 'deliveryHour' => "", 'continent_id' => "",
            'status_all' => "on", 'status_making' => "", 'status_ready' => "", 'status_delivering' => "", 'status_delivered' => "", 'status_cancel' => "", 'small_city' => "Hepsi"
        ];


        array_push($myArray, (object)['information' => 'Hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Hazır.', 'status' => 6]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);

        $countDelivery = count($filteredList);

        $filterShow = 'none';

        return view('admin.upsSales', compact('operationList', 'filteredList', 'id', 'myArray', 'queryParams', 'countDelivery', 'filterShow'));

    }

    public function sendUpsDelivery()
    {

        ini_set("soap.wsdl_cache_enabled", "0");

        $loginString = '<Login_Type1 xmlns="http://ws.ups.com.tr/wsCreateShipment">
      <CustomerNumber>3018WY</CustomerNumber>
      <UserName>ymuebfRgaJGTTLLuTeS7</UserName>
      <Password>FJTzNTb8nkdeZDHWFU3C?</Password>
    </Login_Type1>';

        $wsdl = "http://ws.ups.com.tr/wsCreateShipment/wsCreateShipment.asmx?wsdl";
        $client = new SoapClient($wsdl, array(
            'soap_version' => SOAP_1_2,
            'trace' => true,
        ));
        $args = array(new \SoapVar($loginString, XSD_ANYXML));

        $res = $client->__soapCall('Login_Type1', $args);

        //dd($res);

        ini_set("soap.wsdl_cache_enabled", "0");

        $loginString = '<CreateShipment_Type1 xmlns="http://ws.ups.com.tr/wsCreateShipment">
      <SessionID>' . $res->Login_Type1Result->SessionID . '</SessionID>
      <ShipmentInfo>
        <ShipperAccountNumber>3018WY</ShipperAccountNumber>
        <ShipperName>IF GIRISIM VE TEKNOLOJI A.S. (TR)</ShipperName>
        <ShipperContactName>BAHAR KUREKLI</ShipperContactName>
        <ShipperAddress>BOYACICESME SOKAK.NO:12 EMIRGAN - SARIYER</ShipperAddress>
        <ShipperCityCode>34</ShipperCityCode>
        <ShipperAreaCode>451</ShipperAreaCode>
        <ShipperPhoneNumber>212 212 02 82</ShipperPhoneNumber>
        <ShipperEMail>bahar@bloomandfresh.com</ShipperEMail>
        <ConsigneeName>Hakan ÇETİN</ConsigneeName>
        <ConsigneeAddress>Kemal Pasa Mahallesi menekse sokak etc.</ConsigneeAddress>
        <ConsigneeCityCode>34</ConsigneeCityCode>
        <ConsigneeAreaCode>439</ConsigneeAreaCode>
        <ConsigneePhoneNumber>5313347389</ConsigneePhoneNumber>
        <ServiceLevel>3</ServiceLevel>
        <PaymentType>2</PaymentType>
        <PackageType>K</PackageType>
        <NumberOfPackages>1</NumberOfPackages>
      </ShipmentInfo>
      <ReturnLabelLink>1</ReturnLabelLink>
      <ReturnLabelImage>1</ReturnLabelImage>
    </CreateShipment_Type1>';

        $wsdl = "http://ws.ups.com.tr/wsCreateShipment/wsCreateShipment.asmx?wsdl";
        $client = new SoapClient($wsdl, array(
            'soap_version' => SOAP_1_2,
            'trace' => true,
        ));
        $args = array(new \SoapVar($loginString, XSD_ANYXML));

        $res = $client->__soapCall('CreateShipment_Type1', $args);

        //dd($res);

        DB::table('ups_sales')->insert([
            'sale_id' => 123123,
            'ShipmentNo' => $res->CreateShipment_Type1Result->ShipmentNo,
            'status' => 0,
            'status_desc' => '',
            'LinkForLabelPrinting' => $res->CreateShipment_Type1Result->LinkForLabelPrinting,
            'ErrorCode' => $res->CreateShipment_Type1Result->ErrorCode,
            'ErrorDefinition' => $res->CreateShipment_Type1Result->ErrorDefinition
        ]);

    }

    public function updateUpsProcess()
    {
        ini_set("soap.wsdl_cache_enabled", "0");

        $loginString = '<Login_Type1 xmlns="http://ws.ups.com.tr/wsCreateShipment">
      <CustomerNumber>3018WY</CustomerNumber>
      <UserName>ymuebfRgaJGTTLLuTeS7</UserName>
      <Password>FJTzNTb8nkdeZDHWFU3C?</Password>
    </Login_Type1>';

        $wsdl = "http://ws.ups.com.tr/wsCreateShipment/wsCreateShipment.asmx?wsdl";
        $client = new SoapClient($wsdl, array(
            'soap_version' => SOAP_1_2,
            'trace' => true,
        ));
        $args = array(new \SoapVar($loginString, XSD_ANYXML));

        $loginInfo = $client->__soapCall('Login_Type1', $args);

        //dd($res);

        $salesNotCompleted = DB::table('ups_sales')->where('completed', '0')->get();
        $changedSales = [];

        foreach ($salesNotCompleted as $sale) {

            ini_set("soap.wsdl_cache_enabled", "0");

            $loginString = '<IslemSorguTumHareketler_V1 xmlns="http://ws.ups.com.tr/PaketIslemSorgulari">
            <OturumNo>' . $loginInfo->Login_Type1Result->SessionID . '</OturumNo>
            <BilgiSeviyesi>1</BilgiSeviyesi>
            <TakipNo>' . $sale->ShipmentNo . '</TakipNo>
        </IslemSorguTumHareketler_V1>';

            $wsdl = "http://ws.ups.com.tr/PaketIslemSorgulari/wsPaketBilgileri.asmx?wsdl";
            $client = new SoapClient($wsdl, array(
                'soap_version' => SOAP_1_2,
                'trace' => true,
            ));
            $args = array(new \SoapVar($loginString, XSD_ANYXML));

            $res = $client->__soapCall('IslemSorguTumHareketler_V1', $args);

            $processes = simplexml_load_string($res->IslemSorguTumHareketler_V1Result->any)->NewDataSet->Islemler;

            //dd(simplexml_load_string($res->IslemSorguTumHareketler_V1Result->any)->NewDataSet);

            if (count($processes) > 0) {
                if ($processes[0]->HataKodu == 13 && count($processes) == 1) {

                } else {
                    foreach ($processes as $process) {
                        if (DB::table('ups_delivery_detail')->where('ShipmentNo', $sale->ShipmentNo)->where('KayitNo', $process->KayitNo)->count() == 0) {

                            $tempIslemZamani = $process->IslemZamani;

                            $tempIslemZamani = explode('-', $tempIslemZamani);

                            $tempDate = Carbon::now();

                            $tempDate->year(mb_substr($tempIslemZamani[0], 0, 4))->month(mb_substr($tempIslemZamani[0], 4, 2))->day(mb_substr($tempIslemZamani[0], 6, 2))->hour(mb_substr($tempIslemZamani[1], 0, 2))->minute(mb_substr($tempIslemZamani[1], 2, 2))->second(mb_substr($tempIslemZamani[1], 4, 2))->toDateTimeString();

                            DB::table('ups_delivery_detail')->insert([
                                'sale_id' => $sale->sale_id,
                                'ShipmentNo' => $sale->ShipmentNo,
                                'IslemZamani' => $tempDate,
                                'IslemiYapanSube' => $process->IslemiYapanSube,
                                'DurumKodu' => $process->DurumKodu,
                                'Islem' => $process->Islem,
                                'IslemAciklama' => $process->IslemAciklama,
                                'KayitNo' => $process->KayitNo,
                                'BilgiSeviyesi' => $process->BilgiSeviyesi,
                                'HataKodu' => $process->HataKodu
                            ]);

                            array_push($changedSales, $sale->sale_id);

                        }
                    }
                }
            }

        }

        foreach ($changedSales as $sale) {

            $tempDetailStatus = DB::table('ups_delivery_detail')->where('sale_id', $sale)->orderBy('IslemZamani', 'DESC')->take(1)->get()[0];

            if (DB::table('ups_sales')->where('sale_id', $sale)->get()[0]->status == 0 && $tempDetailStatus->DurumKodu != 0) {

                $sales = DB::table('deliveries')
                    ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->where('sales.id', $sale)
                    ->select('sales_products.products_id', 'customers.user_id as user_id', 'sales.sender_email as email', 'sales.sender_name as FNAME', 'sales.sender_surname as LNAME', 'sales.sum_total as PRICE', 'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME'
                        , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD', 'deliveries.products as PRNAME', 'sales.lang_id', 'sales.id as sale_id')
                    ->get()[0];

                if (!$sales->email) {
                    $sales->email = User::where('id', $sales->user_id)->get()[0]->email;
                }

                $tempMailSubjectName = "Teslimat Aşamasında!";


                \MandrillMail::messages()->sendTemplate('siparis_yola_cikti_ekstre_urun', null, array(
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
                            'content' => ''
                        )
                    )
                ));
                Delivery::where('sales_id', '=', $sale)->update([
                    'status' => 2,
                    'operation_id' => 'UPS',
                    'operation_name' => 'UPS'
                ]);
            }

            if (DB::table('ups_sales')->where('sale_id', $sale)->get()[0]->status != 2 && $tempDetailStatus->DurumKodu == 2) {

                $sales = DB::table('deliveries')
                    ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->where('sales.id', $sale)
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
                            'content' => ucwords(strtolower($tempDetailStatus->IslemAciklama))
                        ), array(
                            'name' => 'EKSTRA_URUN_NOTE',
                            'content' => ''
                        ), array(
                            'name' => 'EKSTRA_URUN_NAME',
                            'content' => ''
                        )
                    )
                ));
                Delivery::where('sales_id', '=', $sale)->update([
                    'delivery_date' => $deliveryDate,
                    'status' => 3,
                    'operation_id' => 'UPS',
                    'operation_name' => 'UPS'
                ]);
            }

            DB::table('ups_sales')->where('sale_id', $sale)->update([
                'status' => $tempDetailStatus->DurumKodu,
                'status_desc' => $tempDetailStatus->IslemAciklama
            ]);
        }
    }
}