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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Çiçek Kategori(özel) Ekleme Sayfası</h1>

    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        {!! Form::model(null , ['action' => 'AdminPanelController@insertFlowersCategory', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <div id="trPart"></div>
        <tr>
            <td>
                Başlık :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('head', null, ['class' => 'form-control']) !!}
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
                Url(Tükçe karakter içermemeli) :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('url_name', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Meta Title :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('meta_tittle', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Meta Description :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('meta_desc', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Banner Fotoğrafı :
            </td>
            <td>
                <div class="form-group">
                    <input name="image" type="file" accept="image/*" id="myFileMain" size="1000000000">
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <input style="width: 200px;margin-left: auto;margin-right: auto;display: block;margin-top: 10px;" class="btn btn-success form-control saveProdcut" id="saveProduct" type="submit" value="Kaydet">
            </td>
        </tr>
        {!! Form::close() !!}
    </table>

@stop()

@section('footer')
    <script>
    </script>
@stop