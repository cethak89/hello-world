<?php namespace App\Http\Controllers;

use DB;

class cardPrintController extends Controller
{
    
    public function cardPrintDeliveryInfo(\Illuminate\Http\Request $request){
        $tempObject = $request->all();
        $tempQueryList = [];
        foreach ($tempObject as $key => $value) {
            if ( explode( '_' ,$key)[0] == 'selected' ) {
                if( strlen(explode( '_' ,$key)[1]) > 8){
                    $deliveryList = (object)[];
                }
                else{
                    $deliveryList = DB::table('sales')
                        ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                        ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                        ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                        //->where('deliveries.wanted_delivery_date' , '>' , $before )
                        //->where('deliveries.wanted_delivery_date' , '<' , $after )
                        ->where('sales.id' , '=' , explode( '_' ,$key)[1] )
                        ->where('sales.payment_methods', '=', 'OK')
                        ->select('sales.id','sales.card_message', 'sales.receiver', 'sales.sender')
                        ->get()[0];
                }

                array_push($tempQueryList, $deliveryList);
            }
        }

        return view('admin.cardDeliverydocument' , compact('tempQueryList') );
    }
    
}
