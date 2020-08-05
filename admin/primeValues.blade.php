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
                <h1>Bloom & Fresh Prime Değerleri</h1>
            </td>
            <td>
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
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body">
                    <table class="table table-hover table-bordered" style="vertical-align: middle;">
                        {!! Form::model($primeValue , ['action' => 'AdminPanelController@updatePrimeValues', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                            <thead>
                                <td align="middle" style="font-weight: bold;align-content: center;" colspan="2">
                                    Aylık Prime Kuponu Bilgileri
                                </td>
                                <td align="middle" style="font-weight: bold;align-content: center;" colspan="2">
                                    Cuma Prime Kuponu Bilgileri
                                </td>
                            </thead>
                            <tr>
                                <td style="width: 100px;vertical-align: inherit;">
                                    Kupon Adı :
                                </td>
                                <td>
                                    <div class="form-group" style="margin-bottom: 0px;">
                                        {!! Form::text('month_name', null, ['class' => 'form-control']) !!}
                                    </div>
                                </td>
                                <td style="width: 100px;vertical-align: inherit;">
                                    Kupon Adı :
                                </td>
                                <td>
                                    <div class="form-group" style="margin-bottom: 0px;">
                                        {!! Form::text('name', null, ['class' => 'form-control']) !!}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 128px;vertical-align: inherit;">
                                    Kupon Açıklaması :
                                </td>
                                <td>
                                    <div class="form-group" style="margin-bottom: 0px;">
                                        {!! Form::text('month_description', null, ['class' => 'form-control']) !!}
                                    </div>
                                </td>
                                <td style="width: 128px;vertical-align: inherit;">
                                    Kupon Açıklaması :
                                </td>
                                <td>
                                    <div class="form-group" style="margin-bottom: 0px;">
                                        {!! Form::text('description', null, ['class' => 'form-control']) !!}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 100px;vertical-align: inherit;">
                                    Aylık İndirim :
                                </td>
                                <td>
                                    <div class="form-group" style="margin-bottom: 0px;">
                                        {!! Form::text('month_value', null, ['class' => 'form-control', 'style' => 'width: 40px;']) !!}
                                    </div>
                                </td>
                                <td style="vertical-align: inherit;">
                                    Cuma İndirimi :
                                </td>
                                <td>
                                    <div class="form-group" style="margin-bottom: 0px;">
                                        {!! Form::text('friday_value', null, ['class' => 'form-control', 'style' => 'width: 40px;']) !!}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4">
                                    {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control saveProdcut', 'style' => 'width:100px;float:right;' ]) !!}
                                </td>
                            </tr>
                        {!! Form::close() !!}
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop()

@section('footer')
@stop