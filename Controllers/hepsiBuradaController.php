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

class hepsiBuradaController extends Controller
{

    public function productFeed(){
        $flowerList = DB::table('shops')
            ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
            ->join('products', 'products_shops.products_id', '=', 'products.id')
            ->join('product_city', 'products.id', '=', 'product_city.product_id')
            ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
            ->where('shops.id', '=', 1)
            ->where('descriptions.lang_id', '=', 'tr')
            ->where('product_city.activation_status_id', '=', 1)
            ->where('product_city.active', '=', 1)
            ->where('products.company_product', '=', 0)
            ->select('products.id', 'products.name', 'products.product_type', 'products.price',
                'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc'
                , 'descriptions.how_to_detail', 'products.youtube_url', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3'
                , 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2', 'descriptions.extra_info_3' )
            ->orderBy('products.id')
            ->get();
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

        return view('admin.hbProductExport', compact('flowerList'));

    }

    public function testHB()
    {
        $username = "bloomandfresh_dev";
        $password = "Bl12345!";
        $remote_url = 'https://oms-external-sit.hepsiburada.com/orders/merchantid/a664b25c-721f-403b-9132-fdfa2ea6d6f8';

        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Authorization: Basic " . base64_encode("$username:$password")
            )
        );

        $context = stream_context_create($opts);

        $file = file_get_contents($remote_url, false, $context);

        $salesOpenHB = json_decode($file)->items;

