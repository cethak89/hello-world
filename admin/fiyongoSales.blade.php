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
                <h1>Bloom & Fresh Fiyongo Satışları</h1>
            </td>
            <td>
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
    <button id="testXls"class="btn btn-danger"  onClick ="$('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Mesajlar',filename: 'Mesajlar'});">Excel Çıktısı İçin Tıklayınız</button>
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th>Tarih</th>
            <th>ID</th>
            <th>Çiçek Adı</th>
            <th>Fiyat</th>
            <th>Kupon Değeri</th>
        </tr>
        @foreach($fiyongoSales as $coupon)
                <tr>
                    <td style="padding-left:21px;">{{$coupon->created_at}}</td>
                    <td style="padding-left:21px;">{{$coupon->id}}</td>
                    <td style="padding-left:21px;">{{$coupon->products}}</td>
                    <td style="padding-left:21px;">{{number_format($coupon->product_price/100*118, 2, ',', '.')}}</td>
                    <td style="padding-left:21px;">{{$coupon->value}}</td>
                </tr>
        @endforeach
    </table>

@stop()

@section('footer')
@stop