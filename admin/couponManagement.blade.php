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
    <table style="width: 100%;">
        <tr style="width: 100%">
            <td>
                <h1>Bloom & Fresh Kupon Yönetim Ekranı</h1>
            </td>
            <td>
            {!! Html::link('/admin/multipleCompany/create' , 'Ürün Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
        </tr>
    </table>
    <table id="products-table" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th> İsim </th>
            <th> Kupon Değeri </th>
            <th> Toplam </th>
            <th> Kullanılan </th>
            <th> Aktif </th>
        </tr>

        @foreach($products as $product)
            <tr>
                <td style="font-size: 18px;">{{$product->name}}</td>
                <td style="font-size: 18px;">{{$product->value}}</td>
                <td style="font-size: 18px;">{{$product->totalSum}}</td>
                <td style="font-size: 18px;">{{$product->usedCoupon}}</td>
                <td style="font-size: 18px;">{{$product->listCoupon}}</td>
            </tr>
        @endforeach
    </table>

@stop()

@section('footer')
    <script>
    </script>
@stop