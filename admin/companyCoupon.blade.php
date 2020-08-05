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
    <h1>Bloom & Fresh Şirket Kuponu Oluşturma Sayfası</h1>

    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                {!! Form::model(null , ['action' => 'AdminPanelController@storeCompanyCoupon', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr>
                    <td>
                        Kupon Adı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('name', '%20 {Şirket Adı} İndirimi', ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Kupon Grubu :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('group', '', ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Kupon Açıklaması :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('description', 'Bloom and Fresh {Şirket Adı} özel indirimi', ['class' => 'form-control']) !!}
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
                        Geçerlilik :
                    </td>
                    <td>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('valid', null, 1, ['style' => 'width:30px;height:30px;']) !!}
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Mail Uzantısı :
                    </td>
                    <td>
                        <div class="form-group">
                            <label>
                                {!! Form::text('mail', null , ['class' => 'form-control']) !!}
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Kupon Değeri :
                    </td>
                    <td>
                        <div class="form-group">
                            <select name="value">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option  selected  value="20">20</option>
                                <option value="25">25</option>
                                <option value="30">30</option>
                            </select>
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
                <div class="form-group">
                {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                </div>
                {!! Form::close() !!}
    </table>

@stop()