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
                <h1 style="padding-left: 10px;">Bloom & Fresh Ürün-Tedarikçi Listesi</h1>
            </td>
        </tr>
    </table>
    <table style="max-width: 440px;margin-bottom: 20px;" id="filterTable" class="table table-hover">
        <tr style="width: 100%">
            <td style="padding-top: 15px;padding-left: 15px;">
                Ürün Durumu :
            </td>
            <td style="width: 140px;">
                <select style="width: 140px;" class="form-control select2" onchange="updateActivity($(this).val());" id="activity">
                    <option @if( $activity == 'all' ) selected @endif value="all">Hepsi</option>
                    <option @if( $activity == '1' ) selected @endif value="1">Aktifler</option>
                    <option @if( $activity == '0' ) selected @endif value="0">Pasifler</option>
                </select>
            </td>
            <td>
                <select style="width: 140px;" class="form-control select2" onchange="updateStatus($(this).val())" id="status">
                    <option @if( $status == 'all' ) selected @endif value="all">Hepsi</option>
                    <option @if( $status == 'stock' ) selected @endif value="stock">Stokta</option>
                    <option @if( $status == 'limit' ) selected @endif value="limit">Tükendi</option>
                    <option @if( $status == 'coming' ) selected @endif value="coming">Yakında</option>
                </select>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: inherit;width: 140px;padding-left: 15px;">
                Kategori
            </td>
            <td colspan="2">
                <div style="margin-bottom: 0px;" class="form-group">
                    <select style="width: 140px;" name="category" class="form-control"  id="category" onchange="updateCategory($(this).val())">
                        <option @if( $category == 0 ) selected @endif value="0">Hepsi</option>
                        <option @if( $category == 1 ) selected @endif value="1">Çiçek</option>
                        <option @if( $category == 2 ) selected @endif value="2">Çikolata</option>
                        <option @if( $category == 3 ) selected @endif value="3">Hediye Kutusu</option>
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: inherit;padding-left: 15px;border-bottom: 1px solid #f4f4f4;">
                Alt Kategori
            </td>
            <td style="border-bottom: 1px solid #f4f4f4;" colspan="2">
                <div style="margin-bottom: 0px;" class="form-group">
                    <select style="width: 140px;" name="sub_category" class="form-control" id="sub_category" onchange="updateSubCategory($(this).val())">
                        <option @if( $sub_category == 0 ) selected @endif value="0">Hepsi</option>
                        <option @if( $sub_category == 11 ) selected @endif value="11">Buket</option>
                        <option @if( $sub_category == 12 ) selected @endif value="12">Masaüstü</option>
                        <option @if( $sub_category == 13 ) selected @endif value="13">Sukulent</option>
                        <option @if( $sub_category == 14 ) selected @endif value="14">Saksı</option>
                        <option @if( $sub_category == 15 ) selected @endif value="15">Orkide</option>
                        <option @if( $sub_category == 16 ) selected @endif value="16">Solmayan Gül</option>
                        <option @if( $sub_category == 16 ) selected @endif value="17">Kutuda Çiçek</option>
                        <option @if( $sub_category == 21 ) selected @endif value="21">Godiva</option>
                        <option @if( $sub_category == 22 ) selected @endif value="22">Baylan</option>
                        <option @if( $sub_category == 23 ) selected @endif value="23">BNF Macarons</option>
                        <option @if( $sub_category == 24 ) selected @endif value="24">Hazz</option>
                        <option @if( $sub_category == 25 ) selected @endif value="25">TAFE</option>
                        <option @if( $sub_category == 31 ) selected @endif value="31">BNF Kutu</option>
                        <option @if( $sub_category == 32 ) selected @endif value="32">Godiva Kutu</option>
                        <option @if( $sub_category == 33 ) selected @endif value="33">TAFE Kutu</option>
                    </select>
                </div>
            </td>
        </tr>
    </table>
    <a style="margin-left: 15px;width: 143px;background-color: #3c8dbc;border-color: #3c8dbc;color: white;" href="https://everybloom.com/salesByProduct" class="btn form-control">Temizle</a>
    <a style="margin-left: 2px;width: 277px;" onclick="filterPage();" class="btn btn-success form-control">Sorgula</a>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <div style="text-align: right;padding-right: 6px;padding-bottom: 5px;">
        <a style="color: #1f7045;cursor: pointer;" onClick ="$('tr').each(function() {}); $('#products-table').table2excel({exclude: '.hidden',name: 'Ürün Listesi',filename: 'Ürün Listesi'});location.reload();">
            <i style="font-size: 25px;" class="fa fa-fw fa-file-excel-o"></i>
        </a>
    </div>
    <table id="products-table" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th style="width: 70px;">Yakında</th>
            <th style="width: 51px;">Limit</th>
            <th style="width: 60px;">Durum</th>
            <th>Ürün Adı</th>
            <th>Kategorisi</th>
            <th>Alt Kategorisi</th>
            <th>Fiyat</th>
            <th>Güncel Stok</th>
            <th>Tedarikçi</th>
            <th>Sipariş</th>
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
                    @if( number_format($product->product_type, 0) == 1 )
                        Çiçek
                    @elseif( number_format($product->product_type, 0) == 2 )
                        Çikolata
                    @else
                        Hediye Kutusu
                    @endif
                </td>
                <td>
                    @if( $product->product_type_sub == 11 )
                        Buket
                    @elseif( $product->product_type_sub == 12 )
                        Masaüstü
                    @elseif( $product->product_type_sub == 13 )
                        Sukulent
                    @elseif( $product->product_type_sub == 14 )
                        Saksı
                    @elseif( $product->product_type_sub == 15 )
                        Orkide
                    @elseif( $product->product_type_sub == 16 )
                        Solmayan Gül
                    @elseif( $product->product_type_sub == 17 )
                        Kutuda Çiçek
                    @elseif( $product->product_type_sub == 21 )
                        Godiva
                    @elseif( $product->product_type_sub == 22 )
                        Baylan
                    @elseif( $product->product_type_sub == 23 )
                        BNF Macarons
                    @elseif( $product->product_type_sub == 24 )
                        Hazz
                    @elseif( $product->product_type_sub == 25 )
                        TAFE
                    @elseif( $product->product_type_sub == 31 )
                        BNF Kutu
                    @elseif( $product->product_type_sub == 32 )
                        Godiva Kutu
                    @elseif( $product->product_type_sub == 33 )
                        TAFE Kutu
                    @endif
                </td>
                <td>
                    {{$product->price}}
                </td>
                <td>
                    {{$product->count}}
                </td>
                <td>
                    @if( $product->supplier_id )
                        @foreach( $suppliers as $supplier )
                            @if( $supplier->id == $product->supplier_id )
                                {{$supplier->name}}
                            @endif
                        @endforeach
                    @endif
                </td>
                <td>
                </td>
            </tr>
        @endforeach
    </table>
