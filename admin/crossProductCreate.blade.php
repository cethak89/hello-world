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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Cross-Sell Ürün Oluşturma Sayfası</h1>
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        {!! Form::model(null , ['action' => 'AdminPanelController@insertCrossSellProduct', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <div id="trPart"></div>
        <tr>
            <td>
                Durum :
            </td>
            <td>
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('status', null, 0, ['style' => 'width:30px;height:30px;']) !!}
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Ürün Adı :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('name', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Fiyat :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('price', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Açıklama :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('desc', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Sıralama :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('sort_number', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Fotoğraf :
            </td>
            <td>
                <div class="form-group">
                    <input name="image" type="file" accept="image/*" id="myFileMain" size="1000000000">
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="form-group">
                    {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control' , 'id' => 'saveProduct']) !!}
                </div>
            </td>
        </tr>
        {!! Form::close() !!}
    </table>

@stop()

@section('footer')
    <script>


    </script>
@stop