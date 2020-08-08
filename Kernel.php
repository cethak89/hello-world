<?php namespace App\Console;

use App\Models\ErrorLog;

use DB;
use App\Models\Reminder;
use SoapClient;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\App;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\Inspire',
        //'LucaDegasperi\OAuth2Server\Console\ClientCreatorCommand',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')->hourly();

        $schedule->call(function () {

            $products = DB::table('products')->where('old_price', '!=', '')->get();

            DB::table('page_flower_production')->where('page_id', '23')->delete();

            foreach ( $products as $product ){

                DB::table('page_flower_production')->insert([
                    'page_id' => '23',
                    'product_id' => $product->id
                ]);

            }

        })->cron('*/30 * * * * *');

        $schedule->call(function () {

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

        })->cron('30 8 * * * *');

        $schedule->call(function () {

            $now = Carbon::now();
            $now->addMinutes(-20);

            $total = DB::table('sales')->where('created_at', '>', $now)->count();
            $transaction = DB::table('sales')->where('created_at', '>', $now)->where('payment_methods', 'OK')->count();
            $error_number = DB::table('sales')->where('created_at', '>', $now)->where('payment_methods', '!=' , 'OK')->whereNotNull('payment_methods')->count();
            $empty_sale = DB::table('sales')->where('created_at', '>', $now)->whereNull('payment_methods')->count();

            if( $total > 6 && ( $transaction == 0 ) ){
                \MandrillMail::messages()->sendTemplate('bnf_sales_exceptional', null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => '!!Hatalı Siparişlerde Artış!!',
                    'subject' => '!!Hatalı Siparişlerde Artış!!',
                    'from_email' => 'teknik@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => 'hakancetinh@gmail.com',
                            'type' => 'to'
                        ),
                        array(
                            'email' => 'fatih@bloomandfresh.com',
                            'type' => 'to'
                        ),
                        array(
                            'email' => 'murat@bloomandfresh.com',
                            'type' => 'to'
                        )
                    ),
                    'merge' => true,
                    'merge_language' => 'mailchimp',
                    'global_merge_vars' => array(
                        array(
                            'name' => 'TRANSACTION',
                            'content' => $total,
                        ), array(
                            'name' => 'SALES',
                            'content' => $transaction,
                        ), array(
                            'name' => 'PERCENTAGE',
                            'content' => $error_number,
                        ), array(
                            'name' => 'EMPTY',
                            'content' => $empty_sale,
                        )
                    )
                ));
            }

        })->cron('*/20 * * * * *');

        $schedule->call(function () {

            $today = Carbon::now();
            $today = $today->startOfWeek();

            $tempSales = DB::table('sales')
                ->join('sales_products', 'sales.id','=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id','=', 'delivery_locations.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
                ->where('sales.payment_methods', 'OK')
                ->where('delivery_locations.city_id', 1)
                ->where('sales.created_at', '>' ,$today)
                ->where('deliveries.status', '<>', '4')
                ->orderByRaw('count(sales_products.products_id) DESC')
                ->groupBy('sales_products.products_id')
                ->take(8)
                ->get();

            if( count($tempSales) < 8 ){

                $today = Carbon::now();
                $today->addDay(-10);

                $tempSales = DB::table('sales')
                    ->join('sales_products', 'sales.id','=', 'sales_products.sales_id')
                    ->join('delivery_locations', 'sales.delivery_locations_id','=', 'delivery_locations.id')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('delivery_locations.city_id', 1)
                    ->where('sales.created_at', '>' ,$today)
                    ->where('deliveries.status', '<>', '4')
                    ->orderByRaw('count(sales_products.products_id) DESC')
                    ->groupBy('sales_products.products_id')
                    ->take(8)
                    ->get();
            }


            DB::table('best_seller_products')->where('city_id', 1)->delete();

            DB::table('product_city')->update([
                'best_seller' => 0
            ]);

            foreach ( $tempSales as $key => $sale ){

                DB::table('product_city')->where('product_id', $sale->products_id )->where('city_id', 1 )->update([
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
                ->join('sales_products', 'sales.id','=', 'sales_products.sales_id')
                ->join('delivery_locations', 'sales.delivery_locations_id','=', 'delivery_locations.id')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
                ->where('sales.payment_methods', 'OK')
                ->where('delivery_locations.city_id', 2)
                ->where('sales.created_at', '>' ,$today)
                ->where('deliveries.status', '<>', '4')
                ->orderByRaw('count(sales_products.products_id) DESC')
                ->groupBy('sales_products.products_id')
                ->take(6)
                ->get();

            if( count($tempSales) < 6 ){

                $today = Carbon::now();
                $today->addDay(-10);

                $tempSales = DB::table('sales')
                    ->join('sales_products', 'sales.id','=', 'sales_products.sales_id')
                    ->join('delivery_locations', 'sales.delivery_locations_id','=', 'delivery_locations.id')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->selectRaw(' count(sales_products.products_id) as totalSales,  sales_products.products_id')
                    ->where('sales.payment_methods', 'OK')
                    ->where('delivery_locations.city_id', 2)
                    ->where('sales.created_at', '>' ,$today)
                    ->where('deliveries.status', '<>', '4')
                    ->orderByRaw('count(sales_products.products_id) DESC')
                    ->groupBy('sales_products.products_id')
                    ->take(6)
                    ->get();
            }

            DB::table('best_seller_products')->where('city_id', 2)->delete();

            foreach ( $tempSales as $key => $sale ){

                DB::table('product_city')->where('product_id', $sale->products_id )->where('city_id', 2 )->update([
                    'best_seller' => 1
                ]);

                DB::table('best_seller_products')->insert([
                    'product_id' => $sale->products_id,
                    'city_id' => 2,
                    'page_id' => 21,
                    'orderId' => $key
                ]);
            }

        })->cron('0 */2 * * * *');

        $schedule->call(function () {

            $tempList = DB::table('landing_with_promo')->get();

            foreach ( $tempList as $sale ){
                DB::table('product_city')->where('product_id', $sale->product_id )->where('city_id', $sale->city_id )->update([
                    'landing_page_order' => $sale->order
                ]);
            }

        })->cron('0 * * * * *');

        $schedule->call(function () {

            $productFuture = DB::table('product_city')->where('future_delivery_day', '>', 0)->get();

            foreach ( $productFuture as $product){

                $today = Carbon::now();

                $tempNow = Carbon::now();
                $tempNow->hour('16');
                $tempNow->minute('00');
                $tempNow->second('00');

                if( $today > $tempNow ){
                    $today->addDay(1);
                }

                $today->addDay($product->future_delivery_day);

                $today->startOfDay();

                if( $today > $product->avalibility_time ){
                    DB::table('product_city')->where('id',$product->id )->update([
                        'avalibility_time' => $today
                    ]);
                }

            }

        })->cron('* * * * * *');

        $schedule->call(function () {

            $now = Carbon::now();

            $now->addDay(1);

            $todayReminderList = DB::table('reminders')
                ->join('customers', 'reminders.customers_id', '=', 'customers.id')
                ->join('users', 'customers.user_id', '=', 'users.id')
                ->select('users.email', 'customers.name as FNAME', 'reminders.reminder_day', 'reminders.reminder_month', 'reminders.name', 'reminders.description')
                ->where('reminders.reminder_day', $now->day)->where('reminders.reminder_month', $now->month)->get();

            foreach ($todayReminderList as $reminder) {

                $tempDate = Carbon::now();
                $tempDate->day($reminder->reminder_day);
                $tempDate->month($reminder->reminder_month);

                setlocale(LC_TIME, "");
                setlocale(LC_ALL, 'tr_TR.utf8');

                \MandrillMail::messages()->sendTemplate('BNF_Hatirlatma_E-posta', null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => 'Bloomandfresh dünyasına hoşgeldiniz',
                    'subject' => 'Bloom and Fresh ekibinden çok önemli bir hatırlatma var!',
                    'from_email' => 'hello@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => $reminder->email,
                            'type' => 'to'
                        )
                    ),
                    'merge' => true,
                    'merge_language' => 'mailchimp',
                    'global_merge_vars' => array(
                        array(
                            'name' => 'FNAME',
                            'content' => ucwords(strtolower($reminder->FNAME)),
                        ),
                        array(
                            'name' => 'NAME',
                            'content' => ucwords(strtolower($reminder->name)),
                        ),
                        array(
                            'name' => 'DATE',
                            'content' => $tempDate->formatLocalized('%A %d %B'),
                        ),
                        array(
                            'name' => 'DESCRIPTION',
                            'content' => $reminder->description,
                        )
                    )
                ));

            }

        })->cron('0 9 * * * *');

        $schedule->call(function () {

            $now = Carbon::now();

            $now->addDay(1);

            $todayReminderList = DB::table('product_later')
                ->join('products', 'product_later.product_id', '=', 'products.id')
                ->join('images', 'products.id', '=', 'images.products_id')
                ->join('tags', 'products.tag_id', '=', 'tags.id')
                ->where('images.type', 'main')
                ->where('tags.lang_id', 'tr')
                ->select('products.name', 'images.image_url', 'products.id', 'product_later.mail', 'products.url_parametre', 'tags.tag_ceo')
                ->where('product_later.mail_send', false)->get();


            foreach ($todayReminderList as $reminder) {


                $tempMailTemplateName = "v2_BNF_Product_Later";
                $tempMailSubjectName = $reminder->name . " siparişini şimdi verebilirsin.";

                \MandrillMail::messages()->sendTemplate($tempMailTemplateName, null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => 'Bloomandfresh dünyasına hoşgeldiniz',
                    'subject' => $tempMailSubjectName,
                    'from_email' => 'hello@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => $reminder->mail,
                            'type' => 'to'
                        )
                    ),
                    'merge' => true,
                    'merge_language' => 'mailchimp',
                    'global_merge_vars' => array(
                        array(
                            'name' => 'product_id',
                            'content' => $reminder->id,
                        ), array(
                            'name' => 'PRNAME',
                            'content' => $reminder->name,
                        ), array(
                            'name' => 'URL',
                            'content' => $reminder->tag_ceo . '/' . $reminder->url_parametre. '-' . $reminder->id,
                        )
                    , array(
                            'name' => 'IMAGE',
                            'content' => $reminder->image_url,
                        )
                    )
                ));

            }

            DB::table('product_later')->update([
                'mail_send' => true
            ]);

        })->cron('0 10 * * * *');

        $schedule->call(function () {

            //try {

            $tempNow = Carbon::now()->addMinute(-5);
            $tempNow2 = Carbon::now()->addMinute(-7);

            $tempCompleteSale = DB::table('sales')->where('payment_methods', 'OK')->where('created_at', '<', $tempNow)->where('created_at', '>', $tempNow2)->where('mailSent', 0)->get();

            foreach ($tempCompleteSale as $tempSale) {

                $mailData = DB::table('sales')
                    ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                    ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                    ->join('delivery_locations', 'sales.delivery_locations_id', '=', 'delivery_locations.id')
                    ->where('sales.id', '=', $tempSale->id)
                    ->select('delivery_locations.district', 'sales.id', 'sales.sum_total', 'customer_contacts.surname as contact_surname', 'customer_contacts.name as contact_name', 'deliveries.wanted_delivery_limit',
                        'deliveries.created_at', 'deliveries.wanted_delivery_date', 'deliveries.products', 'sales.receiver_address as address', 'sales_products.products_id', 'sales.card_message', 'sales.ups',
                        'sales.sender_name as name', 'sales.sender_surname as surname', 'sales.sender_mobile as mobile', 'customers.email', 'customers.user_id as user_id', 'sales.lang_id', 'sales.sender', 'sales.receiver', 'IsTroyCard', 'paymentAmount' )
                    ->get()[0];

                $tempValue = DB::table('cross_sell')
                    ->join('cross_sell_products', 'cross_sell.product_id', '=', 'cross_sell_products.id')
                    ->select('cross_sell_products.image', 'cross_sell_products.name', 'cross_sell.product_price', 'cross_sell.product_price', 'cross_sell.total_price', 'cross_sell.tax', 'cross_sell_products.id')
                    ->where('sales_id', $mailData->id)->get();
                if (count($tempValue) == 0) {
                } else {
                    $mailData->sum_total = number_format(floatval(str_replace(',', '.', $mailData->sum_total)) + floatval(str_replace(',', '.', $tempValue[0]->total_price)), 2);
                    $mailData->sum_total = str_replace('.', ',', $mailData->sum_total);
                }

                if ($mailData->email == null) {
                    $mailData->email = DB::table('users')->where('id', $mailData->user_id)->get()[0]->email;
                }

                $tempBilling = DB::table('billings')->where('sales_id', $tempSale->id)->get();

                $adress = 'İstanbul';
                if ($tempBilling[0]->billing_address) {
                    $adress = $tempBilling[0]->billing_address;
                }

                $created = new Carbon($mailData->wanted_delivery_limit);

                $dateEnd = new Carbon($mailData->wanted_delivery_limit);
                $wantedDeliveryDate = new Carbon($mailData->wanted_delivery_date);
                $wantedDeliveryDateInfo = $wantedDeliveryDate->format('d-m-Y') . ' ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . '00'  . ' - ' . str_pad($dateEnd->hour , 2, '0', STR_PAD_LEFT)  . ':' . '00' ;

                if( $mailData->ups == 1 ){
                    $wantedDeliveryDateInfo = 'UPS Kargo Standart Hizmet Teslimat Süreleri Esastır. ';
                    $deliveryDetailInfo = 'Kargo ile gönderilen ürünlerde UPS Kargo Standart Hizmet Teslimat Süreleri Esastır. ';
                }
                else{
                    $deliveryDetailInfo = '';
                }


                $strOBF = "Ön Bilgilendirme Formu
<br>
<br>
1. SATICIYA İLİŞKİN BİLGİLER
<br>
Ticari Ünvan:IF Girişim ve Teknoloji Anonim Şirketi (Bundan böyle “SATICI” olarak anılacaktır)
<br>
Adres:Boyacıçeşme Sok. No:12  Emirgan Sarıyer-İstanbul
<br>
Telefon:(212) 212 0 282
<br>
Faks:(212) 212 0 292
<br>
E-posta Adresi:siparis@bloomandfresh.com
<br>
Mersis Numarası:0465041271300014
<br>
<br>
2. ALICIYA İLİŞKİN BİLGİLER
<br>
Adı Soyadı:" . $mailData->name . " " . $mailData->surname . "
<br>
Adresi: " . $adress . "
<br>
Telefon: " . $mailData->mobile . "
<br>
E-posta Adresi: " . $mailData->email . "
<br>
<br>
3. KONU
<br>
İşbu Ön Bilgilendirme Formu’nun konusu; Alıcının, aşağıda nitelik ve satış fiyatı belirtilen ürün ya da ürünlerin satışı ve teslimi ile ilgili olarak 6502 sayılı Tüketicilerin Korunması Hakkında Kanun ve 27 Kasım 2014 tarihli ve 29188 sayılı Resmi Gazetede yayınlanan Mesafeli Sözleşmelere Dair Yönetmelik hükümleri gereğince bilgilendirilmesidir.
<br>
<br>
                4. SÖZLEŞME KONUSU ÜRÜNÜN TEMEL ÖZELLİKLERİ ve ÖDEME BİLGİLERİ
<br>
İşbu kısımda sözleşme konusu ürün ya da ürünlerin temel özellikleri açıklanmaktadır.
<br>
                Mal/Ürün/Hizmet Türü: Çiçek ve Hediye Gönderimi
<br>
Sipariş Numarası: " . $mailData->id . "
<br>
Ürün Adı: " . $mailData->products . "
<br>
Miktarı: 1
<br>
Satış Fiyatı (Vergiler Dahil): " . $mailData->sum_total . "
<br>
Sipariş bedelinin ödenme şekli: Kredi Kartı veya Banka Kartı
<br>
Teslimat Şekli: Servis/Anlaşmalı Kargo
<br>
Teslim masraflarının tutarı :0,00 TL
<br>
<br>
5. MAL/HİZMETİN TESLİM ZAMANI
<br>
Teslimat, sipariş sırasında seçilen tarih ve saat aralığı içerisinde gerçekleştirilecektir. " . $deliveryDetailInfo . "
<br>
                6. MAL/HİZMETİN TESLİMATI
<br>
Mal/hizmetin teslimi Alıcı veya gösterdiği adresteki kişi/kuruluşa yapılır. Alıcı, kendisinden başka birine ve de kendi adresinden başka bir adrese teslimat yapılmasını isterse, bu talebi doğrultusunda teslimat yapılır. Teslimat masrafları Alıcıya aittir. Satıcı, web sitesinde, ilan ettiği rakamın üzerinde alışveriş yapanların veya kimi kampanyalarında teslimat ücretinin kendisince karşılanacağını beyan etmişse, teslimat masrafı Satıcı tarafından karşılanır.
<br>
                Sipariş konusu mal/hizmetin teslimatı için işbu önbilgilendirme formunun Alıcı tarafından online olarak onaylanmış olması ve mal/hizmet bedelinin Alıcının tercih ettiği ödeme sekli ile ödenmiş olması şarttır. Herhangi bir nedenle mal/hizmet bedeli ödenmez veya banka kayıtlarında iptal edilir ise, Satıcı mal/hizmetin teslimi yükümlülüğünden kurtulmuş kabul edilir.
<br>
                7. ÖDEME ŞEKLİ
<br>
Ödemeler kredi kartı, EFT veya havale yöntemlerinden birisi kullanılarak yapılabilir.
<br>
                8. GEÇERLİLİK SÜRESİ
<br>
Listelenen ve siteden ilan edilen fiyatlar satış fiyatıdır. İlan edilen fiyatlar ve vaatler güncelleme yapılana ve değiştirilene kadar geçerlidir. Süreli olarak ilan edilen fiyatlar ise belirtilen süre sonuna kadar geçerliliğini korur. Ancak hatayla yanlış yazılan, tedarikçinin geç bildirmesi ile güncellenmemiş olan fiyat farklılıklarında Satıcının müşteriye bildireceği güncel fiyat geçerli kabul edilecektir. Hata durumlarında mal/hizmet bedelinden fazla çekim yapılmışsa farkı iade edilir. Mal/hizmetin gerçek fiyatı ilan edilenden farklı ise Alıcıya gerçek fiyat bildirilir. Müşterinin talebi doğrultusunda gerçek fiyat üzerinden satış gerçekleştirilir ya da satış iptal edilir.
<br>
                9. CAYMA HAKKI
<br>
Alıcı mal satışına ilişkin mesafeli sözleşmelerde, malı teslim aldığı tarihten itibaren on dört gün içerisinde hiçbir hukuki ve cezai sorumluluk üstlenmeksizin ve hiçbir gerekçe göstermeksizin malı reddederek sözleşmeden cayma hakkına sahiptir. Hizmet sunumuna ilişkin mesafeli sözleşmelerde ise, bu süre sözleşmenin imzalandığı tarihte başlar. Sözleşmede, hizmetin ifasının on dört günlük süre dolmadan yapılması kararlaştırılmışsa, tüketici ifanın başlayacağı tarihe kadar cayma hakkını kullanabilir. Cayma hakkının kullanımından kaynaklanan masraflar satıcıya aittir.
<br>
                Cayma hakkının kullanılması için Alıcı tarafından on dört günlük süre içinde Satıcıya yukarıda bildirilen faks, telefon veya elektronik posta ile bildirimde bulunulması şarttır. Cayma Hakkı kapsamında yer alan iade usulleri Mesafeli Satış Sözleşmesinde yer almaktadır. Bu hakkın kullanılması halinde, 3. kişiye veya Alıcıya teslim edilen mal/hizmete ilişkin fatura aslinin iadesi zorunludur. Cayma hakkına ilişkin ihbarın ulaşmasını takip eden en geç 14 (on dört) gün içinde mal/hizmet bedeli ve teslimat masrafları Alıcıya iade edilir ve 10 (on) günlük süre içinde mal/hizmeti alıcı iade etmekle mükelleftir. Fatura asli gönderilmezse Alıcıya KDV ve varsa diğer yasal yükümlülükler iade edilemez. Cayma hakkı ile iade edilen mal/hizmetin teslimat bedeli Alıcı tarafından karşılanır.
<br>
<br>
                10. CAYMA HAKKININ KULLANILAMAYACAĞI MAL/HİZMETLER
<br>
Niteliği itibarıyla iade edilemeyecek mal/hizmetler, hızla bozulan ve son kullanma tarihi geçen mal/hizmetler, tek kullanımlık mal/hizmetler, kopyalanabilir her türlü yazılım ve programlardır.
<br>
                a) Fiyatı finansal piyasalardaki dalgalanmalara bağlı olarak değişen ve satıcı veya sağlayıcının kontrolünde olmayan mal veya hizmetlere ilişkin sözleşmeler.
<br>
                b) Tüketicinin istekleri veya kişisel ihtiyaçları doğrultusunda hazırlanan mallara ilişkin sözleşmeler.
<br>
                c) Çabuk bozulabilen veya son kullanma tarihi geçebilecek malların teslimine ilişkin sözleşmeler.
