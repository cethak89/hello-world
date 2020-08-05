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
    </style>
@section('content')

    <table class="table table-hover">
    <tr>
        <td>
            <h1>Bloom & Fresh Şirket Siparişleri Listesi</h1>
        </td>
        <td style="vertical-align: middle;">
            <button class="btn btn-primary form-control" onclick="$('#filterTable').toggle();">Sorgu Alanlari</button>
        </td>
        <td style="vertical-align: middle;width: 200px;">
            <button id="testXls" class="btn btn-danger pull-right"  onClick ="$('tr').each(function() {$(this).find('td:eq(10)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Sipariş Listesi',filename: 'Sipariş Listesi'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>
        </td>
        <td style="vertical-align: middle;">
            {!! Html::link('/admin/insertDeliveryPage' , 'Sipariş Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
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
    <table id="filterTable" class="table table-hover"  style="display: {{$filterShow}}">
        {!! Form::model($queryParams, ['url' => '/admin/company-deliveries/filter', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            <tr>
                <td style="padding-left:21px;width: 210px;">Ürün Adı</td><td style="padding-left:21px;">
                {!! Form::input('text', 'product_name', $queryParams->product_name, ['class' => 'form-control', 'style' => 'width: 182px;']) !!}
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;width: 210px;">Firma Adı</td><td style="padding-left:21px;">{!! Form::input('text', 'company_name', $queryParams->company_name, ['class' => 'form-control', 'style' => 'width: 182px;']) !!}</td>
            </tr>
            <tr>
                <td style="padding-left:21px;width: 210px;">İstenen Teslim Tarihi</td>
                <td style="padding-left:21px;">
                    <input style="width: 90px;display: inline-block;" id="dateId" type="text" value="{{explode( " " ,$queryParams->wanted_delivery_date)[0]}}" class="form-control" name="wanted_delivery_date">
                    <input style="width: 90px;display: inline-block;" id="dateIdEnd" type="text" value="{{explode( " " ,$queryParams->wanted_delivery_date_end)[0]}}" class="form-control" name="wanted_delivery_date_end">
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;width: 210px;">Teslim Tarihi</td>
                <td style="padding-left:21px;">
                    <input style="width: 90px;display: inline-block;" id="dateId2" type="text" value="{{explode( " " ,$queryParams->delivery_date)[0]}}" class="form-control" name="delivery_date">
                    <input style="width: 90px;display: inline-block;" id="dateId2End" type="text" value="{{explode( " " ,$queryParams->delivery_date_end)[0]}}" class="form-control" name="delivery_date_end">
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;width: 210px;">Sipariş Durumu</td><td style="padding-left:21px;">
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
                <td colspan="2">
                    {!! Form::submit('Sorgula', ['class' => 'btn btn-success form-control' , 'id' => 'submitId', 'style' => 'width: 210px;margin-left: 14px;']) !!}
                    {!! Html::link('/admin/company-deliveries' , 'Temizle', ['class' => 'btn btn-primary form-control', 'style' => 'width: 216px;']) !!}
                    <button style="width: 216px;" onclick="makeToday()" class="btn btn-danger form-control">Bugün</button>
                    <button style="width: 216px;" onclick="makeTomorrow()" class="btn btn-warning form-control">Yarın</button>
                </td>
            </tr>
        {!! Form::close() !!}
    </table>
    <label>Sipariş Sayısı : {{$countDelivery}}</label>

    <div style="overflow-x: scroll;">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
            <tr>
                {!! Form::model($queryParams, ['name' => 'test1' , 'url' => '/admin/company-deliveries/orderAndFilterDesc', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
                {!! Form::hidden('product_name', $queryParams->product_name, ['class' => 'form-control']) !!}
                {!! Form::hidden('company_name', $queryParams->company_name, ['class' => 'form-control']) !!}
                {!! Form::hidden('wanted_delivery_date', $queryParams->wanted_delivery_date, ['class' => 'form-control']) !!}
                {!! Form::hidden('wanted_delivery_date_end', $queryParams->wanted_delivery_date_end, ['class' => 'form-control']) !!}
                {!! Form::hidden('delivery_date', $queryParams->delivery_date, ['class' => 'form-control']) !!}
                {!! Form::hidden('delivery_date_end', $queryParams->delivery_date_end, ['class' => 'form-control']) !!}
                {!! Form::hidden('status_all', $queryParams->status_all, ['class' => 'form-control']) !!}
                {!! Form::hidden('status_making', $queryParams->status_making, ['class' => 'form-control']) !!}
                {!! Form::hidden('status_ready', $queryParams->status_ready, ['class' => 'form-control']) !!}
                {!! Form::hidden('status_delivering', $queryParams->status_delivering, ['class' => 'form-control']) !!}
                {!! Form::hidden('status_delivered', $queryParams->status_delivered, ['class' => 'form-control']) !!}
                {!! Form::hidden('status_cancel', $queryParams->status_cancel, ['class' => 'form-control']) !!}
                {!! Form::hidden('orderParameter', null, ['class' => 'form-control' , 'name' => 'orderParameter']) !!}
                {!! Form::hidden('upOrDown', null, ['class' => 'form-control' , 'name' => 'upOrDown']) !!}
                @if($id == 0 || $id == null )
                    <th><span onmouseover="setOrderParameter('company_name' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Gönderen <span onmouseover="setOrderParameter('company_name' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                    <th><span onmouseover="setOrderParameter('product_name' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Ürün Adı <span onmouseover="setOrderParameter('product_name' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                    <th><span onmouseover="setOrderParameter('receiver' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Alıcı <span onmouseover="setOrderParameter('receiver' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                    <th><span onmouseover="setOrderParameter('wanted_delivery_date' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> İstenen Teslim Tarihi <span onmouseover="setOrderParameter('wanted_delivery_date' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                    <th><span onmouseover="setOrderParameter('delivery_location' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Bölge <span onmouseover="setOrderParameter('delivery_location' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                    <th>Adres</th>
                    <th>Teslim Alan</th>
                    <th><span onmouseover="setOrderParameter('delivery_date' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Teslim Tarihi <span onmouseover="setOrderParameter('delivery_date' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                    <th><span onmouseover="setOrderParameter('status' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Siparişin Durumu <span onmouseover="setOrderParameter('status' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
                    <th></th>
                @else
                    <th>Gönderen</th>
                    <th>Ürün Adı</th>
                    <th>Alıcı</th>
                    <th>İstenen Teslim Tarihi</th>
                    <th>Bölge</th>
                    <th>Adres</th>
                    <th>Teslim Alan</th>
                    <th>Teslim Tarihi</th>
                    <th>Siparişin Durumu</th>
                    <th></th>
                @endif
                {!! Form::close() !!}
            </tr>
        @foreach($deliveryList as $delivery)
            <!-- @if($id == $delivery->id )
                {!! Form::model($delivery, ['action' => 'AdminPanelController@updateDelivery', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                        <tr id="row-id-{{$delivery->id}}">
                        <td style="padding-left:21px;">{{$delivery->requestDate}}</td>
                        <td style="padding-left:21px;">{{$delivery->sale_id}}</td>
                        <td  style="padding-left:21px;">{{$delivery->district}}</td>
                        <!--<td style="padding-left:21px;">{{$delivery->customer_name}}<br>{{$delivery->customer_surname}}</td>-->
                    <td style="padding-left:21px;">{{$delivery->contact_name}}<br>{{$delivery->contact_surname}}</td>
                <!--<td style="padding-left:21px;">{{$delivery->products}}</td>
                        <td style="padding-left:21px;">{{$delivery->wantedDeliveryDate}}</td>-->
                    <td style="padding-left:21px;"><input id="dateId" type="date"  name="delivery_date"><input id="dateHour" style="width: 20px;height: 40px;" type="text" maxlength="2"  name="delivery_date_hour"><input id="dateMin" style="width: 20px;height: 40px;" type="text" maxlength="2"   name="delivery_date_minute"></td>
                    <td style="padding-left:21px;">
                        <div class="form-group">
                            <select id="selectE" name="status" class="btn btn-default dropdown-toggle">
                                @foreach($myArray as $tag)
                                    <option value="{{$tag->status}}"
                                            @if($tag->status == $delivery->status)
                                            selected
                                    @else
                                            @endif
                                    >
                                        {{$tag->information}}</option>
                                @endforeach
                            </select>
                        </div>
                    </td>
                    <td style="padding-left:21px;">{!! Form::text('picker', $delivery->picker, ['class' => 'form-control' , 'style' => 'width : 210px;']) !!}</td>
                    <td>
                        {!! Form::hidden('id', $delivery->id, ['class' => 'form-control']) !!}
                        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                    </td>
                    </tr>
                    {!! Form::close() !!}
                @else -->
                    <tr id="tr_{{$delivery->id}}" data-toggle="modal" data-target="#myModal">
                    <!-- <tr id="row-id-{{$delivery->id}}"  data-toggle="modal" data-target="#myModal" onclick="window.location='{{ action('AdminPanelController@showDeliveries', [ $delivery->id ]) }}'"> -->
                        <td style="padding-left:21px;">{{$delivery->company_name}}</td>
                        <td style="padding-left:21px;">{{$delivery->product_name}}</td>
                        <td style="padding-left:21px;">{{$delivery->receiver}}</td>
                        <td id="wantedDate{{$delivery->id}}" style="padding-left:21px;">{{$delivery->wantedDeliveryDate}}</td>
                        <td style="padding-left:21px;">{{$delivery->delivery_location}}</td>
                        <td style="padding-left:21px;">{{$delivery->receiver_address}}</td>
                        <td style="padding-left:21px;">{{$delivery->picker}}</td>
                        <td style="padding-left:21px;">{{$delivery->deliveryDate}}</td>
                        <td id="statusIdTr{{$delivery->id}}" style="padding-left:21px;">
                            @if( $delivery->status == 1 )
                                Çiçek hazırlanıyor.
                            @elseif($delivery->status == 2)
                                Teslimat aşamasında.
                            @elseif($delivery->status == 3)
                                Teslim edildi.
                            @elseif($delivery->status == 4)
                                İptal edildi.
                            @elseif($delivery->status == 6)
                                Çiçek hazır.
                            @endif
                        </td>
                        <td>
                            {!! Form::hidden('id', $delivery->id, ['class' => 'form-control']) !!}
                            <div class="form-group">
                                {!! Html::link('/admin/company-deliveries/detail/' . $delivery->id , '', ['class' => 'glyphicon glyphicon glyphicon-file col-md-1 col-lg-1' , 'title' => 'Detay Bilgiler']) !!}
                                @if(  Auth::user()->user_group_id == 1  )
                                    <a title="Durumu Güncelle" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="row-id-{{$delivery->id}}"  onclick="openModel(this)"></a>
                                    <a title="Not Ekle" class="glyphicon glyphicon-paperclip col-md-1 col-lg-1" id="row-id-{{$delivery->id}}"  onclick="openAddModel(this)"></a>
                                @endif
                            </div>
                        </td>
                        <div class="modal fade" id="myModal{{$delivery->id}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="myModalLabel"><u style="color: red">{{$delivery->company_name}} </u> tarafından <u style="color: red">{{$delivery->delivery_location}}</u> bölgesine  yollanan <u style="color: red">{{$delivery->product_name}}</u> siparişini değiştiriyorsunuz!</h4>
                                    </div>
                                    <div class="modal-body">
                                        <p class="col-lg-6 col-md-6 col-sm-6 col-xs-6">Teslimat Durumu</p>
                                        <select  class="col-lg-6 col-md-6 col-sm-6 col-xs-6" id="selectE{{$delivery->id}}" name="status" class="btn btn-default dropdown-toggle">
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
                                        <p  class="col-lg-6 col-md-6 col-sm-6 col-xs-6">Teslim alan kişi</p>
                                        <input id="picker{{$delivery->id}}" value="{{$delivery->picker}}" class="form-control col-lg-6 col-md-6 col-sm-6 col-xs-6" style="width : 210px;">
                                        <br>
                                        <br>
                                        <p  class="col-lg-6 col-md-6 col-sm-6 col-xs-6">Tarih</p>
                                        <input id="dateId{{$delivery->id}}" type="date"  name="delivery_date"><input id="dateHour{{$delivery->id}}" style="width: 20px;height: 40px;" type="text" maxlength="2"  name="delivery_date_hour">
                                        <input id="dateMin{{$delivery->id}}" style="width: 20px;height: 40px;" type="text" maxlength="2" name="delivery_date_minute">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                            <button id="update-{{$delivery->id}}" class="btn btn-success form-control" onclick="updateDelivery(this)" data-dismiss="modal" >Güncelle</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="addDeliveryNote{{$delivery->id}}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title" id="myModalLabel"><u style="color: red">{{$delivery->receiver}} </u> tarafından <u style="color: red">{{$delivery->delivery_location}}</u> bölgesine  yollanan <u style="color: red">{{$delivery->product_name}}</u> siparişine not ekliyorsunuz!</h4>
                                    </div>
                                    <div style="margin-bottom: 20px;" class="modal-body">
                                        <p class="col-lg-3 col-md-3 col-sm-3 col-xs-3">Sipariş Notunuz</p>
                                        <input value="{{$delivery->delivery_not}}"  class="col-lg-9 col-md-9 col-sm-9 col-xs-9" type="text" id="deliveryNote{{$delivery->id}}">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                                        <button style="margin-left: 0;" class="btn btn-success col-lg-6 col-md-6 col-sm-6 col-xs-6" id="deliveryButton-{{$delivery->id}}" type="button btn-success" data-dismiss="modal" onclick="setDeliveryNote(this)">Kaydet</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </tr>
                <!-- @endif -->
            @endforeach
        </table>
    </div>
<audio id="audiotag1" src="{{ asset('/sound/soundTrack.wav') }}" preload="auto"></audio>
@stop()

@section('footer')
    <script>
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
        $('#dateId2End').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
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

        //$("#status_all").change(function() {
        //    if(this.checked) {
        //        $('#status_making').attr('checked', false);
        //        $('#status_ready').attr('checked', false);
        //        $('#status_delivering').attr('checked', false);
        //        $('#status_delivered').attr('checked', false);
        //        $('#status_cancel').attr('checked', false);
        //    }
        //    else {
        //        if( !$("#status_cancel").prop('checked') && !$("#status_ready").prop('checked') && !$("#status_delivering").prop('checked') && !$("#status_delivered").prop('checked') && !$("#status_making").prop('checked') ){
        //            console.log( $('#status_all') );
        //            $('#status_all').prop("checked", true);
        //        }
        //    }
        //});

        //$("#status_making").change(function() {
        //    console.log('change');
        //    if($("#status_making").prop('checked')) {
        //        $('#label_status_making').css('background-color' , '#B9C0B9');
        //        $('#status_all').prop("checked", false);
        //        $('#status_making').prop("checked", true);
        //        $('#label_status_all').css('background-color' , '');
        //    }
        //    else {
        //        $("#status_making").prop('checked' , false);
        //        $('#label_status_making').css('background-color' , '');
        //        if( !$("#status_cancel").prop('checked') && !$("#status_ready").prop('checked') && !$("#status_delivering").prop('checked') && !$("#status_delivered").prop('checked') ){
        //            $('#status_all').prop("checked", true);
        //            $('#label_status_all').css('background-color' , '#B9C0B9');
        //        }
        //    }
//
        //});

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

            ///var currentDate = new Date(new Date().getTime());
            ///var day = currentDate.getDate();
            ///var month = currentDate.getMonth() + 1;
            ///var year = currentDate.getFullYear();
            ///month = (month < 10) ? ("0" + month) : month;
            ///day = (day < 10) ? ("0" + day) : day;
            /////$('#wddId').attr('data-date-format' , 'DD MM YYYY');
            ///$('#wddeId').attr('data-date' ,year + '-' + month + '-' + day);
            ///$('#wddId').attr('data-date' ,year + '-' + month + '-' + day);
            ///$('#wddeId2').attr('data-date' ,year + '-' + month + '-' + day);
            ///$('#wddId2').attr('data-date' ,year + '-' + month + '-' + day);
            ///$('#wddeId3').attr('data-date' ,year + '-' + month + '-' + day);
            ///$('#wddId3').attr('data-date' ,year + '-' + month + '-' + day);

            //$("#wddId").on("change", function() {
            //    this.setAttribute(
            //        "data-date",
            //        moment(this.value, "YYYY-MM-DD")
            //        .format( this.getAttribute("data-date-format") )
            //    )
            //}).trigger("change");
        });

            $(".formatDate").on("change", function() {
                this.setAttribute(
                    "data-date",
                    moment(this.value, "YYYY-MM-DD")
                    .format( this.getAttribute("data-date-format") )
                )
            }).trigger("change");


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

        function setDeliveryNote(event){
            console.log($(event).attr('id').split("-")[1]);
            var tempId = $(event).attr('id').split("-")[1];
            var deliveryNoteInputId = '#deliveryNote' + tempId;
            var deliveryNote = $(deliveryNoteInputId).val();
            $.ajax({
                url: '/admin/set-company-delivery-note',
                method: "POST",
                data: { note : deliveryNote , id : tempId },
                success: function(data) {
                    console.log(data);
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
                url: '/admin/update-company-deliveries',
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
            $('#dateId').val(year + '-' + month + '-' + day);
            $('#dateIdEnd').val(year + '-' + month + '-' + day);
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
            $('#dateId').val(year1 + '-' + month1 + '-' + day1 );
            $('#dateIdEnd').val(year1 + '-' + month1 + '-' + day1 );
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

        // go to anchor
        if( {{ $id > 0 ? 'true' : 'false' }} )
        {
            window.scrollTo(0, document.getElementById('row-id-{{ $id }}').offsetTop);
        }
    </script>
@stop