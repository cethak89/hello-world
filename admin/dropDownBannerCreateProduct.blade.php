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
        {!! Form::model(null , ['action' => 'AdminPanelController@insertDropDownBannerProduct', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <div id="trPart"></div>
        <tr>
            <td>
                Aktif :
            </td>
            <td>
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('active', null, 0, ['style' => 'width:30px;height:30px;']) !!}
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Ürün :
            </td>
            <td>
                <div class="form-group">
                    <select  style="width: 220px;" name="product" class="btn btn-default dropdown-toggle selectClass">
                        @foreach( $activeFlowers as $flower )
                            <option value="{{$flower->id}}">
                                {{$flower->name}}
                            </option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <input style="margin-left: auto;margin-right: auto;display: block;width: 200px;float: left;" class="btn btn-success form-control saveProdcut" id="saveProduct" type="submit" value="Kaydet">
            </td>
        </tr>
        {!! Form::close() !!}
    </table>

@stop()

@section('footer')
    <script>

        $(".selectClass").select2();

    </script>
@stop