<br>
                ç) Tesliminden sonra ambalaj, bant, mühür, paket gibi koruyucu unsurları açılmış olan mallardan; iadesi sağlık ve hijyen açısından uygun olmayanların teslimine ilişkin sözleşmeler.
<br>
                d) Tesliminden sonra başka ürünlerle karışan ve doğası gereği ayrıştırılması mümkün olmayan mallara ilişkin sözleşmeler.
<br>
                e) Malın tesliminden sonra ambalaj, bant, mühür, paket gibi koruyucu unsurları açılmış olması halinde maddi ortamda sunulan kitap, dijital içerik ve bilgisayar sarf malzemelerine ilişkin sözleşmeler.
<br>
                f) Abonelik sözleşmesi kapsamında sağlananlar dışında, gazete ve dergi gibi süreli yayınların teslimine ilişkin sözleşmeler.
<br>
                g) Belirli bir tarihte veya dönemde yapılması gereken, konaklama, eşya taşıma, araba kiralama, yiyecek-içecek tedariki ve eğlence veya dinlenme amacıyla yapılan boş zamanın değerlendirilmesine ilişkin sözleşmeler.
<br>
                ğ) Elektronik ortamda anında ifa edilen hizmetler veya tüketiciye anında teslim edilen gayrimaddi mallara ilişkin sözleşmeler.
