@extends('newApp')

@section('html-head')
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2.0, minimum-scale=1, user-scalable=yes"/>
    <style>
        .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th{
            vertical-align: middle;
        }
        div.form-group {
            height: 20px;
        }
    </style>
@stop
    <style>
        .formatDate {
            position: relative;
            width: 150px; height: 20px;
            color: white;
        }

        .formatDate:before {
            position: absolute;
            top: 3px; left: 3px;
            content: attr(data-date);
            display: inline-block;
            color: black;
        }

        .formatDate::-webkit-datetime-edit, input::-webkit-inner-spin-button, input::-webkit-clear-button {
            display: none;
        }

        .formatDate::-webkit-calendar-picker-indicator {
            position: absolute;
            top: 3px;
            right: 0;
            color: black;
            opacity: 1;
        }

        @media only screen and (max-width: 800px) {
            .widthXs{
                width: initial !important;
            }
        }

        @media only screen and (min-width: 800px) {
            .widthXs{
                width: 500px !important;
            }
        }

    </style>
@section('content')

    <table class="table table-hover">
    <tr>
        <td>
            <h1>Bloom & Fresh Sipariş Listesi</h1>
        </td>
        <td style="vertical-align: middle;width: 126px;">
            <button style="width: 175px;" class="btn btn-primary form-control" onclick="$('#filterTable').toggle();">Sorgu Alanları</button>
        </td>
        <td style="vertical-align: middle;width: 200px;">
            <button id="testXls" class="btn btn-danger pull-right"  onClick ="$('tr').each(function() {$(this).find('td:eq(0)').remove();$(this).find('th:eq(0)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Sipariş Listesi',filename: 'Sipariş Listesi'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>
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
    <table id="filterTable" class="table table-hover"  style="display: {{$filterShow}};border-bottom-color: rgb(60, 141, 188);border-bottom-style: groove;border-width: 2px;">
        {!! Form::model($queryParams, ['url' => '/admin/deliveries/filter', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            <tr>
                <td style="padding-left:21px;width: 167px;vertical-align: inherit;">İstek Tarihi</td>
                <td style="padding-left:21px;">
                    <input autocomplete="off" style="width: 90px;display: inline-block;" id="dateId" type="text" value="{{explode( " " ,$queryParams->created_at)[0]}}" class="form-control" name="created_at">
                    <input autocomplete="off" style="width: 90px;display: inline-block;" id="dateIdEnd" type="text" value="{{explode( " " ,$queryParams->created_at_end)[0]}}" class="form-control" name="created_at_end">
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;vertical-align: inherit;">Ürün Adı</td>
                <td style="padding-left:21px;">
                    {!! Form::input('text', 'products', $queryParams->products, ['class' => 'form-control', 'style' => 'width:184px;']) !!}
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;vertical-align: inherit;">İstenen Teslim Tarihi</td>
                <td style="padding-left:21px;">
                    <input autocomplete="off" style="width: 90px;display: inline-block;" id="dateId2" type="text" value="{{explode( " " ,$queryParams->wanted_delivery_date)[0]}}" class="form-control" name="wanted_delivery_date">
                    <input autocomplete="off" style="width: 90px;display: inline-block;" id="dateId2End" type="text" value="{{explode( " " ,$queryParams->wanted_delivery_date_end)[0]}}" class="form-control" name="wanted_delivery_date_end">
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;vertical-align: inherit;">Teslim Tarihi</td>
                <td style="padding-left:21px;">
                    <input autocomplete="off" style="width: 90px;display: inline-block;" id="dateIdDe2" type="text" value="{{explode( " " ,$queryParams->delivery_date)[0]}}" class="form-control" name="delivery_date">
                    <input autocomplete="off" style="width: 90px;display: inline-block;" id="dateIdDe2End" type="text" value="{{explode( " " ,$queryParams->delivery_date_end)[0]}}" class="form-control" name="delivery_date_end">
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;vertical-align: inherit;">Sipariş Durumu</td>
                <td style="padding-left:21px;">
                    <div class="form-group">
                        <div style="display: inline-flex;width: 99px;padding-left: 0px;padding-right: 5px;" id="allDiv" onclick="selectAllDiv(this)" class="col-lg-2">
                            <div class="input-group">
                                <span style="padding: 0px;" class="input-group-addon">
                                    <input style="width: 32px;height: 32px;" id="status_all" name="status_all" type="checkbox" aria-label="..."
                                    @if( $queryParams->status_all == 'on' ) checked @endif>
                                </span>
                                <label id="label_status_all" style="@if( $queryParams->status_all == 'on' )
                                                                                                    background-color: #B9C0B9;
                                                                                                    @endif"  class="form-control" aria-label="...">Hepsi</label>
                            </div><!-- /input-group -->
                        </div><!-- /.col-lg-6 -->
                        <div style="display: inline-flex;width: 141px;padding-left: 0px;padding-right: 5px;" id="makingDiv" onclick="selectMakingDiv(this)" class="col-lg-2">
                            <div class="input-group">
                                <span style="padding: 0px;" class="input-group-addon">
                                    <input style="width: 32px;height: 32px;" id="status_making" name="status_making" type="checkbox" aria-label="..."
                                    @if( $queryParams->status_making == 'on' )
                                    checked
                                    @endif
                                    >
                                </span>
                                <label id="label_status_making" style="@if( $queryParams->status_making == 'on' )
                                                                                                  background-color: #B9C0B9;
                                                                                                  @endif" class="form-control" aria-label="...">Hazırlanıyor</label>
                            </div><!-- /input-group -->
                        </div><!-- /.col-lg-6 -->
                        <div style="display: inline-flex;width: 97px;padding-left: 0px;padding-right: 5px;" id="readyDiv" onclick="selectReadyDiv(this)" class="col-lg-2">
                            <div class="input-group">
                                <span style="padding: 0px;" class="input-group-addon">
                                    <input style="width: 32px;height: 32px;" id="status_ready"  name="status_ready" type="checkbox" aria-label="..."
                                    @if( $queryParams->status_ready == 'on' )
                                    checked
                                    @endif
                                    >
                                </span>
                                <label id="label_status_ready" style="@if( $queryParams->status_ready == 'on' )
                                                                                                 background-color: #B9C0B9;
                                                                                                 @endif"  class="form-control" aria-label="...">Hazır</label>
                            </div><!-- /input-group -->
                        </div><!-- /.col-lg-6 -->
                        <div style="display: inline-flex;width: 129px;padding-left: 0px;padding-right: 5px;" id="deliveringDiv" onclick="selectDeliveringDiv(this)" class="col-lg-2">
                            <div class="input-group">
                                <span style="padding: 0px;" class="input-group-addon">
                                    <input style="width: 32px;height: 32px;" id="status_delivering" name="status_delivering" type="checkbox" aria-label="..."
                                    @if( $queryParams->status_delivering == 'on' )
                                    checked
                                    @endif
                                    >
                                </span>
                                <label id="label_status_delivering" style="@if( $queryParams->status_delivering == 'on' )
                                                                                                 background-color: #B9C0B9;
                                                                                                 @endif" class="form-control" aria-label="...">Teslimatta</label>
                            </div><!-- /input-group -->
                        </div><!-- /.col-lg-6 -->
                        <div style="display: inline-flex;width: 105px;padding-left: 0px;padding-right: 5px;" id="deliveredDiv" onclick="selectDeliveredDiv(this)" class="col-lg-2">
                            <div class="input-group">
                                <span style="padding: 0px;" class="input-group-addon">
                                    <input style="width: 32px;height: 32px;" id="status_delivered" name="status_delivered" type="checkbox" aria-label="..."
                                    @if( $queryParams->status_delivered == 'on' )
                                    checked
                                    @endif
                                    >
                                </span>
                                <label id="label_status_delivered" style="@if( $queryParams->status_delivered == 'on' )
                                                                                                 background-color: #B9C0B9;
                                                                                                 @endif" class="form-control" aria-label="...">Teslim</label>
                            </div><!-- /input-group -->
                        </div><!-- /.col-lg-6 -->
                        <div style="display: inline-flex;width: 93px;padding-left: 0px;padding-right: 5px;" id="cancelDiv" onclick="selectCancelDiv(this)" class="col-lg-2">
                            <div class="input-group">
                                <span style="padding: 0px;" class="input-group-addon">
                                    <input style="width: 32px;height: 32px;" id="status_cancel" name="status_cancel" type="checkbox" aria-label="..."
                                    @if( $queryParams->status_cancel == 'on' )
                                    checked
                                    @endif
                                    >
                                </span>
                                <label id="label_status_cancel" style="@if( $queryParams->status_cancel == 'on' )
                                                                                                  background-color: #B9C0B9;
                                                                                                  @endif" class="form-control" aria-label="...">İptal</label>
                            </div><!-- /input-group -->
                        </div><!-- /.col-lg-6 -->
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;vertical-align: inherit;">Gönderim Saati</td><td style="padding-left:21px;">
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="width: 100px;" name="deliveryHour" class="btn btn-default dropdown-toggle">
                            @foreach($deliveryHourList as $tag)
                                <option value="{{$tag->status}}"
                                 @if($tag->status == $queryParams->deliveryHour)
                                 selected
                                 @else
                                 @endif
                                 >{{$tag->information}}</option>
                            @endforeach
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;vertical-align: inherit;">Gönderim Bölgesi</td><td style="padding-left:21px;">
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="" name="continents[]" class="widthXs form-control select2"  data-placeholder="Kıta Seç" multiple>
                            @foreach($continentList as $tag)
                                <option value="{{$tag->status}}"
                                 @if($tag->selected)
                                 selected
                                 @else
                                 @endif
                                 >{{$tag->information}}</option>
                            @endforeach
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;vertical-align: inherit;">Gönderim İlçesi</td><td style="padding-left:21px;">
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="width: 100px;" name="small_city" class="btn btn-default dropdown-toggle">
                            @foreach($locationList as $tag)
                                <option value="{{$tag->small_city}}"
                                 @if($tag->small_city == $queryParams->small_city)
                                 selected
                                 @else
                                 @endif
                                 >{{$tag->small_city}}</option>
                            @endforeach
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;vertical-align: inherit;">Operasyon</td><td style="padding-left:21px;">
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="width: 100px;" name="operation_name" class="btn btn-default dropdown-toggle">
                            @foreach($operationList as $tag)
                                <option value="{{$tag->name}}"
                                 @if($tag->name == $queryParams->operation_name)
                                 selected
                                 @else
                                 @endif
                                 >{{$tag->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;vertical-align: inherit;">Atanan Operasyon</td>
                <td style="padding-left:21px;">
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="width: 140px;" name="planning_courier_id" class="btn btn-default dropdown-toggle">
                            <option @if( $queryParams->planning_courier_id == 'Hepsi' ) selected @endif value="Hepsi"> Hepsi </option>
                            <option @if( $queryParams->planning_courier_id == 0 and $queryParams->planning_courier_id != 'Hepsi' ) selected @endif value="0"> Atanmamışlar </option>
                            @foreach($operationList as $tag)
                                @if( $tag->name != 'Hepsi' )
                                    <option value="{{$tag->id}}"
                                            @if( $queryParams->planning_courier_id == $tag->name )
                                            selected
                                            @elseif( $tag->name != "Hepsi" )
                                            @if($tag->id == $queryParams->planning_courier_id)
                                            selected
                                    @else
                                            @endif
                                            @endif >{{$tag->name}}</option>
                                @endif
                            @endforeach
                           </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    {!! Form::submit('Sorgula', ['class' => 'btn btn-success form-control' , 'id' => 'submitId', 'style' => 'width: 120px;margin-left: 14px;margin-right: 10px;']) !!}
                    {!! Html::link('/admin/deliveries' , 'Temizle', ['class' => 'btn btn-primary form-control', 'style' => 'width: 126px;margin-right: 10px;']) !!}
                    <button style="width: 120px;margin-right: 10px;" onclick="makeToday()" class="btn btn-danger form-control">Bugün</button>
                    <button style="width: 120px;margin-right: 10px;" onclick="makeTomorrow()" class="btn btn-warning form-control">Yarın</button>
                </td>
            </tr>
        {!! Form::close() !!}
    </table>
    <label style="display: block;padding-left: 10px;">Sipariş Sayısı : {{$countDelivery}}</label>
    <button style="width: 130px;margin-bottom: 20px;margin-left: 10px;margin-top: 15px;" onclick="$('#myModal').modal('show');" class="btn btn-primary form-control">Teslimata Çıkar</button>
    <button style="width: 130px;margin-bottom: 20px;margin-left: 10px;margin-top: 15px;" onclick="$('#requestFormId').attr('action', '/print');$('#submitForm').click();" class="btn btn-warning form-control">Yazdır</button>
    <button style="width: 130px;margin-bottom: 20px;margin-top: 15px;background-color: darkred;border-color: darkred;margin-left: 10px;" onclick="$('#requestFormId').attr('action', '/cardPrintV2');$('#submitForm').click();" class="btn btn-warning form-control">Kart yazdır V2</button>
    <button style="width: 130px;margin-bottom: 20px;margin-top: 15px;margin-left: 10px;" onclick="$('#planningSales').modal('show');" class="btn btn-primary form-control">Operasyon Planla</button>

    <div style="overflow-x: scroll;">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;margin-bottom: 0px;">
        <tr>
            {!! Form::model($queryParams, ['name' => 'test1' , 'url' => '/admin/deliveries/orderAndFilterDesc/', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            {!! Form::hidden('created_at', $queryParams->created_at, ['class' => 'form-control']) !!}
            {!! Form::hidden('created_at_end', $queryParams->created_at_end, ['class' => 'form-control']) !!}
            {!! Form::hidden('products', $queryParams->products, ['class' => 'form-control']) !!}
            {!! Form::hidden('wanted_delivery_date', $queryParams->wanted_delivery_date, ['class' => 'form-control']) !!}
            {!! Form::hidden('wanted_delivery_date_end', $queryParams->wanted_delivery_date_end, ['class' => 'form-control']) !!}
            {!! Form::hidden('delivery_date', $queryParams->delivery_date, ['class' => 'form-control']) !!}
            {!! Form::hidden('delivery_date_end', $queryParams->delivery_date_end, ['class' => 'form-control']) !!}
            {!! Form::hidden('deliveryHour', $queryParams->deliveryHour, ['class' => 'form-control']) !!}

            <select style="width: 500px;" name="continents[]" class="form-control hidden "  data-placeholder="Kıta Seç" multiple>
                @foreach($continentList as $tag)
                    <option value="{{$tag->status}}"
                            @if($tag->selected)
                            selected
                    @else
                            @endif
                    >{{$tag->information}}</option>
                @endforeach
            </select>

            {!! Form::hidden('planning_courier_id', $queryParams->planning_courier_id, ['class' => 'form-control']) !!}
            {!! Form::hidden('operation_name', $queryParams->operation_name, ['class' => 'form-control']) !!}
            {!! Form::hidden('small_city', $queryParams->small_city, ['class' => 'form-control']) !!}
            {!! Form::hidden('status_all', $queryParams->status_all, ['class' => 'form-control']) !!}
            {!! Form::hidden('status_making', $queryParams->status_making, ['class' => 'form-control']) !!}
            {!! Form::hidden('status_ready', $queryParams->status_ready, ['class' => 'form-control']) !!}
            {!! Form::hidden('status_delivering', $queryParams->status_delivering, ['class' => 'form-control']) !!}
            {!! Form::hidden('status_delivered', $queryParams->status_delivered, ['class' => 'form-control']) !!}
            {!! Form::hidden('status_cancel', $queryParams->status_cancel, ['class' => 'form-control']) !!}
            {!! Form::hidden('orderParameter', null, ['class' => 'form-control' , 'name' => 'orderParameter']) !!}
            {!! Form::hidden('upOrDown', null, ['class' => 'form-control' , 'name' => 'upOrDown']) !!}
            @if($id == 0 || $id == null )
                <th style="text-align: center;">
                    Seç
                    <input style="width :30px;height:30px;display: block;margin-right: auto;margin-left: auto;" onclick="checkAll();" type="checkbox">
                </th>
                <th style="min-width: 90px"><span onmouseover="setOrderParameter('created_at' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> İstek Tarihi <span onmouseover="setOrderParameter('created_at' , 'up')" onclick="document.test1.submit();"   class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                <th>Id</th>
                <th><span onmouseover="setOrderParameter('district' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Bölge <span onmouseover="setOrderParameter('district' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                <th style="min-width: 280px">Adres</th>
                <th><span onmouseover="setOrderParameter('sender_name' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span>Gönderen<span onmouseover="setOrderParameter('sender_name' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span></th>
                <th><span onmouseover="setOrderParameter('contact_name' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span>Alıcı<span onmouseover="setOrderParameter('contact_name' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span></th>
                <th><span onmouseover="setOrderParameter('products' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Ürün Adı <span onmouseover="setOrderParameter('products' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                <th>Ekstra Ürün</th>
                <th style="min-width: 90px"><span onmouseover="setOrderParameter('wanted_delivery_date' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> İstenen Teslim Tarihi <span onmouseover="setOrderParameter('wanted_delivery_date' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                <th style="min-width: 90px"><span onmouseover="setOrderParameter('delivery_date' , 'down')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Teslim Tarihi <span  onmouseover="setOrderParameter('delivery_date' , 'up')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                <th><span onmouseover="setOrderParameter('status' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Siparişin Durumu <span onmouseover="setOrderParameter('status' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                <th>Teslim Alan</th>
                <th style="min-width: 90px"><span onmouseover="setOrderParameter('operation_name' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Operasyon <span onmouseover="setOrderParameter('operation_name' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                <th style="min-width: 90px"><span onmouseover="setOrderParameter('planning_courier_id' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Atanan Operasyon<span onmouseover="setOrderParameter('planning_courier_id' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span></th>
                <th></th>
            @else
                <th>İstek Tarihi</th>
                <th>Id</th>
                <th>Bölge</th>
                <th>Adres</th>
                <th>Alıcı</th>
                <th>Teslim Tarihi</th>
                <th>Siparişin Durumu</th>
                <th>Teslim Alan</th>
                <th>Operasyon</th>
                <th>Atanan Operasyon</th>
                <th></th>
            @endif
            {!! Form::close() !!}
        </tr>
            {!! Form::model($queryParams, ['url' => '/print', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post', 'id' => 'requestFormId']) !!}
            @foreach($deliveryList as $delivery)
                <input style="width :30px;height:30px;" class="checkSaleHidden hidden" id="tempHidden_{{$delivery->id}}" name="selected_{{$delivery->sale_id}}" type="checkbox">
                <tr style="@if($delivery->studio)
                    background-color: pink;
                @else
                    @if($delivery->continent_id == "Avrupa")
                        background-color: #9CDA9B;
                    @elseif($delivery->continent_id == "Asya")
                        background-color: #BDB86F;
                    @elseif($delivery->continent_id == "Asya-2")
                        background-color: #f39c12;
                    @elseif($delivery->continent_id == "Avrupa-3")
                        background-color: #00904e;
                    @else
                        background-color: #8DBDE6;
                    @endif
                @endif" id="tr_{{$delivery->id}}" data-toggle="modal" data-target="#myModal">
                <!-- <tr id="row-id-{{$delivery->id}}"  data-toggle="modal" data-target="#myModal" onclick="window.location='{{ action('AdminPanelController@showDeliveries', [ $delivery->id ]) }}'"> -->
                    <td  style="text-align: center;">
                        <input style="width :30px;height:30px;" class="checkSale" id="temp_{{$delivery->id}}" onchange="changeHiddenBox(this);" type="checkbox">
                    </td>
                    <td>{{$delivery->requestDate}}</td>
                    <td id="tdId_{{$delivery->sale_id}}">
                    {{$delivery->sale_id}}
                    @if($delivery->delivery_not)
                    <a style="cursor: pointer;" title="Not Görüntüle" class="glyphicon glyphicon-info-sign runJs col-md-1 col-lg-1" id="row-id-{{$delivery->sale_id}}"  onclick="openShowModel(this)"></a>
                    @endif
                    @if($delivery->isPrintedDelivery)
                        <i class="fa fa-fw fa-car"></i>
                    @endif
                    @if($delivery->isPrintedNote)
                        <i class="fa fa-fw fa-envelope"></i>
                    @endif
                    </td>
                    <td>{{$delivery->district}}</td>
                    <td>{{$delivery->receiver_address}}</td>
                    <td>@if($delivery->prime > 0)<i class="fa fa-fw fa-star" style="color: #FF007D;"></i>@endif{{$delivery->customer_name}} {{$delivery->customer_surname}}@if($delivery->prime > 0)<i class="fa fa-fw fa-star" style="color: #FF007D;"></i>@endif</td>
                    <td>{{$delivery->contact_name}} {{$delivery->contact_surname}}</td>
                    <td>{{$delivery->product_name}}</td>
                    <td>{{$delivery->cikilot}}</td>
                    <td id="wantedDate{{$delivery->id}}">{{$delivery->wantedDeliveryDate}}</td>
                    <td id="dateIdTr{{$delivery->id}}">{{$delivery->deliveryDate}}</td>
                    <td id="statusIdTr{{$delivery->id}}">
                    @if( $delivery->status == 1 )
                        @if( count($delivery->scottyInfo) > 0 )
                            Scotty Bekleniyor.
                        @else
                            Çiçek hazırlanıyor.
                        @endif
                    @elseif($delivery->status == 2)
                        Teslimat aşamasında.
                    @elseif($delivery->status == 3)
                        Teslim edildi.
                    @elseif($delivery->status == 4)
                        İptal edildi.
                    @elseif($delivery->status == 6)
                            @if( count($delivery->scottyInfo) > 0 )
                                Scotty Bekleniyor.
                            @else
                                Çiçek hazır.
                            @endif
                    @endif
                    </td>
                    <td>{{$delivery->picker}}</td>
                    <td>{{$delivery->operation_name}}</td>
                    <td>
                        @foreach($operationList as $tag)
                            @if($tag->name != 'Hepsi' )
                                @if( $delivery->planning_courier_id == $tag->id )
                                    {{$tag->name}}
                                @endif
                            @endif
                        @endforeach
                    </td>
                    <td>
                        {!! Form::hidden('id', $delivery->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            @if($delivery->studio)
                                {!! Html::link('/admin/studioBloom/updateDetail/' . $delivery->id , '', [ 'target' => '_blank', 'class' => 'glyphicon glyphicon glyphicon-file col-md-1 col-lg-1' , 'title' => 'Detay Bilgiler']) !!}
                            @else
                                {!! Html::link('/admin/deliveries/detail/' . $delivery->id , '', [ 'target' => '_blank',  'class' => 'glyphicon glyphicon glyphicon-file col-md-1 col-lg-1' , 'title' => 'Detay Bilgiler']) !!}
                            @endif
                            @if(  Auth::user()->user_group_id == 1  )
                            <a style="cursor: pointer;" title="Durumu Güncelle" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="row-id-{{$delivery->id}}"  onclick="openModel(this)"></a>
                            <a style="cursor: pointer;" title="Not Ekle" class="
                            @if($delivery->delivery_not)
                            runJs
                            @endif glyphicon glyphicon-paperclip col-md-1 col-lg-1" id="row-id-{{$delivery->sale_id}}"  onclick="openAddModel(this)"></a>
                            @endif
                        </div>
                    </td>
                    <div class="modal fade" id="myModal{{$delivery->id}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="myModalLabel"><u style="color: red">{{$delivery->customer_name}} {{$delivery->customer_surname}}</u> tarafından <u style="color: red">{{$delivery->district}}</u> bölgesine  yollanan <u style="color: red">{{$delivery->products}}</u> siparişini değiştiriyorsunuz!</h4>
                                </div>
                                <div class="modal-body">
                                    <p style="font-size: 18px;" class="col-lg-6 col-md-6 col-sm-6 col-xs-6">Teslimat Durumu</p>
                                    <select style="width: 210px;font-size: 18px;" class="col-lg-6 col-md-6 col-sm-6 col-xs-6" id="selectE{{$delivery->id}}" name="status" class="btn btn-default dropdown-toggle">
                                        @foreach($myArray as $tag)
                                            @if($tag->information != 'Hepsi')
                                            <option value="{{$tag->status}}"
                                                @if($tag->status == $delivery->status)
                                                    selected
                                                @else
                                                @endif>
                                                    {{$tag->information}}
                                            </option>
                                            @else
                                            @endif
                                        @endforeach
                                    </select>
                                    <br>
                                    <br>
                                    <p style="font-size: 18px;margin-top: 2px;" class="col-lg-6 col-md-6 col-sm-6 col-xs-6">Teslim alan kişi</p>
                                    <input id="picker{{$delivery->id}}" value="{{$delivery->picker}}" class="form-control col-lg-6 col-md-6 col-sm-6 col-xs-6" style="width: 210px;font-size: 18px;margin-bottom: 13px;">
                                    <br>
                                    <br>
                                    <p style="font-size: 18px;margin-top: 4px;" class="col-lg-6 col-md-6 col-sm-6 col-xs-6">Tarih</p>
                                        <input style="height: 40px;font-size: 18px;text-align: center;width: 100px;" class="deliveryDateModal" id="dateId{{$delivery->id}}" type="text" name="delivery_date">
                                        <input class="hourDeliveryTime" id="dateHour{{$delivery->id}}" style="width: 25px;height: 40px;padding-top: 1px;margin-left: 20px;text-align: center;font-size: 18px;" type="text" maxlength="2"  name="delivery_date_hour">
                                    :
                                        <input class="minuteDeliveryTime"id="dateMin{{$delivery->id}}" style="width: 25px;height: 40px;padding-top: 1px;text-align: center;font-size: 18px;" type="text" maxlength="2"   name="delivery_date_minute">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                        <button id="update-{{$delivery->id}}" class="btn btn-success form-control" onclick="
                                        @if($delivery->studio)
                                        updateDeliveryStudio(this);
                                        @else
                                        updateDelivery(this);
                                        @endif
                                        " data-dismiss="modal" >Güncelle</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="addDeliveryNote{{$delivery->sale_id}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="myModalLabel"><u style="color: red">{{$delivery->customer_name}} {{$delivery->customer_surname}}</u> tarafından <u style="color: red">{{$delivery->district}}</u> bölgesine  yollanan <u style="color: red">{{$delivery->products}}</u> siparişine not ekliyorsunuz!</h4>
                                </div>
                                <div style="margin-bottom: 20px;" class="modal-body">
                                    <p class="col-lg-3 col-md-3 col-sm-3 col-xs-3">Sipariş Notunuz</p>
                                    <input value="{{$delivery->delivery_not}}"  class="col-lg-9 col-md-9 col-sm-9 col-xs-9" type="text" id="deliveryNote{{$delivery->sale_id}}">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                                    <button style="margin-left: 0;" class="btn btn-success col-lg-6 col-md-6 col-sm-6 col-xs-6" id="deliveryButton-{{$delivery->sale_id}}" type="button btn-success" data-dismiss="modal" onclick="
                                    @if($delivery->studio)
                                        setStudioDeliveryNote(this);
                                    @else
                                        setDeliveryNote(this);
                                    @endif">Kaydet</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="addDeliveryNoteShow{{$delivery->sale_id}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="myModalLabel"><u style="color: red">{{$delivery->customer_name}} {{$delivery->customer_surname}}</u> tarafından <u style="color: red">{{$delivery->district}}</u> bölgesine  yollanan <u style="color: red">{{$delivery->products}}</u> siparişin notunu görüntülüyorsunuz.</h4>
                                </div>
                                <div style="margin-bottom: 20px;" class="modal-body">
                                    <p id="deliveryNoteLabel{{$delivery->sale_id}}" style="font-size: x-large;" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">{{$delivery->delivery_not}}</p>
                                </div>
                                <div style="    border: none;" class="modal-footer">
                                    <button style="margin-left: 0;" class="btn btn-success col-lg-12 col-md-12 col-sm-12 col-xs-12" type="button btn-success" data-dismiss="modal">Tamam</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </tr>
            @endforeach
            @foreach($operationList as $tag)
                @if($tag->name != 'Hepsi')
                <input id="status2_{{$tag->id}}" name="status2_{{$tag->id}}" onclick="selectDiv2(this)" class="allCheckBox hidden" type="checkbox" aria-label="...">
                @endif
            @endforeach
            <input class="hidden" name="plannig_courier" id="plannig_courier_id" >
            {!! Form::submit('Yazdırma Sayfasına Git', ['class' => 'btn btn-success form-control hidden' , 'id' => 'submitForm', 'target' => '_blank' ]) !!}
            {!! Form::close() !!}
        </table>
    </div>
    <button style="width: 119px;margin-bottom: 20px;margin-left: 10px;margin-top: 15px;" onclick="$('#myModal').modal('show');" class="btn btn-primary form-control">Teslimata Çıkar</button>
    <button style="width: 169px;margin-bottom: 20px;margin-top: 15px;" onclick="$('#requestFormId').attr('action', '/print');$('#submitForm').click();" class="btn btn-warning form-control">Yazdır</button>
<audio id="audiotag1" src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/bloomandfresh.com/music/music3.wav" preload="auto"></audio>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div style="min-height: 270px;width: 700px;" class="modal-content">
                <div class="modal-body">
                    <table id="products-table" class="table table-hover table-bordered" style="margin-bottom: 0px;vertical-align: middle;">
                        <tr>
                            <th>Gönderen</th>
                            <th>Ürün</th>
                            <th>Saat</th>
                            <th>Kıta</th>
                            <th>Adres</th>
                            <th>Semt</th>
                        </tr>
                        @foreach($deliveryList as $delivery)
                            <tr id="modal_tr_{{$delivery->id}}" class="hidden" style="
                            @if($delivery->studio)
                            background-color: pink;
                            @else
                            @if($delivery->continent_id == "Avrupa") background-color: #E6EBF3; @else background-color: #E9F5E7; @endif
                            @endif
                            ">
                                <td style="padding-left:21px;">{{$delivery->customer_name}} {{$delivery->customer_surname}}</td>
                                <td style="padding-left:21px;">{{$delivery->products}}</td>
                                <td style="padding-left:21px;">{{$delivery->wantedDeliveryDate}}</td>
                                <td style="padding-left:21px;">{{$delivery->continent_id}}</td>
                                <td style="padding-left:21px;">{{$delivery->receiver_address}}</td>
                                <td style="padding-left:21px;">{{$delivery->district}}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div class="modal-footer">
                    <div class="col-lg-12">
                        @foreach($operationList as $tag)
                            @if($tag->name != 'Hepsi')
                                <div class="col-lg-2 col-md-2 col-sm-4 col-xs-6">
                                    <span style="padding-top: 0px;padding-bottom: 0px;" class="input-group-addon">
                                        <input style="width: 25px;height: 25px;" id="status_{{$tag->id}}" name="status_{{$tag->id}}" onclick="selectDiv2(this)" class="allCheckBox" type="checkbox" aria-label="...">
                                    </span>
                                    <label style="text-align: center;height: 47px;padding-left: 0px;" id="label_status_{{$tag->name}}" onclick="selectDiv(this)" class="form-control" aria-label="...">
                                        {{$tag->name}}
                                    </label>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" class="modal-footer">
                        <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <button disabled class="btn btn-success form-control" id="testD" onclick="$('#requestFormId').attr('action', '/admin/delivery-on-way-from');$('#submitForm').click();" data-dismiss="modal" >Teslimata Çıkar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="modal fade" id="planningSales" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" style="margin-bottom: 200px;" role="document">
            <div style="min-height: 270px;width: 700px;" class="modal-content">
                <div class="modal-body">
                    <table id="products-table" class="table table-hover table-bordered" style="margin-bottom: 0px;vertical-align: middle;">
                        <tr>
                            <th>Gönderen</th>
                            <th>Ürün</th>
                            <th>Saat</th>
                            <th>Kıta</th>
                            <th>Adres</th>
                            <th>Semt</th>
                        </tr>
                        @foreach($deliveryList as $delivery)
                            <tr id="modal_planning_tr_{{$delivery->id}}" class="hidden" style="
                            @if($delivery->studio)
                                    background-color: pink;
                            @else
                            @if($delivery->continent_id == "Avrupa") background-color: #E6EBF3; @else background-color: #E9F5E7; @endif
                            @endif
                                    ">
                                <td style="padding-left:21px;">{{$delivery->customer_name}} {{$delivery->customer_surname}}</td>
                                <td style="padding-left:21px;">{{$delivery->products}}</td>
                                <td style="padding-left:21px;">{{$delivery->wantedDeliveryDate}}</td>
                                <td style="padding-left:21px;">{{$delivery->continent_id}}</td>
                                <td style="padding-left:21px;">{{$delivery->receiver_address}}</td>
                                <td style="padding-left:21px;">{{$delivery->district}}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div class="modal-footer">
                    <div style="text-align: center;margin-bottom: 20px;" class="col-lg-12">
                        <label style="font-size: 18px;vertical-align: sub;margin-right: 6px;">Operasyon :</label>
                        <select style="width: 200px;text-align: center;font-size: 18px;" name="deliveryHour" class="form-control select2" onchange="changeNameValue(value);">
                            <option value="0">Atanmamış</option>
                            @foreach($operationList as $tag)
                                @if($tag->name != 'Hepsi')
                                    <option value="{{$tag->id}}">{{$tag->name}}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" class="modal-footer">
                        <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                            <button class="btn btn-success form-control" id="saveButtonDisable" onclick="updatePlanningOperations();" data-dismiss="modal" >Kaydet</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop()

@section('footer')
    <script>

        function changeNameValue(value) {

            $('#plannig_courier_id').val(value);

        }

        function updatePlanningOperations() {

            $('#saveButtonDisable').attr('disabled', 'disabled');

            var tempSalesId = [];

            $(".checkSaleHidden").each(function( index ) {
                if( this.checked ){
                    tempSalesId.push($(this).attr('name').split("_")[1])
                }
            });

            //$('#requestFormId').attr('action', '/admin/update-planning-courier');$('#submitForm').click();

            console.log(tempSalesId);
            console.log($('#plannig_courier_id').val());

            var tempCourId = $('#plannig_courier_id').val();

            if(tempCourId == ''){
                tempCourId = 0;
            }

            $.ajax({
                url: '/admin/update-planning-cour-ajax',
                method: "POST",
                data: { saleIds : tempSalesId , courId : tempCourId },
                success: function(data) {
                    location.reload();
                }
            });

        }

        function changeHiddenBox(event) {

            if( $(event).prop('checked' ) ){

                var tempIdCheck = '#tempHidden_';
                tempIdCheck = tempIdCheck + $(event).attr('id').split("_")[1];
                $(tempIdCheck).prop('checked', true);
                //console.log('checked');
            }
            else{
                var tempIdCheck = '#tempHidden_';
                tempIdCheck = tempIdCheck + $(event).attr('id').split("_")[1];
                $(tempIdCheck).prop('checked', false);
                //console.log('unChecked');
            }
            //console.log(event);
        }

        var checked = false;
        function checkAll() {
            if( checked == false){
                $('.checkSale').prop('checked', true);
                $('.checkSaleHidden').prop('checked', true);
                checked = true;
            }
            else{
                $('.checkSale').prop('checked', false);
                $('.checkSaleHidden').prop('checked', false);
                checked = false;
            }

            $(".checkSale").each(function( index ) {
                var tempID = $(this).attr('id').split("_")[1];
                var tempID = '#modal_tr_' +  tempID;
                if(this.checked) {
                    $(tempID).removeClass('hidden');
                }
                else{
                    $(tempID).addClass('hidden');
                }

                var tempOperationID = '#modal_planning_tr_' +  $(this).attr('id').split("_")[1];
                if(this.checked) {
                    $(tempOperationID).removeClass('hidden');
                }
                else{
                    $(tempOperationID).addClass('hidden');
                }

            });
        }

        function selectDiv(event){
            var tempID = $(event).attr('id').split("_")[2];
            var tempSelector = "#status_" + tempID;
            var tempSelector2 = "#status2_" + tempID;
            if($(tempSelector).prop("checked")){
                $(tempSelector).prop("checked", false);
                $(tempSelector2).prop("checked", false);
                $('#testD').attr('disabled' , true);
            }
            else{
                $('.allCheckBox').prop("checked", false);
                $(tempSelector).prop("checked", true);
                $(tempSelector2).prop("checked", true);
                $('#testD').attr('disabled' , false);
            }
        }

        function selectDiv2(event){
            var tempID = $(event).attr('id').split("_")[1];
            var tempSelector = "#status_" + tempID;
            var tempSelector2 = "#status2_" + tempID;
            if($(tempSelector).prop("checked")){
                $('.allCheckBox').prop("checked", false);
                $(tempSelector).prop("checked", true);
                $(tempSelector2).prop("checked", true);
                $('#testD').attr('disabled' , false);
            }
            else{
                $(tempSelector2).prop("checked", false);
                $('#testD').attr('disabled' , true);
                //$(tempSelector).prop("checked", true);
            }
        }

        $(".checkSale").change(function() {
            console.log(this);
            var tempID = $(this).attr('id').split("_")[1];
            var tempID = '#modal_tr_' +  tempID;


            if(this.checked) {
                console.log($(tempID));
                $(tempID).removeClass('hidden');
            }
            else{
                $(tempID).addClass('hidden');
            }

            var tempOperationID = '#modal_planning_tr_' +  $(this).attr('id').split("_")[1];
            if(this.checked) {
                $(tempOperationID).removeClass('hidden');
            }
            else{
                $(tempOperationID).addClass('hidden');
            }

        });

        $('#dateId').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
        $('#dateIdEnd').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
        $('#dateId2').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
        $('.deliveryDateModal').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
        $('#dateId2End').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
        $('#dateIdDe2').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
        $('#dateIdDe2End').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });

        var tempColorBool = true;
        setInterval(function(){
            if(tempColorBool)
            $('.runJs').css('color', 'red');
            else
            $('.runJs').css('color', 'blue');
            tempColorBool=!tempColorBool;
            //console.log($('.runJs'));
        }, 300);

        function selectAllDiv(event){
            console.log($("#status_all").prop('checked'));
            if($("#label_status_all").css('background-color') == 'rgb(185, 192, 185)') {
                $('#status_all').prop("checked", true);
            }
            else {
                $('#label_status_all').css('background-color' , '#B9C0B9');
                $('#status_all').prop("checked", true);
                $('#status_making').attr('checked', false);
                $('#status_ready').attr('checked', false);
                $('#status_delivering').attr('checked', false);
                $('#status_delivered').attr('checked', false);
                $('#status_cancel').attr('checked', false);
                $('#scotty_waiting').attr('checked', false);

                $('#label_scotty_waiting').css('background-color', '');
                $('#label_status_making').css('background-color', '');
                $('#label_status_ready').css('background-color', '');
                $('#label_status_delivering').css('background-color', '');
                $('#label_status_delivered').css('background-color', '');
                $('#label_status_cancel').css('background-color', '');
            }
        }

        function selectMakingDiv(event){
            console.log($("#label_status_making").css('background-color') == 'rgb(185, 192, 185)');
            if($("#label_status_making").css('background-color') == 'rgb(185, 192, 185)') {
                $("#status_making").prop('checked' , false);
                $('#label_status_making').css('background-color' , '');
                if( !$("#status_cancel").prop('checked') && !$("#status_ready").prop('checked') && !$("#status_delivering").prop('checked') && !$("#status_delivered").prop('checked') ){
                    $('#status_all').prop("checked", true);
                    $('#label_status_all').css('background-color' , '#B9C0B9');
                }
            }
            else {
                $('#label_status_making').css('background-color' , '#B9C0B9');
                $('#status_all').prop("checked", false);
                $('#status_making').prop("checked", true);
                $('#label_status_all').css('background-color' , '');
            }
        }

        function selectReadyDiv(event){
            console.log($("#label_status_ready").css('background-color') == 'rgb(185, 192, 185)');
            if($("#label_status_ready").css('background-color') == 'rgb(185, 192, 185)') {
                $("#status_ready").prop('checked' , false);
                $('#label_status_ready').css('background-color' , '');
                if( !$("#status_cancel").prop('checked') && !$("#status_making").prop('checked') && !$("#status_delivering").prop('checked') && !$("#status_delivered").prop('checked') ){
                    $('#status_all').prop("checked", true);
                    $('#label_status_all').css('background-color' , '#B9C0B9');
                }
            }
            else {
                $('#label_status_ready').css('background-color' , '#B9C0B9');
                $('#status_all').prop("checked", false);
                $('#status_ready').prop("checked", true);
                $('#label_status_all').css('background-color' , '');
            }
        }

        function selectScottyDiv(event){
            console.log($("#label_scotty_waiting").css('background-color') == 'rgb(185, 192, 185)');
            if($("#label_scotty_waiting").css('background-color') == 'rgb(185, 192, 185)') {
                $("#scotty_waiting").prop('checked' , false);
                $('#label_scotty_waiting').css('background-color' , '');
                if( !$("#status_cancel").prop('checked') && !$("#status_making").prop('checked') && !$("#status_delivering").prop('checked') && !$("#status_delivered").prop('checked') ){
                    $('#status_all').prop("checked", true);
                    $('#label_status_all').css('background-color' , '#B9C0B9');
                }
            }
            else {
                $('#label_scotty_waiting').css('background-color' , '#B9C0B9');
                $('#status_all').prop("checked", false);
                $('#scotty_waiting').prop("checked", true);
                $('#label_status_all').css('background-color' , '');
            }
        }

        function selectDeliveringDiv(event){
            console.log($("#label_status_delivering").css('background-color') == 'rgb(185, 192, 185)');
            if($("#label_status_delivering").css('background-color') == 'rgb(185, 192, 185)') {
                $("#status_delivering").prop('checked' , false);
                $('#label_status_delivering').css('background-color' , '');
                if( !$("#status_cancel").prop('checked') && !$("#status_making").prop('checked') && !$("#status_ready").prop('checked') && !$("#status_delivered").prop('checked') ){
                    $('#status_all').prop("checked", true);
                    $('#label_status_all').css('background-color' , '#B9C0B9');
                }
            }
            else {
                $('#label_status_delivering').css('background-color' , '#B9C0B9');
                $('#status_all').prop("checked", false);
                $('#status_delivering').prop("checked", true);
                $('#label_status_all').css('background-color' , '');
            }
        }

        function selectDeliveredDiv(event){
            console.log($("#label_status_delivered").css('background-color') == 'rgb(185, 192, 185)');
            if($("#label_status_delivered").css('background-color') == 'rgb(185, 192, 185)') {
                $("#status_delivered").prop('checked' , false);
                $('#label_status_delivered').css('background-color' , '');
                if( !$("#status_cancel").prop('checked') && !$("#status_making").prop('checked') && !$("#status_ready").prop('checked') && !$("#status_delivering").prop('checked') ){
                    $('#status_all').prop("checked", true);
                    $('#label_status_all').css('background-color' , '#B9C0B9');
                }
            }
            else {
                $('#label_status_delivered').css('background-color' , '#B9C0B9');
                $('#status_all').prop("checked", false);
                $('#status_delivered').prop("checked", true);
                $('#label_status_all').css('background-color' , '');
            }
        }

        function selectCancelDiv(event){
            console.log($("#label_status_cancel").css('background-color') == 'rgb(185, 192, 185)');
            if($("#label_status_cancel").css('background-color') == 'rgb(185, 192, 185)') {
                $("#status_cancel").prop('checked' , false);
                $('#label_status_cancel').css('background-color' , '');
                if( !$("#status_delivered").prop('checked') && !$("#status_making").prop('checked') && !$("#status_ready").prop('checked') && !$("#status_delivering").prop('checked') ){
                    $('#status_all').prop("checked", true);
                    $('#label_status_all').css('background-color' , '#B9C0B9');
                }
            }
            else {
                $('#label_status_cancel').css('background-color' , '#B9C0B9');
                $('#status_all').prop("checked", false);
                $('#status_cancel').prop("checked", true);
                $('#label_status_all').css('background-color' , '');
            }
        }

        $("#status_ready").change(function() {
            if(this.checked) {
                $('#status_all').attr('checked', false);
            }
            else{
                if( !$("#status_cancel").prop('checked') && !$("#status_making").prop('checked') && !$("#status_delivering").prop('checked') && !$("#status_delivered").prop('checked') ){
                    $('#status_all').prop("checked", true);
                }
            }
        });

        $("#status_delivering").change(function() {
            if(this.checked) {
                $('#status_all').attr('checked', false);
            }
            else{
                if( !$("#status_cancel").prop('checked') && !$("#status_making").prop('checked') && !$("#status_ready").prop('checked') && !$("#status_delivered").prop('checked') ){
                    $('#status_all').prop("checked", true);
                }
            }
        });

        $("#status_delivered").change(function() {
            if(this.checked) {
                $('#status_all').attr('checked', false);
            }
            else{
                if( !$("#status_cancel").prop('checked') && !$("#status_making").prop('checked') && !$("#status_ready").prop('checked') && !$("#status_delivering").prop('checked') ){
                    $('#status_all').prop("checked", true);
                }
            }
        });

        $("#status_cancel").change(function() {
            if(this.checked) {
                $('#status_all').attr('checked', false);
            }
            else{
                if( !$("#status_delivered").prop('checked') && !$("#status_making").prop('checked') && !$("#status_ready").prop('checked') && !$("#status_delivering").prop('checked') ){
                    $('#status_all').prop("checked", true);
                }
            }
        });

        $( document ).ready(function() {

            if($('#wddId').attr('data-date') == 'Invalid date')
                $('#wddId').attr('data-date' , '');

            if($('#wddeId').attr('data-date') == 'Invalid date')
                $('#wddeId').attr('data-date' , '');

            if($('#wddId2').attr('data-date') == 'Invalid date')
                $('#wddId2').attr('data-date' , '');

            if($('#wddeId2').attr('data-date') == 'Invalid date')
                $('#wddeId2').attr('data-date' , '');

            if($('#wddId3').attr('data-date') == 'Invalid date')
                $('#wddId3').attr('data-date' , '');

            if($('#wddeId3').attr('data-date') == 'Invalid date')
                $('#wddeId3').attr('data-date' , '');


            var nowCurrentDate = new Date(new Date().getTime());
            var nowDay = nowCurrentDate.getDate();
            var nowMonth = nowCurrentDate.getMonth() + 1;
            var nowYear = nowCurrentDate.getFullYear();

            //console.log(nowCurrentDate.getMinutes());

            nowMonth = (nowMonth < 10) ? ("0" + nowMonth) : nowMonth;
            nowDay = (nowDay < 10) ? ("0" + nowDay) : nowDay;
            $('.deliveryDateModal').val(nowYear + '-' + nowMonth + '-' + nowDay);
            $('.hourDeliveryTime').val(nowCurrentDate.getHours());
            $('.minuteDeliveryTime').val( ('0'+ nowCurrentDate.getMinutes()).slice(-2) );

        });

            $(".formatDate").on("change", function() {
                this.setAttribute(
                    "data-date",
                    moment(this.value, "YYYY-MM-DD")
                    .format( this.getAttribute("data-date-format") )
                )
            }).trigger("change");
        (function worker() {
            $.ajax({
                url: '/admin/check-new-sales',
                success: function(data) {
                console.log(data);
                    if(data.new > 0){
                        var soundOn = getCookie('sound_on');
                        console.log(soundOn);
                        if(soundOn == 'true'){
                            setCookie('sound_on' , false , 1);
                            document.getElementById('audiotag1').play();
                            //responsiveVoice.speak(data.readingText , "Turkish Female");
                            //setTimeout(function() { alert('hello world'); }, 5);
                            //alert('YENİ SİPARİŞ');
                            //var person = prompt("YENİ SİPARİŞ");
                            //document.getElementById('audiotag1').play();,
                            setTimeout(function() {
                                alert('YENİ SİPARİŞ');
                                //window.location.replace("https://everybloom.com/admin/deliveries/today");
                            }, 8000);

                        }
                        else{

                        }
                        document.cookie="username=John Doe";
                    }
                    else{
                        setCookie('sound_on' , true , 1);
                    }
                },
                complete: function() {
                  // Schedule the next request when the current one's complete
                  setTimeout(worker, 30000);
                }
            });
        })();

        function setCookie(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
            var expires = "expires="+d.toUTCString();
            document.cookie = cname + "=" + cvalue + "; " + expires;
        }

        function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i<ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1);
                if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
            }
            return "";
        }

        function setStudioDeliveryNote(event){
            var tempId = $(event).attr('id').split("-")[1];
            var deliveryNoteInputId = '#deliveryNote' + tempId;
            var deliveryNote = $(deliveryNoteInputId).val();
            $.ajax({
                url: '/admin/set-studio-delivery-note',
                method: "POST",
                data: { note : deliveryNote , id : tempId },
                success: function(data) {
                var tempStr = "#tdId_" + data.data.id ;
                var tempStrLast = "#row-id-" + data.data.id ;
                var tempStrLabelId = '#deliveryNoteLabel' + data.data.id ;
                var tempStrInputId = '#deliveryNote' + data.data.id ;
                $(tempStrLabelId).text(data.data.note);
                $(tempStrInputId).val(data.data.note);
                $(tempStrLast).addClass('runJs');
                $(tempStr).append('<a title="Not Görüntüle" class="glyphicon glyphicon-info-sign runJs col-md-1 col-lg-1" id="row-id-' + data.data.id  + '"  onclick="openShowModel(this)"></a>');
                }
            });
        }

        function setDeliveryNote(event){
            var tempId = $(event).attr('id').split("-")[1];
            var deliveryNoteInputId = '#deliveryNote' + tempId;
            var deliveryNote = $(deliveryNoteInputId).val();
            $.ajax({
                url: '/admin/set-delivery-note',
                method: "POST",
                data: { note : deliveryNote , id : tempId },
                success: function(data) {
                var tempStr = "#tdId_" + data.data.id ;
                var tempStrLast = "#row-id-" + data.data.id ;
                var tempStrLabelId = '#deliveryNoteLabel' + data.data.id ;
                var tempStrInputId = '#deliveryNote' + data.data.id ;
                $(tempStrLabelId).text(data.data.note);
                $(tempStrInputId).val(data.data.note);
                $(tempStrLast).addClass('runJs');
                $(tempStr).append('<a title="Not Görüntüle" class="glyphicon glyphicon-info-sign runJs col-md-1 col-lg-1" id="row-id-' + data.data.id  + '"  onclick="openShowModel(this)"></a>');
                }
            });
        }

        function updateDelivery(event){
            console.log($(event).attr('id').split("-")[1]);
            var tempId = $(event).attr('id').split("-")[1];
            var status = '#selectE' + tempId;
            var picker = '#picker' + tempId;
            var dateId= '#dateId' + tempId;

            var hour = '#dateHour' + tempId;
            var min = '#dateMin' + tempId;

            //var d = new Date($('#dateId1646').val());d.getUTCDate();
            var statusValue = $(status).val();
            var pickerValue = $(picker).val();
            var dateIdValue = $(dateId).val();
            var hourValue = $(hour).val();
            var minValue = $(min).val();

            $.ajax({
                url: '/admin/update-deliveries',
                method: "POST",
                data: {
                    id : tempId,
                    status : statusValue ,
                    picker : pickerValue,
                    delivery_date : dateIdValue,
                    delivery_date_hour : hourValue,
                    delivery_date_minute : minValue
                },
                success: function(data) {
                    var tempId = '#statusIdTr' + data.id;
                    var dateId = '#dateIdTr' + data.id;
                    var pickerId = '#pickerIdTr' + data.id;
                    var trId = '#tr_' + data.id;
                    var tempText = "";
                    if( data.status == 1 )
                        tempText = "Çiçek hazırlanıyor.";
                    else if(data.status == 2)
                       tempText = "Teslimat aşamasında.";
                    else if(data.status == 3)
                       tempText = "Teslim edildi.";
                    else if(data.status == 4)
                       tempText = "İptal edildi.";
                    else if(data.status == 6)
                       tempText = "Çiçek hazır.";
                    $(tempId).text(tempText);
                    $(dateId).text(data.date);
                    $(pickerId).text(data.picker);
                    $(trId).fadeIn().fadeOut().fadeIn().fadeOut().fadeIn().fadeOut().fadeIn();
                    $(trId).css('font-weight' , 'bold');
                },
                error: function (data) {
                    alert("Sipariş iptalliği kaldırılamaz! İlgili ürünün stok sayısı yetersiz!");
                }
            });
        }

        function updateDeliveryStudio(event){
            console.log($(event).attr('id').split("-")[1]);
            var tempId = $(event).attr('id').split("-")[1];
            var status = '#selectE' + tempId;
            var picker = '#picker' + tempId;
            var dateId= '#dateId' + tempId;

            var hour = '#dateHour' + tempId;
            var min = '#dateMin' + tempId;

            //var d = new Date($('#dateId1646').val());d.getUTCDate();
            var statusValue = $(status).val();
            var pickerValue = $(picker).val();
            var dateIdValue = $(dateId).val();
            var hourValue = $(hour).val();
            var minValue = $(min).val();

            $.ajax({
                url: '/admin/studio-update-deliveries',
                method: "POST",
                data: {
                    id : tempId,
                    status : statusValue ,
                    picker : pickerValue,
                    delivery_date : dateIdValue,
                    delivery_date_hour : hourValue,
                    delivery_date_minute : minValue
                },
                success: function(data) {
                    var tempId = '#statusIdTr' + data.id;
                    var dateId = '#dateIdTr' + data.id;
                    var pickerId = '#pickerIdTr' + data.id;
                    var trId = '#tr_' + data.id;
                    var tempText = "";
                    if( data.status == 1 )
                        tempText = "Çiçek hazırlanıyor.";
                    else if(data.status == 2)
                       tempText = "Teslimat aşamasında.";
                    else if(data.status == 3)
                       tempText = "Teslim edildi.";
                    else if(data.status == 4)
                       tempText = "İptal edildi.";
                    else if(data.status == 6)
                       tempText = "Çiçek hazır.";
                    $(tempId).text(tempText);
                    $(dateId).text(data.date);
                    $(pickerId).text(data.picker);
                    $(trId).fadeIn().fadeOut().fadeIn().fadeOut().fadeIn().fadeOut().fadeIn();
                    $(trId).css('font-weight' , 'bold');
                }
            });
        }

        function openModel(event){
            var tempDateId = '#wantedDate' + $(event).attr('id').split("-")[2];
            var tempDay = $(tempDateId).text().split(" ")[1];
            var d = new Date();
            //d.getUTCDate();
            console.log(d.getUTCDate());
            console.log(tempDay);
            if(d.getUTCDate() != tempDay){
                if (confirm( "BUGÜN TARİHLİ OLMAYAN sipariş güncellemek istediğine emin misin?"  ) == true) {

                } else {
                    return false;
                }
            }
            var tempId = '#myModal' + $(event).attr('id').split("-")[2];
            $(tempId).modal('show');
        }

        function openAddModel(event){
            console.log($(event).attr('id').split("-")[2]);
            var tempId = '#addDeliveryNote' + $(event).attr('id').split("-")[2];
            $(tempId).modal('show');
        }

        function openShowModel(event){
            console.log($(event).attr('id').split("-")[2]);
            var tempId = '#addDeliveryNoteShow' + $(event).attr('id').split("-")[2];
            $(tempId).modal('show');
        }

        function makeToday(){
            var currentDate = new Date(new Date().getTime());
            var day = currentDate.getDate();
            var month = currentDate.getMonth() + 1;
            var year = currentDate.getFullYear();

            //var before = new Date();
            //var after = new Date();
            //var tomorrowDay = before.getDay();
            //var month = parseInt(before.getDay()) + 1;
            //before.setDay();
            //console.log( before.getFullYear() + '-' + before.getMonth() + '-' + before.getDay());
            //var month = parseInt(before.getMonth()) + 1;
            //after.setHours(23,59,59,59);
            //before.setHours(0,0,0,0);
            month = (month < 10) ? ("0" + month) : month;
            day = (day < 10) ? ("0" + day) : day;
            $('#dateId2').val(year + '-' + month + '-' + day);
            $('#dateId2End').val(year + '-' + month + '-' + day);
            $('#submitId').click();
        }

        function makeTomorrow(){
            var currentDate1 = new Date(new Date().getTime() + 24 * 60 * 60 * 1000);
            var day1 = currentDate1.getDate();
            var month1 = currentDate1.getMonth() + 1;
            var year1 = currentDate1.getFullYear();

            //var currentDate = new Date(new Date().getTime());
            //var day = currentDate.getDate();
            //var month = currentDate.getMonth() + 1;
            //var year = currentDate.getFullYear();
            ////var before = new Date();
            //var after = new Date();
            //var tomorrowDay = before.getDay();
            //var month = parseInt(before.getDay()) + 1;
            //before.setDay();
            //console.log( before.getFullYear() + '-' + before.getMonth() + '-' + before.getDay());
            //var month = parseInt(before.getMonth()) + 1;
            //after.setHours(23,59,59,59);
            //before.setHours(0,0,0,0);
            //month = (month < 10) ? ("0" + month) : month;
            //day = (day < 10) ? ("0" + day) : day;
            month1 = (month1 < 10) ? ("0" + month1) : month1;
            day1 = (day1 < 10) ? ("0" + day1) : day1;
            $('#dateId2').val(year1 + '-' + month1 + '-' + day1 );
            $('#dateId2End').val(year1 + '-' + month1 + '-' + day1 );
            $('#submitId').click();
        }

        function setOrderParameter(paremeter , upOrDown){
            $('input[name=orderParameter]').val(paremeter);
            $('input[name=upOrDown]').val(upOrDown);
            console.log( $('input[name=orderParameter]').val());
        }
        //$('html').click(function() {
        //    window.location='/admin/deliveries';
        //});

        $('#products-table').click(function(event){
            event.stopPropagation();
        });
        $(".select2").select2();
    </script>
@stop