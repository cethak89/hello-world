<?php namespace App\Http\Controllers;

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
use NumberFormatter;

class BillingOperation extends Controller
{

    public function serverXML(){

        $xml = new DOMDocument();
        $xml_album = $xml->createElement("Album");
        $xml_track = $xml->createElement("Track");
        $xml_album->appendChild( $xml_track );
        $xml->appendChild( $xml_album );


        $xml->save("/tmp/test.xml");
    }

    public function testDate(){
        dd("Sözleşme Tarihi: " . Carbon::now()->format('d-m-Y H:i') );
    }

    public function testStockManagement(){

            $salesWithoutStock = DB::table('sales')
                ->join('delivery_locations', 'sales.delivery_locations_id', '=' , 'delivery_locations.id')
                ->join('sales_products', 'sales.id', '=' , 'sales_products.sales_id')
                ->where('is_stock_checked', 0)
                ->where('sales.payment_methods', 'OK')
                ->select('sales.id', 'delivery_locations.city_id', 'sales_products.products_id')->get();

            dd($salesWithoutStock);

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

    public function testMailUPS(){

        //try {

        $tempNow = Carbon::now()->addMinute(-1);
        $tempNow2 = Carbon::now()->addMinute(-7);

        $tempCompleteSale = DB::table('sales')->where('payment_methods', 'OK')->where('created_at', '<', $tempNow)->where('created_at', '>', $tempNow2)->where('mailSent', 0)->get();

        //dd($tempCompleteSale);

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
                    'sales.sender_name as name', 'sales.sender_surname as surname', 'sales.sender_mobile as mobile', 'customers.email', 'customers.user_id as user_id', 'sales.lang_id', 'sales.sender', 'sales.receiver')
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
                Mal/Ürün/Hizmet Türü: Çiçek Gönderimi
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
Teslimat Şekli: Servis
<br>
Teslim masraflarının tutarı :0,00 TL
<br>
<br>
5. MAL/HİZMETİN TESLİM ZAMANI
<br>
Teslimat, sipariş sırasında seçilen tarih ve saat aralığı içerisinde gerçekleştirilecektir.
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
Sözleşme Tarihi: " . Carbon::now() . "
<br>
Ürünün teslim tarihi/saat aralığı: " . $mailData->wanted_delivery_date . "
<br>
Mal/Ürün/Hizmet Türü: Çiçek Gönderimi
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
Teslimat Şekli: Servis
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
    }

    public function testPeriodikMethods(){

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


            if( $sale->ShipmentNo == '1Z3018WY6800000888' ){
                dd(simplexml_load_string($res->IslemSorguTumHareketler_V1Result->any));
            }

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
                        , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD' , 'deliveries.products as PRNAME' , 'sales.lang_id' , 'sales.id as sale_id' )
                    ->get()[0];

                if(!$sales->email){
                    $sales->email = User::where('id' , $sales->user_id)->get()[0]->email;
                }

                $tempMailSubjectName = "Teslimat Aşamasında!";


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
                $wantedDeliveryDateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . '00'  . ' - ' . str_pad($created->hour , 2, '0', STR_PAD_LEFT)  . ':' . '00' ;

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

    }

