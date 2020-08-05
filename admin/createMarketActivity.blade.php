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
                <h1>Bloom & Fresh Kupon Oluşturma Sayfası</h1>
            </td>
        </tr>
    </table>

    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                {!! Form::model(null , ['action' => 'AdminPanelController@insertCoupon', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr>
                    <td>
                        Kupon Adı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('name', '%15 Yılbaşı Özel İndirimi', ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Kupon Açıklaması :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('description', '%15 Yılbaşı Özel İndirimi', ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Kupon Tipi:
                    </td>
                    <td>
                        <div class="form-group">
                            <select name="type">
                                    <option value="1">TL</option>
                                    <option selected value="2">Yüzde</option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Kupon Değeri :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('value', 15, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Geçerlilik :
                    </td>
                    <td>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('valid', null, 1, ['style' => 'width: 30px;height: 30px;']) !!}
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        İF Kuponu :
                    </td>
                    <td>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('long_term', null, 1, ['style' => 'width: 30px;height: 30px;']) !!}
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Mail(İF ventures için) :
                    </td>
                    <td>
                        <div class="form-group">
                            <label>
                                {!! Form::text('email', null , ['class' => 'form-control']) !!}
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Geçirli olduğu süre :
                    </td>
                    <td>
                        <div class="form-group">
                              <input type="datetime-local" value={{$now}}  name="expiredDate">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Kupon Adeti :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('count', 1, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <div class="form-group">
                {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                </div>
                {!! Form::close() !!}
    </table>

@stop()