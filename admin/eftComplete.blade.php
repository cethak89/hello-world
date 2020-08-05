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
                <h1>Sipariş Onay</h1>
            </td>
            <td>
                <button style="float: right;" id="" class="btn btn-danger"  onClick ="$('#example1').table2excel({exclude: '.excludeThisClass',name: 'Mesajlar',filename: 'Mesajlar'});">Excel Çıktısı İçin Tıklayınız</button>
            </td>
        </tr>
    </table>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body">
                    <table  style="width: 100%; font-size: 14px;" id="example1" data-width="100%" data-compression="6" data-min="1" data-max="14" cellpadding="0" cellspacing="0" class="table table-bordered table-striped responsive responsiveTable">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Üye No</th>
                                <th>Gönderen</th>
                                <th>Telefon</th>
                                <th>Sipariş No</th>
                                <th>Ürün</th>
                                <th>Eksta Ürün</th>
                                <th>Alıcı</th>
                                <th>Kupon</th>
                                <th>Çiçek Fiyatı</th>
                                <th>Ödenecek Tutar</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deliveryList as $coupon)
                                @if($coupon->complete)
                                @else
                                    <tr>
                                        <td style="padding-left:21px;">{{$coupon->date}}</td>
                                        <td style="padding-left:21px;">{{$coupon->user_id}}</td>
                                        <td style="padding-left:21px;">{{$coupon->customer_name}} {{$coupon->customer_surname}}</td>
                                        <td style="padding-left:21px;">{{$coupon->sender_mobile}}</td>
                                        <td style="padding-left:21px;">{{$coupon->id}}</td>
                                        <td style="padding-left:21px;">{{$coupon->products}}</td>
                                        <td style="padding-left:21px;">{{$coupon->extra_product}}</td>
                                        <td style="padding-left:21px;">{{$coupon->contact_name}} {{$coupon->contact_surname}}</td>
                                        <td>
                                            <button onclick="$('#myModal_{{$coupon->id}}').modal('show');" style="width: 90px;" class="btn btn-success form-control">Kullan</button>
                                            <table class="hidden">
                                                <tr>
                                                    <td>
                                                        {!! Form::checkbox('coupon_use', null, 0, ['class' => 'changeSales' , 'style' => 'width: 30px;height: 30px;' , 'id' => $coupon->id]) !!}
                                                    </td>
                                                    <td style="background-color: #EFEFEF">
                                                        <p>{{$coupon->coupon_value}}%</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        {!! Form::model($coupon, ['action' => 'AdminPanelController@completeFailSales', 'files'=>true ,  'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps']) !!}
                                        <td style="padding-left:21px;">
                                            @foreach($coupon->couponList as $key => $coupons)
                                                <p class="{{$coupon->id}} @if($key > 0) hidden @endif" style="color: red;" id="price_{{$coupons->id}}">{{ number_format($coupons->priceWithoutCoupon, 2, ',', '') }}</p>
                                                <p style="color: blue;" id="price_coupon_{{$coupons->id}}" class="hidden {{$coupon->id}}">{{ number_format($coupons->priceWithCoupon, 2, ',', '') }}</p>
                                            @endforeach
                                            @if(count($coupon->couponList) == 0)
                                                <p id="price_{{$coupon->id}}">{{$coupon->priceWithoutCoupon}}</p>
                                                <p id="price_coupon_{{$coupon->id}}" class="hidden">{{$coupon->priceWithCoupon}}</p>
                                            @endif
                                        </td>
                                        <td>
                                            @foreach($coupon->couponList as $key => $coupons)
                                                <p class="{{$coupon->id}} @if($key > 0) hidden @endif" style="color: red;" id="price_extra_{{$coupons->id}}">{{ number_format($coupons->extra_priceWithoutCoupon, 2, ',', '') }}</p>
                                                <p style="color: blue;" id="price_extra_coupon_{{$coupons->id}}" class="hidden {{$coupon->id}}">{{ number_format($coupons->extra_priceWithCoupon, 2, ',', '') }}</p>
                                            @endforeach
                                            @if(count($coupon->couponList) == 0)
                                                <p id="price_extra_{{$coupon->id}}">{{ number_format($coupon->extra_priceWithoutCoupon, 2, ',', '') }}</p>
                                                    <p id="price_extra_coupon_{{$coupon->id}}" class="hidden">{{ number_format($coupon->extra_priceWithCoupon, 2, ',', '') }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            {!! Form::hidden('id', $coupon->id, ['class' => 'form-control']) !!}
                                                {!! Form::hidden('price', $coupon->priceWithoutCoupon, ['class' => 'form-control']) !!}
                                                {!! Form::hidden('price_coupon', $coupon->priceWithCoupon, ['class' => 'form-control']) !!}
                                                {!! Form::hidden('coupon_ids', null, ['class' => 'form-control', 'id' => 'last_' . $coupon->id ]) !!}
                                            {!! Form::submit('Tamamla', ['class' => 'btn btn-success form-control']) !!}
                                        </td>
                                        {!! Form::close() !!}
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Tarih</th>
                                <th>Üye No</th>
                                <th>Gönderen</th>
                                <th>Telefon</th>
                                <th>Mail</th>
                                <th>Ürün</th>
                                <th>Eksta Ürün</th>
                                <th>Alıcı</th>
                                <th>Kupon</th>
                                <th>Çiçek Fiyatı</th>
                                <th>Ödenecek Tutar</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @foreach($deliveryList as $coupon)
        <div class="modal fade" id="myModal_{{$coupon->id}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div style="width: 700px;" class="modal-content">
                    <div class="modal-body">
                        <table class="table table-bordered table-striped" style="margin-bottom: 0px;">
                            <thead>
                                <td>
                                    Aktif
                                </td>
                                <td>
                                    İsim
                                </td>
                                <td style="width: 40px;">
                                    Değer
                                </td>
                            </thead>
                            @foreach($coupon->couponList as $coupons)
                                <tr>
                                    <td style="width: 40px;">
                                        <input class="changeSales" style="width: 30px;height: 30px;" id="{{$coupons->id}}" name="{{$coupon->id}}" type="checkbox">
                                    </td>
                                    <td style="text-align: left;vertical-align: middle;">
                                        {{$coupons->name}}
                                    </td>
                                    <td style="text-align: center;vertical-align: middle;">
                                        {{$coupons->value}}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button onclick="$('.modal').modal('hide');" style="width: 90px;" class="btn btn-success form-control">Tamam</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@stop()

@section('footer')
<script>
$( document ).ready(function() {
    $('#changeFluid').removeClass('container');
    $('#changeFluid').addClass('container-fluid');
});

$(".changeSales").change(function() {
    if(this.checked) {
        var tempIdLast = '#last_' + this.name;
        $(tempIdLast).val(this.id);
        $('.changeSales').prop("checked", false);
        var allClass = '.' + this.name;
        $(allClass).addClass('hidden');
        var selectedClass = '#' + this.id;
        $(selectedClass).prop("checked", true);
        var tempId = '#price_coupon_' + this.id;
        var tempIdWithout = '#price_' + this.id;
        var tempIdExtra =  '#price_extra_coupon_' + this.id;
        var tempIdWithoutExtra = '#price_extra_' + this.id;
        $(tempId).removeClass('hidden');
        $(tempIdWithout).addClass('hidden');
        $(tempIdExtra).removeClass('hidden');
        $(tempIdWithoutExtra).addClass('hidden');
        console.log($(tempIdExtra));
        //$('#status_all').attr('checked', false);
    }
    else{
        var tempIdLast = '#last_' + this.name;
        $(tempIdLast).val("");
        var allClass = '.' + this.name;
        $(allClass).addClass('hidden');
        var tempId = '#price_coupon_' + this.id;
        var tempIdWithout = '#price_' + this.id;
        var tempIdExtra =  '#price_extra_coupon_' + this.id;
        var tempIdWithoutExtra = '#price_extra_' + this.id;
        $(tempId).addClass('hidden');
        $(tempIdWithout).removeClass('hidden');
        $(tempIdExtra).addClass('hidden');
        $(tempIdWithoutExtra).removeClass('hidden');
        console.log($(tempIdWithoutExtra));
    }
});

</script>
@stop