        foreach ($salesOpenHB as $sale) {
            if (DB::table('hb_payment_done_orders')->where('id', $sale->id)->count() == 0) {
                DB::table('hb_payment_done_orders')->insert([
                    "dueDate" => $sale->dueDate,
                    "lastStatusUpdateDate" => $sale->lastStatusUpdateDate,
                    "id" => $sale->id,
                    "sku" => $sale->sku,
                    "orderId" => $sale->orderId,
                    "orderNumber" => $sale->orderNumber,
                    "orderDate" => $sale->orderDate,
                    "quantity" => $sale->quantity,
                    "merchantId" => $sale->merchantId,
                    "totalPriceAmount" => $sale->totalPrice->amount,
                    "unitPriceAmount" => $sale->unitPrice->amount,
                    "hbDiscountTotalPrice" => $sale->hbDiscount->totalPrice->amount,
                    "hbDiscountUnitPrice" => $sale->hbDiscount->unitPrice->amount,
                    "vat" => $sale->vat,
                    "vatRate" => $sale->vatRate,
                    "customerName" => $sale->customerName,
                    "status" => $sale->status,
                    "shippingAddress" => $sale->shippingAddress,
                    "invoice_turkishIdentityNumber" => $sale->invoice->turkishIdentityNumber,
                    "invoice_taxNumber" => $sale->invoice->taxNumber,
                    "invoice_taxOffice" => $sale->invoice->taxOffice,
                    "invoice_addressId" => $sale->invoice->address->addressId,
                    "invoice_address" => $sale->invoice->address->address,
                    "invoice_name" => $sale->invoice->address->name,
                    "invoice_email" => $sale->invoice->address->email,
                    "invoice_countryCode" => $sale->invoice->address->countryCode,
                    "invoice_phoneNumber" => $sale->invoice->address->phoneNumber,
                    "invoice_alternatePhoneNumber" => $sale->invoice->address->alternatePhoneNumber,
                    "invoice_district" => $sale->invoice->address->district,
                    "invoice_city" => $sale->invoice->address->city,
                    "invoice_town" => $sale->invoice->address->town,
                    "sapNumber" => $sale->sapNumber,
                    "dispatchTime" => $sale->dispatchTime,
                    "commission_amount" => $sale->commission->amount,
                    "paymentTermInDays" => $sale->paymentTermInDays,
                    "commissionType" => $sale->commissionType,
                    "cargoCompanyModelId" => $sale->cargoCompanyModel->id,
                    "cargoCompanyModelName" => $sale->cargoCompanyModel->name,
                    "cargoCompany" => $sale->cargoCompany,
                    "customizedText01" => $sale->customizedText01,
                    "customizedText02" => $sale->customizedText02,
                    "customizedText03" => $sale->customizedText03,
                    "customizedText04" => $sale->customizedText04,
                    "customizedTextX" => $sale->customizedTextX,
                    "creditCardHolderName" => $sale->creditCardHolderName,
                    "isCustomized" => $sale->isCustomized,
                    "canCreatePackage" => $sale->canCreatePackage,
                    "isCancellable" => $sale->isCancellable,
                    "isCancellableByHbAdmin" => $sale->isCancellableByHbAdmin,
                    "deliveryType" => $sale->deliveryType,
                    "deliveryOptionId" => $sale->deliveryOptionId,
                    "slot" => $sale->slot,
                    "pickUpTime" => $sale->pickUpTime
                ]);
            }
            else {
                DB::table('hb_payment_done_orders')->where('id', $sale->id)->update([
                    "dueDate" => $sale->dueDate,
                    "lastStatusUpdateDate" => $sale->lastStatusUpdateDate,
                    "id" => $sale->id,
                    "sku" => $sale->sku,
                    "orderId" => $sale->orderId,
                    "orderNumber" => $sale->orderNumber,
                    "orderDate" => $sale->orderDate,
                    "quantity" => $sale->quantity,
                    "merchantId" => $sale->merchantId,
                    "totalPriceAmount" => $sale->totalPrice->amount,
                    "unitPriceAmount" => $sale->unitPrice->amount,
                    "hbDiscountTotalPrice" => $sale->hbDiscount->totalPrice->amount,
                    "hbDiscountUnitPrice" => $sale->hbDiscount->unitPrice->amount,
                    "vat" => $sale->vat,
                    "vatRate" => $sale->vatRate,
                    "customerName" => $sale->customerName,
                    "status" => $sale->status,
                    "shippingAddress" => $sale->shippingAddress,
                    "invoice_turkishIdentityNumber" => $sale->invoice->turkishIdentityNumber,
                    "invoice_taxNumber" => $sale->invoice->taxNumber,
                    "invoice_taxOffice" => $sale->invoice->taxOffice,
                    "invoice_addressId" => $sale->invoice->address->addressId,
                    "invoice_address" => $sale->invoice->address->address,
                    "invoice_name" => $sale->invoice->address->name,
                    "invoice_email" => $sale->invoice->address->email,
                    "invoice_countryCode" => $sale->invoice->address->countryCode,
                    "invoice_phoneNumber" => $sale->invoice->address->phoneNumber,
                    "invoice_alternatePhoneNumber" => $sale->invoice->address->alternatePhoneNumber,
                    "invoice_district" => $sale->invoice->address->district,
                    "invoice_city" => $sale->invoice->address->city,
                    "invoice_town" => $sale->invoice->address->town,
                    "sapNumber" => $sale->sapNumber,
                    "dispatchTime" => $sale->dispatchTime,
                    "commission_amount" => $sale->commission->amount,
                    "paymentTermInDays" => $sale->paymentTermInDays,
                    "commissionType" => $sale->commissionType,
                    "cargoCompanyModelId" => $sale->cargoCompanyModel->id,
                    "cargoCompanyModelName" => $sale->cargoCompanyModel->name,
                    "cargoCompany" => $sale->cargoCompany,
                    "customizedText01" => $sale->customizedText01,
                    "customizedText02" => $sale->customizedText02,
                    "customizedText03" => $sale->customizedText03,
                    "customizedText04" => $sale->customizedText04,
                    "customizedTextX" => $sale->customizedTextX,
                    "creditCardHolderName" => $sale->creditCardHolderName,
                    "isCustomized" => $sale->isCustomized,
                    "canCreatePackage" => $sale->canCreatePackage,
                    "isCancellable" => $sale->isCancellable,
                    "isCancellableByHbAdmin" => $sale->isCancellableByHbAdmin,
                    "deliveryType" => $sale->deliveryType,
                    "deliveryOptionId" => $sale->deliveryOptionId,
                    "slot" => $sale->slot,
                    "pickUpTime" => $sale->pickUpTime
                ]);
            }
        }

