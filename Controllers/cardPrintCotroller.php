<?php namespace App\Http\Controllers;

use DB;
use Request;

class cardPrintCotroller extends Controller
{

    public function cardPrintDeliveryInfo(\Illuminate\Http\Request $request){

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

                DB::table('sales')->where('id', '=', explode('_', $key)[1] )->update([
                    'isPrintedNote' => 1
                ]);

                array_push($tempQueryList, $deliveryList);
            }
        }

        return view('admin.cardV2', compact('tempQueryList'));
    }

    public function customNotePage(){

        return view('admin.customNotePage');
    }

    public function customNotePageLongText(){

        return view('admin.customNotePageLongText');
    }

    public function customNotePageLongTextAll(){

        $allReceiver = DB::table('Sheet1')->get();

        return view('admin.customNotePageLongTextData', compact('allReceiver'));
    }

    public function customNotePageLongTextAll2($id){

        $tempCount = 50;
        $tempPass = 50*( $id - 1 );

        if( $id == 5 ){
            $tempCount = 60;
        }

        $allReceiver = DB::table('Sheet1')->take($tempCount)->skip($tempPass)->get();

        return view('admin.customNotePageLongTextData', compact('allReceiver'));
    }

    public function listSuppliers() {

        $supplierList = DB::table('suppliers')->orderBy('name')->get();

        return view('admin.supplierList', compact('supplierList'));
    }

    public function addSupplier(){

        return view('admin.supplierListCreate', compact('supplierListCreate'));
    }

    public function insertSupplier(){

        $tempStatus = 0;
        if (Request::input('status') == 'on') {
            $tempStatus = 1;
        }

        DB::table('suppliers')->insert([
            'name' => Request::input('name'),
            'active' => $tempStatus
        ]);

        return redirect('/listSuppliers');
    }

    public function detailSupplier($id) {

        $supplierDetail = DB::table('suppliers')->where('id', $id)->orderBy('name')->get()[0];

        return view('admin.supplierDetail', compact('supplierDetail'));
    }

    public function updateSupplierDetail(){

        $tempStatus = 0;
        if (Request::input('status') == 'on') {
            $tempStatus = 1;
        }

        DB::table('suppliers')->where('id', Request::input('id'))->update([
            'name' => Request::input('name'),
            'active' => $tempStatus
        ]);

        return redirect('/listSuppliers');

    }

    public function productWithSupplier(){

        $category = Request::get('category');
        $sub_category = Request::get('sub_category');
        $activity = Request::get('activity');
        $status = Request::get('status');

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

        $whereStatus = 'product_city.activation_status_id = 1';

        if( $activity == null ){
            $activity = 1;
        }

        if( $status == null ){
            $status = 'all';
            $whereActivity = ' 1 = 1';
        }

        if( $status == 'all' ){
            $whereActivity = ' 1 = 1';
        }
        else if( $status == 'stock' ){
            $whereActivity = ' product_city.limit_statu = 0 ';
        }
        else if( $status == 'limit' ){
            $whereActivity = ' product_city.limit_statu = 1 ';
        }
        else if( $status == 'coming' ){
            $whereActivity = ' product_city.coming_soon = 1 ';
        }

        if( $activity == 'all' ){
            $whereStatus = ' 1 = 1';
        }
        else if( $activity == '1' ){
            $whereStatus = 'product_city.activation_status_id = 1';
        }
        else if( $activity == '0'  ){
            $whereStatus = 'product_city.activation_status_id = 0';
        }

        $cityList = DB::table('user_city')->where('user_id', \Auth::user()->id)->where('active', 1)->where('valid', 1)->get();
        $tempWhere = ' ( 1 = 0  ';
        foreach ($cityList as $city) {
            $tempWhere = $tempWhere . ' or product_city.city_id = ' . $city->city_id;
        }

        $tempWhere = $tempWhere . ' ) ';

        $products = DB::table('products')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->join('images', 'products.id', '=', 'images.products_id')
            ->join('product_stocks', 'products.id', '=', 'product_stocks.product_id')
            ->whereRaw($tempWhere)
            ->whereRaw($whereStatus)
            ->whereRaw($whereActivity)
            ->where('product_stocks.city_id', '=', $cityList[0]->city_id)
            ->where('products.company_product', '=', 0)
            ->whereIn('products.product_type', $categoryList)
            ->whereIn('products.product_type_sub', $subCategoryList)
            ->where('type', '=', 'main')->select('product_city.activation_status_id', 'product_city.limit_statu', 'product_city.coming_soon' ,'products.name', 'products.id', 'products.product_type', 'products.product_type_sub', 'products.supplier_id', 'products.price', 'count' )->get();

        $suppliers = DB::table('suppliers')->get();

        //dd($products);

        return view('admin.productsForExport', compact('products', 'category', 'sub_category', 'activity', 'status', 'suppliers'));

    }

}
