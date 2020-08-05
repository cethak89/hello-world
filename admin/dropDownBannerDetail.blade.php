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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Drop-Down Banner Oluşturma Sayfası</h1>

    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        {!! Form::model($banner , ['action' => 'AdminPanelController@updateDropDownBanner', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <div id="trPart"></div>
        <tr>
            <td>
                Aktif :
            </td>
            <td>
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('active', null, $banner->active, ['style' => 'width:30px;height:30px;']) !!}
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                İlk Başlık :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('first_header', $banner->first_header, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                İkinci Başlık :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('second_header', $banner->second_header, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Button Text :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('button_name', $banner->button_name, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Link :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('link_url', $banner->link_url, ['class' => 'form-control']) !!}
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
                    <a href="{{ $banner->image }}" target="_blank">
                        Image
                    </a>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                {!! Form::text('id', $banner->id, ['class' => 'form-control hidden']) !!}
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