    public function upsMailTest(){

        $changedSales = [ '165422' ];

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
                        , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD' , 'deliveries.products as PRNAME' , 'sales.lang_id' , 'sales.id as sale_id' )
                    ->get()[0];

                if(!$sales->email){
                    $sales->email = User::where('id' , $sales->user_id)->get()[0]->email;
                }

                $tempMailSubjectName = "Teslimat Aşamasında!";


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
                $wantedDeliveryDateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . '00'  . ' - ' . str_pad($created->hour , 2, '0', STR_PAD_LEFT)  . ':' . '00' ;

                $tempMailSubjectName = "Teslimat Aşamasında!";

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
                        )
                    )
                ));

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

    }

    public function testUPS(){

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

                if( $sale->ShipmentNo == '1Z3018WY6800000799' ){
                    //dd($processes);
                }


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
                            , 'sales.receiver_mobile as CNTTEL', 'sales.receiver_address as CNTADD' , 'deliveries.products as PRNAME' , 'sales.lang_id' , 'sales.id as sale_id' )
                        ->get()[0];

                    if(!$sales->email){
                        $sales->email = User::where('id' , $sales->user_id)->get()[0]->email;
                    }

                    $tempMailSubjectName = "Teslimat Aşamasında!";


                    \MandrillMail::messages()->sendTemplate('siparis_yola_cikti_ekstre_urun', null, array(
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
                                'name' => 'EKSTRA_URUN_NOTE',
                                'content' => ''
                            )
                        )
                    ));
                    DB::table('deliveries')->where('sales_id', '=', $sale )->update([
                        'status' => 2,
                        'operation_id' => 'UPS',
                        'operation_name' => 'UPS'
                    ]);
                }

                //dd($tempDetailStatus);

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
                    $wantedDeliveryDateInfo = $wantedDeliveryDate->formatLocalized('%A %d %b') . ' | ' . str_pad($wantedDeliveryDate->hour, 2, '0', STR_PAD_LEFT)  . ':' . '00'  . ' - ' . str_pad($created->hour , 2, '0', STR_PAD_LEFT)  . ':' . '00' ;

                    \MandrillMail::messages()->sendTemplate('siparis_teslim_alindi_ekstre_urun', null, array(
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
                            ), array(
                                'name' => 'EKSTRA_URUN_NOTE',
                                'content' => ''
                            ), array(
                                'name' => 'EKSTRA_URUN_NAME',
                                'content' => ''
                            )
                        )
                    ));
                    DB::table('deliveries')->where('sales_id', '=', $sale)->update([
                        'delivery_date' => $deliveryDate,
                        'status' => 3,
                        'operation_id' => 'UPS',
                        'operation_name' => 'UPS',
                        'picker' => $tempDetailStatus->IslemAciklama
                    ]);
                }
                else{
                    DB::table('deliveries')->where('sales_id', '=', $sale->sale_id )->update([
                        'status' => 2
                    ]);
                }

                DB::table('ups_sales')->where('sale_id', $sale)->update([
                    'status' => $tempDetailStatus->DurumKodu,
                    'status_desc' => $tempDetailStatus->IslemAciklama
                ]);
            }

    }

    public function generate1080Images(){

        $products = DB::table('products')->where('city_id', 1)->get();

        foreach ( $products as $product ){

            DB::table('images_social')->insert([
                'type' => '1080Main',
                'image_url' => 'https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/' . $product->id . '.jpg',
                'products_id' => $product->id
            ]);

        }

    }

    public function generateUserRights(){

        $allUsers = DB::table('user_rights')->where('name_id', '=', 'print_list')->select('user_id')->get();

        foreach ( $allUsers as $user ){
            DB::table('user_rights')->insert([
                'name' => 'Stok Yönetimi',
                'group_name' => 'product',
                'active' => 0,
                'user_id' => $user->user_id,
                'name_id' => 'stock'
            ]);
        }

        dd($allUsers);

    }

    public function generateUpsLocations(){

        $locations = DB::table('ups_locations')->get();

        foreach ( $locations as $location ){


            $city =  ucfirst(mb_strtolower(str_replace('I', 'ı', str_replace('İ', 'i', $location->il))));
            $small_city =  ucfirst(mb_strtolower(str_replace('I', 'ı', str_replace('İ', 'i', $location->ilce))));

            //dd($city);

            $tempId = DB::table('delivery_locations')->insertGetId([
                'city_id' => 3,
                'city' => $city,
                'small_city' => $small_city,
                'district' => $city . ' - ' . $small_city,
                'shop_id' => 1,
                'continent_id' => 'Ups',
                'active' => 1
            ]);

            DB::table('ups_locations')->where('id', $location->id )->update([
                'delivery_location_id' => $tempId
            ]);

        }
    }


    public function testTime(){

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
            }
            else {

                $tempFlowerNowTag = $NowTag;
                $tempFlowerTomorrowTag = $TomorrowTag;
                if ($flowerList[$x]->avalibility_time > $now) {
                    $tempFlowerNowTag = false;
                }

                $nowTemp2 = Carbon::now();
                //dd($tempFlowerNowTag);
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

                //dd($tempFlowerNowTag);
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

                if( $flowerList[$x]->id == 314 ){
                    //dd($flowerList[$x]);
                }

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

        //DB::table('landing_products_times')->delete();

        //dd($flowerList);

        foreach ($flowerList as $flower) {

            if( DB::table('landing_products_times')->where('product_id', $flower->id )->where('city_id', $flower->city_id )->count() == 0 ){

                DB::table('landing_products_times')->insert([
                    'product_id' => $flower->id,
                    'city_id' => $flower->city_id,
                    'avalibility_time' => $flower->avalibility_time,
                    'theDayAfter' => $flower->theDayAfter,
                    'today' => $flower->today,
                    'tomorrow' => $flower->tomorrow,
                    'MainImage' => $flower->MainImage,
                    'mobileImage' => $flower->mobileImage,
                    'tag_main' => $flower->tag_main

                ]);

            }
            else{
                DB::table('landing_products_times')->where('product_id', $flower->id )->where('city_id', $flower->city_id )->update([
                    'avalibility_time' => $flower->avalibility_time,
                    'theDayAfter' => $flower->theDayAfter,
                    'today' => $flower->today,
                    'tomorrow' => $flower->tomorrow,
                    'MainImage' => $flower->MainImage,
                    'mobileImage' => $flower->mobileImage,
                    'tag_main' => $flower->tag_main
                ]);
            }

        }

    }

    public function mailErrorTrigger(){
        $now = Carbon::now();
        $now->addMinutes(-15);

        $total = DB::table('sales')->where('created_at', '>', $now)->count();
        $transaction = DB::table('sales')->where('created_at', '>', $now)->where('payment_methods', 'OK')->count();
        $error_number = DB::table('sales')->where('created_at', '>', $now)->where('payment_methods', '!=' , 'OK')->whereNotNull('payment_methods')->count();
        $empty_sale = DB::table('sales')->where('created_at', '>', $now)->whereNull('payment_methods')->count();

        if( $total < $error_number*4 ){
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

    }


    public function mailTrigger(){

        $now = Carbon::now();
        $now->addDays(-1);

        $total = DB::table('sales')->where('created_at', '>', $now)->count();
        $transaction = DB::table('sales')->where('created_at', '>', $now)->where('payment_methods', 'OK')->count();
        $error_number = DB::table('sales')->where('created_at', '>', $now)->where('payment_methods', '!=' , 'OK')->whereNotNull('payment_methods')->count();
        $empty_sale = DB::table('sales')->where('created_at', '>', $now)->whereNull('payment_methods')->count();

        \MandrillMail::messages()->sendTemplate('bnf_sales_error_analiz', null, array(
            'html' => '<p>Example HTML content</p>',
            'text' => 'Günlük Ödeme Terk Analizi',
            'subject' => 'Günlük Ödeme Terk Analizi',
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

    public function senkronisationProductOrder(){
        $tempList = DB::table('landing_with_promo')->get();

        foreach ( $tempList as $sale ){
            DB::table('product_city')->where('product_id', $sale->product_id )->where('city_id', $sale->city_id )->update([
                'landing_page_order' => $sale->order
            ]);
        }
    }

    public function getProductSoonTime($id)
    {
        try {
            $flower = DB::table('products')
                ->where('products.id', '=', $id)
                ->get()[0];

            $now = Carbon::now();
            $tomorrow = Carbon::now();
            $theDayAfter = Carbon::now();
            $tomorrowDay = ($tomorrow->dayOfWeek + 1) % 8;

            $continent_ids = [
                (object)[
                    'continent_id' => 'Asya',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Avrupa',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Avrupa-2',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Avrupa-3',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Asya-2',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Ankara-1',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ],
                (object)[
                    'continent_id' => 'Ankara-2',
                    'now' => false,
                    'tomorrow' => false,
                    'theDayAfter' => Carbon::now()
                ]
            ];

            foreach ($continent_ids as $continent) {
                $tempNowTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', $now->dayOfWeek)
                    ->where('delivery_hours.continent_id', $continent->continent_id)
                    ->where('dayHours.active', 1)
                    ->select('dayHours.start_hour')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();
                $tempTomorrowTag = DB::table('delivery_hours')
                    ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                    ->where('delivery_hours.day_number', $tomorrowDay)
                    ->where('dayHours.active', 1)
                    ->where('delivery_hours.continent_id', $continent->continent_id)
                    ->select('dayHours.start_hour')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                $NowTag = false;
                $TomorrowTag = false;
                $theDayAfterTag = false;
                if (count($tempNowTag) == 0) {
                    $NowTag = false;
                } else {
                    $NowTag = true;
                    $now->hour(explode(":", $tempNowTag[0]->start_hour)[0]);
                    if ($now->hour != "18") {
                        $now->addHours(1);
                    } else if ($now->hour == "18") {
                        $now->subHours(1);
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
                    ->where('delivery_hours.continent_id', $continent->continent_id)
                    ->select('dayHours.start_hour', 'delivery_hours.day_number')
                    ->orderBy('delivery_hours.day_number')
                    ->orderBy('dayHours.start_hour', 'DESC')
                    ->get();

                if (count($tempDayAfterTag) > 0) {
                    $continent->theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                    $continent->theDayAfter->minute(0);
                    $continent->theDayAfter->addDays($tempDayAfterTag[0]->day_number - $continent->theDayAfter->dayOfWeek);
                    $theDayAfterTag = true;
                } else {
                    $tempDayAfterTag = DB::table('delivery_hours')
                        ->join('dayHours', 'delivery_hours.id', '=', 'dayHours.day_number')
                        ->where('delivery_hours.day_number', '<', $now->dayOfWeek)
                        ->where('dayHours.active', 1)
                        ->where('delivery_hours.continent_id', $continent->continent_id)
                        ->select('dayHours.start_hour', 'delivery_hours.day_number')
                        ->orderBy('delivery_hours.day_number')
                        ->orderBy('dayHours.start_hour', 'DESC')
                        ->get();

                    if (count($tempDayAfterTag) > 0) {
                        $continent->theDayAfter->hour(explode(":", $tempDayAfterTag[0]->start_hour)[0]);
                        $continent->theDayAfter->minute(0);
                        $continent->theDayAfter->addDays(7 + $tempDayAfterTag[0]->day_number - $continent->theDayAfter->dayOfWeek);
                        $theDayAfterTag = true;
                    }
                }
                //}

                $continent->now = $NowTag;
                $continent->tomorrow = $TomorrowTag;
                //$tempFlowerNowTag = $NowTag;
                //$tempFlowerTomorrowTag = $TomorrowTag;
                if ($flower->avalibility_time > $now) {
                    $continent->now = false;
                }
                $nowTemp2 = Carbon::now();
                if ($nowTemp2 > $now) {
                    $continent->now = false;
                }
                if ($flower->limit_statu) {
                    $continent->now = false;
                    $continent->tomorrow = false;
                }
                if ($flower->coming_soon) {
                    $continent->now = false;
                    $continent->tomorrow = false;
                }
                if (!$continent->now && $flower->avalibility_time > $tomorrow) {
                    $continent->tomorrow = false;
                    //dd($flowerList[$x]);
                }
                if ($theDayAfterTag || (!$continent->tomorrow && !$continent->now)) {
                    setlocale(LC_TIME, "");
                    setlocale(LC_ALL, 'tr_TR.utf8');
                    if ($flower->avalibility_time > $continent->theDayAfter) {
                        $continent->theDayAfter = new Carbon($flower->avalibility_time);
                        $continent->theDayAfter = $continent->theDayAfter->formatLocalized('%d %B');
                    } else {
                        $continent->theDayAfter = $continent->theDayAfter->formatLocalized('%d %B');
                    }
                }
                $continent->tomorrow = $continent->tomorrow && !$continent->now;
                $continent->now = $continent->now;
            }

            return response()->json(["status" => 1, "data" => $continent_ids], 200);

        } catch (\Exception $e) {
            logEventController::logErrorToDB('getProductSoonTime', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
    }
    

    public function getFlowerListForAllCity()
    {
        try {

            $flowerList = DB::table('shops')
                ->join('products_shops', 'shops.id', '=', 'products_shops.shops_id')
                ->join('products', 'products_shops.products_id', '=', 'products.id')
                ->join('product_city', 'products.id', '=', 'product_city.product_id')
                ->join('descriptions', 'products.id', '=', 'descriptions.products_id')
                ->join('landing_products_times', 'products.id', '=', 'landing_products_times.product_id')
                ->join('landing_with_promo', 'products.id', '=', 'landing_with_promo.product_id')
                //->join( DB::raw(' landing_products_times on products.id = landing_products_times.product_id and product_city.city_id = landing_products_times.city_id ') )
                ->where('shops.id', '=', 1)
                ->where('descriptions.lang_id', '=', 'tr')
                ->where('product_city.activation_status_id', '=', 1)
                ->where('product_city.active', '=', 1)
                ->whereRaw('landing_products_times.city_id = product_city.city_id ')
                ->whereRaw('landing_with_promo.city_id = product_city.city_id ')
                ->select('products.choosen','products.best_seller','products.cargo_sendable', 'products.tag_id', 'products.product_type', 'product_city.coming_soon', 'product_city.limit_statu', 'products.id', 'products.name', 'products.price','products.old_price', 'products.image_name',
                    'products.background_color', 'products.second_background_color', 'descriptions.landing_page_desc', 'descriptions.how_to_title', 'descriptions.detail_page_desc', 'products.url_parametre',
                    'products.company_product', 'product_city.city_id', 'descriptions.how_to_detail', 'products.youtube_url', 'descriptions.how_to_step1', 'descriptions.how_to_step2', 'descriptions.how_to_step3',
                    'descriptions.meta_description', 'product_city.avalibility_time', 'descriptions.img_title', 'descriptions.url_title', 'descriptions.extra_info_1', 'descriptions.extra_info_2',
                    'descriptions.extra_info_3', 'products.speciality', 'landing_products_times.avalibility_time', 'landing_products_times.theDayAfter', 'landing_products_times.today',
                    'landing_products_times.theDayAfter_ups', 'landing_products_times.today_ups', 'landing_products_times.tomorrow_ups',
                    'landing_products_times.tomorrow', 'landing_products_times.MainImage', 'landing_products_times.mobileImage', 'landing_products_times.landingAnimation', 'landing_products_times.landingAnimation2', 'landing_products_times.tag_main')
                ->orderBy('landing_with_promo.order')
                ->get();

            /*for ($x = 0; $x < count($flowerList); $x++) {


                if ($flowerList[$x]->city_id == 2) {

                    $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', 'tr')->get();
                    if (count($primaryTag) > 0) {
                        $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                    } else {
                        $flowerList[$x]->tag_main = 'cicek';
                    }
                } else {

                    $primaryTag = DB::table('tags')->where('id', $flowerList[$x]->tag_id)->where('lang_id', 'tr')->get();
                    if (count($primaryTag) > 0) {
                        $flowerList[$x]->tag_main = $primaryTag[0]->tag_ceo;
                    } else {
                        $flowerList[$x]->tag_main = 'cicek';
                    }

                }

            }*/

            return $flowerList;
        } catch (\Exception $e) {
            logEventController::logErrorToDB('getFlowerList', $e->getCode(), $e->getMessage(), 'WS', '');
            return response()->json(["status" => -1, "description" => 400], 400);
        }
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
                    'MainImage' => $flower->MainImage,
                    'mobileImage' => $flower->mobileImage,
                    'tag_main' => $flower->tag_main

                ]);

            }
            else{
                DB::table('landing_products_times')->where('product_id', $flower->id )->where('city_id', $flower->city_id )->update([
                    'avalibility_time' => $flower->avalibility_time,
                    'theDayAfter' => $flower->theDayAfter,
                    'today' => $flower->today,
                    'tomorrow' => $flower->tomorrow,
                    'MainImage' => $flower->MainImage,
                    'mobileImage' => $flower->mobileImage,
                    'tag_main' => $flower->tag_main
                ]);
            }

        }

    }

    public function fillMobileImage()
    {
        $tempImages = DB::table('images')->where('type', 'main')->get();

        foreach ($tempImages as $mainImage) {

            $tempvar = DB::table('images')->where('products_id', $mainImage->products_id)->where('type', 'mobile')->get();

            if (count($tempvar) == 0) {
                dd($mainImage->products_id);
            }

            /*DB::table('images')->insert([
                'type' => 'mobile',
                'image_url' => $mainImage->image_url,
                'version_id' => 0,
                'description' => '',
                'products_id' => $mainImage->products_id,
                'order_no' => 0
            ]);*/
        }

        dd($tempImages);
    }

    public static function soapTest($sales_id)
    {
        return '';
        try {
            $tempLogString = "";

            $list = DB::table('sales')
                ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
                ->join('billings', 'sales.id', '=', 'billings.sales_id')
                ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
                ->join('products', 'sales_products.products_id', '=', 'products.id')
                ->where('sales.id', $sales_id)
                ->where('sales.payment_methods', 'OK')
                ->where('sales.send_billing', '0')
                ->where('sales.payment_type', '!=', 'KURUMSAL')
                ->orderBy('deliveries.delivery_date')
                ->select('sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling',
                    'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
                    'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'sales.sender_mobile', 'products.name as products', 'products.city_id', 'sales.sender_name', 'sales.sender_surname',
                    'sales.product_price as price', 'products.id', 'products.product_type', 'sales.customer_contact_id', 'sales.send_billing', 'deliveries.delivery_date', 'sales.payment_type')
                ->get();
            DB::table('sales')->where('id', $sales_id)->update([
                'send_billing' => 1
            ]);
            $tempLogString = $tempLogString . count($list) . " kayıt/";

            $firstPrice = 0;
            $totalDiscount = 0;
            $totalPartial = 0;
            $totalKDV = 0;
            $total = 0;
            if (count($list) > 0)
                if ($list[0]->send_billing == 0) {
                    $total_discount = 0;
                    $flower_discount = 0;
                    foreach ($list as $row) {
                        if ($row->products == 'B&F Cornet') {
                            $row->products = 'BNF Cornet';
                        }

                        if ($row->product_type == 2) {
                            $tempKDV = '8.0';
                        } else {
                            $tempKDV = '18.0';
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

                        //$total = $total + $row->sumTotal;
                    }

                    if ($tempCikolat) {
                        $cikolatPrice = $tempCikolat->total_price;
                        $cikolatProductPrice = $tempCikolat->product_price;
                        $cikolatProductTax = $tempCikolat->tax;

                        if ($tempCikolat->discount > 0) {
                            //$total_discount = floatval(str_replace(',', '.', $total_discount)) + floatval(str_replace(',', '.', $tempCikolat->discount));
                            //$total_discount = str_replace(',', '.', $total_discount);
                            //$row->discountVal = floatval(str_replace(',', '.', $tempCikolat->discount)) + floatval(str_replace(',', '.', $row->discountVal));
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
                        } else {
                            $tempDiscountTextCrossSell = '';
                            $tempCikolatNumber = '2';
                        }

                        $cikolatLine = '<ns5:InvoiceLine>
                            <ns4:ID>' . $tempCikolatNumber . '</ns4:ID>
                            <ns4:InvoicedQuantity unitCode="C62">1</ns4:InvoicedQuantity>
                            <ns4:LineExtensionAmount currencyID="TRY">' . str_replace(',', '.', $tempCikolat->product_price) . '</ns4:LineExtensionAmount>
                            ' . $tempDiscountTextCrossSell . '
                            <ns5:Item>
                                <ns4:Name>' . $tempCikolat->name . '</ns4:Name>
                                <ns5:SellersItemIdentification>
                                    <ns4:ID>c-' . $tempCikolat->id . '</ns4:ID>
                                </ns5:SellersItemIdentification>
                            </ns5:Item>
                            <!--Optional:-->
                            <ns5:TaxTotal>
                                <ns4:TaxAmount currencyID="TRY">' . str_replace(',', '.', $tempCikolat->product_price) . '</ns4:TaxAmount>
                                <ns5:TaxSubtotal>
                                    <ns4:TaxableAmount
                                            currencyID="TRY">' . str_replace(',', '.', $tempCikolat->product_price) . '
                                    </ns4:TaxableAmount>
                                    <ns4:TaxAmount
                                            currencyID="TRY">' . str_replace(',', '.', $tempCikolat->tax) . '
                                    </ns4:TaxAmount>
                                    <ns4:CalculationSequenceNumeric>
                                        1
                                    </ns4:CalculationSequenceNumeric>
                                    <ns4:Percent>8.0</ns4:Percent>
                                    <ns5:TaxCategory>
                                        <ns5:TaxScheme>
                                            <ns4:TaxTypeCode>0015</ns4:TaxTypeCode>
                                        </ns5:TaxScheme>
                                    </ns5:TaxCategory>
                                </ns5:TaxSubtotal>
                            </ns5:TaxTotal>
                            <ns5:Price>
                                <!--Optional:-->
                                <ns4:PriceAmount currencyID="TRY">' . str_replace(',', '.', $tempCikolat->product_price) . '</ns4:PriceAmount>
                            </ns5:Price>
                            <!--Zero or more repetitions:-->
                        </ns5:InvoiceLine>';

                        $cikolatTaxLine = '<ns5:TaxSubtotal>
                                <ns4:TaxableAmount
                                        currencyID="TRY">' . str_replace(',', '.', $tempCikolat->tax) . '
                                </ns4:TaxableAmount>
                                <ns4:TaxAmount
                                        currencyID="TRY">' . str_replace(',', '.', $tempCikolat->tax) . '
                                </ns4:TaxAmount>
                                <ns4:CalculationSequenceNumeric>
                                    1
                                </ns4:CalculationSequenceNumeric>
                                <ns4:Percent>8.0</ns4:Percent>
                                <ns5:TaxCategory>
                                    <ns5:TaxScheme>
                                        <ns4:TaxTypeCode>0015</ns4:TaxTypeCode>
                                    </ns5:TaxScheme>
                                </ns5:TaxCategory>
                            </ns5:TaxSubtotal>';
                    } else {
                        $cikolatLine = '';
                        $cikolatTaxLine = '';
                        $cikolatPrice = 0;
                        $cikolatProductPrice = 0;
                        $cikolatProductTax = 0;
                    }

                    $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                    $word = $f->format(intval($row->sumTotal + $cikolatPrice));
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
                    //$tempList = array_slice($tempListQuery, 5*($x - 1), 5);
                    //dd($list);
                    foreach ($list as $row) {

                        if ($row->product_type == 2) {
                            $tempKDV = '8.0';
                        } else {
                            $tempKDV = '18.0';
                        }

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
                            $tempPaymentType = "SANAL POS";
                            $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
                        } else {
                            $tempPaymentType = "EFT/HAVALE";
                            $tempPaymentTool = "EFT/HAVALE";
                        }

                        if ($row->sumTotal + $cikolatPrice == 0) {
                            return true;
                        }

                        if ($row->discount > 0) {

                            if ($discount[0]->type == 2) {
                                /*$tempDiscountText = '<ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                İndirim
                            </ns4:AllowanceChargeReason>
                            <ns4:MultiplierFactorNumeric>
                                ' . str_replace(',', '.', $row->discount / 100) . '
                            </ns4:MultiplierFactorNumeric>
                            <ns4:Amount currencyID="TRY">
                                ' . $flower_discount . '
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                ' . $row->price . '
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>';*/
                                $tempDiscountText = '<ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                İndirim
                            </ns4:AllowanceChargeReason>
                            <ns4:Amount currencyID="TRY">
                                ' . $flower_discount . '
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                ' . $row->price . '
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>';
                            } else {
                                $tempDiscountText = '';
                            }

                        } else {
                            $tempDiscountText = '';
                        }

                        if (number_format(str_replace(',', '.', $priceWithDiscount), 2) > 0) {
                            $tempFlowerLine = '<ns5:InvoiceLine>
                            <ns4:ID>1</ns4:ID>
                            <ns4:InvoicedQuantity unitCode="C62">1</ns4:InvoicedQuantity>
                            <ns4:LineExtensionAmount currencyID="TRY">' . $row->price . '</ns4:LineExtensionAmount>
                            ' . $tempDiscountText . '
                            <ns5:Item>
                                <ns4:Name>' . $row->products . '</ns4:Name>
                                <ns5:SellersItemIdentification>
                                    <ns4:ID>' . $row->id . '</ns4:ID>
                                </ns5:SellersItemIdentification>
                            </ns5:Item>
                            <!--Optional:-->
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
                                    <ns4:Percent>' . $tempKDV . '</ns4:Percent>
                                    <ns5:TaxCategory>
                                        <ns5:TaxScheme>
                                            <ns4:TaxTypeCode>0015</ns4:TaxTypeCode>
                                        </ns5:TaxScheme>
                                    </ns5:TaxCategory>
                                </ns5:TaxSubtotal>
                            </ns5:TaxTotal>
                            <ns5:Price>
                                <!--Optional:-->
                                <ns4:PriceAmount currencyID="TRY">' . $row->price . '</ns4:PriceAmount>
                            </ns5:Price>
                            <!--Zero or more repetitions:-->
                        </ns5:InvoiceLine>';
                            $tempFlowerLineTax = '<ns5:TaxSubtotal>
                                <ns4:TaxableAmount
                                        currencyID="TRY">' . $row->sumPartial . '
                                </ns4:TaxableAmount>
                                <ns4:TaxAmount
                                        currencyID="TRY">' . $row->discountValue . '
                                </ns4:TaxAmount>
                                <ns4:CalculationSequenceNumeric>
                                    1
                                </ns4:CalculationSequenceNumeric>
                                <ns4:Percent>' . $tempKDV . '</ns4:Percent>
                                <ns5:TaxCategory>
                                    <ns5:TaxScheme>
                                        <ns4:TaxTypeCode>0015</ns4:TaxTypeCode>
                                    </ns5:TaxScheme>
                                </ns5:TaxCategory>
                            </ns5:TaxSubtotal>';
                        } else {
                            $tempFlowerLine = '';
                            $tempFlowerLineTax = '';
                        }

                        if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {

                            $tempLogString = $tempLogString . $row->sales_id . "/";

                            if ($row->tc == '' || $row->tc == null)
                                $row->tc = '11111111111';
                            else {
                                $row->billing_address = '<ns4:StreetName>' . $row->billing_address . '</ns4:StreetName>';
                                $row->bigCity = $row->small_city;
                            }

                            if ($row->sender_surname == '' || $row->sender_surname == null)
                                $row->sender_surname = 'Yılmaz';
                            $tempSaleDate = explode('.', explode(' ', $row->created_at)[0]);
                            $tempDeliveryDate = explode('-', explode(' ', $row->delivery_date)[0]);

                            $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->sales_id . '">
                    <ns1:Invoice>
                        <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                        <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                        <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                        <ns4:CopyIndicator>false</ns4:CopyIndicator>
                        <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
                        <ns4:IssueTime>' . explode(' ', $row->delivery_date)[1] . '</ns4:IssueTime>
                        <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                        <ns4:Note>Satış internet üzerinden gerçekleştirilmiştir.</ns4:Note>
                        <ns4:Note>' . $row->totalWithWord . '</ns4:Note>
                        <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                        <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                        <ns5:DespatchDocumentReference>
				    	    <ns4:ID>A-' . $row->sales_id . '</ns4:ID>
						    <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
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
                            <ns4:TaxAmount currencyID="TRY">' . str_replace(',', '.', (floatval(str_replace(',', '.', $row->discountValue)) + floatval(str_replace(',', '.', $cikolatProductTax)))) . '</ns4:TaxAmount>
                            ' . $tempFlowerLineTax . '
                            ' . $cikolatTaxLine . '
                        </ns5:TaxTotal>
                        <ns5:LegalMonetaryTotal>
                            <ns4:LineExtensionAmount
                                    currencyID="TRY">' . str_replace(',', '.', (floatval(str_replace(',', '.', $cikolatProductPrice)) + floatval(str_replace(',', '.', $row->price)))) . '
                            </ns4:LineExtensionAmount>
                            <ns4:TaxExclusiveAmount
                                    currencyID="TRY">' . parse_str(number_format(floatval(str_replace(',', '.', $cikolatProductPrice)) + floatval(str_replace(',', '.', $row->price)) - floatval(str_replace(',', '.', $row->discountVal)), 2)) . '
                            </ns4:TaxExclusiveAmount>
                            <ns4:TaxInclusiveAmount
                                    currencyID="TRY">' . str_replace(',', '.', (floatval(str_replace(',', '.', $cikolatPrice)) + floatval(str_replace(',', '.', $row->sumTotal)))) . '
                            </ns4:TaxInclusiveAmount>
                            <ns4:AllowanceTotalAmount currencyID="TRY">' . $total_discount . '</ns4:AllowanceTotalAmount>
                            <ns4:PayableRoundingAmount
                                    currencyID="TRY">0.0
                            </ns4:PayableRoundingAmount>
                            <ns4:PayableAmount
                                    currencyID="TRY">' . str_replace(',', '.', (floatval(str_replace(',', '.', $cikolatPrice)) + floatval(str_replace(',', '.', $row->sumTotal)))) . '
                            </ns4:PayableAmount>
                        </ns5:LegalMonetaryTotal>
                        ' . $tempFlowerLine . '
                        ' . $cikolatLine . '
                    </ns1:Invoice>

                    <ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                        <ns1:InternetSalesInfo>
                            <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                            <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                            <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                            <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                            <ns1:ShipmentInfo>
                                <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
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

                        } else {

                            $tempLogString = $tempLogString . $row->sales_id . "/";

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
                            $tempDeliveryDate = explode('-', explode(' ', $row->delivery_date)[0]);
                            ini_set("soap.wsdl_cache_enabled", "0");
                            $tempCheckBilling = '<ns1:IsEInvoiceUser>
                            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
                            <ns1:vknTckn>' . $row->tax_no . '</ns1:vknTckn>
                            </ns1:IsEInvoiceUser>';

                            $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                            $client = new SoapClient($wsdl, array(
                                'soap_version' => SOAP_1_1,
                                'trace' => true,
                            ));

                            $args = array(new \SoapVar($tempCheckBilling, XSD_ANYXML));

                            $res = $client->__soapCall('IsEInvoiceUser', $args);

                            if ($res->IsEInvoiceUserResult->Value) {
                                $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                                <ns1:InternetSalesInfo>
                                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                                    <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                                    <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                                    <ns1:ShipmentInfo>
                                        <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
                                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                                    </ns1:ShipmentInfo>
                                </ns1:InternetSalesInfo>
                            </ns1:EArchiveInvoiceInfo>
                            <ns1:Scenario>eInvoice</ns1:Scenario>';
                                $tempNote = "Satış internet üzerinden gerçekleştirilmiştir.
                                                </ns4:Note><ns4:Note>Ödeme Aracısı: " . $tempPaymentType . " | Ödeme Tarihi: " . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . " | Ödeme Tipi: " . $tempPaymentTool;
                            } else {
                                $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                                <ns1:InternetSalesInfo>
                                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                                    <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                                    <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                                    <ns1:ShipmentInfo>
                                        <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
                                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                                    </ns1:ShipmentInfo>
                                </ns1:InternetSalesInfo>
                            </ns1:EArchiveInvoiceInfo>
                            <ns1:Scenario>eArchive</ns1:Scenario>';
                                $tempNote = "Satış internet üzerinden gerçekleştirilmiştir.";
                            }

                            if ($row->discount > 0) {
                                if ($discount[0]->type == 2) {
                                    $tempDiscountText = '<ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                İndirim
                            </ns4:AllowanceChargeReason>
                            <ns4:MultiplierFactorNumeric>
                                ' . str_replace(',', '.', $row->discount / 100) . '
                            </ns4:MultiplierFactorNumeric>
                            <ns4:Amount currencyID="TRY">
                                ' . $flower_discount . '
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                ' . $row->price . '
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>';
                                } else {
                                    $tempDiscountText = '<ns5:AllowanceCharge>
                            <ns4:ChargeIndicator>
                                false
                            </ns4:ChargeIndicator>
                            <ns4:AllowanceChargeReason>
                                İndirim
                            </ns4:AllowanceChargeReason>
                            <ns4:Amount currencyID="TRY">
                                ' . $row->discountVal . '
                            </ns4:Amount>
                            <ns4:BaseAmount currencyID="TRY">
                                ' . $row->price . '
                            </ns4:BaseAmount>
                        </ns5:AllowanceCharge>';
                                }
                            } else {
                                $tempDiscountText = '';
                            }
                            $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->sales_id . '">
                            <ns1:Invoice>
                                <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                                <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                                <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                                <ns4:CopyIndicator>false</ns4:CopyIndicator>
                                <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
                                <ns4:IssueTime>' . explode(' ', $row->delivery_date)[1] . '</ns4:IssueTime>
                                <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                                <ns4:Note>' . $tempNote . '</ns4:Note>
                                <ns4:Note>' . $row->totalWithWord . '</ns4:Note>
                                <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                                <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                                <ns5:DespatchDocumentReference>
				    		        <ns4:ID>A-' . $row->sales_id . '</ns4:ID>
						            <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
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
                                    <ns4:TaxAmount currencyID="TRY">' . str_replace(',', '.', (floatval(str_replace(',', '.', $row->discountValue)) + floatval(str_replace(',', '.', $cikolatProductTax)))) . '</ns4:TaxAmount>
                                    ' . $tempFlowerLineTax . '
                                    ' . $cikolatTaxLine . '     
                                </ns5:TaxTotal>
                                <ns5:LegalMonetaryTotal>
                                    <ns4:LineExtensionAmount
                                            currencyID="TRY">' . str_replace(',', '.', (floatval(str_replace(',', '.', $cikolatProductPrice)) + floatval(str_replace(',', '.', $row->price)))) . '
                                    </ns4:LineExtensionAmount>
                                    <ns4:TaxExclusiveAmount
                                            currencyID="TRY">' . str_replace(',', '.', parse_str(number_format(floatval(str_replace(',', '.', $cikolatProductPrice)) + floatval(str_replace(',', '.', $row->price)) - floatval(str_replace(',', '.', $row->discountVal)), 2))) . '
                                    </ns4:TaxExclusiveAmount>
                                    <ns4:TaxInclusiveAmount
                                            currencyID="TRY">' . str_replace(',', '.', (floatval(str_replace(',', '.', $cikolatPrice)) + floatval(str_replace(',', '.', $row->sumTotal)))) . '
                                    </ns4:TaxInclusiveAmount>
                                    <ns4:AllowanceTotalAmount currencyID="TRY">' . $total_discount . '</ns4:AllowanceTotalAmount>
                                    <ns4:PayableRoundingAmount
                                            currencyID="TRY">0.0
                                    </ns4:PayableRoundingAmount>
                                    <ns4:PayableAmount
                                            currencyID="TRY">' . str_replace(',', '.', (floatval(str_replace(',', '.', $cikolatPrice)) + floatval(str_replace(',', '.', $row->sumTotal)))) . '
                                    </ns4:PayableAmount>        
                                </ns5:LegalMonetaryTotal>
                                ' . $tempFlowerLine . '
                                ' . $cikolatLine . '        
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
                        }
                    }

                    DB::table('error_logs')->insert([
                        'method_name' => 'BillingOperation',
                        'error_code' => 'log',
                        'error_message' => $tempLogString,
                        'type' => 'WS',
                        'related_variable' => 'billing'
                    ]);

                    ini_set("soap.wsdl_cache_enabled", "0");
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
                    DB::table('sales')->where('id', $sales_id)->update([
                        'billing_number' => $res->SaveAsDraftResult->Value->Id
                    ]);
                    //dd($res->SaveAsDraftResult->Value->Id);

                }

        } catch (\Exception $e) {
            $before = Carbon::now();
            dd($e);
            DB::table('error_logs')->insert([
                'method_name' => 'BillingException',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'Billing',
                'related_variable' => 'BillingException',
                'created_at' => $before
            ]);
        }
    }

    public static function studioBillingSendProd($sales_id)
    {
        try {
            $tempLogString = "";

            $list = DB::table('studioBloom')
                ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
                ->where('studioBloom.id', $sales_id)
                ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                    , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                    , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile', 'studio_billings.billing_surname')->get();

            //dd($list);

            //$list = DB::table('sales')
            //    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            //    ->join('billings', 'sales.id', '=', 'billings.sales_id')
            //    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            //    ->join('products', 'sales_products.products_id', '=', 'products.id')
            //    ->where('sales.id', $sales_id)
            //    ->where('sales.payment_methods', 'OK')
            //    ->where('sales.send_billing', '0')
            //    ->where('sales.payment_type', '!=', 'KURUMSAL')
            //    ->orderBy('deliveries.delivery_date')
            //    ->select('sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling',
            //        'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
            //        'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'sales.sender_mobile', 'products.name as products', 'sales.sender_name', 'sales.sender_surname',
            //        'sales.product_price as price', 'products.id', 'sales.customer_contact_id', 'sales.send_billing', 'deliveries.delivery_date' , 'sales.payment_type')
            //    ->get();
            DB::table('studioBloom')->where('id', $sales_id)->update([
                'send_billing' => 1
            ]);
            $tempLogString = $tempLogString . count($list) . " kayıt/";
            $firstPrice = 0;
            $totalDiscount = 0;
            $totalPartial = 0;
            $totalKDV = 0;
            $total = 0;
            if (count($list) > 0)
                if ($list[0]->send_billing == 0) {

                    foreach ($list as $row) {
                        $tempTotal = 0;
                        $tempVal = str_replace(',', '.', $row->price);
                        $firstPrice = $firstPrice + floatval($tempVal);
                        $discount = 0;

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
                            $row->name = $row->billing_name . ' ' . $row->billing_surname;
                            $districtTemp = 'Sarıyer-Emirgan';
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

                        $dateTemp = new Carbon($row->wanted_date);

                        $row->wantedDate = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year;

                        $dateTemp = new Carbon($row->payment_date);

                        $row->created_at = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year . ' ' . sprintf("%02d", $dateTemp->hour) . ':' . sprintf("%02d", $dateTemp->minute) . ':' . '00';

                        $row->id = sprintf("%03d", $row->id);

                        $row->discount = 0;
                        $row->discountVal = 0;
                        $row->sumPartial = $row->price;

                        $priceWithDiscount = $row->price;
                        $priceWithDiscount = str_replace(',', '.', $priceWithDiscount);
                        $totalPartial = $totalPartial + $priceWithDiscount;

                        $row->discountValue = floatval(floatval($priceWithDiscount) * 18 / 100);

                        $row->discountValue = number_format($row->discountValue, 2, '.', '');
                        parse_str($row->discountValue);
                        $row->discountValue = str_replace('.', ',', $row->discountValue);

                        $priceWithDiscount = floatval(floatval($priceWithDiscount) * 118 / 100);
                        $priceWithDiscount = number_format($priceWithDiscount, 2, '.', '');

                        $tempTotal = $priceWithDiscount;
                        parse_str($priceWithDiscount);
                        $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                        $row->sumTotal = $priceWithDiscount;

                        $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                        $word = $f->format(intval($row->sumTotal));

                        $wordExtra = $f->format(explode(",", $row->sumTotal)[1]);
                        $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';

                        //$total = $total + $row->sumTotal;
                    }

                    $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
                    $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';
                    //$tempList = array_slice($tempListQuery, 5*($x - 1), 5);
                    //dd($list);
                    foreach ($list as $row) {

                        $row->price = str_replace(',', '.', $row->price);
                        $row->discountVal = str_replace(',', '.', $row->discountVal);
                        $row->discountValue = str_replace(',', '.', $row->discountValue);
                        $row->sumPartial = str_replace(',', '.', $row->sumPartial);
                        $row->sumTotal = str_replace(',', '.', $row->sumTotal);
                        $tempPaymentType = "";
                        $tempPaymentTool = "";
                        if ($row->payment_type == "POS") {
                            $tempPaymentType = "SANAL POS";
                            $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
                        } else {
                            $tempPaymentType = "EFT/HAVALE";
                            $tempPaymentTool = "EFT/HAVALE";
                        }

                        if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {

                            $tempLogString = $tempLogString . $row->id . "/";

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
                                ' . str_replace(',', '.', $row->discount / 100) . '
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
                            if ($row->billing_name == '' || $row->billing_surname == null)
                                $row->billing_surname = 'Yılmaz';
                            $tempSaleDate = explode('.', explode(' ', $row->created_at)[0]);
                            $tempDeliveryDate = explode('-', explode(' ', $row->delivery_date)[0]);
                            $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->id . '">
                    <ns1:Invoice>
                        <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                        <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                        <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                        <ns4:CopyIndicator>false</ns4:CopyIndicator>
                        <ns4:IssueDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns4:IssueDate>
                        <ns4:IssueTime>' . explode(' ', $row->created_at)[1] . '</ns4:IssueTime>
                        <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                        <ns4:Note>Satış internet üzerinden gerçekleştirilmiştir.</ns4:Note>
                        <ns4:Note>' . $row->totalWithWord . '</ns4:Note>
                        <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                        <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                        <ns5:DespatchDocumentReference>
				    	    <ns4:ID>A-' . substr($sales_id, 0, 15) . '</ns4:ID>
						    <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
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
                                    <ns4:FirstName>' . $row->billing_name . '</ns4:FirstName>
                                    <ns4:FamilyName>' . $row->billing_surname . '</ns4:FamilyName>
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
                                <ns4:Name>' . $row->flower_name . '</ns4:Name>
                                <ns5:SellersItemIdentification>
                                    <ns4:ID>' . substr($sales_id, 0, 15) . '</ns4:ID>
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
                            <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                            <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                            <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                            <ns1:ShipmentInfo>
                                <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
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
                            //dd($tempQueryString);
                            $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                            $client = new SoapClient($wsdl, array(
                                'soap_version' => SOAP_1_1,
                                'trace' => true,
                            ));

                        } else {

                            $tempLogString = $tempLogString . $row->id . "/";

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
                            $tempDeliveryDate = explode('-', explode(' ', $row->delivery_date)[0]);
                            ini_set("soap.wsdl_cache_enabled", "0");
                            $tempCheckBilling = '<ns1:IsEInvoiceUser>
                            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
                            <ns1:vknTckn>' . $row->tax_no . '</ns1:vknTckn>
                            </ns1:IsEInvoiceUser>';

                            $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                            $client = new SoapClient($wsdl, array(
                                'soap_version' => SOAP_1_1,
                                'trace' => true,
                            ));

                            $args = array(new \SoapVar($tempCheckBilling, XSD_ANYXML));

                            $res = $client->__soapCall('IsEInvoiceUser', $args);

                            if ($res->IsEInvoiceUserResult->Value) {
                                $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                                <ns1:InternetSalesInfo>
                                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                                    <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                                    <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                                    <ns1:ShipmentInfo>
                                        <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
                                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                                    </ns1:ShipmentInfo>
                                </ns1:InternetSalesInfo>
                            </ns1:EArchiveInvoiceInfo>
                            <ns1:Scenario>eInvoice</ns1:Scenario>';
                                $tempNote = "Satış internet üzerinden gerçekleştirilmiştir.
                                                </ns4:Note><ns4:Note>Ödeme Aracısı: " . $tempPaymentType . " | Ödeme Tarihi: " . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . " | Ödeme Tipi: " . $tempPaymentTool;
                            } else {
                                $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                                <ns1:InternetSalesInfo>
                                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                                    <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                                    <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                                    <ns1:ShipmentInfo>
                                        <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
                                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                                    </ns1:ShipmentInfo>
                                </ns1:InternetSalesInfo>
                            </ns1:EArchiveInvoiceInfo>
                            <ns1:Scenario>eArchive</ns1:Scenario>';
                                $tempNote = "Satış internet üzerinden gerçekleştirilmiştir.";
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
                                ' . str_replace(',', '.', $row->discount / 100) . '
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
                            $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->id . '">
                            <ns1:Invoice>
                                <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                                <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                                <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                                <ns4:CopyIndicator>false</ns4:CopyIndicator>
                                <ns4:IssueDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns4:IssueDate>
                                <ns4:IssueTime>' . explode(' ', $row->created_at)[1] . '</ns4:IssueTime>
                                <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                                <ns4:Note>' . $tempNote . '</ns4:Note>
                                <ns4:Note>' . $row->totalWithWord . '</ns4:Note>
                                <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                                <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                                <ns5:DespatchDocumentReference>
				    		        <ns4:ID>A-' . substr($sales_id, 0, 15) . '</ns4:ID>
						            <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
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
                                        <ns4:Name>' . $row->flower_name . '</ns4:Name>
                                        <ns5:SellersItemIdentification>
                                            <ns4:ID>' . substr($sales_id, 0, 15) . '</ns4:ID>
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
                        }

                    }

                    DB::table('error_logs')->insert([
                        'method_name' => 'BillingOperation',
                        'error_code' => 'log',
                        'error_message' => $tempLogString,
                        'type' => 'WS',
                        'related_variable' => 'billing'
                    ]);

                    ini_set("soap.wsdl_cache_enabled", "0");
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
                    DB::table('studioBloom')->where('id', $sales_id)->update([
                        'billing_number' => $res->SaveAsDraftResult->Value->Id
                    ]);
                    //dd($res->SaveAsDraftResult->Value->Id);
                }

        } catch (\Exception $e) {
            $before = Carbon::now();
            DB::table('error_logs')->insert([
                'method_name' => 'StudioBillingException',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'Billing',
                'related_variable' => 'BillingException',
                'created_at' => $before
            ]);
            return $e;
        }

    }

    public static function getValueWithChar($sumTotal)
    {
        $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
        $word = $f->format(intval($sumTotal));

        $wordExtra = $f->format(explode(",", $sumTotal)[1]);
        return $word . ' Lira ' . $wordExtra . ' Kuruş';
        //dd($word . ' Lira ' . $wordExtra . ' Kuruş');
    }

    public function sendDayBilling()
    {
        $list = DB::table('sales')
            ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            ->join('billings', 'sales.id', '=', 'billings.sales_id')
            ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            ->join('products', 'sales_products.products_id', '=', 'products.id')
            ->where('sales.payment_methods', 'OK')
            ->where('sales.send_billing', '0')
            ->where('deliveries.delivery_date', '>', '2016-06-30 00:00:00')
            ->where('deliveries.delivery_date', '<', '2016-06-30 23:59:59')
            ->where('sales.payment_type', '!=', 'KURUMSAL')
            ->orderBy('deliveries.delivery_date')
            ->select('sales.id')
            ->get();

        foreach ($list as $raw) {
            BillingOperation::soapTest($raw->id);
        }
    }

    public static function studioBillingSend($sales_id)
    {
        try {
            $tempLogString = "";

            $list = DB::table('studioBloom')
                ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
                ->where('studioBloom.id', $sales_id)
                ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                    , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                    , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile', 'studio_billings.billing_surname')->get();

            //dd($list);

            //$list = DB::table('sales')
            //    ->join('deliveries', 'sales.id', '=', 'deliveries.sales_id')
            //    ->join('billings', 'sales.id', '=', 'billings.sales_id')
            //    ->join('sales_products', 'sales.id', '=', 'sales_products.sales_id')
            //    ->join('products', 'sales_products.products_id', '=', 'products.id')
            //    ->where('sales.id', $sales_id)
            //    ->where('sales.payment_methods', 'OK')
            //    ->where('sales.send_billing', '0')
            //    ->where('sales.payment_type', '!=', 'KURUMSAL')
            //    ->orderBy('deliveries.delivery_date')
            //    ->select('sales.payment_type', 'sales.device', 'sales.delivery_locations_id', 'deliveries.wanted_delivery_date', 'billings.small_city', 'billings.city', 'billings.tc', 'billings.userBilling',
            //        'billings.billing_type', 'billings.company', 'billings.billing_address', 'billings.tax_office', 'billings.tax_no', 'billings.billing_send', 'billings.billing_name', 'billings.billing_surname',
            //        'billings.id as billing_id', 'sales.id as sales_id', 'sales.created_at', 'sales.sender_mobile', 'products.name as products', 'sales.sender_name', 'sales.sender_surname',
            //        'sales.product_price as price', 'products.id', 'sales.customer_contact_id', 'sales.send_billing', 'deliveries.delivery_date' , 'sales.payment_type')
            //    ->get();
            DB::table('studioBloom')->where('id', $sales_id)->update([
                'send_billing' => 1
            ]);
            $tempLogString = $tempLogString . count($list) . " kayıt/";
            $firstPrice = 0;
            $totalDiscount = 0;
            $totalPartial = 0;
            $totalKDV = 0;
            $total = 0;
            if (count($list) > 0)
                if ($list[0]->send_billing == 0) {

                    foreach ($list as $row) {
                        $tempTotal = 0;
                        $tempVal = str_replace(',', '.', $row->price);
                        $firstPrice = $firstPrice + floatval($tempVal);
                        $discount = 0;

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
                            $row->name = $row->billing_name . ' ' . $row->billing_surname;
                            $districtTemp = 'Sarıyer-Emirgan';
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

                        $dateTemp = new Carbon($row->wanted_date);

                        $row->wantedDate = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year;

                        $dateTemp = new Carbon($row->payment_date);

                        $row->created_at = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year . ' ' . sprintf("%02d", $dateTemp->hour) . ':' . sprintf("%02d", $dateTemp->minute) . ':' . '00';

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
                        $priceWithDiscount = number_format($priceWithDiscount, 2);

                        $tempTotal = $priceWithDiscount;
                        parse_str($priceWithDiscount);
                        $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

                        $row->sumTotal = $priceWithDiscount;

                        $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
                        $word = $f->format(intval($row->sumTotal));

                        $wordExtra = $f->format(explode(",", $row->sumTotal)[1]);
                        $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';

                        //$total = $total + $row->sumTotal;
                    }

                    $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
                    $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';
                    //$tempList = array_slice($tempListQuery, 5*($x - 1), 5);
                    //dd($list);
                    foreach ($list as $row) {

                        $row->price = str_replace(',', '.', $row->price);
                        $row->discountVal = str_replace(',', '.', $row->discountVal);
                        $row->discountValue = str_replace(',', '.', $row->discountValue);
                        $row->sumPartial = str_replace(',', '.', $row->sumPartial);

                        $row->sumTotal = str_replace(',', '.', $row->sumTotal);

                        $tempPaymentType = "";
                        $tempPaymentTool = "";
                        if ($row->payment_type == "POS") {
                            $tempPaymentType = "SANAL POS";
                            $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
                        } else {
                            $tempPaymentType = "EFT/HAVALE";
                            $tempPaymentTool = "EFT/HAVALE";
                        }

                        if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {

                            $tempLogString = $tempLogString . $row->id . "/";

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
                                ' . str_replace(',', '.', $row->discount / 100) . '
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
                            if ($row->billing_name == '' || $row->billing_surname == null)
                                $row->billing_surname = 'Yılmaz';
                            $tempSaleDate = explode('.', explode(' ', $row->created_at)[0]);
                            $tempDeliveryDate = explode('-', explode(' ', $row->delivery_date)[0]);
                            $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->id . '">
                    <ns1:Invoice>
                        <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                        <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                        <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                        <ns4:CopyIndicator>false</ns4:CopyIndicator>
                        <ns4:IssueDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns4:IssueDate>
                        <ns4:IssueTime>' . explode(' ', $row->created_at)[1] . '</ns4:IssueTime>
                        <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                        <ns4:Note>Satış internet üzerinden gerçekleştirilmiştir.</ns4:Note>
                        <ns4:Note>' . $row->totalWithWord . '</ns4:Note>
                        <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                        <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                        <ns5:DespatchDocumentReference>
				    	    <ns4:ID>A-' . substr($sales_id, 0, 15) . '</ns4:ID>
						    <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
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
                                    <ns4:FirstName>' . $row->billing_name . '</ns4:FirstName>
                                    <ns4:FamilyName>' . $row->billing_surname . '</ns4:FamilyName>
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
                                <ns4:Name>' . $row->flower_name . '</ns4:Name>
                                <ns5:SellersItemIdentification>
                                    <ns4:ID>' . substr($sales_id, 0, 15) . '</ns4:ID>
                                </ns5:SellersItemIdentification>
                            </ns5:Item>
                            <!--Optional:-->
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
                            <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                            <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                            <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                            <ns1:ShipmentInfo>
                                <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
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
                            //dd($tempQueryString);
                            $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                            $client = new SoapClient($wsdl, array(
                                'soap_version' => SOAP_1_1,
                                'trace' => true,
                            ));

                        } else {

                            $tempLogString = $tempLogString . $row->id . "/";

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
                            $tempDeliveryDate = explode('-', explode(' ', $row->delivery_date)[0]);
                            ini_set("soap.wsdl_cache_enabled", "0");
                            $tempCheckBilling = '<ns1:IsEInvoiceUser>
                            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
                            <ns1:vknTckn>' . $row->tax_no . '</ns1:vknTckn>
                            </ns1:IsEInvoiceUser>';

                            $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                            $client = new SoapClient($wsdl, array(
                                'soap_version' => SOAP_1_1,
                                'trace' => true,
                            ));

                            $args = array(new \SoapVar($tempCheckBilling, XSD_ANYXML));

                            $res = $client->__soapCall('IsEInvoiceUser', $args);

                            if ($res->IsEInvoiceUserResult->Value) {
                                $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                                <ns1:InternetSalesInfo>
                                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                                    <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                                    <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                                    <ns1:ShipmentInfo>
                                        <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
                                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                                    </ns1:ShipmentInfo>
                                </ns1:InternetSalesInfo>
                            </ns1:EArchiveInvoiceInfo>
                            <ns1:Scenario>eInvoice</ns1:Scenario>';
                                $tempNote = "Satış internet üzerinden gerçekleştirilmiştir.
                                                </ns4:Note><ns4:Note>Ödeme Aracısı: " . $tempPaymentType . " | Ödeme Tarihi: " . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . " | Ödeme Tipi: " . $tempPaymentTool;
                            } else {
                                $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                                <ns1:InternetSalesInfo>
                                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                                    <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                                    <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                                    <ns1:ShipmentInfo>
                                        <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
                                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                                    </ns1:ShipmentInfo>
                                </ns1:InternetSalesInfo>
                            </ns1:EArchiveInvoiceInfo>
                            <ns1:Scenario>eArchive</ns1:Scenario>';
                                $tempNote = "Satış internet üzerinden gerçekleştirilmiştir.";
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
                                ' . str_replace(',', '.', $row->discount / 100) . '
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
                            $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->id . '">
                            <ns1:Invoice>
                                <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                                <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                                <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                                <ns4:CopyIndicator>false</ns4:CopyIndicator>
                                <ns4:IssueDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns4:IssueDate>
                                <ns4:IssueTime>' . explode(' ', $row->created_at)[1] . '</ns4:IssueTime>
                                <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                                <ns4:Note>' . $tempNote . '</ns4:Note>
                                <ns4:Note>' . $row->totalWithWord . '</ns4:Note>
                                <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                                <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                                <ns5:DespatchDocumentReference>
				    		        <ns4:ID>A-' . substr($sales_id, 0, 15) . '</ns4:ID>
						            <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
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
                                        <ns4:Name>' . $row->flower_name . '</ns4:Name>
                                        <ns5:SellersItemIdentification>
                                            <ns4:ID>' . substr($sales_id, 0, 15) . '</ns4:ID>
                                        </ns5:SellersItemIdentification>
                                    </ns5:Item>
                                    <!--Optional:-->
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
                        }
                    }

                    DB::table('error_logs')->insert([
                        'method_name' => 'BillingOperation',
                        'error_code' => 'log',
                        'error_message' => $tempLogString,
                        'type' => 'WS',
                        'related_variable' => 'billing'
                    ]);

                    ini_set("soap.wsdl_cache_enabled", "0");
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
                    DB::table('studioBloom')->where('id', $sales_id)->update([
                        'billing_number' => $res->SaveAsDraftResult->Value->Id
                    ]);
                    //dd($res->SaveAsDraftResult->Value->Id);
                }

        } catch (\Exception $e) {
            $before = Carbon::now();
            DB::table('error_logs')->insert([
                'method_name' => 'StudioBillingException',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'type' => 'Billing',
                'related_variable' => 'BillingException',
                'created_at' => $before
            ]);
            return $e;
        }

    }

    public function testStudioCron()
    {

        $now = Carbon::now();
        $now->subDay();
        $now->hour(17);
        $now->minute(00);
        $now->second(00);

        $list = DB::table('studioBloom')
            ->join('studio_billings', 'studioBloom.id', '=', 'studio_billings.sales_id')
            ->where('studioBloom.status', 'Ödeme Yapıldı')->where('studioBloom.payment_date', '>', $now)->where('studioBloom.send_billing', 0)
            ->select('studioBloom.*', 'studio_billings.billing_send', 'studio_billings.billing_name', 'studio_billings.city', 'studio_billings.small_city', 'studio_billings.company'
                , 'studio_billings.billing_address', 'studio_billings.tax_office', 'studio_billings.tax_no', 'studio_billings.billing_type', 'note'
                , 'studio_billings.tc', 'studio_billings.userBilling', 'studioBloom.customer_mobile', 'studio_billings.billing_surname')->get();
        foreach ($list as $row) {
            $tempLogString = "";
            DB::table('studioBloom')->where('id', $row->id)->update([
                'send_billing' => 1
            ]);
            $tempLogString = $tempLogString . count($list) . " kayıt/";
            $firstPrice = 0;
            $totalDiscount = 0;
            $totalPartial = 0;
            $totalKDV = 0;
            $total = 0;

            $tempTotal = 0;
            $tempVal = str_replace(',', '.', $row->price);
            $firstPrice = $firstPrice + floatval($tempVal);
            $discount = 0;

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
                $row->name = $row->billing_name . ' ' . $row->billing_surname;
                $districtTemp = 'Sarıyer-Emirgan';
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

            $dateTemp = new Carbon($row->wanted_date);

            $row->wantedDate = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year;

            $dateTemp = new Carbon($row->payment_date);

            $row->created_at = sprintf("%02d", $dateTemp->day) . '.' . sprintf("%02d", $dateTemp->month) . '.' . $dateTemp->year . ' ' . sprintf("%02d", $dateTemp->hour) . ':' . sprintf("%02d", $dateTemp->minute) . ':' . '00';

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
            $priceWithDiscount = number_format($priceWithDiscount, 2);

            $tempTotal = $priceWithDiscount;
            parse_str($priceWithDiscount);
            $priceWithDiscount = str_replace('.', ',', $priceWithDiscount);

            $row->sumTotal = $priceWithDiscount;

            $f = new \NumberFormatter('tr', \NumberFormatter::SPELLOUT);
            $word = $f->format(intval($row->sumTotal));

            $wordExtra = $f->format(explode(",", $row->sumTotal)[1]);
            $row->totalWithWord = 'Yalnız #' . ucfirst($word) . ' Türk Lirası ' . ucfirst($wordExtra) . ' Kuruş#';


            $tempQueryString = '<ns1:SaveAsDraft>
            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
            <ns1:invoices>';
            $tempQueryEnd = '</ns1:invoices></ns1:SaveAsDraft>';
            //$tempList = array_slice($tempListQuery, 5*($x - 1), 5);
            //dd($list);

            $row->price = str_replace(',', '.', $row->price);
            $row->discountVal = str_replace(',', '.', $row->discountVal);
            $row->discountValue = str_replace(',', '.', $row->discountValue);
            $row->sumPartial = str_replace(',', '.', $row->sumPartial);
            $row->sumTotal = str_replace(',', '.', $row->sumTotal);
            $tempPaymentType = "";
            $tempPaymentTool = "";
            if ($row->payment_type == "POS") {
                $tempPaymentType = "SANAL POS";
                $tempPaymentTool = "KREDIKARTI/BANKAKARTI";
            } else {
                $tempPaymentType = "EFT/HAVALE";
                $tempPaymentTool = "EFT/HAVALE";
            }

            if (($row->billing_type == 0 || $row->billing_type == 1) && $row->payment_type != 'KURUMSAL') {

                $tempLogString = $tempLogString . $row->id . "/";

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
                                ' . str_replace(',', '.', $row->discount / 100) . '
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
                if ($row->billing_name == '' || $row->billing_surname == null)
                    $row->billing_surname = 'Yılmaz';
                $tempSaleDate = explode('.', explode(' ', $row->created_at)[0]);
                $tempDeliveryDate = explode('-', explode(' ', $row->delivery_date)[0]);
                $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->id . '">
                    <ns1:Invoice>
                        <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                        <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                        <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                        <ns4:CopyIndicator>false</ns4:CopyIndicator>
                        <ns4:IssueDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns4:IssueDate>
                        <ns4:IssueTime>' . explode(' ', $row->created_at)[1] . '</ns4:IssueTime>
                        <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                        <ns4:Note>Satış internet üzerinden gerçekleştirilmiştir.</ns4:Note>
                        <ns4:Note>' . $row->totalWithWord . '</ns4:Note>
                        <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                        <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                        <ns5:DespatchDocumentReference>
				    	    <ns4:ID>A-' . substr($row->id, 0, 15) . '</ns4:ID>
						    <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
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
                                    <ns4:FirstName>' . $row->billing_name . '</ns4:FirstName>
                                    <ns4:FamilyName>' . $row->billing_surname . '</ns4:FamilyName>
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
                                <ns4:Name>' . $row->flower_name . '</ns4:Name>
                                <ns5:SellersItemIdentification>
                                    <ns4:ID>' . substr($row->id, 0, 15) . '</ns4:ID>
                                </ns5:SellersItemIdentification>
                            </ns5:Item>
                            <!--Optional:-->
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
                            <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                            <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                            <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                            <ns1:ShipmentInfo>
                                <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
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
                //dd($tempQueryString);
                $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                $client = new SoapClient($wsdl, array(
                    'soap_version' => SOAP_1_1,
                    'trace' => true,
                ));

            } else {

                $tempLogString = $tempLogString . $row->id . "/";

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
                $tempDeliveryDate = explode('-', explode(' ', $row->delivery_date)[0]);
                ini_set("soap.wsdl_cache_enabled", "0");
                $tempCheckBilling = '<ns1:IsEInvoiceUser>
                            <ns1:userInfo Username="Ifgirisim_WebServis" Password="y1BDpiWb"/>
                            <ns1:vknTckn>' . $row->tax_no . '</ns1:vknTckn>
                            </ns1:IsEInvoiceUser>';

                $wsdl = "http://efatura.uyumsoft.com.tr/Services/BasicIntegration?singleWsdl";
                $client = new SoapClient($wsdl, array(
                    'soap_version' => SOAP_1_1,
                    'trace' => true,
                ));

                $args = array(new \SoapVar($tempCheckBilling, XSD_ANYXML));

                $res = $client->__soapCall('IsEInvoiceUser', $args);

                if ($res->IsEInvoiceUserResult->Value) {
                    $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                                <ns1:InternetSalesInfo>
                                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                                    <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                                    <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                                    <ns1:ShipmentInfo>
                                        <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
                                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                                    </ns1:ShipmentInfo>
                                </ns1:InternetSalesInfo>
                            </ns1:EArchiveInvoiceInfo>
                            <ns1:Scenario>eInvoice</ns1:Scenario>';
                    $tempNote = "Satış internet üzerinden gerçekleştirilmiştir.
                                                </ns4:Note><ns4:Note>Ödeme Aracısı: " . $tempPaymentType . " | Ödeme Tarihi: " . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . " | Ödeme Tipi: " . $tempPaymentTool;
                } else {
                    $tempBillingType = '<ns1:EArchiveInvoiceInfo DeliveryType="Electronic">
                                <ns1:InternetSalesInfo>
                                    <ns1:WebAddress>bloomandfresh.com</ns1:WebAddress>
                                    <ns1:PaymentMidierName>' . $tempPaymentType . '</ns1:PaymentMidierName>
                                    <ns1:PaymentType>' . $tempPaymentTool . '</ns1:PaymentType>
                                    <ns1:PaymentDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns1:PaymentDate>
                                    <ns1:ShipmentInfo>
                                        <ns1:SendDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns1:SendDate>
                                        <ns1:Carier SenderTcknVkn="4650412713" SenderName="IF GİRİŞİM VE TEKNOLOJİ A.Ş."/>
                                    </ns1:ShipmentInfo>
                                </ns1:InternetSalesInfo>
                            </ns1:EArchiveInvoiceInfo>
                            <ns1:Scenario>eArchive</ns1:Scenario>';
                    $tempNote = "Satış internet üzerinden gerçekleştirilmiştir.";
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
                                ' . str_replace(',', '.', $row->discount / 100) . '
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
                $tempQueryString = $tempQueryString . '<ns1:InvoiceInfo LocalDocumentId="' . $row->id . '">
                            <ns1:Invoice>
                                <ns4:UBLVersionID>2.0</ns4:UBLVersionID>
                                <ns4:CustomizationID>TR1.0</ns4:CustomizationID>
                                <ns4:ProfileID>TEMELFATURA</ns4:ProfileID>
                                <ns4:CopyIndicator>false</ns4:CopyIndicator>
                                <ns4:IssueDate>' . $tempSaleDate[2] . '-' . $tempSaleDate[1] . '-' . $tempSaleDate[0] . '</ns4:IssueDate>
                                <ns4:IssueTime>' . explode(' ', $row->created_at)[1] . '</ns4:IssueTime>
                                <ns4:InvoiceTypeCode>SATIS</ns4:InvoiceTypeCode>
                                <ns4:Note>' . $tempNote . '</ns4:Note>
                                <ns4:Note>' . $row->totalWithWord . '</ns4:Note>
                                <ns4:DocumentCurrencyCode>TRY</ns4:DocumentCurrencyCode>
                                <ns4:LineCountNumeric>1</ns4:LineCountNumeric>
                                <ns5:DespatchDocumentReference>
				    		        <ns4:ID>A-' . substr($row->id, 0, 15) . '</ns4:ID>
						            <ns4:IssueDate>' . $tempDeliveryDate[0] . '-' . $tempDeliveryDate[1] . '-' . $tempDeliveryDate[2] . '</ns4:IssueDate>
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
                                        <ns4:Name>' . $row->flower_name . '</ns4:Name>
                                        <ns5:SellersItemIdentification>
                                            <ns4:ID>' . substr($row->id, 0, 15) . '</ns4:ID>
                                        </ns5:SellersItemIdentification>
                                    </ns5:Item>
                                    <!--Optional:-->
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
            }

            DB::table('error_logs')->insert([
                'method_name' => 'BillingOperation',
                'error_code' => 'log',
                'error_message' => $tempLogString,
                'type' => 'WS',
                'related_variable' => 'billing'
            ]);

            ini_set("soap.wsdl_cache_enabled", "0");
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
            DB::table('studioBloom')->where('id', $row->id)->update([
                'billing_number' => $res->SaveAsDraftResult->Value->Id
            ]);
            //dd($res->SaveAsDraftResult->Value->Id);

        }
    }
}