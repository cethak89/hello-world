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
            <!--<th>Tanım</th>-->
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
                    <!--<td>
                        <div class="form-group">
                            {!! Form::text('description', null, ['class' => 'form-control' ,  'maxlength' => '110']) !!}
                        </div>
                    </td>-->
                    <td style="padding-left:21px;">
                        <div style="width: 20px;margin-left: auto;margin-right: auto;">
                            <a href="{{ $product->mainImage }}" target="_blank">
                                Foto
                            </a>
                        </div>
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
                    <td>
                        @if( $product->coming_soon == 1 )
                            <span style="font-size: 21px;margin-left: auto;margin-right: auto;display: table;color: #00a65a;" class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        @endif
                    </td>
                    <td>
                        @if( $product->limit_statu == 1 )
                            <span style="font-size: 21px;margin-left: auto;margin-right: auto;display: table;color: #00a65a;" class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        @endif
                    </td>
                    <td>
                        @if( $product->activation_status_id == 1 )
                            <span style="font-size: 21px;margin-left: auto;margin-right: auto;display: table;color: #00a65a;" class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                        @endif
                    </td>
                    <td>{{$product->name}} <span style="font-size: 10px;vertical-align: inherit;">@if( count($cityList)> 1 ) @if( $product->city_id  == 1 ) İstanbul @else Ankara @endif  @endif</span> </td>
                    <td>{{$product->price}}</td>
                    <!--<td style="padding-left:21px;">{{$product->description}}</td>-->
                    <td>
                        <div style="width: 20px;margin-left: auto;margin-right: auto;">
                            <a href="{{ $product->mainImage }}" target="_blank">
                                Foto
                            </a>
                        </div>
                    </td>
                    <td>
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                        <div style="    margin-bottom: 0px;" class="form-group">
                            <a href="/admin/products/detail/{{$product->id}}?bl=1">
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

        //$('.test').click(function() {var x;if (confirm("Silmek istediğinize emin misiniz?") == true) {var tempId = '#form_{{$product->id}}';$(tempId).submit();return true;} else {return false;}});
//
        //function deleteProduct(productId){
//
        //}

        $( document ).ready(function() {
            var tempTr = '.' + 1;
            $('.trClass').addClass('hidden');
            $(tempTr).removeClass('hidden');
            return false;
        });

        function selectProduct(salesId){
            var tempTr = '.' + salesId;
            $('.trClass').addClass('hidden');
            $(tempTr).removeClass('hidden');
            return false;
        }

        $('.changeClass').click(function() {
            var tempId = $(this).attr('id').split("_")[1];
            var tempPast = '#past_' + tempId;
            var tempActive = '#active_' + tempId;
            console.log($(tempPast).val());
            console.log($(tempActive).is(':checked'));
            if($(tempActive).is(':checked') && $(tempPast).val() == 0 ){
                window.alert("Açtığın Ürünün Dağıtım Saatlerini Güncellemeyi Unutma!");
            }
            var tempId = $(this).attr('id').split("_")[1];
            var tempLimit = '#limitStatus_' + tempId;
            var tempSoon = '#comingSoon_' + tempId;
            if($(tempActive).is(':checked') == false && ($(tempLimit).is(':checked') || $(tempSoon).is(':checked') ) ){
                window.alert("Satışta olmayan ürün tükendi yada yakında olamaz.");
                return false;
            }
        });
        //$('html').click(function() {
        //    window.location='/admin/products';
        //});

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