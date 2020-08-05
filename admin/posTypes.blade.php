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
    <h1 class="col-lg-12 col-md-12">Pos Seçim Senaryosu</h1>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;max-width: 600px;">
        {!! Form::model(null , ['action' => 'AdminPanelController@updatePosType', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <tr>
            <td style="width: 216px;vertical-align: middle;text-align: center;">
                Aktif Pos Seçim Senaryosu
            </td>
            <td>
                <div style="margin-bottom: 0px;" class="form-group">
                    <select name="posName" style="width: 383px;" class="form-control select2" onchange="selectProduct($(this).val());" id="tagId">
                        @foreach( $posTypes as $pos )
                            <option @if( $pos->active ) selected @endif value="{{$pos->id}}">{{$pos->name}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <button style="width: 300px;margin-left: auto;margin-right: auto;" type="submit" class="btn btn-block btn-success" >Kaydet</button>
            </td>
        </tr>
        {!! Form::close() !!}
    </table>
@stop()
@section('footer')
@stop