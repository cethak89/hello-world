@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        div.form-group {
            height: 20px;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .top-container {
            background-color: #f1f1f1;
            padding: 30px;
            text-align: center;
        }

        .header {
            padding: 10px 16px;
            background: #555;
            color: #f1f1f1;
        }

        .content {
            padding: 16px;
        }

        .sticky {
            position: fixed;
            top: 0;
            width: 100%;
        }

        .sticky + .content {
            padding-top: 102px;
        }
    </style>
@stop

@section('content')

    <table class="top-container" width="100%">
        <tr style="width: 100%">
            <td>
                <h1>Bloom & Fresh Katalog
                    @if( Request::get('date') == 'lastweek' )
                        <span style="color: #00a65a;">Geçen Hafta</span>
                    @elseif( Request::get('date') == 'yesterday' )
                        <span style="color: #3c8dbc;">Dün</span>
                    @elseif( Request::get('date') == 'today' )
                        <span style="color: #00c0ef;">Bugün</span>
                    @elseif( Request::get('date') == 'thisweek' )
                        <span style="color: #f39c12;">Bu Hafta</span>
                    @elseif( Request::get('date') == 'thismonth' )
                        <span style="color: #dd4b39;">Bu Ay</span>
                    @else
                        <span style="color: #00a65a;">Hepsi</span>
                    @endif
                </h1>
            </td>
            <td>
            </td>
        </tr>
    </table>
    <a style="width: 125px;margin-bottom: 20px;margin-left: 5px;"
       href="https://everybloom.com/productOrder?date=lastweek" class="btn btn-success form-control">Geçtiğimiz
        Hafta</a>
    <a style="width: 100px;margin-bottom: 20px;" href="https://everybloom.com/productOrder?date=yesterday"
       class="btn btn-primary form-control">Dün</a>
    <a style="width: 100px;margin-bottom: 20px;" href="https://everybloom.com/productOrder?date=today"
       class="btn btn-info form-control">Bugün</a>
    <a style="width: 100px;margin-bottom: 20px;" href="https://everybloom.com/productOrder?date=thisweek"
       class="btn btn-warning form-control">Bu Hafta</a>
    <a style="width: 100px;margin-bottom: 20px;" href="https://everybloom.com/productOrder?date=thismonth"
       class="btn btn-danger form-control">Bu Ay</a>
    <a style="width: 100px;margin-bottom: 20px;" href="https://everybloom.com/productOrder"
       class="btn btn-success form-control">Hepsi</a>
    <div style="background: white;" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        @foreach($products as $product)
            @if( $product->saleCount > 0 )
                <div style="height: 370px;" class="col-lg-2 col-md-3 col-sm-6 col-xs-12">
                    <a href="/goProductDetail/{{$product->id}}">
                        <img style="width: 100%;height: 307px;" src="{{$product->image_url}}">
                    </a>
                    <div style="width: 100%;border: 1px solid #d0c9c9;text-align: center;">
                        {{$product->name}} {{$product->price}} TL
                    </div>
                    <div style="width: 100%;border: 1px solid #d0c9c9;text-align: center;">
                        {{$product->saleCount}} Adet
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@stop()

@section('footer')
@stop