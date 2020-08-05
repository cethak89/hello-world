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
    <h1>Bloom & Fresh Şirket Siparişi Ekleme Sayfası</h1>

    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        {!! Form::model(null , ['action' => 'AdminPanelController@insertCompanySales', 'files'=>true, 'accept' => 'text/*,image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <tr>
            <td>
                Excel
            </td>
            <td>
                <div class="form-group">
                    <input name="file" type="file" accept="text/*" id="myFile" size="1000000000">
                </div>
            </td>
        </tr>
        <div class="form-group">
        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
        </div>
        {!! Form::close() !!}
    </table>

@stop()