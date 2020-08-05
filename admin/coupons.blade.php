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
                <h1>Bloom & Fresh Kupon Listesi</h1>
            </td>
            <td>
            {!! Html::link('/admin/create/coupon' , 'Kupon Oluştur', ['class' => 'btn btn-success', 'style' => 'width:110px; vertical-align: middle;float: right;margin-right: 15px;']) !!}
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
    <button id="testXls"class="btn btn-danger" onClick ="$('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Kupon Listesi',filename: 'Kupon Listesi'});">Excel Çıktısı İçin Tıklayınız</button>
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            {!! Form::model(null, ['name' => 'test1' , 'url' => '/admin/coupons/orderAndFilterDesc/', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            {!! Form::hidden('orderParameter', null, ['class' => 'form-control' , 'name' => 'orderParameter']) !!}
            {!! Form::hidden('upOrDown', null, ['class' => 'form-control' , 'name' => 'upOrDown']) !!}

            <th style="text-align: center;">Valid</th>
            <th style="text-align: center;width: 140px;">Kullanıcı Listesinde</th>
            <th style="text-align: center;">Kullanım</th>
            <th style="padding-left: 20px;"><span onmouseover="setOrderParameter('publish_id' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span>Id<span onmouseover="setOrderParameter('publish_id' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th style="padding-left: 20px;">İsim</th>
            <th style="padding-left: 20px;">Tür </th>
            <th style="padding-left: 20px;"><span onmouseover="setOrderParameter('value' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span>Değer<span onmouseover="setOrderParameter('value' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th style="padding-left: 20px;"><span onmouseover="setOrderParameter('expiredDate' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span>Geçerlilik Süresi<span onmouseover="setOrderParameter('expiredDate' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th> </th>
            {!! Form::close() !!}
        </tr>
        {{--{!! Form::open(['action' => 'AdminPanelController@store', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}--}}
        {{--<tr>--}}
            {{--<td>--}}
                {{--<div class="checkbox">--}}
                    {{--<label>--}}
                        {{--{!! Form::checkbox('activation_status', null, null, ['class' => 'form-control']) !!}--}}
                    {{--</label>--}}
                {{--</div>--}}
            {{--</td>--}}
            {{--<td>--}}
                {{--<div class="form-group">--}}
                    {{--{!! Form::text('name', null, ['class' => 'form-control']) !!}--}}
                {{--</div>--}}
            {{--</td>--}}
            {{--<td>--}}
                {{--<div class="form-group">--}}
                    {{--{!! Form::text('price', null, ['class' => 'form-control']) !!}--}}
                {{--</div>--}}
            {{--</td>--}}
            {{--<td>--}}
                {{--<div class="form-group">--}}
                    {{--{!! Form::text('description', null, ['class' => 'form-control']) !!}--}}
                {{--</div>--}}
            {{--</td>--}}
            {{--<td style="padding-left:21px;">--}}
                {{--{!! Form::file('img') !!}--}}
            {{--</td>--}}
            {{--<td>--}}
                {{--<div class="form-group">--}}
                    {{--{!! Form::submit('Ekle', ['class' => 'btn btn-primary form-control']) !!}--}}
                {{--</div>--}}
            {{--</td>--}}
        {{--</tr>--}}
        {{--{!! Form::close() !!}--}}

        @foreach($coupons as $coupon)
            @if($id == $coupon->id )
                {!! Form::model($coupon, ['action' => 'AdminPanelController@storeCoupon', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr id="row-id-{{$coupon->id}}">
                    <td style="text-align: center;">
                        <div style="margin: 0px;" class="checkbox">
                            <label>
                                {!! Form::checkbox('valid', null, $coupon->valid, ['style' => 'width: 30px;height: 30px;']) !!}
                            </label>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <div style="margin: 0px;" class="checkbox">
                            <label>
                                {!! Form::checkbox('active', null, $coupon->used, ['style' => 'width: 30px;height: 30px;']) !!}
                            </label>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <div style="margin: 0px;" class="checkbox">
                            <label>
                                {!! Form::checkbox('used', null, $coupon->used, ['style' => 'width: 30px;height: 30px;']) !!}
                            </label>
                        </div>
                    </td>

                    <td>
                        <div class="form-group">
                            {!! Form::text('publish_id', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('name', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <div class="form-group">
                                <select name="type">
                                        <option value="1" {{$coupon->type == 1  ? 'selected' : ''}}>TL</option>
                                        <option value="2" {{$coupon->type == 2  ? 'selected' : ''}}>Yüzde</option>
                                </select>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('value', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <div class="form-group">
                                  <input type="datetime" value="{{$coupon->expiredDate}}" name="expiredDate">
                            </div>
                        </div>
                    </td>
                    <td>
                        {!! Form::hidden('id', $coupon->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                        </div>
                    </td>
                </tr>
                {!! Form::close() !!}
            @else
                {!! Form::open(['action' => 'AdminPanelController@deleteCoupon', 'method' => 'DELETE' ]) !!}
                <tr id="row-id-{{$coupon->id}}">
                    <td style="text-align: center;">
                        <div style="margin: 0px;" class="checkbox">
                            <label>
                                {!! Form::checkbox('activation_status', null, $coupon->valid, ['style' => 'width: 30px;height: 30px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <div style="margin: 0px;" class="checkbox">
                            <label>
                                {!! Form::checkbox('activation_status', null, $coupon->active, ['style' => 'width: 30px;height: 30px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <div style="margin: 0px;" class="checkbox">
                            <label>
                                {!! Form::checkbox('activation_status', null, $coupon->used, ['style' => 'width: 30px;height: 30px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>

                    <td style="padding-left:21px;vertical-align: middle;">{{$coupon->publish_id}}
                        <i title="Linki Kopyala" style="cursor: pointer;font-size: 25px;color: green;" onclick="copyToClipboard('{{$coupon->publish_id}}')" class="fa fa-fw fa-files-o"></i>
                        <p id="{{$coupon->publish_id}}" class="hidden">{{$coupon->publish_id}}</p>
                    </td>
                    <td style="padding-left:21px;vertical-align: middle;">{{$coupon->name}}</td>
                    <td style="padding-left:21px;vertical-align: middle;">{{$coupon->type == 1  ? 'TL' : 'Yuzde'}}</td>
                    <td style="padding-left:21px;vertical-align: middle;">{{$coupon->value}}</td>
                    <td style="padding-left:21px;vertical-align: middle;">{{$coupon->expiredDate}}</td>
                    <td>
                        {!! Form::hidden('id', $coupon->id, ['class' => 'form-control']) !!}
                        <div style="margin-bottom: 0px;" class="form-group">
                            {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control', 'style' => 'width:100%;']) !!}
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

        function setOrderParameter(paremeter , upOrDown){
            $('input[name=orderParameter]').val(paremeter);
            $('input[name=upOrDown]').val(upOrDown);
            console.log( $('input[name=orderParameter]').val());
        }

        $('#products-table').click(function(event){
            event.stopPropagation();
        });

        // go to anchor
        if( {{ $id > 0 ? 'true' : 'false' }} )
        {
            window.scrollTo(0, document.getElementById('row-id-{{ $id }}').offsetTop);
        }

        function copyToClipboard(elementId) {

            // Create a "hidden" input
            var aux = document.createElement("input");

            // Assign it the value of the specified element
            aux.setAttribute("value", document.getElementById(elementId).innerHTML);

            // Append it to the body
            document.body.appendChild(aux);

            // Highlight its content
            aux.select();

            // Copy the highlighted text
            document.execCommand("copy");

            // Remove it from the body
            document.body.removeChild(aux);

        }

    </script>
@stop