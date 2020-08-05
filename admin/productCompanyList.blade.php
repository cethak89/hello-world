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
                <h1>Bloom & Fresh Kurumsal Ürün Listesi</h1>
            </td>
            <td>
            {!! Html::link('/admin/CompanyInfo/addProductPage' , 'Ürün Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
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
    <button id="testXls" class="btn btn-danger"  onClick ="$('tr').each(function() {$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(4)').remove();$(this).find('th:eq(5)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(4)').remove();$(this).find('td:eq(4)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Ürün Listesi',filename: 'Ürün Listesi'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>
    <table id="example1" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <thead>
            <th>Ürün Adı</th>
            <th>Fiyat</th>
            <th>Satış Adeti</th>
            <th style="width: 80px;">Fotoğraf</th>
            <th style="width: 40px;"> </th>
        </thead>

        @foreach($products as $product)
            @if($id == $product->id )
                {!! Form::model($product, ['action' => 'AdminPanelController@store', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr id="row-id-{{$product->id}}">
                    <td>
                        <div class="form-group">
                            {!! Form::text('name', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('price', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('landing_page_order', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td style="padding-left:21px;">{{$product->saleCount}}</td>
                    <!--<td>
                        <div class="form-group">
                            {!! Form::text('description', null, ['class' => 'form-control' ,  'maxlength' => '110']) !!}
                        </div>
                    </td>-->
                    <td style="padding-left:21px;">
                        <div style="width: 20px;margin-left: auto;margin-right: auto;">
                            <a href="{{ $product->mainImage }}" target="_blank">
                                <img style="box-shadow: 0px 1px 2px black;width: 20px;" src="{{ $product->mainImage }}">
                            </a>
                        </div>
                    </td>
                    <td style="width: 40px;">
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control changeClass' , 'id' => 'changeProductDetail_' . $product->id ]) !!}
                        </div>
                    </td>
                </tr>
                {!! Form::close() !!}
            @else
                <tr id="row-id-{{$product->id}}">
                    <td style="padding-left:21px;">{{$product->name}}</td>
                    <td style="padding-left:21px;">{{$product->price}}</td>
                    <td style="padding-left:21px;">{{$product->saleCount}}</td>
                    <!--<td style="padding-left:21px;">{{$product->description}}</td>-->
                    <td style="width: 80px;padding-left:21px;">
                        <div style="width: 20px;margin-left: auto;margin-right: auto;">
                            <a href="{{ $product->mainImage }}" target="_blank">
                                <img style="box-shadow: 0px 1px 2px black;width: 20px;" src="{{ $product->mainImage }}">
                            </a>
                        </div>
                    </td>
                    <td>
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            {!! Html::link('/admin/CompanyInfo/detailProductPage/' . $product->id , 'Detay', ['class' => 'btn btn-primary form-control', 'style' => 'width:100%; vertical-align: middle;']) !!}
                        </div>
                    </td>
                </tr>
            @endif
        @endforeach
    </table>

@stop()

@section('footer')
    <script>
        $('.test').click(function() {
            var x;
                if (confirm("Silmek istediğinize emin misiniz?") == true) {
                    return true;
                } else {
                    return false;
                }
        });

        $('.changeClass').click(function() {
            var tempId = $(this).attr('id').split("_")[1];
            var tempPast = '#past_' + tempId;
            var tempActive = '#active_' + tempId;
            console.log($(tempPast).val());
            console.log($(tempActive).is(':checked'));
            if($(tempActive).is(':checked') && $(tempPast).val() == 0 ){
                window.alert("Açtığın Ürünün Dağıtım Saatlerini Güncellemeyi Unutma!");
            }
        });

        $('#products-table').click(function(event){
            event.stopPropagation();
        });

        // go to anchor
        if( {{ $id > 0 ? 'true' : 'false' }} )
        {
            window.scrollTo(0, document.getElementById('row-id-{{ $id }}').offsetTop);
        }
    </script>
@stop