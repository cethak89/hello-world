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
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <button id="testXls" class="btn btn-danger"  onClick ="$('tr').each(function() {$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(4)').remove();$(this).find('th:eq(5)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(4)').remove();$(this).find('td:eq(4)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Ürün Listesi',filename: 'Ürün Listesi'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body">
                    <table  style="width: 100%; font-size: 14px;" id="example1" data-width="100%" data-compression="6" data-min="1" data-max="14" cellpadding="0" cellspacing="0" class="table table-bordered table-striped responsive responsiveTable">
                        <thead>
                            <tr>
                                <th>Coming Soon</th>
                                <th>Limit</th>
                                <th>Durum</th>
                                <th>Ürün</th>
                                <th>Fiyat</th>
                                <th>Ekran Sırası</th>
                                <th>Satış Adeti</th>
                                <th>Fotoğraf</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                @if($id == $product->id )
                                    <tr id="row-id-{{$product->id}}">
                                    {!! Form::model($product, ['action' => 'AdminPanelController@store', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                                        <td data-sort="{{$product->coming_soon}}">
                                             <div class="form-group">
                                                <label>
                                                    <input name="test" type="checkbox" class="minimal" checked>
                                                </label>
                                             </div>
                                        </td>
                                        <td data-sort="{{$product->limit_statu}}">
                                            <div class="checkbox">
                                                <label>
                                                    {!! Form::checkbox('limit_statu', null, $product->limit_statu, ['style' => 'width:20px;height:20px;']) !!}
                                                </label>
                                            </div>
                                        </td>
                                        <td data-sort="{{$product->activation_status}}">
                                        {!! Form::checkbox('activation_status', null, $product->activation_status_id, ['style' => 'width:20px;height:20px;', 'id' => 'active_' . $product->id ]) !!}
                                            <div class="checkbox">
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
                                        <td style="padding-left:21px;"> <a href="{{ $product->mainImage }}" target="_blank">Image</a></td>
                                        <td>
                                            {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                                            <div class="form-group">
                                                {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control changeClass' , 'id' => 'changeProductDetail_' . $product->id ]) !!}
                                            </div>
                                        </td>
                                    {!! Form::close() !!}
                                    </tr>
                                @else
                                    {!! Form::open(['action' => 'AdminPanelController@delete', 'method' => 'DELETE' ]) !!}
                                    <tr id="row-id-{{$product->id}}" onclick="window.location='{{ action('AdminPanelController@show', [ $product->id ]) }}'">
                                        <td data-sort="{{$product->coming_soon}}">
                                            <div class="checkbox">
                                                <label>
                                                    {!! Form::checkbox('coming_soon', null, $product->coming_soon, ['style' => 'width:20px;height:20px;', 'disabled' => 'true']) !!}
                                                </label>
                                            </div>
                                        </td>
                                        <td data-sort="{{$product->limit_statu}}">
                                            <div class="checkbox">
                                                <label>
                                                    {!! Form::checkbox('limit_statu', null, $product->limit_statu, ['style' => 'width:20px;height:20px;', 'disabled' => 'true']) !!}
                                                </label>
                                            </div>
                                        </td>
                                        <td data-sort="{{$product->activation_status_id}}">
                                            <div class="checkbox">
                                                <label>
                                                    {!! Form::checkbox('activation_status', null, $product->activation_status_id, ['style' => 'width:20px;height:20px;', 'disabled' => 'true']) !!}
                                                </label>
                                            </div>
                                        </td>
                                        <td>{{$product->name}}</td>
                                        <td>{{$product->price}}</td>
                                        <td>{{$product->landing_page_order}}</td>
                                        <td>{{$product->saleCount}}</td>
                                        <!--<td style="padding-left:21px;">{{$product->description}}</td>-->
                                        <td><a href="{{ $product->mainImage  }}" target="_blank">Image</a></td>
                                        <td>
                                            {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                                            <div class="form-group">
                                                {!! Html::link('/admin/products/detail/' . $product->id , 'Detay', ['class' => 'btn btn-primary form-control', 'style' => 'width:100%; vertical-align: middle;']) !!}
                                                {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control test' , 'style' => 'width:100%;']) !!}
                                            </div>
                                        </td>
                                    </tr>
                                    {!! Form::close() !!}
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Coming Soon</th>
                                <th>Limit</th>
                                <th>Durum</th>
                                <th>Ürün</th>
                                <th>Fiyat</th>
                                <th>Ekran Sırası</th>
                                <th>Satış Adeti</th>
                                <th>Fotoğraf</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
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