        $tempSales = DB::table('hb_payment_done_orders')->get();

        foreach ( $tempSales as $detailSale ){
            $username = "bloomandfresh_dev";
            $password = "Bl12345!";
            $remote_url = 'https://oms-external-sit.hepsiburada.com/orders/merchantid/a664b25c-721f-403b-9132-fdfa2ea6d6f8/ordernumber/' . $detailSale->orderNumber;

            $opts = array(
                'http' => array(
                    'method' => "GET",
                    'header' => "Authorization: Basic " . base64_encode("$username:$password")
                )
            );

            $context = stream_context_create($opts);

            $file = file_get_contents($remote_url, false, $context);

            $salesOpenHB = json_decode($file);

            //foreach ($salesOpenHB as $sale) {
            //dd($sale);

            if (DB::table('hb_sale_detail')->where('orderNumber', $salesOpenHB->orderNumber)->count() == 0) {
                DB::table('hb_sale_detail')->insert([
                    "orderId" => $salesOpenHB->orderId,
                    "orderNumber" => $salesOpenHB->orderNumber,
                    "paymentStatus" => $salesOpenHB->paymentStatus,
                    "orderDate" => $salesOpenHB->orderDate,
                    "createdDate" => $salesOpenHB->createdDate,
                    "customerId" => $salesOpenHB->customer->customerId,
                    "customerName" => $salesOpenHB->customer->name,
                    "addressId" => $salesOpenHB->deliveryAddress->addressId,
                    "address" => $salesOpenHB->deliveryAddress->address,
                    "name" => $salesOpenHB->deliveryAddress->name,
                    "email" => $salesOpenHB->deliveryAddress->email,
                    "countryCode" => $salesOpenHB->deliveryAddress->countryCode,
                    "phoneNumber" => $salesOpenHB->deliveryAddress->phoneNumber,
                    "alternatePhoneNumber" => $salesOpenHB->deliveryAddress->alternatePhoneNumber,
                    "district" => $salesOpenHB->deliveryAddress->district,
                    "city" => $salesOpenHB->deliveryAddress->city,
                    "town" => $salesOpenHB->deliveryAddress->town
                ]);
            } else {
                DB::table('hb_sale_detail')->where('orderNumber', $salesOpenHB->orderNumber)->update([
                    "orderId" => $salesOpenHB->orderId,
                    "orderNumber" => $salesOpenHB->orderNumber,
                    "paymentStatus" => $salesOpenHB->paymentStatus,
                    "orderDate" => $salesOpenHB->orderDate,
                    "createdDate" => $salesOpenHB->createdDate,
                    "customerId" => $salesOpenHB->customer->customerId,
                    "customerName" => $salesOpenHB->customer->name,
                    "addressId" => $salesOpenHB->deliveryAddress->addressId,
                    "address" => $salesOpenHB->deliveryAddress->address,
                    "name" => $salesOpenHB->deliveryAddress->name,
                    "email" => $salesOpenHB->deliveryAddress->email,
                    "countryCode" => $salesOpenHB->deliveryAddress->countryCode,
                    "phoneNumber" => $salesOpenHB->deliveryAddress->phoneNumber,
                    "alternatePhoneNumber" => $salesOpenHB->deliveryAddress->alternatePhoneNumber,
                    "district" => $salesOpenHB->deliveryAddress->district,
                    "city" => $salesOpenHB->deliveryAddress->city,
                    "town" => $salesOpenHB->deliveryAddress->town
                ]);
            }
        }
    }

    public function testCreatePackage()
    {

        $username = "bloomandfresh_dev";
        $password = "Bl12345!";
        $remote_url = 'https://oms-external-sit.hepsiburada.com/packages/merchantid/a664b25c-721f-403b-9132-fdfa2ea6d6f8';

        $lineId = 'cc5e2115-5f76-4224-a594-fd30392c9f92';

        //$data = '{ "lineItemRequests":  [ { "id": "' . $lineId . '", "quantity":"1" } ] }';

        $opts = array(
            'http' => array(
                'method' => "POST",
                'header' => "Authorization: Basic " . base64_encode("$username:$password") . "\r\n" .
                    "Content-Type: application/json\r\n",
                'content' => '{ "lineItemRequests":  [ { "id": "cc5e2115-5f76-4224-a594-fd30392c9f92", "quantity":"1" } ] }'
            )
        );

        $context = stream_context_create($opts);

        $file = file_get_contents($remote_url, false, $context);

        //dd($file);
        $salesOpenHB = json_decode($file);

        dd($salesOpenHB);
    }

    public function testPackage()
    {
        $username = "bloomandfresh_dev";
        $password = "Bl12345!";
        $remote_url = 'https://oms-external-sit.hepsiburada.com/orders/merchantid/a664b25c-721f-403b-9132-fdfa2ea6d6f8/ordernumber/041249977';

        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Authorization: Basic " . base64_encode("$username:$password")
            )
        );

        $context = stream_context_create($opts);

        $file = file_get_contents($remote_url, false, $context);

        $salesOpenHB = json_decode($file);

        foreach ($salesOpenHB as $sale) {
            //dd($sale);

            if (DB::table('hb_packages_list')->where('id', $sale->id)->count() == 0) {
                DB::table('hb_packages_list')->insert([
                    "id" => $sale->id,
                    "status" => $sale->status,
                    "customerId" => $sale->customerId,
                    "orderDate" => $sale->orderDate,
                    "dueDate" => $sale->dueDate,
                    "unpackedDate" => $sale->unpackedDate,
                    "barcode" => $sale->barcode,
                    "packageNumber" => $sale->packageNumber,
                    "cargoCompany" => $sale->cargoCompany,
                    "shippingAddressDetail" => $sale->shippingAddressDetail,
                    "recipientName" => $sale->recipientName,
                    "shippingCountryCode" => $sale->shippingCountryCode,
                    "shippingDistrict" => $sale->shippingDistrict,
                    "shippingTown" => $sale->shippingTown,
                    "shippingCity" => $sale->shippingCity,
                    "email" => $sale->email,
                    "phoneNumber" => $sale->phoneNumber,
                    "companyName" => $sale->companyName,
                    "billingAddress" => $sale->billingAddress,
                    "billingCity" => $sale->billingCity,
                    "billingTown" => $sale->billingTown,
                    "billingDistrict" => $sale->billingDistrict,
                    "taxOffice" => $sale->taxOffice,
                    "taxNumber" => $sale->taxNumber,
                    "identityNo" => $sale->identityNo,
                    "lineItemId" => $sale->items[0]->lineItemId,
                    "listingId" => $sale->items[0]->listingId,
                    "merchantId" => $sale->items[0]->merchantId,
                    "hbSku" => $sale->items[0]->hbSku,
                    "merchantSku" => $sale->items[0]->merchantSku,
                    "quantity" => $sale->items[0]->quantity,
                    "price" => $sale->items[0]->price->amount,
                    "vat" => $sale->items[0]->vat,
                    "totalPrice" => $sale->items[0]->totalPrice->amount,
                    "commission" => $sale->items[0]->commission->amount,
                    "unitHBDiscount" => $sale->items[0]->unitHBDiscount->amount,
                    "totalHBDiscount" => $sale->items[0]->totalHBDiscount->amount,
                    "merchantUnitPrice" => $sale->items[0]->merchantUnitPrice->amount,
                    "merchantTotalPrice" => $sale->items[0]->merchantTotalPrice->amount,
                    "cargoPaymentInfo" => $sale->items[0]->cargoPaymentInfo,
                    "customizedText01" => $sale->items[0]->customizedText01,
                    "customizedText02" => $sale->items[0]->customizedText02,
                    "customizedText03" => $sale->items[0]->customizedText03,
                    "customizedText04" => $sale->items[0]->customizedText04,
                    "productName" => $sale->items[0]->productName,
                    "orderNumber" => $sale->items[0]->orderNumber,
                    "orderDate" => $sale->items[0]->orderDate,
                    "deliveryType" => $sale->items[0]->deliveryType,
                    "customerDelivery" => $sale->items[0]->customerDelivery,
                    "pickupTime" => $sale->items[0]->pickupTime
                ]);
            } else {
                DB::table('hb_packages_list')->where('id', $sale->id)->update([
                    "id" => $sale->id,
                    "status" => $sale->status,
                    "customerId" => $sale->customerId,
                    "orderDate" => $sale->orderDate,
                    "dueDate" => $sale->dueDate,
                    "unpackedDate" => $sale->unpackedDate,
                    "barcode" => $sale->barcode,
                    "packageNumber" => $sale->packageNumber,
                    "cargoCompany" => $sale->cargoCompany,
                    "shippingAddressDetail" => $sale->shippingAddressDetail,
                    "recipientName" => $sale->recipientName,
                    "shippingCountryCode" => $sale->shippingCountryCode,
                    "shippingDistrict" => $sale->shippingDistrict,
                    "shippingTown" => $sale->shippingTown,
                    "shippingCity" => $sale->shippingCity,
                    "email" => $sale->email,
                    "phoneNumber" => $sale->phoneNumber,
                    "companyName" => $sale->companyName,
                    "billingAddress" => $sale->billingAddress,
                    "billingCity" => $sale->billingCity,
                    "billingTown" => $sale->billingTown,
                    "billingDistrict" => $sale->billingDistrict,
                    "taxOffice" => $sale->taxOffice,
                    "taxNumber" => $sale->taxNumber,
                    "identityNo" => $sale->identityNo,
                    "lineItemId" => $sale->items[0]->lineItemId,
                    "listingId" => $sale->items[0]->listingId,
                    "merchantId" => $sale->items[0]->merchantId,
                    "hbSku" => $sale->items[0]->hbSku,
                    "merchantSku" => $sale->items[0]->merchantSku,
                    "quantity" => $sale->items[0]->quantity,
                    "price" => $sale->items[0]->price->amount,
                    "vat" => $sale->items[0]->vat,
                    "totalPrice" => $sale->items[0]->totalPrice->amount,
                    "commission" => $sale->items[0]->commission->amount,
                    "unitHBDiscount" => $sale->items[0]->unitHBDiscount->amount,
                    "totalHBDiscount" => $sale->items[0]->totalHBDiscount->amount,
                    "merchantUnitPrice" => $sale->items[0]->merchantUnitPrice->amount,
                    "merchantTotalPrice" => $sale->items[0]->merchantTotalPrice->amount,
                    "cargoPaymentInfo" => $sale->items[0]->cargoPaymentInfo,
                    "customizedText01" => $sale->items[0]->customizedText01,
                    "customizedText02" => $sale->items[0]->customizedText02,
                    "customizedText03" => $sale->items[0]->customizedText03,
                    "customizedText04" => $sale->items[0]->customizedText04,
                    "productName" => $sale->items[0]->productName,
                    "orderNumber" => $sale->items[0]->orderNumber,
                    "orderDate" => $sale->items[0]->orderDate,
                    "deliveryType" => $sale->items[0]->deliveryType,
                    "customerDelivery" => $sale->items[0]->customerDelivery,
                    "pickupTime" => $sale->items[0]->pickupTime
                ]);
            }
        }
    }

    public function testSalesDetail()
    {
        $username = "bloomandfresh_dev";
        $password = "Bl12345!";
        $remote_url = 'https://oms-external-sit.hepsiburada.com/orders/merchantid/a664b25c-721f-403b-9132-fdfa2ea6d6f8/ordernumber/041249977';

        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Authorization: Basic " . base64_encode("$username:$password")
            )
        );

        $context = stream_context_create($opts);

        $file = file_get_contents($remote_url, false, $context);

        $salesOpenHB = json_decode($file);

        //foreach ($salesOpenHB as $sale) {
        //dd($sale);

        if (DB::table('hb_sale_detail')->where('orderNumber', $salesOpenHB->orderNumber)->count() == 0) {
            DB::table('hb_sale_detail')->insert([
                "orderId" => $salesOpenHB->orderId,
                "orderNumber" => $salesOpenHB->orderNumber,
                "paymentStatus" => $salesOpenHB->paymentStatus,
                "orderDate" => $salesOpenHB->orderDate,
                "createdDate" => $salesOpenHB->createdDate,
                "customerId" => $salesOpenHB->customer->customerId,
                "customerName" => $salesOpenHB->customer->name,
                "addressId" => $salesOpenHB->deliveryAddress->addressId,
                "address" => $salesOpenHB->deliveryAddress->address,
                "name" => $salesOpenHB->deliveryAddress->name,
                "email" => $salesOpenHB->deliveryAddress->email,
                "countryCode" => $salesOpenHB->deliveryAddress->countryCode,
                "phoneNumber" => $salesOpenHB->deliveryAddress->phoneNumber,
                "alternatePhoneNumber" => $salesOpenHB->deliveryAddress->alternatePhoneNumber,
                "district" => $salesOpenHB->deliveryAddress->district,
                "city" => $salesOpenHB->deliveryAddress->city,
                "town" => $salesOpenHB->deliveryAddress->town
            ]);
        } else {
            DB::table('hb_sale_detail')->where('orderNumber', $salesOpenHB->orderNumber)->update([
                "orderId" => $salesOpenHB->orderId,
                "orderNumber" => $salesOpenHB->orderNumber,
                "paymentStatus" => $salesOpenHB->paymentStatus,
                "orderDate" => $salesOpenHB->orderDate,
                "createdDate" => $salesOpenHB->createdDate,
                "customerId" => $salesOpenHB->customer->customerId,
                "customerName" => $salesOpenHB->customer->name,
                "addressId" => $salesOpenHB->deliveryAddress->addressId,
                "address" => $salesOpenHB->deliveryAddress->address,
                "name" => $salesOpenHB->deliveryAddress->name,
                "email" => $salesOpenHB->deliveryAddress->email,
                "countryCode" => $salesOpenHB->deliveryAddress->countryCode,
                "phoneNumber" => $salesOpenHB->deliveryAddress->phoneNumber,
                "alternatePhoneNumber" => $salesOpenHB->deliveryAddress->alternatePhoneNumber,
                "district" => $salesOpenHB->deliveryAddress->district,
                "city" => $salesOpenHB->deliveryAddress->city,
                "town" => $salesOpenHB->deliveryAddress->town
            ]);
        }
    }

    public function hbSaleList(){

        $tempSales = DB::table('hb_payment_done_orders')->join('hb_sale_detail', 'hb_payment_done_orders.orderId', '=' , 'hb_sale_detail.orderId')
            ->select('hb_payment_done_orders.*', 'hb_sale_detail.paymentStatus', 'hb_sale_detail.address', 'hb_sale_detail.name', 'hb_sale_detail.email', 'hb_sale_detail.phoneNumber', 'hb_sale_detail.alternatePhoneNumber', 'hb_sale_detail.district', 'hb_sale_detail.city', 'hb_sale_detail.town')->get();

        DB::table('hb_bnf')->insert([
            'paymentId' => $tempSales->id,
            'orderNumber' => $tempSales->orderNumber,
            'orderId' => $tempSales->orderId,
            'line_id' => '',
            'packageNumber' => $tempSales->packageNumber,
            'orderDate' => $tempSales->orderDate,
            'customerName' => $tempSales->customerName,
            'address' => $tempSales->address,
            'email' => $tempSales->email,
            'phone_number_1' => $tempSales->phoneNumber,
            'phone_number_2' => $tempSales->alternatePhoneNumber,
            'district' => $tempSales->district,
            'city' => $tempSales->city,
            'town' => $tempSales->town,
            'card_message' => '',
            'receiver' => $tempSales->name,
            'sender' => $tempSales->customerName,
            'card_receiver' => '',
            'card_sender' => '',
            'status' => 1,
            'product_name' => '',
            'product_id' => $tempSales->product_id
        ]);

        dd($tempSales);

    }

    //}
}