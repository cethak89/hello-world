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
                <h1>Bloom & Fresh Ürün Listesi</h1>
            </td>
            <td>
                {!! Html::link('/admin/create/product' , 'Ürün Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
            </td>
        </tr>
    </table>
    <table id="filterTable" class="table table-hover">
        <tr style="width: 100%">
            <td>
                Ürün Durumu :
            </td>
            <td>
                <select style="width: 300px;" class="form-control select2" onchange="selectProduct($(this).val());" id="tagId">
                    <option value="all">Hepsi</option>
                    <option selected value="1">Aktifler</option>
                    <option value="0">Pasifler</option>
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
    <button id="testXls" class="btn btn-danger"  onClick ="$('tr').each(function() {$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(4)').remove();$(this).find('th:eq(5)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(4)').remove();$(this).find('td:eq(4)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Ürün Listesi',filename: 'Ürün Listesi'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>
    <table id="products-table" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th style="width: 70px;">Yakında</th>
            <th style="width: 51px;">Limit</th>
            <th style="width: 60px;">Durum</th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDesc',['name'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Ürün Adı <span onclick="window.location='{{ action('AdminPanelController@orderWith' , ['name'])  }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDesc',['price'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Fiyat <span onclick="window.location='{{ action('AdminPanelController@orderWith' , ['price'] ) }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDesc',['landing_page_order'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Ekran Sirasi <span onclick="window.location='{{ action('AdminPanelController@orderWith' , ['landing_page_order'] ) }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDesc',['count'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Satış Adeti <span onclick="window.location='{{ action('AdminPanelController@orderWith' , ['count'] ) }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th style="width: 30px;">Fotoğraf</th>
            <th style="width: 90px;"> </th>
        </tr>
        @foreach($products as $product)
            @if($id == $product->id )
                {!! Form::model($product, ['action' => 'AdminPanelController@store', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr id="row-id-{{$product->id}}">
                    <td>
                        <div style="margin-bottom: 0px;margin-top: 0px;width: 20px;margin-left: auto;margin-right: auto;" class="checkbox">
                            <label>
                                {!! Form::checkbox('coming_soon', null, $product->coming_soon, ['style' => 'width:20px;height:20px;', 'id' => 'comingSoon_' . $product->id]) !!}
                            </label>
                        </div>
                    </td>
                    <td>
                        <div style="margin-bottom: 0px;margin-top: 0px;width: 20px;margin-left: auto;margin-right: auto;" class="checkbox">
                            <label>
                                {!! Form::checkbox('limit_statu', null, $product->limit_statu, ['style' => 'width:20px;height:20px;', 'id' => 'limitStatus_' . $product->id]) !!}
                            </label>
                        </div>
                    </td>
                    <td>
                        <div style="margin-bottom: 0px;margin-top: 0px;width: 20px;margin-left: auto;margin-right: auto;" class="checkbox">
                            <label>
                                {!! Form::checkbox('activation_status', null, $product->activation_status_id, ['style' => 'width:20px;height:20px;', 'id' => 'active_' . $product->id ]) !!}
                                <input class="hidden" id="past_{{$product->id}}" value="{{$product->activation_status_id}}">
                            </label>
                        </div>
                    </td>
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
                    <td style="padding-left:21px;">
                    </td>
                    <td>
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control changeClass' , 'id' => 'changeProductDetail_' . $product->id ]) !!}
                        </div>
                    </td>
                </tr>
                {!! Form::close() !!}
            @else
                {!! Form::open(['action' => 'AdminPanelController@delete', 'method' => 'DELETE', 'id' => 'form_' . $product->id ]) !!}
                <tr class="trClass all {{$product->activation_status_id}}" id="row-id-{{$product->id}}" >
                    <td onclick="window.location='{{ action('AdminPanelController@show', [ $product->id ]) }}'">
                        <div style="margin-bottom: 0px;margin-top: 0px;width: 20px;margin-left: auto;margin-right: auto;" class="checkbox">
                            <label>
                                {!! Form::checkbox('coming_soon', null, $product->coming_soon, ['style' => 'width:20px;height:20px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>
                    <td onclick="window.location='{{ action('AdminPanelController@show', [ $product->id ]) }}'">
                        <div style="margin-bottom: 0px;margin-top: 0px;width: 20px;margin-left: auto;margin-right: auto;" class="checkbox">
                            <label>
                                {!! Form::checkbox('limit_statu', null, $product->limit_statu, ['style' => 'width:20px;height:20px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>
                    <td onclick="window.location='{{ action('AdminPanelController@show', [ $product->id ]) }}'">
                        <div style="margin-bottom: 0px;margin-top: 0px;width: 20px;margin-left: auto;margin-right: auto;" class="checkbox">
                            <label>
                                {!! Form::checkbox('activation_status', null, $product->activation_status_id, ['style' => 'width:20px;height:20px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>
                    <td onclick="window.location='{{ action('AdminPanelController@show', [ $product->id ]) }}'">{{$product->name}}</td>
                    <td onclick="window.location='{{ action('AdminPanelController@show', [ $product->id ]) }}'">{{$product->price}}</td>
                    <td onclick="window.location='{{ action('AdminPanelController@show', [ $product->id ]) }}'">{{$product->landing_page_order}}</td>
                    <td onclick="window.location='{{ action('AdminPanelController@show', [ $product->id ]) }}'">{{$product->saleCount}}</td>
                    <td>
                    </td>
                    <td>
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                        <div style="    margin-bottom: 0px;" class="form-group">
                            <a href="/admin/products/detail/{{$product->id}}">
                                <i style="font-size: 25px;" class="fa fa-fw fa-cog"></i>
                            </a>
                            <a href="#" onclick="var x;if (confirm('Silmek istediğinize emin misiniz?') == true) {var tempId = '#form_{{$product->id}}';$(tempId).submit();return true;} else {return false;}" class="test">
                                <i style="font-size: 25px;color:red" class="fa fa-fw fa-remove"></i>
                            </a>
                            {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control test hidden' , 'style' => 'width:100%;', 'id' => 'deleteButton']) !!}
                        </div>
                    </td>
                </tr>
                {!! Form::close() !!}
            @endif
        @endforeach
    </table>

@stop()

@section('footer')
    <script>
    </script>
@stop