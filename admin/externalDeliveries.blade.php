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
            <h1>Bloom & Fresh Özel Dağıtım Saatleri</h1>
        </td>
    </tr>
    </table>
    <label style="margin-left: 15px;" >Gönderim Bölgesi :</label>
    <select style="width: 100px;margin-left: 10px;margin-bottom: 15px;display: inline-block;" class="form-control select2" onchange="updateLocations();" id="tagId">
        <option selected value="all">Hepsi</option>
        <option value="Asya">Asya</option>
        <option value="Asya-2">Asya-2</option>
        <option value="Avrupa">Avrupa</option>
        <option value="Avrupa-2">Oyaka</option>
        <option value="Avrupa-3">Avrupa-3</option>
        <option value="Ups">Ups</option>
    </select>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <table id="products-table" class="table table-hover table-bordered col-md-6 col-lg-6" style="vertical-align: middle;">
    <tr>
        <th><p  class="col-lg-6">Gün</p> <p class="col-lg-6" style="font-weight: bold"> (AVRUPA) </p></th>
        <th>Bölge</th>
        <th>1. Gönderim Saati</th>
        <th>2. Gönderim Saati</th>
        <th>3. Gönderim Saati</th>
        <th>----------</th>
    </tr>
    @foreach($dayListEu as $day)
        @if($id == $day->id && $day->continent_id == $continent_id  )
            {!! Form::model($day, ['action' => 'AdminPanelController@updateExternalDeliveryHours', 'files'=>true ]) !!}
            <tr id="row-id-{{$day->id}}">
                <td style="padding-left:21px;">{{$day->date}}</td>
                <td style="padding-left:21px;">{{$day->continent_id}}</td>
                @foreach($day->hours as $hour)
                    <td style="padding-left:21px;">
                        <div style="  float: left;">
                            <select name="start_{{$hour->id}}" class="btn btn-default dropdown-toggle">
                            @foreach($myArray as $tag)
                                <option value="{{$tag->val}}"
                                    @if($tag->val == $hour->start_hour)
                                        selected
                                    @else
                                    @endif
                                >{{$tag->hour}}</option>
                            @endforeach
                            </select>     -
                            <select name="end_{{$hour->id}}" class="btn btn-default dropdown-toggle">
                            @foreach($myArray as $tag)
                                <option value="{{$tag->val}}"
                                    @if($tag->val == $hour->end_hour)
                                        selected
                                    @else
                                    @endif
                                >{{$tag->hour}}</option>
                            @endforeach
                            </select>
                        </div>
                        <div style="  float: left; width: 30px">
                            {!! Form::checkbox( 'active_' . $hour->id, null, $hour->active, [ 'style' => 'margin-top: 0px;width: 30px;height: 30px;']) !!}
                        </div>
                    </td>
                @endforeach
                <td>
                    {!! Form::hidden('id', $day->id, ['class' => 'form-control']) !!}
                        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                </td>
            </tr>
            {!! Form::close() !!}
        @else
            <tr>
                <td style="padding-left:21px;">{{$day->date}}</td>
                <td style="padding-left:21px;">{{$day->continent_id}}</td>
                @foreach($day->hours as $hour)
                    <td style="padding-left:21px;">
                    <div style="  float: left;">
                        {{$hour->start_hour}} - {{$hour->end_hour}}
                    </div>
                    <div style="  float: left; width: 20px">
                        {!! Form::checkbox('active', null, $hour->active, [ 'style' => 'margin-top: 0px;width: 30px;height: 30px;' , 'disabled']) !!}
                    </div></td>
                @endforeach
                <td>
                    <div class="form-group">
                        {!! Html::link('/admin/externalDeliveryHours/' . $day->id . '/' . $day->continent_id  , 'Değiştir', ['class' => 'btn btn-primary form-control']) !!}
                    </div>
                </td>
            </tr>
        @endif
    @endforeach
    </table>
    <table id="products-table" class="table table-hover table-bordered col-md-6 col-lg-6" style="vertical-align: middle;">
        <tr>
            <th><p class="col-lg-6">Gün</p> <p class="col-lg-6" style="font-weight: bold"> (ASYA) </p></th>
            <th>Bölge</th>
            <th>1. Gönderim Saati</th>
            <th>2. Gönderim Saati</th>
            <th>3. Gönderim Saati</th>
            <th>----------</th>
        </tr>
        @foreach($dayList as $day)
            @if($id == $day->id && $day->continent_id == $continent_id)
                {!! Form::model($day, ['action' => 'AdminPanelController@updateExternalDeliveryHours', 'files'=>true ]) !!}
                <input id="selectedId" class="hidden" value='{{$continent_id}}' >
                <tr id="row-asya-id-{{$day->id}}">
                    <td style="padding-left:21px;">{{$day->date}}</td>
                    <td style="padding-left:21px;">{{$day->continent_id}}</td>
                    @foreach($day->hours as $hour)
                        <td style="padding-left:21px;">
                            <div style="  float: left;">
                                <select name="start_{{$hour->id}}" class="btn btn-default dropdown-toggle">
                                @foreach($myArray as $tag)
                                    <option value="{{$tag->val}}"
                                        @if($tag->val == $hour->start_hour)
                                            selected
                                        @else
                                        @endif
                                    >{{$tag->hour}}</option>
                                @endforeach
                                </select>     -
                                <select name="end_{{$hour->id}}" class="btn btn-default dropdown-toggle">
                                @foreach($myArray as $tag)
                                    <option value="{{$tag->val}}"
                                        @if($tag->val == $hour->end_hour)
                                            selected
                                        @else
                                        @endif
                                    >{{$tag->hour}}</option>
                                @endforeach
                                </select>
                            </div>
                            <div style="  float: left; width: 30px">
                                {!! Form::checkbox( 'active_' . $hour->id, null, $hour->active, [ 'style' => 'margin-top: 0px;width: 30px;height: 30px;']) !!}
                            </div>
                        </td>
                    @endforeach
                    <td>
                        {!! Form::hidden('id', $day->id, ['class' => 'form-control']) !!}
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                    </td>
                </tr>
                {!! Form::close() !!}
            @else
                <tr>
                    <td style="padding-left:21px;">{{$day->date}}</td>
                    <td style="padding-left:21px;">{{$day->continent_id}}</td>
                    @foreach($day->hours as $hour)
                        <td style="padding-left:21px;">
                        <div style="  float: left;">
                            {{$hour->start_hour}} - {{$hour->end_hour}}
                        </div>
                        <div style="  float: left; width: 20px">
                            {!! Form::checkbox('active', null, $hour->active, [ 'style' => 'margin-top: 0px;width: 30px;height: 30px;' , 'disabled']) !!}
                        </div></td>
                    @endforeach
                    <td>
                        <div class="form-group">
                            {!! Html::link('/admin/externalDeliveryHours1/' . $day->id . '/' . $day->continent_id  , 'Değiştir11', ['class' => 'btn btn-primary form-control']) !!}
                        </div>
                    </td>
                </tr>
            @endif
        @endforeach
    </table>
@stop()

@section('footer')
    <script>

        function updateLocations() {

            $('.all-locations').addClass('hidden');

            if( $('#tagId').val() == 'all' ){
                $('.all-locations').removeClass('hidden');
            }
            else{
                var tempId = '.' + $('#tagId').val()
                $(tempId).removeClass('hidden');

            }
        }

        $( document ).ready(function() {

            console.log($('#selectedId').val())

            if( $('#selectedId').val() ){
                $('#tagId').val($('#selectedId').val());
                updateLocations();
            }

        });

        if( {{ $id > 0 ? 'true' : 'false' }} )
        {
            window.scrollTo(0, document.getElementById('row-id-{{ $id }}').offsetTop);
        }
    </script>
@stop