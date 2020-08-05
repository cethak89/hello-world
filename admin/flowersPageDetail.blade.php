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
        {!! Form::model($flowersPage , ['action' => 'AdminPanelController@updateFlowersCategory', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <div id="trPart"></div>
        <tr>
            <td>
                Aktif :
            </td>
            <td>
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('active', null, $flowersPage->active, ['style' => 'width:30px;height:30px;']) !!}
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Başlık :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('head', $flowersPage->head, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Açıklama :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('desc', $flowersPage->desc, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Url(Tükçe karakter içermemeli) :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('url_name', $flowersPage->url_name, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Meta Title :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('meta_tittle', $flowersPage->meta_tittle, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Meta Description :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('meta_desc', $flowersPage->meta_desc, ['class' => 'form-control']) !!}
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
                    <a href="{{ $flowersPage->image }}" target="_blank">
                        Image
                    </a>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Ürünler :
            </td>
            <td>
                <div>
                    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                        <tr>
                            <td>
                                <select  class="form-control select2"  data-placeholder="Ürün Seç" style="width: 400px;" id="customerId" name="products[]" multiple>
                                    @foreach($productList as $product)
                                        <option value="{{$product->id}}" {{$product->selected ? 'selected' : ''}}>{{$product->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                {!! Form::text('id', $flowersPage->id, ['class' => 'form-control hidden']) !!}
                <input style="width: 200px;margin-left: auto;margin-right: auto;display: block;margin-top: 10px;" class="btn btn-success form-control saveProdcut" id="saveProduct" type="submit" value="Kaydet">
            </td>
        </tr>
        {!! Form::close() !!}
    </table>

@stop()

@section('footer')
    <script>
        $(".select2").select2();
    </script>
@stop