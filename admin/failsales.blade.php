@extends('newApp')

@section('html-head')
    <style>
        .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th{
            vertical-align: middle;
        }
        div.form-group {
            height: 20px;
        }
    </style>
@stop

@section('content')

    <table width="100%">
        <tr style="width: 100%">
            <td>
                <h1>Bloom & Fresh Ödeme Terk</h1>
            </td>
            <td>
            </td>
        </tr>
    </table>
    <button id="testXls" class="btn btn-danger"  onClick ="$('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Mesajlar',filename: 'Mesajlar'});">Excel Çıktısı İçin Tıklayınız</button>
    {!! Html::link('/admin/get-fail-sale'  , 'Hepsi', ['class' => 'hidden btn btn-primary']) !!}
    {!! Html::link('/admin/get-fail-sale-checked' , 'İşaretliler', ['class' => 'hidden btn btn-success']) !!}
    {!! Html::link('/admin/get-fail-sale-unchecked'  , 'İşaretsizler', ['class' => 'hidden btn btn-info']) !!}
    <div style="text-align: center;">
        @for($page = 1; $page <= $pageCount ; $page++)
            <a href="https://everybloom.com/admin/get-fail-sale-unchecked?page={{$page}}" style="width: 40px;text-align: center;" @if( $page == $pageNumber ) class="btn btn-success form-control" @else class="btn btn-primary form-control" @endif >{{$page}}</a>
        @endfor
    </div>
    <div style="overflow-x: scroll;">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th>Tarih</th>
            <th>Id</th>
            <th>Gönderen</th>
            <th>Telefon</th>
            <th>Mail</th>
            <th>Ürün</th>
            <th>Alıcı</th>
            <th>Not</th>
            <th>Sonuç</th>
            <th>Hata Mesajı</th>
            <th>Bölge</th>
            <th>Tarih</th>
            <th>Tutar</th>
            <th width="30px">Görünürlük</th>
            <th title="Kullanıcının gün içinde gerçekleştirdiği başarılı sipariş sayısı" width="30px">Gün içi satış</th>
        </tr>
        @foreach($deliveryList as $coupon)
            <tr>
                <td style="padding-left:21px;">{{$coupon->date}}</td>
                <td style="padding-left:21px;">{{$coupon->id}}</td>
                <td style="padding-left:21px;">
                    @if( $coupon->user_id  )
                        <i title="Bloom And Fresh Kullanıcısı" class="fa fa-fw fa-user"></i>
                    @endif
                    {{$coupon->customer_name}} {{$coupon->customer_surname}}
                </td>
                <td style="padding-left:21px;">{{$coupon->sender_mobile}}</td>
                <td style="padding-left:21px;">{{$coupon->sender_email}}</td>
                <td style="padding-left:21px;">{{$coupon->products}}</td>
                <td style="padding-left:21px;">{{$coupon->contact_name}} {{$coupon->contact_surname}}</td>
                <td style="padding-left:21px;">{{$coupon->card_message}}</td>
                <td style="padding-left:21px;">
                    @foreach($coupon->isBank as $error_log)
                        {{$error_log->error_message}}
                        <br>
                    @endforeach
                    @foreach($coupon->garantiLog as $garanti_log)
                        @if( $garanti_log->error_code != 40001 )
                                {{$tempErrorCode[(int)$garanti_log->error_code]}}
                        @endif
                            <br>
                    @endforeach
                    @if(count($coupon->isBank) > 0)
                            (ISB)
                    @else
                        @if(count($coupon->garantiLog) == 0 && $coupon->payment_methods != "" && $coupon->payment_methods != "Banka sayfasında.")
                            Hata kodu bulunamadı!
                        @elseif(count($coupon->garantiLog) == 0 && $coupon->payment_methods == "Banka sayfasında.")
                            Banka sayfasından dönüş olmamış
                        @endif
                    @endif
                </td>
                <td style="padding-left:21px;">
                    @foreach($coupon->isBank as $error_log)
                        @if( $error_log->code == '1050' || $error_log->code == '1051' || $error_log->code == '1052' || $error_log->code == '1054' || $error_log->code == '1084' ||
                        $error_log->code == '04' || $error_log->code == '05' || $error_log->code == '07' || $error_log->code == '33' || $error_log->code == '37' ||
                        $error_log->code == '54' || $error_log->code == '82' || $error_log->code == '6000' )
                            Banka Mesajı: Girmiş olduğunuz kart bilgileri hatalı. Kontrol edip tekrar deneyiniz.
                        @elseif( $error_log->code == '51' )
                            Banka Mesajı: İşlem yapmaya çalıştığınız karta ait limit müsait değil.
                        @elseif( $error_log->code == '57' )
                            Banka Mesajı: Kartınızın işlem izni yok. Lütfen bankanızla iletişime geçin.
                        @elseif( $error_log->code == 'NNNN' )
                            Banka Mesajı: Kartınız 3D ile işlem için kayıtlı değil.
                        @elseif( $error_log->code == '93' )
                            Banka Mesajı: Kartınız internet üzerinden alışverişe kapalıdır.
                        @else
                            Satış gerçekleşmedi. Banka veya kart ile ilgili teknik bir sorun var. Lütfen tekrar dene. Ya da 0212 212 0 282’den bizimle iletişime geç
                        @endif
                        <br>
                    @endforeach
                    @foreach($coupon->garantiLog as $garanti_log)
                            @if( $garanti_log->error_code == '4'  || $garanti_log->error_code == '14' || $garanti_log->error_code == '18' || $garanti_log->error_code == '33' ||
                        $garanti_log->error_code == '34' || $garanti_log->error_code == '36' || $garanti_log->error_code == '37' || $garanti_log->error_code == '41' ||
                        $garanti_log->error_code == '43' || $garanti_log->error_code == '55' || $garanti_log->error_code == '56' || $garanti_log->error_code == '82' || $garanti_log->error_code == '12' )
                                Banka Mesajı: Girmiş olduğunuz kart bilgileri hatalı. Kontrol edip tekrar deneyiniz.
                            @elseif( $garanti_log->error_code == '16' || $garanti_log->error_code == '51'  )
                                Banka Mesajı: İşlem yapmaya çalıştığınız karta ait limit müsait değil.
                            @else
                                Satış gerçekleşmedi. Banka veya kart ile ilgili teknik bir sorun var. Lütfen tekrar dene. Ya da 0212 212 0 282’den bizimle iletişime geç
                            @endif
                        <br>
                    @endforeach
                </td>
                <td style="padding-left:21px;">{{$coupon->district}}</td>
                <td style="width: 125px;">
                   {{explode(" ", $coupon->wanted_delivery_date)[0]}} - {{ explode(":", explode(" ", $coupon->wanted_delivery_date)[1])[0] }}:{{ explode(":", explode(" ", $coupon->wanted_delivery_limit)[1])[0] }}
                </td>
                <td style="padding-left:21px;">{{$coupon->sum_total}}</td>
                @if($id == $coupon->id )
                    {!! Form::model($coupon, ['action' => 'AdminPanelController@updateSales', 'files'=>true ,  'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps']) !!}
                    <td>
                        {!! Form::checkbox('sale_fail_visibility', null, $coupon->sale_fail_visibility, [ 'style' => 'width :30px;height:30px']) !!}
                    </td>
                        {!! Form::hidden('id', $coupon->id, ['class' => 'form-control']) !!}
                    <td>
                        {!! Form::text('admin_not', $coupon->admin_not, ['class' => 'form-control']) !!}
                    </td>
                    <td>{!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}</td>
                    {!! Form::close() !!}
                @else
                    <td>{!! Form::checkbox($coupon->id . '_active', null, $coupon->sale_fail_visibility, ['class' => 'changeSales'  , 'style' => 'width : 30px;height : 30px;']) !!}</td>
                    <td style="padding-left:21px;">{{$coupon->complete}}</td>
                @endif
            </tr>
        @endforeach
    </table>
    </div>

@stop()

@section('footer')
<script>
$( document ).ready(function() {
    $('#changeFluid').removeClass('container');
    $('#changeFluid').addClass('container-fluid');
});

$(".changeSales").change(function() {
    if(this.checked) {
        console.log(this.name.split('_')[0]);
        $.ajax({
                url: '/admin/update-selected-fail-sale',
                method: "POST",
                data: {
                    id : this.name.split('_')[0],
                    value : 1
                },
                success: function(data) {
                    console.log('Başarılı');
                }
        });
        //$('#status_all').attr('checked', false);
    }
    else{
        $.ajax({
            url: '/admin/update-selected-fail-sale',
            method: "POST",
            data: {
                id : this.name.split('_')[0],
                value : 0
            },
            success: function(data) {
                console.log('Başarılı');
            }
        });
    }
});

</script>
@stop