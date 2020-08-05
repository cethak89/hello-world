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
                <h1>Bloom & Fresh Kurum Bazlı Ürün Listesi</h1>
            </td>
            <td>
            {!! Html::link('/admin/CompanyInfo/addProductCompanyPage' , 'Ürün-Kurum Bağlantısı Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
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
    <table id="filterTable" class="table table-hover">
        <tr>
            <td>
                Şirket :
            </td>
            <td>
                <div class="form-group">
                    <select id="companyId" onchange="filterProductByCompany($(this));" class="btn btn-default dropdown-toggle">
                        <option value="Hepsi">Hepsi</option>
                        @foreach($companyList as $tag)
                            <option value="{{$tag->id}}">{{$tag->name}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Kurumsal Ürünler :
            </td>
            <td>
                <div class="form-group">
                    <select id="productId" onchange="filterProductByProduct($(this));" name="company" class="btn btn-default dropdown-toggle">
                        <option value="Hepsi">Hepsi</option>
                        @foreach($productList as $tag)
                            <option value="{{$tag->id}}">{{$tag->name}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
    </table>
    <table id="example1" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <thead>
            <th>Yakında</th>
            <th style="max-width: 130px;">Tükendi</th>
            <th style="max-width: 130px;">Durum</th>
            <th>Ürün Adı</th>
            <th>Kurum</th>
            <th>Fiyat</th>
            <th>Ekran Sirasi</th>
            <th>Satış Adeti</th>
            <th>Fotoğraf</th>
            <th> </th>
        </thead>

        @foreach($products as $product)
            @if($id == $product->id )
                {!! Form::model($product, ['action' => 'AdminPanelController@storeCompanyProduct', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr id="row-id-{{$product->id}}">
                    <td data-order="{{$product->coming_soon}}">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('coming_soon', null, $product->coming_soon, ['style' => 'width:30px;height:30px;']) !!}
                            </label>
                        </div>
                    </td>
                    <td data-order="{{$product->limit_statu}}">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('limit_statu', null, $product->limit_statu, ['style' => 'width:30px;height:30px;']) !!}
                            </label>
                        </div>
                    </td>
                    <td data-order="{{$product->activation_status_id}}">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('activation_status', null, $product->activation_status_id, ['style' => 'width:30px;height:30px;', 'id' => 'active_' . $product->id ]) !!}
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
                        {{$product->companyName}}
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
                    <td>
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control changeClass' , 'id' => 'changeProductDetail_' . $product->id ]) !!}
                        </div>
                    </td>
                </tr>
                {!! Form::close() !!}
            @else
                {!! Form::open(['action' => 'AdminPanelController@deleteProductCompany', 'method' => 'DELETE' , 'id' => 'form_' . $product->id ]) !!}
                <tr class="c_{{$product->companies_info_id}} {{$product->companies_info_id}}{{$product->product_id_for_company}} p_{{$product->product_id_for_company}} all" id="row-id-{{$product->id}}">
                    <td data-order="{{$product->coming_soon}}">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('coming_soon', null, $product->coming_soon, ['style' => 'width:30px;height:30px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>
                    <td data-order="{{$product->limit_statu}}">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('limit_statu', null, $product->limit_statu, ['style' => 'width:30px;height:30px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>
                    <td data-order="{{$product->activation_status_id}}">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('activation_status', null, $product->activation_status_id, ['style' => 'width:30px;height:30px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>
                    <td style="padding-left:21px;">{{$product->name}}</td>
                    <td style="padding-left:21px;">{{$product->companyName}}</td>
                    <td style="padding-left:21px;">{{$product->price}}</td>
                    <td style="padding-left:21px;">{{$product->landing_page_order}}</td>
                    <td style="padding-left:21px;">{{$product->saleCount}}</td>
                    <!--<td style="padding-left:21px;">{{$product->description}}</td>-->
                    <td style="padding-left:21px;">
                        <div style="width: 20px;margin-left: auto;margin-right: auto;">
                            <a href="{{ $product->mainImage }}" target="_blank">
                                <img style="box-shadow: 0px 1px 2px black;width: 20px;" src="{{ $product->mainImage }}">
                            </a>
                        </div>
                    </td>
                    <td>
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            <a href="/admin/CompanyInfo/productRelatedCompanyDetail/{{$product->id}}">
                                <i style="font-size: 25px;" class="fa fa-fw fa-cog"></i>
                            </a>
                                <i onclick="var tempProductId = '#deletedProductId';$(tempProductId).val({{$product->id}});var tempId = '#deleteButton';$(tempId).click();" style="cursor: pointer;font-size: 25px;color:red" class="fa fa-fw fa-remove"></i>
                        </div>
                    </td>
                </tr>
                {!! Form::close() !!}
            @endif
        @endforeach
    </table>
    {!! Form::open(['action' => 'AdminPanelController@deleteProductCompany', 'method' => 'DELETE' , 'id' => 'formId']) !!}
        {!! Form::hidden('id', null , ['class' => 'form-control hidden', 'id' => 'deletedProductId']) !!}
        {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control hidden test' , 'style' => 'width:100%;', 'id' => 'deleteButton']) !!}
    {!! Form::close() !!}

@stop()

@section('footer')
    <script>

        function filterProductByProduct(temp){
            var tempStr = '.' + $(temp).val();
            console.log($(temp).val());
            if($(temp).val() == 'Hepsi'){
                //$('.all').removeClass('hidden');
                tempStr = '.c_';
            }
            else{
                $('.all').addClass('hidden');
            }
            $tempContinent = "";
            if($('#companyId').val() == 'Hepsi'){
                $tempContinent = "";
                if(tempStr == '.c_'){
                    tempStr = '.all';
                }
                else
                    tempStr = '.' + 'p_' + $(temp).val();
            }
            else{
                $tempContinent = $('#companyId').val();
            }
            if($(temp).val() != 'Hepsi' && $('#companyId').val() != 'Hepsi'){
                tempStr = '.' + $tempContinent + $(temp).val();
            }
            else
                tempStr = tempStr + $tempContinent;
            console.log(tempStr);
            $(tempStr).removeClass('hidden');
        }

        function filterProductByCompany(temp){
            var tempStr = '.' + $(temp).val();
            console.log($(temp).val());
            if($(temp).val() == 'Hepsi'){
                //$('.all').removeClass('hidden');
                tempStr = '.p_';
            }
            else{
                $('.all').addClass('hidden');
            }
            $tempContinent = "";
            if($('#productId').val() == 'Hepsi'){
                $tempContinent = "";
                if(tempStr == '.p_'){
                    tempStr = '.all';
                }
                else
                    tempStr = '.' + 'c_' + $(temp).val();
            }
            else{
                $tempContinent = $('#productId').val();
            }
            tempStr = tempStr + $tempContinent;
            console.log(tempStr);
            $(tempStr).removeClass('hidden');
        }

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