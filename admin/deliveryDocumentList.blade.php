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

    <table class="table table-hover">
    <tr>
        <td>
            <h1>Bloom & Fresh Sipariş Döküman Listesi</h1>
        </td>
        <td>
            {!! Html::link('/document' , 'Bugün', ['class' => 'btn btn-primary', 'style' => 'width:100%; vertical-align: middle;']) !!}
        </td>
        <td>
            {!! Html::link('/documentTomorrow' , 'Yarın', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
        </td>
    </tr>
    </table>
     <table id="filterTable" class="table table-hover">
        {!! Form::model($queryParams, ['url' => '/admin/deliveries/filter', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            <tr>
                <td style="padding-left:21px;">Gönderim Saati</td><td style="padding-left:21px;">
                <div class="form-group">
                <select onclick="filterDelivery(this)" id="deliveryHour" name="deliveryHour" class="btn btn-default dropdown-toggle">
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
                <td>
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;">Gönderim Bölgesi</td><td style="padding-left:21px;">
                <div class="form-group">
                <select onclick="filterDeliveryByRegion(this)" id="continent_id" name="continent_id" class="btn btn-default dropdown-toggle">
                    <option value="Hepsi">Hepsi</option>
                    <option value="Avrupa">Avrupa</option>
                    <option value="Avrupa-2">Oyaka</option>
                    <option value="Avrupa-3">Avrupa-3</option>
                    <option value="Asya">Asya</option>
                    <option value="Asya-2">Asya-2</option>
                </select>
                </div>
                </td>
                <td>
                </td>
            </tr>
        {!! Form::close() !!}
     </table>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    {!! Form::model($deliveryList, ['url' => '/print', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
            <tr>
                <th>Sipariş Tarihi</th>
                <th>ID</th>
                <th>Çiçek Adı</th>
                <th>Teslim Tarihi</th>
                <th>Semt</th>
                <th>Adres</th>
                <th>Müşteri Adı Soyadı</th>
                <th></th>
            </tr>
            @foreach($deliveryList as $delivery)
                <tr style="@if($delivery->studio)
                                   background-color: pink;
                                           @endif" id="tr_{{$delivery->id}}" class="c_{{$delivery->continent_id}} {{$delivery->continent_id}}{{str_replace(' ', '',explode( ':' ,$delivery->dateInfo)[0])}} p_{{str_replace(' ', '',explode( ':' ,$delivery->dateInfo)[0])}} all">
                    <td style="padding-left:21px;">{{$delivery->created_at}}</td>
                    <td style="padding-left:21px;">{{$delivery->id}}</td>
                    <td style="padding-left:21px;">{{$delivery->products}}</td>
                    <td style="padding-left:21px;">{{$delivery->dateInfo}}</td>
                    <td style="padding-left:21px;">{{$delivery->district}}</td>
                    <td style="padding-left:21px;">{{$delivery->address}}</td>
                    <td style="padding-left:21px;">{{$delivery->name}} {{$delivery->surname}}</td>
                    <td style="padding-left:21px;">
                        {!! Form::checkbox('selected_' . $delivery->id , null, null, [ 'style' => 'width :30px;height:30px;']) !!}
                    </td>
                </tr>
            @endforeach
        </table>
        {!! Form::submit('Yazdırma Sayfasına Git', ['class' => 'btn btn-success form-control' , 'id' => 'submitForm' ]) !!}
    {!! Form::close() !!}
@stop()

@section('footer')
    <script>
        function filterDelivery(temp){
            var tempStr = '.' + $(temp).val().split(":")[0];
            console.log($(temp).val());
            if($(temp).val() == 'Hepsi'){
                //$('.all').removeClass('hidden');
                tempStr = '.c_';
            }
            else{
                $('.all').addClass('hidden');
            }
            $tempContinent = "";
            if($('#continent_id').val() == 'Hepsi'){
                $tempContinent = "";
                if(tempStr == '.c_'){
                    tempStr = '.all';
                }
                else
                    tempStr = '.' + 'p_' + $(temp).val().split(":")[0];
            }
            else{
                $tempContinent = $('#continent_id').val();
            }
            if($(temp).val() != 'Hepsi' && $('#continent_id').val() != 'Hepsi'){
                tempStr = '.' + $tempContinent + $(temp).val().split(":")[0];
            }
            else
                tempStr = tempStr + $tempContinent;
            console.log(tempStr);
            $(tempStr).removeClass('hidden');
        }

        function filterDeliveryByRegion(temp){
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
            if($('#deliveryHour').val() == 'Hepsi'){
                $tempContinent = "";
                if(tempStr == '.p_'){
                    tempStr = '.all';
                }
                else
                    tempStr = '.' + 'c_' + $(temp).val();
            }
            else{
                $tempContinent = $('#deliveryHour').val().split(":")[0];
            }
            tempStr = tempStr + $tempContinent;
            console.log(tempStr);
            $(tempStr).removeClass('hidden');
        }

        function setOrderParameter(paremeter , upOrDown){
            $('input[name=orderParameter]').val(paremeter);
            $('input[name=upOrDown]').val(upOrDown);
            console.log( $('input[name=orderParameter]').val());
        }

        $('#products-table').click(function(event){
            event.stopPropagation();
        });

        // go to anchor
    </script>
@stop