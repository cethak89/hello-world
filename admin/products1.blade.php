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
                <h1 style="padding-left: 10px;">Bloom & Fresh Ürün Listesi</h1>
            </td>
        </tr>
    </table>
    <table style="max-width: 440px;margin-bottom: 0px;" id="filterTable" class="table table-hover">
        <tr style="width: 100%">
            <td style="padding-top: 15px;padding-left: 15px;">
                Ürün Durumu :
            </td>
            <td>
                <select style="width: 100px;" class="form-control select2" onchange="updateProducts();" id="tagId">
                    <option value="all">Hepsi</option>
                    <option selected value="1">Aktifler</option>
                    <option value="0">Pasifler</option>
                </select>
            </td>
            <td>
                <select style="width: 100px;" class="form-control select2" onchange="updateProducts();" id="statusId">
                    <option selected value="all">Hepsi</option>
                    <option value="stock">Stokta</option>
                    <option value="limit">Tükendi</option>
                    <option value="coming">Yakında</option>
                </select>
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
    <div style="text-align: right;padding-right: 6px;padding-bottom: 5px;">
        <a style="color: #1f7045;cursor: pointer;" onClick ="$('tr').each(function() {$(this).find('th:eq(5)').remove();$(this).find('td:eq(5)').remove()}); $('#products-table').table2excel({exclude: '.hidden',name: 'Ürün Listesi',filename: 'Ürün Listesi'});location.reload();">
            <i style="font-size: 25px;" class="fa fa-fw fa-file-excel-o"></i>
        </a>
        <a style="color: #00a65a;" href="/admin/create/product">
            <i style="font-size: 25px;" class="fa fa-fw fa-plus"></i>
        </a>
    </div>
    <table id="products-table" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th style="width: 70px;">Yakında</th>
            <th style="width: 51px;">Limit</th>
            <th style="width: 60px;">Durum</th>
            <th>Ürün Adı</th>
            <th>Fiyat</th>
            <th style="width: 80px;"> </th>
        </tr>
        @foreach($products as $product)
            <tr class="trClass all {{$product->activation_status_id}} @if( $product->activation_status_id == 0 ) hidden @endif @if( $product->coming_soon == 1 ) coming @endif @if( $product->limit_statu == 1 ) limit @else stock @endif " id="row-id-{{$product->id}}" >
                <td>
                    @if( $product->coming_soon == 1 )
                        OK
                    @else
                        X
                    @endif
                </td>
                <td>
                    @if( $product->limit_statu == 1 )
                        OK
                    @else
                        X
                    @endif
                </td>
                <td>
                    @if( $product->activation_status_id == 1 )
                        OK
                    @else
                        X
                    @endif
                </td>
                <td>
                    {{$product->name}}
                </td>
                <td>
                    {{$product->price}}
                </td>
                <td style="text-align: center;">
                    <a href="/admin/products/detail/{{$product->id}}?bl=2">
                        <i style="font-size: 25px;" class="fa fa-fw fa-cog"></i>
                    </a>
                </td>
            </tr>
        @endforeach
    </table>
@stop()

@section('footer')

    <script>

        $( document ).ready(function() {

            /*$('.trClass').addClass('hidden');
            $('.trClass').each(function(i, obj) {

                if( $(obj).hasClass('1') ){
                    $(obj).removeClass('hidden');
                }

            });*/

            setTimeout(function(){
                $('.trClass').addClass('hidden');
                $('.trClass').each(function(i, obj) {

                    if( $(obj).hasClass('1') ){
                        $(obj).removeClass('hidden');
                    }

                });
            }, 1000);

        });

        function updateProducts() {

            console.log('test');

            tempTagId = $('#tagId').val();
            tempStatusId = $('#statusId').val();

            $('.trClass').addClass('hidden');

            $('.trClass').each(function(i, obj) {

                if( $(obj).hasClass(tempTagId) && $(obj).hasClass(tempStatusId) ){
                    $(obj).removeClass('hidden');
                }

            });

        }

    </script>
@stop