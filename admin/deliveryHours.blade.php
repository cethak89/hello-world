@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        div.form-group {
            height: 20px;
        }

    </style>
@stop

@section('content')

    <style>
        .form-group {
            margin-bottom: 0px !important;
        }
    </style>

    <table style="margin-bottom: 0px;" class="table table-hover">
        <tr>
            <td>
                <h1>Teslim Saatleri</h1>
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
    <label style="margin-left: 15px;" >Gönderim Bölgesi :</label>

    @if( count($tempContinentList) > 6 )
        <select style="width: 100px;margin-left: 10px;margin-bottom: 15px;display: inline-block;" class="form-control select2" onchange="updateLocations();" id="tagId">
            <option selected value="all">Hepsi</option>
            <option value="Asya">Asya</option>
            <option value="Asya-2">Asya-2</option>
            <option value="Avrupa">Avrupa</option>
            <option value="Avrupa-2">Oyaka</option>
            <option value="Avrupa-3">Avrupa-3</option>
            <option value="Ankara-1">Ankara-1</option>
            <option value="Ankara-2">Ankara-2</option>
            <option value="Ups">Ups</option>
        </select>
    @elseif( count($tempContinentList) > 3 )
        <select style="width: 100px;margin-left: 10px;margin-bottom: 15px;display: inline-block;" class="form-control select2" onchange="updateLocations();" id="tagId">
            <option selected value="all">Hepsi</option>
            <option value="Asya">Asya</option>
            <option value="Asya-2">Asya-2</option>
            <option value="Avrupa">Avrupa</option>
            <option value="Avrupa-2">Oyaka</option>
            <option value="Avrupa-3">Avrupa-3</option>
            <option value="Ups">Ups</option>
        </select>
    @elseif( count($tempContinentList) <= 3 && $tempContinentList[0]->continent_id == 'Asya' )
            <select style="width: 100px;margin-left: 10px;margin-bottom: 15px;display: inline-block;" class="form-control select2" onchange="updateLocations();" id="tagId">
                <option selected value="all">Hepsi</option>
                <option value="Asya">Asya</option>
                <option value="Asya-2">Asya-2</option>
            </select>
    @else
        <select style="width: 100px;margin-left: 10px;margin-bottom: 15px;display: inline-block;" class="form-control select2" onchange="updateLocations();" id="tagId">
            <option selected value="all">Hepsi</option>
            <option value="Ankara-1">Ankara-1</option>
            <option value="Ankara-2">Ankara-2</option>
        </select>
    @endif




    @foreach( $tempContinentList as $continent )
        <table id="products-table" class="table table-hover all-locations {{$continent->continent_id}}" style="vertical-align: middle;vertical-align: middle;border: 2px #222d32 solid;max-width: 1440px;">
            <tr style="height: 60px;font-size: 32px;font-weight: 800;color: #3c8dbc;text-align: center;">
                <td colspan="6">
                    @if( $continent->continent_id == 'Avrupa-2' )
                        Oyaka
                    @else
                        {{$continent->continent_id}}
                    @endif
                </td>
            </tr>
            <tr>
                <th><p>Gün</p></th>
                <th style="text-align: center;">Bölge</th>
                <th style="text-align: center;">1. Gönderim Saati</th>
                <th style="text-align: center;">
                    @if( $continent->continent_id != 'Ups' )
                        2. Gönderim Saati
                    @endif
                </th>
                <th style="text-align: center;">
                    @if( $continent->continent_id != 'Ups' )
                        3. Gönderim Saati
                    @endif
                </th>
                <th></th>
            </tr>
            @foreach($continent->days as $day)
                @if($id == $day->id && $day->continent_id == $continent_id  )
                    {!! Form::model($day, ['action' => 'AdminPanelController@updateDeliveryHours', 'files'=>true ]) !!}
                    <input id="selectedId" class="hidden" value='{{$continent_id}}' >
                    <tr id="row-id-{{$day->id}}">
                        <td style="vertical-align: middle;">{{$day->date}}</td>
                        <td style="vertical-align: middle;">{{$day->continent_id}}</td>
                        @foreach($day->hours as $key => $hour)
                            <td style="text-align: center;vertical-align: middle;">
                                @if( $continent->continent_id == 'Ups' && $key > 0 )
                                    <div class="hidden" style="display: inline-block;vertical-align: text-bottom;">
                                        <select name="start_{{$hour->id}}" class="btn btn-default dropdown-toggle">
                                            @foreach($myArray as $tag)
                                                <option value="{{$tag->val}}"
                                                        @if($tag->val == $hour->start_hour)
                                                        selected
                                                @else
                                                        @endif
                                                >{{$tag->hour}}</option>
                                            @endforeach
                                        </select> -
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
                                    <div style="width: 20px;display: inline-block;vertical-align: middle;">
                                        {!! Form::checkbox( 'active_' . $hour->id, null, $hour->active, [ 'style' => 'margin-top: 0px;height:30px;width:30px;', 'class' => 'hidden']) !!}
                                    </div>
                                @else
                                    <div style="display: inline-block;vertical-align: text-bottom;">
                                        <select name="start_{{$hour->id}}" class="btn btn-default dropdown-toggle">
                                            @foreach($myArray as $tag)
                                                <option value="{{$tag->val}}"
                                                        @if($tag->val == $hour->start_hour)
                                                        selected
                                                @else
                                                        @endif
                                                >{{$tag->hour}}</option>
                                            @endforeach
                                        </select> -
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
                                    <div style="display: inline-block;vertical-align: text-bottom;">
                                        {!! Form::checkbox( 'active_' . $hour->id, null, $hour->active, [ 'style' => 'margin-top: 0px;height:30px;width:30px;']) !!}
                                    </div>
                                @endif
                            </td>
                        @endforeach
                        <td style="text-align: center;vertical-align: middle;">
                            {!! Form::hidden('id', $day->id, ['class' => 'form-control']) !!}
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                        </td>
                    </tr>
                    {!! Form::close() !!}
                @else
                    <tr>
                        <td style="vertical-align: middle;">{{$day->date}}</td>
                        <td style="vertical-align: middle;text-align: center;">{{$day->continent_id}}</td>
                        @foreach($day->hours as $key => $hour)
                            <td style="vertical-align: middle;text-align: center; @if( $hour->active ) text-align: center;background-color: green;color: white;font-size: 24px;font-weight: 800; @endif ">
                                @if( $continent->continent_id == 'Ups' && $key > 0 )
                                @else
                                    <div style="display: inline-block;vertical-align: text-bottom;">
                                        {{$hour->start_hour}} - {{$hour->end_hour}}
                                    </div>
                                    <div style="display: inline-block;vertical-align: middle;">
                                        {!! Form::checkbox('active', null, $hour->active, [ 'style' => 'margin-top: 0px;height:30px;width:30px;' , 'disabled']) !!}
                                    </div>
                                @endif
                            </td>
                        @endforeach
                        <td style="vertical-align: middle;text-align: center;">
                            <div class="form-group">
                                {!! Html::link('/admin/deliveryHours/' . $day->id . '/' . $day->continent_id  , 'Değiştir', ['class' => 'btn btn-primary form-control']) !!}
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>
    @endforeach
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

        if ( {{ $id > 0 ? 'true' : 'false' }} ) {
            window.scrollTo(0, document.getElementById('row-id-{{ $id }}').offsetTop);
        }
    </script>
@stop