<br>
                h) Cayma hakkı süresi sona ermeden önce, tüketicinin onayı ile ifasına başlanan hizmetlere ilişkin sözleşmeler.
<br>
<br>
                11. GEÇERLİLİK
<br>
İşbu ön bilgilendirme formu, elektronik ortamda Alıcı tarafından okunarak kabul edildikten sonra Mesafeli Satış Sözleşmesi kurulması aşamasına geçilecektir.
<br>
<br>
                12. YETKİLİ MAHKEME
<br>
Tüketici; şikâyet ve itirazları konusunda başvurularını, T.C. Gümrük ve Ticaret Bakanlığı tarafından her yıl Aralık ayında belirlenen parasal sınırlar dâhilinde tüketicinin mal veya hizmeti satın aldığı veya ikametgâhının bulunduğu yerdeki tüketici sorunları hakem heyetine veya tüketici mahkemesine yapabilir.
<br>
                13. SON HÜKÜMLER
<br>
Siparişe ilişkin verilen belge ve bilgilerin eksik, sahte ve/veya yanlış olduğunun saptanması veya siparişin kötü niyetle/veya ticari ve/veya kazanç elde etmek amacıyla yapılmış olduğuna dair şüphenin varlığı ya da tespiti halinde, herhangi bir zamanda, Alıcı’yı bilgilendirmek koşuluyla sipariş başvurusunu, gerekli incelemelerin yapılmasını teminen durdurma ve/veya iptal etme hakkını saklı tutar. İptal halinde, ödeme için iade süreci yine Alıcı’ya bildirilmek kaydıyla yapılabilir.
<br>
                14. İSTİSNA
