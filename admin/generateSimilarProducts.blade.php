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
    <h1 style="margin-top: 0px;display: inline;">Bloom & Fresh Similar Products Simulator</h1>

    <table class="table table-hover table-bordered">
        <tr>
            <th style="text-align: center;">
                Ürün Adı
            </th>
            <th style="text-align: center;">
                Benzer Ürün 1
            </th>
            <th style="text-align: center;">
                Benzer Ürün 2
            </th>
            <th style="text-align: center;">
                Benzer Ürün 3
            </th>
            <th style="text-align: center;">
                Benzer Ürün 4
            </th>
        </tr>
        @foreach( $allFlowers as $flower )
            <tr>
                <td style="text-align: center;">
                    <p style="display: block;text-align: center;">{{$flower->name}}</p>
                    <img style="width: 90px;" src="{{$flower->image_url}}">
                </td>
                <td style="text-align: center;">
                    <p style="display: block;text-align: center;">{{$flower->similarProducts[0]->name}}</p>
                    <img style="width: 90px;" src="{{$flower->similarProducts[0]->image_url}}">
                </td>
                <td style="text-align: center;">
                    <p style="display: block;text-align: center;">{{$flower->similarProducts[1]->name}}</p>
                    <img style="width: 90px;" src="{{$flower->similarProducts[1]->image_url}}">
                </td>
                <td style="text-align: center;">
                    <p style="display: block;text-align: center;">{{$flower->similarProducts[2]->name}}</p>
                    <img style="width: 90px;" src="{{$flower->similarProducts[2]->image_url}}">
                </td>
                <td style="text-align: center;">
                    <p style="display: block;text-align: center;">{{$flower->similarProducts[3]->name}}</p>
                    <img style="width: 90px;" src="{{$flower->similarProducts[3]->image_url}}">
                </td>
            </tr>
        @endforeach
    </table>

@stop()

@section('footer')
    <script>

    </script>

@stop()
