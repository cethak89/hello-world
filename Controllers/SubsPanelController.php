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

class SubsPanelController extends Controller
{
    //public  $site_url = 'https://bloomandfresh.com';
    public $site_url = 'http://188.166.86.116';
    public $backend_url = 'http://188.166.86.116:3000';

    //public $backend_url = 'https://everybloom.com';

    public function getFailSubsInfo($id){
        try {
            $subSales = DB::table('subs_sales')->where('id', $id)->get()[0];

            $firstDay = DB::table('subs_first_date')->where('id', $subSales->first_day_id)->get();

            setlocale(LC_TIME, "");
            setlocale(LC_ALL, 'tr_TR.utf8');
            foreach ($firstDay as $day){
                $now = Carbon::now();
                $firstDelivery = Carbon::now();
                $firstDeliveryEnd = Carbon::now();

                if ($now->dayOfWeek >= $day->number_of_week) {
                    $firstDelivery->addDays($day->number_of_week - $now->dayOfWeek + 7);
                } else {
                    $firstDelivery->addDays($day->number_of_week - $now->dayOfWeek);
                }
                $day->dayName = $firstDelivery->formatLocalized('%d %B, %A');;
            }

            $locationData = DB::table('delivery_locations')->where('id', $subSales->location_id)->get()[0];

            $freqData = DB::table('subs_freq')->where('id', $subSales->freq_id)->get()[0];

            $hours = DB::table('subs_hours')->where('id', $subSales->hour_id)->get();

            foreach ($hours as $subsHour){
                $subsHour->hourName = $subsHour->start_hour . ':00 - ' . $subsHour->end_hour . ':00';
            }

            $flower = DB::table('subs_products')->where('id', $subSales->flower_id)->get()[0];

            return response()->json(["status" => 1, "sales" => $subSales, "firstDay" => $firstDay[0], "freqData" => $freqData, "hour" => $hours[0], "flower" => $flower , 'extraProduct' => $subSales->cup_status, 'locationData' => $locationData ], 200);

        } catch (\Exception $e) {
            return redirect()->away($this->site_url);
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
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

        $tempOperationInfo = DB::table('operation_person')->where('name', $tempOperationPerson)->get()[0];

        foreach ($tempIds as $id) {

            DB::table('subs_delivery')->where('id', '=',$id->id)->update([
                'operation_id' => $tempOperationInfo->id,
                'delivery_status' => 2,
                'operation_name' => $tempOperationInfo->name
            ]);

        }

        return redirect('/admin/subs/deliveries');
    }

    public function printDelivery(\Illuminate\Http\Request $request)
    {
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
                $deliveryList = DB::table('subs_sales')->join('users', 'subs_sales.user_id', '=', 'users.id')->join('subs_delivery', 'subs_sales.id', '=', 'subs_delivery.sale_id')
                    ->join('delivery_locations', 'subs_sales.location_id', '=', 'delivery_locations.id')
                    ->where('subs_delivery.id', '=', explode('_', $key)[1])
                    ->select('subs_sales.flower_name as products', 'subs_delivery.delivery_date_start as wanted_delivery_date', 'subs_delivery.delivery_date_end as wanted_delivery_limit'
                        , DB::raw("'0' as studio"), 'subs_delivery.contact_address as address', 'subs_delivery.contact_name', 'subs_delivery.contact_mobile as mobile',
                        'delivery_locations.district', 'users.name', 'users.surname', 'subs_delivery.id', 'subs_delivery.delivery_note'
                    )
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

        return view('admin.subs.document', compact('tempQueryList'));
    }

    public function setDeliveryStatus()
    {
        DB::table('subs_delivery')->where('id', Request::get('id'))->update([
            'delivery_note' => Request::get('note')
        ]);
        return response()->json(["status" => Request::get('id'), "note" => Request::get('note'), "data" => Request::all()], 200);
    }

    public function updateDeliveryStatus(\Illuminate\Http\Request $request)
    {
        $dateInfo = "";
        $input = $request->all();
        if ($input['status'] == 3) {
            $limitDate = new Carbon($input['delivery_date']);
            $limitDate->hour($input['delivery_date_hour']);
            $limitDate->minute($input['delivery_date_minute']);
            DB::table('subs_delivery')->where('id', '=', $input['id'])->update([
                'delivery_date' => $limitDate,
                'delivery_status' => $input['status'],
                'picker' => $input['picker']
            ]);
        }
        DB::table('subs_delivery')->where('id', '=', $input['id'])->update([
            'delivery_status' => $input['status']
        ]);
        return response()->json(["success" => 1, "status" => $input['status'], "id" => $input['id'], "picker" => $input['picker'], "date" => $dateInfo], 200);
        //return redirect('/admin/deliveries');
    }

    public function deliveries()
    {
        $myArray = [];
        array_push($myArray, (object)['information' => 'Çiçek hazırlanıyor.', 'status' => 1]);
        array_push($myArray, (object)['information' => 'Çiçek hazır', 'status' => 6]);
        array_push($myArray, (object)['information' => 'Teslimat aşamasında.', 'status' => 2]);
        array_push($myArray, (object)['information' => 'Teslim edildi.', 'status' => 3]);
        array_push($myArray, (object)['information' => 'İptal edildi.', 'status' => 4]);
        array_push($myArray, (object)['information' => 'Hepsi', 'status' => 0]);

        $operationList = DB::table('operation_person')->select('id', 'name')->get();
        array_push($operationList, (object)['name' => 'Hepsi', 'id' => 0]);

        $deliveryList = DB::table('subs_sales')->join('users', 'subs_sales.user_id', '=', 'users.id')->join('subs_delivery', 'subs_sales.id', '=', 'subs_delivery.sale_id')
            ->join('delivery_locations', 'subs_sales.location_id', '=', 'delivery_locations.id')
            ->where('subs_delivery.transaction_status', '=', 1)
            ->select('subs_delivery.id', 'delivery_locations.district', 'subs_delivery.contact_address', 'users.name', 'users.surname', 'subs_delivery.contact_name', 'subs_delivery.delivery_date_start',
                'subs_delivery.delivery_date_end', 'subs_delivery.delivery_date', 'subs_delivery.delivery_status as status', 'subs_delivery.picker', 'subs_delivery.operation_name', 'subs_delivery.delivery_note',
                'subs_sales.flower_name', 'subs_sales.delivery_monthly', 'subs_sales.delivery_weekly', 'subs_sales.delivery_times', 'delivery_locations.continent_id', 'subs_sales.cup_status')->get();

        return view('admin.subs.deliveries', compact('deliveryList', 'myArray', 'operationList'));
    }

    public function transactions()
    {
        $transactions = DB::table('subs_sales')->join('users', 'subs_sales.user_id', '=', 'users.id')->join('subs_delivery', 'subs_sales.id', '=', 'subs_delivery.sale_id')
            ->join('subs_billing', 'subs_sales.id', '=', 'subs_billing.sales_id')
            ->where('subs_delivery.transaction_status', '=', 1)
            ->select('subs_delivery.id', 'subs_sales.id as general_id', 'users.name', 'users.surname', 'subs_billing.small_city', 'subs_billing.billing_address', 'subs_billing.tax_no'
                , 'subs_delivery.delivery_date_start', 'subs_delivery.transaction_date', 'subs_sales.flower_id', 'subs_sales.flower_name'
                , 'subs_sales.delivery_monthly', 'subs_sales.delivery_weekly', 'subs_sales.delivery_times', 'subs_sales.flower_price', 'subs_sales.cup_price', 'subs_sales.cup_status', 'total_price'
            )->get();
        return view('admin.subs.transactions', compact('transactions'));
    }

    public function resumeCustomer($id)
    {
        DB::table('subs_sales')->where('id', $id)->update([
            'status' => 1
        ]);
        return redirect('/admin/subs/customers');
    }

    public function pauseCustomer($id)
    {
        DB::table('subs_sales')->where('id', $id)->update([
            'status' => 3
        ]);
        return redirect('/admin/subs/customers');
    }

    public function cancelCustomer($id)
    {
        DB::table('subs_sales')->where('id', $id)->update([
            'status' => 2
        ]);
        return redirect('/admin/subs/customers');
    }

    public function customers()
    {
        $allUser = DB::table('subs_sales')->join('users', 'subs_sales.user_id', '=', 'users.id')->join('customers', 'customers.user_id', '=', 'users.id')
            ->select('subs_sales.created_at', 'subs_sales.status', 'users.name', 'users.surname', 'users.email', 'customers.mobile', 'subs_sales.flower_name', 'subs_sales.id', 'users.id as user_id')->get();
        return view('admin.subs.customers', compact('allUser'));
    }

    public function testSubsSales($userId, $productId, $cup, $freq, $firstDay, $hourStart, $hourEnd)
    {
        DB::beginTransaction();
        try {
            $contact_name = 'talya';
            $contact_address = 'cihangir mahallesi 3 numara kapi yani taksim istanbul';
            $contact_mobile = '5313347389';
            $contactName = 'talya';
            $location_id = '1';
            $note = 'hergün de isterim her haftada';
            $contactName = 'talya';
            $transaction_times = 0;
            $status = 1;
            $tempStatus = 0;

            $freqData = DB::table('subs_freq')->where('id', $freq)->get()[0];

            $productData = DB::table('subs_products')->where('id', $productId)->get()[0];

            if ($cup) {
                $totalPrice = (float)$productData->side_price + (float)$productData->price;
            } else {
                $totalPrice = (float)$productData->price;
            }

            $now = Carbon::now();
            $firstDelivery = Carbon::now();
            $firstDeliveryEnd = Carbon::now();

            if ($now->dayOfWeek >= $firstDay) {
                $firstDelivery->addDays($firstDay - $now->dayOfWeek + 7);
            } else {
                $firstDelivery->addDays($firstDay - $now->dayOfWeek);
            }
            $firstDelivery->hour($hourStart);
            $firstDelivery->minute(00);
            $firstDelivery->second(00);
            $firstDeliveryEnd->hour($hourEnd);
            $firstDeliveryEnd->minute(00);
            $firstDeliveryEnd->second(00);

            $sale_id = DB::table('subs_sales')->insertGetId(
                [
                    'user_id' => $userId,
                    'contact_name' => $contact_name,
                    'contact_address' => $contact_address,
                    'contact_mobile' => $contact_mobile,
                    'location_id' => $location_id,
                    'note' => $note,
                    'flower_id' => $productId,
                    'flower_name' => $productData->name,
                    'flower_desc' => '',
                    'flower_price' => $productData->price,
                    'cup_price' => $productData->side_price,
                    'cup_status' => $cup,
                    'total_price' => $totalPrice,
                    'first_delivery_date' => $firstDelivery,
                    'delivery_weekly' => $freqData->weekly,
                    'delivery_monthly' => $freqData->monthly,
                    'delivery_times' => $freqData->times,
                    'delivery_hours_start' => $hourStart,
                    'delivery_hours_end' => $hourEnd,
                    'day_of_week' => $firstDay,
                    'status' => 1,
                    'transaction_date' => $now,
                    'transaction_times' => 1
                ]
            );

            DB::table('subs_delivery')->insert([
                'sale_id' => $sale_id,
                'transaction_status' => 1,
                'delivery_date_start' => $firstDelivery,
                'delivery_date_end' => $firstDeliveryEnd,
                'delivery_status' => 1,
                'send_billing' => 0,
                'contact_name' => $contact_name,
                'contact_address' => $contact_address,
                'contact_mobile' => $contact_mobile,
                'location_id' => $location_id,
                'note' => $note
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
    }

    public function updateHour()
    {
        DB::beginTransaction();
        try {
            $tempStatus = 0;
            if (Request::input('active') == 'on') {
                $tempStatus = 1;
            }

            DB::table('subs_hours')->where('id', Request::input('id'))->update(
                [
                    'active' => $tempStatus,
                    'start_hour' => Request::input('start_hour'),
                    'end_hour' => Request::input('end_hour')
                ]
            );

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/subs/hours');
    }

    public function hour($id)
    {
        $hour = DB::table('subs_hours')->where('id', $id)->get()[0];

        return view('admin.subs.hour', compact('hour'));
    }

    public function hours()
    {
        $hours = DB::table('subs_hours')->get();

        return view('admin.subs.hours', compact('hours'));
    }

    public function insertHour()
    {
        DB::beginTransaction();
        try {
            $tempStatus = 0;
            if (Request::input('active') == 'on') {
                $tempStatus = 1;
            }

            DB::table('subs_hours')->insert(
                [
                    'active' => $tempStatus,
                    'start_hour' => Request::input('start_hour'),
                    'end_hour' => Request::input('end_hour')
                ]
            );

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/subs/hours');
    }

    public function addHoursPage()
    {
        return view('admin.subs.addHours');
    }

    public function updateFirstDate()
    {
        DB::beginTransaction();
        try {
            $tempStatus = 0;
            if (Request::input('active') == 'on') {
                $tempStatus = 1;
            }

            DB::table('subs_first_date')->where('id', Request::input('id'))->update(
                [
                    'name' => Request::input('name'),
                    'active' => $tempStatus
                ]
            );

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/subs/firstDates');
    }

    public function firstDate($id)
    {
        $firstDate = DB::table('subs_first_date')->where('id', $id)->get()[0];

        return view('admin.subs.firstDate', compact('firstDate'));
    }

    public function firstDates()
    {
        $firstDates = DB::table('subs_first_date')->get();

        return view('admin.subs.firstDates', compact('firstDates'));
    }

    public function addProductPage()
    {

        return view('admin.subs.addProduct');
    }

    public function frequencies()
    {
        $frequencies = DB::table('subs_freq')->get();

        return view('admin.subs.frequencies', compact('frequencies'));
    }

    public function freq($id)
    {
        $freq = DB::table('subs_freq')->where('id', $id)->get()[0];

        return view('admin.subs.freqDetail', compact('freq'));
    }

    public function updateFreq()
    {
        DB::beginTransaction();
        try {
            $tempStatus = 0;
            if (Request::input('active') == 'on') {
                $tempStatus = 1;
            }

            DB::table('subs_freq')->where('id', Request::input('id'))->update(
                [
                    'name' => Request::input('name'),
                    'active' => $tempStatus,
                    'order' => Request::input('order')
                ]
            );

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/subs/frequencies');
    }

    public function insertProduct(\Illuminate\Http\Request $request)
    {
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        DB::beginTransaction();
        try {
            if (isset($input['active']))
                $input['active'] = 1;
            else
                $input['active'] = 0;

            $insertedProduct = DB::table('subs_products')->insertGetId(
                [
                    'name' => $input['name'],
                    'price' => $input['price'],
                    'active' => $input['active'],
                    'price' => $input['price'],
                    'side_price' => $input['side_price'],
                    'order' => $input['order']
                ]
            );

            //dd(Request::file('img'));
            if (Request::file('photo_url')) {
                $file = Request::file('photo_url');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $randomFix = str_random(5);

                $tempName = $input['name'] . '_' . $randomFix;

                DB::table('subs_products')->where('id', $insertedProduct)->update([
                    'photo_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/subsProduct/" . $tempName . "." . $fileExtension
                ]);

                $fileMoved = Request::file('photo_url')->move(public_path() . "/productImageUploads/", $input['name'] . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('photo_url');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/subsProduct/' . $tempName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['name'] . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                //return $fileMoved->getExtension();
            } else {
                dd('resim yuklemeniz gerekmektedir.');
            }

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/subs/products');
    }

    public function products()
    {
        $products = DB::table('subs_products')->get();

        return view('admin.subs.products', compact('products'));
    }

    public function product($id)
    {
        $product = DB::table('subs_products')->where('id', $id)->get()[0];

        return view('admin.subs.productDetail', compact('product'));
    }

    public function updateProduct(\Illuminate\Http\Request $request)
    {
        $siteUrl = $this->backend_url;
        //$siteUrl = 'http://188.166.86.116:3000';
        $input = $request->all();
        DB::beginTransaction();
        try {
            if (isset($input['active']))
                $input['active'] = 1;
            else
                $input['active'] = 0;

            DB::table('subs_products')->where('id', $input['id'])->update(
                [
                    'name' => $input['name'],
                    'price' => $input['price'],
                    'active' => $input['active'],
                    'price' => $input['price'],
                    'side_price' => $input['side_price'],
                    'order' => $input['order']
                ]
            );

            //dd(Request::file('img'));
            if (Request::file('photo_url')) {
                $file = Request::file('photo_url');
                $filename = $file->getClientOriginalName();
                $fileExtension = explode(".", $filename)[1];

                $randomFix = str_random(5);

                $tempName = $input['name'] . '_' . $randomFix;

                DB::table('subs_products')->where('id', $input['id'])->update([
                    'photo_url' => 'https://d1z5skrvc8vebc.cloudfront.net/' . explode("//", $siteUrl)[1] . "/subsProduct/" . $tempName . "." . $fileExtension
                ]);

                $fileMoved = Request::file('photo_url')->move(public_path() . "/productImageUploads/", $input['name'] . "." . $fileExtension);
                $s3 = \AWS::get('s3');
                $file = Request::file('photo_url');
                $s3->putObject(array(
                    'Bucket' => 'bloomandfresh',
                    'Key' => explode("//", $siteUrl)[1] . '/subsProduct/' . $tempName . "." . $fileExtension,
                    //'Body' => fopen(public_path() . "/productImageUploads/" . $imageId . "." . $fileExtension , 'r')
                    'Body' => fopen(public_path() . "/productImageUploads/" . $input['name'] . "." . $fileExtension, 'r'),
                    'ACL' => 'public-read',
                    'CacheControl' => 'max-age=2996000'
                ));
                //return $fileMoved->getExtension();
            }

        } catch (\Exception $e) {
            DB::rollback();
            return $e;
            //return ["status" => -1, "description" => "Bir hata gerceklesti"];
        }
        DB::commit();
        return redirect('/admin/subs/products');
    }
}