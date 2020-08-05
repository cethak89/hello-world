@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        div.form-group {
            height: 20px;
        }

    </style>
@stop

@section('content')
    <style>
        .table > tbody > tr > td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
        }
    </style>
    @foreach($tempQueryList as $key=>$product )
        @if($product->studio)
            <img style="width: 180px;padding-left: 20px;"
                 src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/logo/s.bloom.svg">
        @else
            <img style="padding-left: 20px;" src="{{ asset('adminImage/bnfLogo.png') }}">
        @endif
        <img style="margin-right: auto;margin-left: auto;display: inline-table;float: right;"
             src="data:image/png;base64, {{ base64_encode(QrCode::format('png')->size(100)->encoding('UTF-8')->errorCorrection('H')->generate("https://everybloom.com/admin/completeSalePage/" . $product->id))}}">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
            <tr>
                <td width="27%">
                    Ürün Adı :
                </td>
                <td>
                    <div style="margin-bottom: 0px;font-size: 18px;font-weight: bold;" class="form-group">
                        {{$product->products}}
                    </div>
                </td>
            </tr>
            <tr>
                <td width="27%">
                    Ekstra Ürün :
                </td>
                <td>
                    <div style="margin-bottom: 0px;font-size: 18px;font-weight: bold;" class="form-group">
                        {{$product->cikolatName}}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Teslim Saati :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        {{$product->dateInfo}}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Teslim Notu :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        {{$product->delivery_not}}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Semt :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        {{$product->district}}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Adres :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        {{$product->address}}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Alıcı Ad Soyad :
                </td>
                <td>
                    <div style="margin-bottom: 0px;font-weight: 900;" class="form-group">
                        {{$product->name}} {{$product->surname}}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Alıcı Telefon :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        (ÖNCE GÖNDERİCİYİ ARADIĞINDAN EMİN OL!) <br> @if( $product->mobile ) ({{substr($product->mobile, 0, 3)}}) {{substr($product->mobile, 3, 3)}} {{substr($product->mobile, 6, 2)}} {{substr($product->mobile, 8, 2)}} @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Gönderen Ad Soyad :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        {{$product->sender_name}} {{$product->sender_surname}}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    İrsaliye Numarası :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        A-{{$product->id}}
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Gönderen Telefon :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        @if( $product->sender_mobile ) ({{substr($product->sender_mobile, 0, 3)}}) {{substr($product->sender_mobile, 3, 3)}} {{substr($product->sender_mobile, 6, 2)}} {{substr($product->sender_mobile, 8, 2)}} @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Teslim Alan Ad Soyad :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">

                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    İmza :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">

                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Teslim Tarihi :
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">

                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div style="margin-bottom: 0px;text-align: center;" class="form-group">
                        @if( $product->cikolatName )
                            **** DİKKAT! Bu sipariş ile 2(iki) adet ürün teslim edilecektir! ****
                        @endif
                    </div>
                </td>
            </tr>
        </table>
        <!--<img  class="center-block"  src="data:image/png;base64, {{ base64_encode(QrCode::format('png')->size(100)->encoding('UTF-8')->errorCorrection('H')->generate("https://everybloom.com/admin/completeSalePage/" . $product->id))}}">
    <br>
    <br>
    <br>
    @if($product->studio)
            <img class="center-block" style="width: 50px;" src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/logo/s.bloom2.svg">
@else
            <img class="center-block"  src="{{ asset('adminImage/bnfSmall.png') }}">
        @endif
                <p style=" @if( ($key + 1) != count($tempQueryList) )  page-break-before: always @endif"></p>-->
    @endforeach

@stop()