<br>
İşbu ön bilgilendirme formunda yer alan ve 6502 sayılı Tüketicinin Korunması Hakkında Kanundan doğarak tüketicilere hukuki koruma sağlayan madde hükümleri sadece alıcının Tüketici olduğu hallerde geçerli olarak hüküm ifade edecek olup; alıcının 6502 sayılı kanunda yer alan Tüketici tanımına uymadığı hallerde ilgili maddeler taraflar arasında hüküm ifade etmeyecektir.
<br>
                Alıcı; 6502 S.K.’un M. 48, f.2 ve Mes. Söz. Yön. 5., 6. ve 7. maddeleri gereğince Ön Bilgileri okuyup bilgi sahibi olduğunu ve elektronik ortamda gerekli teyidi verdiğini kabul, taahhüt ve beyan eder.";

                $strMSS = "Mesafeli Satış Sözleşmesi
<br>
<br>
MADDE 1 - TARAFLAR
<br>
<br>
1.1 SATICI
<br>
Ticari Ünvan:IF Girişim ve Teknoloji Anonim Şirketi (Bundan böyle “SATICI” olarak anılacaktır)
<br>
Adres:Boyacıçeşme Sok. No:12  Emirgan Sarıyer-İstanbul
<br>
Telefon:(212) 212 0 282
<br>
Faks:(212) 212 0 292
<br>
E-posta Adresi:siparis@bloomandfresh.com
<br>
Mersis Numarası:0465041271300014
<br>
<br>
1.2 ALICI
<br>
Adı Soyadı: " . $mailData->name . " " . $mailData->surname . "
<br>
Adresi: " . $adress . "
<br>
Telefon: " . $mailData->mobile . "
<br>
E-posta Adresi: " . $mailData->email . "
<br>
<br>
MADDE 2 - KONU
<br>
İşbu Sözleşmenin konusu, ALICI'nın SATICI'ya ait www.bloomandfresh.com İnternet sitesinden elektronik ortamda siparişini yaptığı aşağıda nitelikleri ve satış fiyatı belirtilen ürünün satışı ve teslimi ile ilgili olarak 6502 sayılı Tüketicilerin Korunması Hakkındaki Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri gereğince tarafların hak ve yükümlülüklerinin saptanmasıdır.
<br>
<br>
MADDE 3 - SÖZLEŞME KONUSU ÜRÜN
<br>
Sözleşme Tarihi: " . Carbon::now()->format('d-m-Y H:i') . "
<br>
Ürünün teslim tarihi/saat aralığı: " . $wantedDeliveryDateInfo . "
<br>
Mal/Ürün/Hizmet Türü: Çiçek ve Hediye Gönderimi
<br>
Sipariş Numarası: " . $mailData->id . "
<br>
Ürün Adı: " . $mailData->products . "
<br>
Miktarı: 1
<br>
Satış Fiyatı (Vergiler Dahil): " . $mailData->sum_total . "
<br>
Sipariş bedelinin ödenme şekli: Kredi Kartı veya Banka Kartı
<br>
Teslimat Şekli: Servis/Anlaşmalı Kargo
<br>
Teslim masraflarının tutarı :0,00 TL
<br>
<br>
MADDE 4 - CAYMA HAKKI
<br>
Alıcı mal satışına ilişkin mesafeli sözleşmelerde, malı teslim aldığı tarihten itibaren on dört gün içerisinde hiçbir hukuki ve cezai sorumluluk üstlenmeksizin ve hiçbir gerekçe göstermeksizin malı reddederek sözleşmeden cayma hakkına sahiptir. Hizmet sunumuna ilişkin mesafeli sözleşmelerde ise, bu süre sözleşmenin imzalandığı tarihte başlar. Sözleşmede, hizmetin ifasının on dört günlük süre dolmadan yapılması kararlaştırılmışsa, tüketici ifanın başlayacağı tarihe kadar cayma hakkını kullanabilir. Cayma hakkının kullanımından kaynaklanan masraflar satıcıya aittir.
<br>
Cayma hakkının kullanılması için Alıcı tarafından on dört günlük süre içinde Satıcıya yukarıda bildirilen faks, telefon veya elektronik posta ile bildirimde bulunulması şarttır. Cayma Hakkı kapsamında yer alan iade usulleri Mesafeli Satış Sözleşmesinde yer almaktadır. Bu hakkın kullanılması halinde, 3. kişiye veya Alıcıya teslim edilen mal/hizmete ilişkin fatura aslinin iadesi zorunludur. Cayma hakkına ilişkin ihbarın ulaşmasını takip eden en geç 14 (on dört) gün içinde mal/hizmet bedeli ve teslimat masraflarI Alıcıya iade edilir ve 10 (on) günlük süre içinde mal/hizmeti alıcı iade etmekle mükelleftir. Fatura asli gönderilmezse Alıcıya KDV ve varsa diğer yasal yükümlülükler iade edilemez. Cayma hakkı ile iade edilen mal/hizmetin teslimat bedeli Alıcı tarafından karşılanır.
<br>
<br>
MADDE 5 - CAYMA HAKKININ KULLANILAMAYACAĞI MAL/HİZMETLER
<br>
Niteliği itibarıyla iade edilemeyecek mal/hizmetler, hızla bozulan ve son kullanma tarihi geçen mal/hizmetler, tek kullanımlık mal/hizmetler, kopyalanabilir her türlü yazılım ve programlardır.
<br>
a) Fiyatı finansal piyasalardaki dalgalanmalara bağlı olarak değişen ve satıcı veya sağlayıcının kontrolünde olmayan mal veya hizmetlere ilişkin sözleşmeler.
<br>
b) Tüketicinin istekleri veya kişisel ihtiyaçları doğrultusunda hazırlanan mallara ilişkin sözleşmeler.
<br>
c) Çabuk bozulabilen veya son kullanma tarihi geçebilecek malların teslimine ilişkin sözleşmeler.
<br>
ç) Tesliminden sonra ambalaj, bant, mühür, paket gibi koruyucu unsurları açılmış olan mallardan; iadesi sağlık ve hijyen açısından uygun olmayanların teslimine ilişkin sözleşmeler.
<br>
d) Tesliminden sonra başka ürünlerle karışan ve doğası gereği ayrıştırılması mümkün olmayan mallara ilişkin sözleşmeler.
<br>
e) Malın tesliminden sonra ambalaj, bant, mühür, paket gibi koruyucu unsurları açılmış olması halinde maddi ortamda sunulan kitap, dijital içerik ve bilgisayar sarf malzemelerine ilişkin sözleşmeler.
<br>
f) Abonelik sözleşmesi kapsamında sağlananlar dışında, gazete ve dergi gibi süreli yayınların teslimine ilişkin sözleşmeler.
<br>
g) Belirli bir tarihte veya dönemde yapılması gereken, konaklama, eşya taşıma, araba kiralama, yiyecek-içecek tedariki ve eğlence veya dinlenme amacıyla yapılan boş zamanın değerlendirilmesine ilişkin sözleşmeler.
<br>
ğ) Elektronik ortamda anında ifa edilen hizmetler veya tüketiciye anında teslim edilen gayrimaddi mallara ilişkin sözleşmeler.
<br>
h) Cayma hakkı süresi sona ermeden önce, tüketicinin onayı ile ifasına başlanan hizmetlere ilişkin sözleşmeler.
<br>
<br>
MADDE 6 - GENEL HÜKÜMLER
<br>
6.1 ALICI, Madde 3'te belirtilen Sözleşme konusu ürünün temel nitelikleri, satış fiyatı ve ödeme şekli ile teslimata ilişkin tüm ön bilgileri okuyup bilgi sahibi olduğunu ve elektronik ortamda gerekli teyidi verdiğini beyan eder.
<br>
6.2 Sözleşme konusu ürün, yasal 30 (otuz) günlük süreyi aşmamak koşulu ile her bir ürün için ALICI'nın yerleşim yerinin uzaklığına bağlı olarak ön bilgiler içinde açıklanan süre içinde ALICI veya gösterdiği adresteki kişi/kuruluşa teslim edilir. Satıcı bu yükümlülüğüne aykırı davranır ise tüketici işbu Sözleşme’yi feshedebilir. Sözleşme’nin feshi durumunda, satıcı veya sağlayıcı, varsa teslimat masrafları da dahil olmak üzere tahsil edilen tüm ödemeleri fesih bildiriminin kendisine ulaştığı tarihten itibaren 14 (ondört) gün içinde tüketiciye ilgili mevzuat uyarınca belirlenen kanuni faiziyle birlikte geri ödemek ve varsa tüketiciyi borç altına sokan tüm kıymetli evrak ve benzeri belgeleri iade etmek zorundadır.
<br>
6.3 Sözleşme konusu ürün, ALICI'dan başka bir kişi/kuruluşa teslim edilecek ise, teslim edilecek kişi/kuruluşun teslimatı kabul etmemesinden SATICI sorumlu tutulamaz.
<br>
6.4 SATICI, Sözleşme konusu ürünün sağlam, eksiksiz, siparişte belirtilen niteliklere uygun olarak teslim edilmesinden sorumludur. Haklı bir sebebe dayanmak şartıyla SATICI, Sözleşme’den doğan ifa yükümlülüğünün süresi dolmadan, ALICI’ya eşit kalite ve fiyatta mal veya hizmet tedarik edebilir.
<br>
6.5 Sözleşme konusu ürünün teslimatı için işbu Sözleşme’nin elektronik ortamda teyit edilmesi ve Sözleşme konusu siparişin bedelinin ödenmesi şarttır. Herhangi bir nedenle ürün bedeli ödenmez veya banka kayıtlarında iptal edilir ise, SATICI ürün teslimi yükümlülüğünden kurtulmuş kabul edilir.
<br>
6.6 SATICI sipariş konusu mal ya da hizmet ediminin yerine getirilmesinin imkansızlaştığı hallerde durumu öğrendiği tarihten itiberen 3 gün içerisinde ALICI'ya durumu yazılı olarak veya kalıcı veri saklayıcısı ile bildirmekle yükümlüdür. Bu durumda SATICI teslimat masrafları da dahil olmak üzere tahsil edilen tüm ödemeleri bildirim tarihinden itibaren en geç 14 (ondört) gün içerisinde ALICI’ya iade eder.
<br>
6.7 SATICI, malın ALICI ya da ALICI’nın taşıyıcı dışında belirleyeceği üçüncü bir kişiye teslimine kadar oluşan kayıp ve hasarlardan sorumludur.
<br>
6.8 ALICI’nın SATICI’nın belirlediği taşıyıcı dışında başka bir taşıyıcı ile malın gönderilmesini talep etmesi durumunda, malın ilgili taşıyıcıya tesliminden itibaren oluşabilecek kayıp ya da hasardan SATıCı sorumlu değildir.
<br>
6.9 SATICI tarafından sunulan hizmet perakende satış kapsamında tüketiciye yöneliktir; SATICI, ALICI’nın yeniden satış amacı bulunduğundan şüphe etmesi halinde işbu Sözleşme kurulmuş olsa dahi siparişi iptal etme ve ürünleri teslim etmeme hakkını saklı tutar.
<br>
6.10 SATICI mücbir sebepler veya nakliyeyi engelleyen hava muhalefeti, ulaşımın kesilmesi gibi olağanüstü durumlar nedeni ile Sözleşme konusu ürünü süresi içinde teslim edemez ise, durumu ALICI'ya bildirmekle yükümlüdür. Bu takdirde ALICI siparişin iptal edilmesini, Sözleşme konusu ürünün varsa emsali ile değiştirilmesini, ve/veya teslimat süresinin engelleyici durumun ortadan kalkmasına kadar ertelenmesi haklarından birini kullanabilir. ALICI'nın siparişi iptal etmesi halinde ödediği tutar 14 gün içinde kendisine nakden ve defaten ödenir.
<br>
<br>
MADDE 7 - YETKİLİ MAHKEME
<br>
İşbu Sözleşmenin uygulanmasında, tüketici; şikâyet ve itirazları konusunda başvurularını, T.C. Gümrük ve Ticaret Bakanlığı tarafından her yıl Aralık ayında belirlenen parasal sınırlar dâhilinde tüketicinin mal veya hizmeti satın aldığı veya ikametgâhının bulunduğu yerdeki tüketici sorunları hakem heyetine veya tüketici mahkemesine yapabilir.
<br>
İstisna
<br>
İşbu mesafeli satış sözleşmesinde yer alan ve 6502 sayılı Tüketicinin Korunması Hakkında Kanundan doğarak tüketicilere hukuki koruma sağlayan madde hükümleri sadece alıcının Tüketici olduğu hallerde geçerli olarak hüküm ifade edecek olup; alıcının 6502 sayılı kanunda yer alan Tüketici tanımına uymadığı hallerde ilgili maddeler taraflar arasında hüküm ifade etmeyecektir.
<br>
IF Girişim ve Teknoloji Anonim Şirketi A.Ş.";

                $pdf1 = \App::make('dompdf.wrapper');

                $pdf1->loadHTML('
                <html>
                <head>
                    <meta http-equiv=\"content-type\" content=\"application/vnd.ms-excel; charset=UTF-8\">
                    <style>
                    .page-break {
                        page-break-after: always;
                    }
                </style>
                </head>
                <body style="font-family: DejaVu Sans,sans-serif;">

                <p>' . $strMSS . '</p>
                </body>
                </html>
                ');

                $pdf2 = \App::make('dompdf.wrapper');

                $pdf2->loadHTML('
                <html>
                <head>
                    <meta http-equiv=\"content-type\" content=\"application/vnd.ms-excel; charset=UTF-8\">
                    <style>
                    .page-break {
                        page-break-after: always;
                    }
                </style>
                </head>
                <body style="font-family: DejaVu Sans,sans-serif;">

                <p>' . $strOBF . '</p>
                </body>
                </html>
                ');

                $contentStr = base64_encode($pdf1->stream());

                $contentObf = base64_encode($pdf2->stream());

                setlocale(LC_TIME, "");
                setlocale(LC_ALL, 'tr_TR.utf8');

                $created = new Carbon($mailData->wanted_delivery_limit);

                $deliveryDate = new Carbon($mailData->created_at);
                $dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT);

                $wantedDeliveryDate = new Carbon($mailData->wanted_delivery_date);
                $wantedDeliveryDateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT) . ':' . '00' . ' - ' . str_pad($created->hour, 2, '0', STR_PAD_LEFT) . ':' . '00';

                DB::table('sales')->where('id', $tempSale->id)->update([
                    'mailSent' => 1
                ]);
                $tempMailTemplateName = "siparis_alindi_ekstre_urun";
                if ($mailData->lang_id == 'en') {
                    $tempMailTemplateName = "siparis_alindi_en";
                }

                $tempMailSubjectName = " Siparişin Bize Ulaştı";
                if ($mailData->lang_id == 'en') {
                    $tempMailSubjectName = " Order Has Been Taken";
                }

                if ($mailData->sender == "")
                    $mailData->sender = " ";
                if ($mailData->receiver == "")
                    $mailData->receiver = " ";

                $tempCikolat = DB::table('cross_sell')
                    ->join('cross_sell_products', 'cross_sell.product_id', '=', 'cross_sell_products.id')
                    ->select('cross_sell_products.image', 'cross_sell_products.name', 'cross_sell.product_price', 'cross_sell.product_price', 'cross_sell.total_price', 'cross_sell.tax', 'cross_sell_products.id', 'cross_sell_products.desc')
                    ->where('sales_id', $mailData->id)->get();
                if (count($tempCikolat) == 0) {
                    $tempCikolatDesc = "";
                    $tempCikolatName = "";
                } else {
                    $tempCikolatDesc = "Yanında da " . $tempCikolat[0]->name . " hazır bekliyor.";
                    $tempCikolatName = "Ekstra: " . $tempCikolat[0]->name . "<br>";
                }

                if( $mailData->ups ){
                    $tempMailTemplateName = "siparis_alindi_ups";
                    $wantedDeliveryDateInfo = explode("|", $wantedDeliveryDateInfo )[0];
                }

                if( $mailData->IsTroyCard ){
                    $mailData->paymentAmount = floatval($mailData->paymentAmount)/100;

                    parse_str($mailData->paymentAmount);
                    $mailData->sum_total = str_replace('.', ',', $mailData->paymentAmount);
                }

                \MandrillMail::messages()->sendTemplate($tempMailTemplateName, null, array(
                    'html' => '<p>Example HTML content</p>',
                    'text' => 'Siparişiniz başarıyla verilmiştir.',
                    'subject' => ucwords(strtolower($mailData->name)) . ', Bloom And Fresh - ' . $mailData->products . $tempMailSubjectName,
                    'from_email' => 'siparis@bloomandfresh.com',
                    'from_name' => 'Bloom And Fresh',
                    'to' => array(
                        array(
                            'email' => $mailData->email,
                            'type' => 'to'
                        )
                    ),
                    'merge' => true,
                    'merge_language' => 'mailchimp',
                    'global_merge_vars' => array(
                        array(
                            'name' => 'FNAME',
                            'content' => ucwords(strtolower($mailData->name)),
                        ), array(
                            'name' => 'SALEID',
                            'content' => $mailData->id,
                        ), array(
                            'name' => 'CNTCNAME',
                            'content' => ucwords(strtolower($mailData->contact_name)),
                        ), array(
                            'name' => 'CNTCLNAME',
                            'content' => ucwords(strtolower($mailData->contact_surname)),
                        ), array(
                            'name' => 'CNTTEL',
                            'content' => $mailData->mobile,
                        ), array(
                            'name' => 'CNTADD',
                            'content' => $mailData->address
                        ), array(
                            'name' => 'WANTEDDATE',
                            'content' => $wantedDeliveryDateInfo
                        ), array(
                            'name' => 'PRICE',
                            'content' => $mailData->sum_total
                        ), array(
                            'name' => 'PIMAGE',
                            'content' => DB::table('images')->where('type', 'main')->where('products_id', $mailData->products_id)->get()[0]->image_url
                        ), array(
                            'name' => 'PRNAME',
                            'content' => $mailData->products
                        ), array(
                            'name' => 'ORDERDATE',
                            'content' => $dateInfo
                        ), array(
                            'name' => 'NOTE',
                            'content' => $mailData->card_message
                        ), array(
                            'name' => 'SENDER',
                            'content' => $mailData->sender
                        ), array(
                            'name' => 'RECEIVER',
                            'content' => $mailData->receiver
                        ), array(
                            'name' => 'EKSTRA_URUN_NOTE',
                            'content' => $tempCikolatDesc
                        ), array(
                            'name' => 'EKSTRA_URUN_NAME',
                            'content' => $tempCikolatName
                        )
                    ),
                    'attachments' => array(
                        array(
                            'type' => 'application/pdf',
                            'name' => 'Mesafeli Satış Sözleşmesi.pdf',
                            'content' => $contentStr
                        ),
                        array(
                            'type' => 'application/pdf',
                            'name' => 'Ön Bilgilendirme Formu.pdf',
                            'content' => $contentObf
                        )
                    )
                ));
            }
            //} catch (\Exception $e) {
            //    ErrorLog::create([
            //        'method_name' => $e->getCode(),
            //        'error_code' => $e->getMessage(),
            //        'error_message' => $e->getCode(),
            //        'type' =>  $e->getMessage(),
            //        'related_variable' => $e->getCode(),
            //    ]);
            //}
        })->cron('* * * * * *');

        $schedule->call(function () {

            $sendingMails = DB::table('product_stock_mails')->where('is_mail_sent', 0)->get();

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

            }

            /*$salesWithoutStock = DB::table('sales')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=' , 'delivery_locations.id')
                ->join('sales_products', 'sales.id', '=' , 'sales_products.sales_id')
                ->where('is_stock_checked', 0)
                ->where('sales.payment_methods', 'OK')
                ->select('sales.id', 'delivery_locations.city_id', 'sales_products.products_id')->get();

            $salesWithoutStockCrossSell = DB::table('sales')
                ->join('cross_sell', 'sales.id', '=' , 'cross_sell.sales_id')
                ->join('cross_sell_products', 'cross_sell.product_id', '=' , 'cross_sell_products.id')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=' , 'delivery_locations.id')
                ->where('is_stock_checked', 0)
                ->where('sales.payment_methods', 'OK')
                ->where('delivery_locations.city_id', '!=' , '2')
                ->select('sales.id', 'delivery_locations.city_id', 'cross_sell.product_id as products_id', 'cross_sell_products.product_id')->get();

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

                                    DB::table('mail_trigger')->where('product_stock_id', $tempProductStock[0]->id )->update([
                                        'no_stock' => 0
                                    ]);

                                    DB::table('product_city')->where('city_id', $tempCityId)->where('product_id', $tempProductStock[0]->product_id)->update([
                                        'limit_statu' => '1'
                                    ]);

                                    if( $tempProductStock[0]->product_id ){

                                        DB::table('cross_sell_products')->where('city_id', $tempCityId)->where('product_id', $tempProductStock[0]->product_id)->update([
                                            'status' => 0
                                        ]);

                                    }

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
            
            foreach ( $salesWithoutStockCrossSell as $sale ){

                if( $sale->product_id == 0 ){

                    if( $sale->city_id == 2 ){
                        $tempCityId = 2;
                    }
                    else{
                        $tempCityId = 1;
                    }

                    $tempProductStock = DB::table('product_stocks')->where('cross_sell_id', $sale->products_id)->where('city_id', 1 )->get();

                    if( count($tempProductStock) > 0 ){
                        //dd($tempProductStock);
                        if( $tempProductStock[0]->active == 0 ){

                        }
                        else{
                            if( $tempProductStock[0]->count == 0 ){

                                DB::table('product_stock_user_log')->insert([
                                    'user_id' => 1,
                                    'product_stock_id' => $tempProductStock[0]->id,
                                    'type' => 'Ürün stok sayısı 0 iken sipariş alındı',
                                    'comment' => 'No comment'
                                ]);

                                $mailData = DB::table('mail_trigger')->where('product_stock_id', $tempProductStock[0]->id )->get()[0];

                                $tempProductStock = DB::table('product_stocks')->where('id', $tempProductStock[0]->id )->get();

                                $stringMain = 'Main';

                                if( count($tempProductStock) > 0 ){
                                    if( $tempProductStock[0]->count < 5 && $mailData->under_email == 1 ){

                                        $productData = DB::table('cross_sell_products')
                                            ->where('cross_sell_products.id', $tempProductStock[0]->cross_sell_id)
                                            ->select('name', 'image')->get()[0];

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
                                                    'content' => $productData->image,
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

                                        $productData = DB::table('cross_sell_products')
                                            ->where('cross_sell_products.id', $tempProductStock[0]->cross_sell_id)
                                            ->select('name', 'image')->get()[0];

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
                                                    'content' => $productData->image,
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

                                        DB::table('cross_sell_products')->where('city_id', $tempCityId)->where('id', $tempProductStock[0]->cross_sell_id)->update([
                                            'status' => '0'
                                        ]);

                                    }

                                }

                            }
                            else{
                                DB::table('product_stocks')->where('id', $tempProductStock[0]->id )->decrement('count', 1);

                                $mailData = DB::table('mail_trigger')->where('product_stock_id', $tempProductStock[0]->id )->get()[0];

                                $tempProductStock = DB::table('product_stocks')->where('id', $tempProductStock[0]->id )->get();

                                $stringMain = 'Main';

                                if( count($tempProductStock) > 0 ){
                                    if( $tempProductStock[0]->count < 5 && $mailData->under_email == 1 ){

                                        $productData = DB::table('cross_sell_products')
                                            ->where('cross_sell_products.id', $tempProductStock[0]->cross_sell_id)
                                            ->select('name', 'image')->get()[0];

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
                                                    'content' => $productData->image,
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

                                        $productData = DB::table('cross_sell_products')
                                            ->where('cross_sell_products.id', $tempProductStock[0]->cross_sell_id)
                                            ->select('name', 'image')->get()[0];

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
                                                    'content' => $productData->image,
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

                                        DB::table('cross_sell_products')->where('city_id', $tempCityId)->where('id', $tempProductStock[0]->cross_sell_id)->update([
                                            'status' => '0'
                                        ]);

                                    }

                                }

                            }
                        }
                    }

                }
                else{

                    $sale->products_id = $sale->product_id;

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

                                        DB::table('mail_trigger')->where('product_stock_id', $tempProductStock[0]->id )->update([
                                            'no_stock' => 0
                                        ]);

                                        DB::table('product_city')->where('city_id', $tempCityId)->where('product_id', $tempProductStock[0]->product_id)->update([
                                            'limit_statu' => '1'
                                        ]);

                                        DB::table('cross_sell_products')->where('city_id', $tempCityId)->where('id', $tempProductStock[0]->cross_sell_id)->update([
                                            'status' => '0'
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

            }*/

        })->cron('* * * * * *');

        $schedule->call(function () {

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
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
                ->select('dayHours.start_hour', 'delivery_hours.continent_id')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTag = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
                ->where('delivery_hours.city_id', 1)
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();

            $tempNowTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $now->dayOfWeek)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.city_id', 2)
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
                ->select('dayHours.start_hour')
                ->orderBy('dayHours.start_hour', 'DESC')
                ->get();
            $tempTomorrowTagAnk = DB::table('delivery_hours')
                ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                ->where('delivery_hours.day_number', $tomorrowDay)
                ->where('dayHours.active', 1)
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
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
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
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
                    ->where('delivery_hours.continent_id', '!=' , 'Ups')
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
                }
                else if (explode(":", $tempNowTag[0]->start_hour)[0] != "11" && ( $tempNowTag[0]->continent_id == 'Asya' || $tempNowTag[0]->continent_id == 'Asya-2' ) ) {
                    $now->addHours(-3);
                }
                else {
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
                ->where('delivery_hours.continent_id', '!=' , 'Ups')
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
                    ->where('delivery_hours.continent_id', '!=' , 'Ups')
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
                }
                else{
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
                    }
                    else{
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
                else {

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
                    else{
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
                $flowerList[$x]->landingAnimation = '';
                $flowerList[$x]->landingAnimation2 = '';
                for ($y = 0; $y < count($imageList); $y++) {

                    if ($imageList[$y]->type == "main") {
                        $flowerList[$x]->MainImage = $imageList[$y]->image_url;
                    } else if ($imageList[$y]->type == "mobile") {
                        $flowerList[$x]->mobileImage = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "landingAnimation") {
                        $flowerList[$x]->landingAnimation = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "landingAnimation2") {
                        $flowerList[$x]->landingAnimation2 = $imageList[$y]->image_url;
                    }
                    else if ($imageList[$y]->type == "detailImages") {
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

                if( DB::table('landing_products_times')->where('product_id', $flower->id )->where('city_id', $flower->city_id )->count() == 0 ){

                    DB::table('landing_products_times')->insert([
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

                    ]);

                }
                else{
                    DB::table('landing_products_times')->where('product_id', $flower->id )->where('city_id', $flower->city_id )->update([
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
                    ]);
                }

            }

        })->cron('* * * * * *');

        $schedule->call(function () {

            $now = Carbon::now();

            MarketingAct::where('expiredDate', '<', $now)->update([
                'valid' => '0'
            ]);

        })->cron('0 2 * * * *');

        $schedule->call(function () {

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

            foreach ($salesNotCompleted as $sale){

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

                if( count($processes) > 0 ){
                    if( $processes[0]->HataKodu == 13 && count($processes) == 1 ){

                    }
                    else{
                        foreach ($processes as $process){
                            if( DB::table('ups_delivery_detail')->where('ShipmentNo', $sale->ShipmentNo )->where('KayitNo', $process->KayitNo )->count() == 0 ){

                                $tempIslemZamani = $process->IslemZamani;

                                $tempIslemZamani = explode( '-' , $tempIslemZamani);

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

                                array_push($changedSales, $sale->sale_id );

                            }
                        }
                    }
                }

            }

            foreach ( $changedSales as $sale ){

                $tempDetailStatus = DB::table('ups_delivery_detail')->where('sale_id', $sale )->orderBy('IslemZamani', 'DESC')->take(1)->get()[0];

                if( DB::table('ups_sales')->where('sale_id', $sale)->get()[0]->status == 0 &&  $tempDetailStatus->DurumKodu != 0 ){

                    $sales = DB::table('deliveries')
                        ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                        ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                        ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                        ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                        ->where('sales.id' , $sale )
                        ->select(  'sales_products.products_id'  , 'customers.user_id as user_id' , 'sales.sender_email as email' , 'sales.sender_name as FNAME' , 'sales.sender_surname as LNAME' , 'sales.sum_total as PRICE', 'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME'
                            , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD' , 'deliveries.products as PRNAME' , 'sales.lang_id' , 'sales.id as sale_id', 'sales.delivery_locations_id' )
                        ->get()[0];

                    if(!$sales->email){
                        $sales->email = User::where('id' , $sales->user_id)->get()[0]->email;
                    }

                    $tempMailSubjectName = "Teslimat Aşamasında!";

                    $locationDaysNumber = DB::table('delivery_locations')
                        ->join('ups_cities', 'delivery_locations.city', '=' , 'ups_cities.name')
                        ->where( 'delivery_locations.id', $sales->delivery_locations_id )
                        ->select('ups_cities.delivery_days')
                        ->get()[0]->delivery_days;

                    \MandrillMail::messages()->sendTemplate('siparis_yola_cikti_ups', null, array(
                        'html' => '<p>Example HTML content</p>',
                        'text' => 'Siparişiniz yola çıktı.',
                        'subject' =>  ucwords(strtolower($sales->FNAME)) . ', Bloom And Fresh - ' . $sales->PRNAME . $tempMailSubjectName,
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
                            ),array(
                                'name' => 'LNAME',
                                'content' => ucwords(strtolower($sales->LNAME)),
                            ),array(
                                'name' => 'CNTCNAME',
                                'content' => ucwords(strtolower($sales->CNTCNAME)),
                            ),array(
                                'name' => 'CNTCLNAME',
                                'content' => ucwords(strtolower($sales->CNTCLNAME)),
                            ),array(
                                'name' => 'CNTTEL',
                                'content' => $sales->CNTTEL,
                            ),array(
                                'name' => 'CNTADD',
                                'content' => $sales->CNTADD,
                            ),array(
                                'name' => 'PRICE',
                                'content' => $sales->PRICE,
                            ), array(
                                'name' => 'PIMAGE',
                                'content' => DB::table('images')->where('type', 'main')->where('products_id', $sales->products_id)->get()[0]->image_url
                            ), array(
                                'name' => 'PRNAME',
                                'content' => $sales->PRNAME
                            ), array(
                                'name' => 'UPS_NO',
                                'content' => $tempDetailStatus->ShipmentNo
                            ), array(
                                'name' => 'DELIVERYDAYS',
                                'content' => $locationDaysNumber
                            )
                        )
                    ));
                    DB::table('deliveries')->where('sales_id', '=', $sale )->update([
                        'status' => 2,
                        'operation_id' => 'UPS',
                        'operation_name' => 'UPS'
                    ]);
                }

                if( DB::table('ups_sales')->where('sale_id', $sale)->get()[0]->status != 2 && $tempDetailStatus->DurumKodu == 2 ){

                    $sales = DB::table('deliveries')
                        ->join('sales', 'deliveries.sales_id', '=', 'sales.id')
                        ->join('customer_contacts', 'sales.customer_contact_id', '=', 'customer_contacts.id')
                        ->join('customers', 'customer_contacts.customer_id', '=', 'customers.id')
                        ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                        ->where('sales.id' , $sale )
                        ->select( 'deliveries.id as deliveryId' , 'sales_products.products_id' ,'deliveries.wanted_delivery_date' ,'deliveries.created_at as orderDate' ,'sales.id as id' ,'customers.user_id as user_id' , 'sales.sender_email as email' , 'sales.sender_name as FNAME' , 'sales.sender_surname as LNAME' , 'sales.sum_total as PRICE', 'customer_contacts.name as CNTCNAME', 'customer_contacts.surname as CNTCLNAME'
                            , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD' , 'deliveries.products as PRNAME' , 'deliveries.wanted_delivery_limit' )
                        ->get()[0];

                    if(!$sales->email){
                        $sales->email = User::where('id' , $sales->user_id)->get()[0]->email;
                    }

                    setlocale(LC_TIME, "");
                    setlocale(LC_ALL, 'tr_TR.utf8');

                    $created = new Carbon($sales->wanted_delivery_limit);

                    $requestDeliveryDate = new Carbon($sales->orderDate);
                    $requestDateInfo = $requestDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($requestDeliveryDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . str_pad($requestDeliveryDate->minute, 2, '0', STR_PAD_LEFT) ;

                    $deliveryDate = Carbon::now();

                    $dateInfo = $deliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($deliveryDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . str_pad($deliveryDate->minute, 2, '0', STR_PAD_LEFT) ;

                    $wantedDeliveryDate = new Carbon($sales->wanted_delivery_date);
                    $wantedDeliveryDateInfo = $wantedDeliveryDate->formatLocalized('%A %d %B') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . '00'  . ' - ' . str_pad($created->hour , 2, '0', STR_PAD_LEFT)  . ':' . '00' ;

                    $wantedDeliveryDateInfo = explode("|", $wantedDeliveryDateInfo )[0];

                    \MandrillMail::messages()->sendTemplate('siparis_teslim_alindi_ups', null, array(
                        'html' => '<p>Example HTML content</p>',
                        'text' => 'Siparişiniz başarıyla teslim edilmistir',
                        'subject' =>  ucwords(strtolower($sales->FNAME)) . ', Bloom And Fresh - ' . $sales->PRNAME . ' Teslim Edildi!',
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
                            ),array(
                                'name' => 'LNAME',
                                'content' => ucwords(strtolower($sales->LNAME)),
                            ),array(
                                'name' => 'CNTCNAME',
                                'content' => ucwords(strtolower($sales->CNTCNAME)),
                            ),array(
                                'name' => 'CNTCLNAME',
                                'content' => ucwords(strtolower($sales->CNTCLNAME)),
                            ),array(
                                'name' => 'CNTTEL',
                                'content' => $sales->CNTTEL,
                            ),array(
                                'name' => 'CNTADD',
                                'content' => $sales->CNTADD,
                            ),array(
                                'name' => 'TAKETIME',
                                'content' => $dateInfo,
                            ),array(
                                'name' => 'PRICE',
                                'content' => $sales->PRICE,
                            ),array(
                                'name' => 'PRNAME',
                                'content' => $sales->PRNAME
                            ),array(
                                'name' => 'SALEID',
                                'content' => $sales->id
                            ),array(
                                'name' => 'ORDERDATE',
                                'content' => $requestDateInfo
                            ),array(
                                'name' => 'WANTEDDATE',
                                'content' => $wantedDeliveryDateInfo
                            ),array(
                                'name' => 'PIMAGE',
                                'content' => DB::table('images')->where('type', 'main')->where('products_id', $sales->products_id)->get()[0]->image_url
                            ),array(
                                'name' => 'PICKER',
                                'content' => ucwords(strtolower( $tempDetailStatus->IslemAciklama ))
                            )
                        )
                    ));
                    DB::table('deliveries')->where('sales_id', '=', $sale)->update([
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

        })->cron('*/5 * * * * *');

        $schedule->call(function () {

            $xml = new \DOMDocument();

            $tempProductList = DB::table('products')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->join('tags', 'products.tag_id', '=', 'tags.id')
                ->join('product_city', 'products.id', '=', 'product_city.product_id')
                ->where('descriptions.lang_id', '=', 'tr')
                ->where('product_city.coming_soon', '0')
                ->where('product_city.limit_statu', '0')
                ->where('product_city.activation_status_id', '1')
                ->where('products.id', '!=', '75')
                ->where('product_city.city_id', '=', '1')
                ->where('products.company_product', '=', '0')
                ->where('tags.lang_id', '=', 'tr')
                ->select('products.price', 'products.id', 'products.name', 'descriptions.landing_page_desc', 'tags.tag_ceo', 'url_title', 'products.url_parametre')
                ->get();

            $tempString = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://bloomandfresh.com</loc><changefreq>Daily</changefreq><priority>1.0</priority></url>';
            $tempString = $tempString . '<url><loc>https://bloomandfresh.com/ayni-gun-teslim-hizli-cicek-gonder</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>';

            $tags = DB::table('tags')->where('lang_id', 'tr')->get();
            $flowerPages = DB::table('flowers_page')->where('active', 1)->get();

            foreach( $tags as $tag ){
                $tempString = $tempString . '<url><loc>https://bloomandfresh.com/' . $tag->tag_ceo . '</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>' ;
            }

            foreach( $flowerPages as $page ){
                $tempString = $tempString . '<url><loc>https://bloomandfresh.com/' . $page->url_name . '</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>' ;
            }

            $tempString = $tempString . '<url><loc>https://bloomandfresh.com/istanbul-online-cicek-siparisi</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>';

            foreach ($tempProductList as $product) {
                $tempString =  $tempString . '<url><loc>https://bloomandfresh.com/' . htmlspecialchars($product->tag_ceo) . '/' . htmlspecialchars($product->url_parametre) . '-' . $product->id . '</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>';
            }

            $tempString = $tempString . '<url><loc>https://bloomandfresh.com/hakkimizda</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/tasarim</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/detay</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/tazelik</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/sinirli-koleksiyon</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/gorundugu-gibi</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/nasil-yapiyoruz/hizli-siparis-ve-teslimat</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/bize-ulasin</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/destek</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/kurumsal-siparisler</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
<url><loc>https://bloomandfresh.com/godiva-cikolata-gonder</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>';

            $tempString = $tempString . '</urlset>';

            $xml->loadXml($tempString);

            $xml->save("/var/www/BNF/dist/sitemap.xml");

        })->cron('0 * * * * *');

        $schedule->call(function () {

            $now = Carbon::now();

            DB::table('daily_coupons')->where('end_date', '<', $now)->update([
                'active' => 0
            ]);

        })->cron('*/5 * * * * *');
    }

}
