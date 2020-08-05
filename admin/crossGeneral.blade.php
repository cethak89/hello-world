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
    <h1>Bloom & Fresh Cross-Sell</h1>
    @foreach( $optionsData as $option)
        {!! Form::model($option , ['action' => 'AdminPanelController@crossUpdateStatus', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
            <tr>
                <td style="width: 6%;vertical-align: inherit;">
                    Aktif( @foreach( $cityList as $city ) @if( $city->city_id == $option->city_id  ) {{$city->name}} @endif @endforeach ) :
                </td>
                <td style="width: 30px;">
                    <div style="margin-top: 0px;" class="checkbox">
                        <label>
                            {!! Form::checkbox('status', null, $option->active, ['style' => 'width: 30px;height: 30px;']) !!}
                        </label>
                    </div>
                </td>
            </tr>
            {!! Form::close() !!}
        </table>
        <div class="form-group">
            <input class="hidden" value="{{$option->id}}" name="id" >
            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
        </div>
        {!! Form::close() !!}
    @endforeach

@stop()