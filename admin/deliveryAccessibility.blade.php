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
                <h1>Bloom & Fresh Uygunluk Listesi</h1>
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
            <th>Çiçek Adı</th>
            <th>Kapanış Saati</th>
            <th>Açılış Saati</th>
        </tr>
        @foreach($messages as $coupon)
                <tr>
                    <td style="padding-left:21px;">{{$coupon->flowers_name}}</td>
                    <td style="padding-left:21px;">{{$coupon->close_time}}</td>
                    <td style="padding-left:21px;">{{$coupon->open_time}}</td>
                </tr>
        @endforeach
    </table>
    <button id="testXls"class="btn btn-danger"  onClick ="$('#products2-table').table2excel({exclude: '.excludeThisClass',name: 'Mesajlar',filename: 'Mesajlar'});">Excel Çıktısı İçin Tıklayınız</button>
        <table id="products2-table" class="table table-hover table-bordered" style="vertical-align: middle;">
            <tr>
                <th>Zaman Dilimi</th>
                <th>Kapanış Saati</th>
                <th>Açılış Saati</th>
                <th>Dağıtım Bölge</th>
            </tr>
            @foreach($messages2 as $coupon2)
                    <tr>
                        <td style="padding-left:21px;">{{$coupon2->time_segment}}</td>
                        <td style="padding-left:21px;">{{$coupon2->close_time}}</td>
                        <td style="padding-left:21px;">{{$coupon2->open_time}}</td>
                        <td style="padding-left:21px;">{{$coupon2->delivery_location}}</td>
                    </tr>
            @endforeach
        </table>

@stop()

@section('footer')
@stop