@stop()

@section('footer')

    <script>

        $( document ).ready(function() {

            if($('#category').val()){
                updateCategory($('#category').val());
            }

            if($('#sub_category').val()){
                updateSubCategory($('#sub_category').val());
            }

            if($('#activity').val()){
                updateActivity($('#activity').val());
            }

            if($('#status').val()){
                updateStatus($('#status').val());
            }

        });

        var category = '';
        var sub_category = '';
        var productActivity = '';
        var productStatus = '';

        function updateCategory(tempCategory) {
            category = tempCategory;
        }

        function updateSubCategory(tempCategory) {
            sub_category = tempCategory;
        }

        function updateActivity(activity) {
            productActivity = activity;
        }

        function updateStatus(status) {
            productStatus = status;
        }

        function filterPage() {

            tempUrl = 'https://everybloom.com/productWithSupplier?';

            if(category){
                tempUrl = tempUrl + 'category='  + category + '&'
            }

            if(sub_category){
                tempUrl = tempUrl + 'sub_category='  + sub_category + '&'
            }

            if(productActivity){
                tempUrl = tempUrl + 'activity='  + productActivity + '&'
            }

            if(productStatus){
                tempUrl = tempUrl + 'status='  + productStatus + '&'
            }

            location.replace(tempUrl);

        }

    </script>
@stop