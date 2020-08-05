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
    <style>
        .table > tbody > tr > td {
            padding-top: 3px !important;
            padding-bottom: 3px !important;
        }
    </style>
    @foreach($tempQueryList as $key=>$product )
    <img style="padding-left: 20px;" src="{{ asset('adminImage/bnfLogo.png') }}">
    <img style="margin-right: auto;margin-left: auto;display: inline-table;float: right;" src="data:image/png;base64, {{ base64_encode(QrCode::format('png')->size(100)->encoding('UTF-8')->errorCorrection('H')->generate("https://everybloom.com/admin/completeSalePage/" . $product->id))}}">
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                <tr>
                    <td width="27%">
                        Ürün Adı :
                    </td>
                    <td>
                        <div style="margin-bottom: 0px;" class="form-group">
                            {{$product->product_name}}
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
                            {{$product->delivery_location}}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Adres :
                    </td>
                    <td>
                        <div style="margin-bottom: 0px;" class="form-group">
                            {{$product->receiver_address}}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Alıcı Ad Soyad :
                    </td>
                    <td>
                        <div style="margin-bottom: 0px;" class="form-group">
                            {{$product->receiver}}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Alıcı Telefon :
                    </td>
                    <td>
                        <div style="margin-bottom: 0px;" class="form-group">
                            {{$product->receiver_mobile}}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Gönderen Ad Soyad :
                    </td>
                    <td>
                        <div style="margin-bottom: 0px;" class="form-group">
                            {{$product->company_name}}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Gönderen Telefon :
                    </td>
                    <td>
                        <div style="margin-bottom: 0px;" class="form-group">
                            {{$product->company_mobile}}
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
                            {{$product->id}}
                        </div>
                    </td>
                </tr>
        </table>
    @endforeach

@stop()