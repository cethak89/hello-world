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
                <h1>Bloom & Fresh Çiçek Kategorileri(Özel)</h1>
            </td>
            <td>
                {!! Html::link('/admin/create-flowers-page' , 'Kategori Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
            </td>
        </tr>
    </table>
    <button id="testXls" class="btn btn-danger"  onClick ="$('tr').each(function() {$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(4)').remove();$(this).find('th:eq(5)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(4)').remove();$(this).find('td:eq(4)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Ürün Listesi',filename: 'Ürün Listesi'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>
    <table id="products-table" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th style="width: 60px;">Durum</th>
            <th> Başlık</th>
            <th> Açıklama </th>
            <th> Url </th>
            <th> Meta Title </th>
            <th> Meta Description </th>
            <th> Image </th>
            <th style="width: 85px;">  </th>
        </tr>

        @foreach($flowersPage as $page)
            <tr>
                <td>
                    <div style="margin-bottom: 0px;margin-top: 0px;width: 20px;margin-left: auto;margin-right: auto;" class="checkbox">
                        <label>
                            {!! Form::checkbox('status', null, $page->active, ['style' => 'width:20px;height:20px;', 'disabled' => 'true']) !!}
                        </label>
                    </div>
                </td>
                <td>{{$page->head}}</td>
                <td>{{$page->desc}}</td>
                <td>{{$page->url_name}}</td>
                <td>{{$page->meta_tittle}}</td>
                <td>{{$page->meta_desc}}</td>
                <td>
                    <a href="{{ $page->image }}" target="_blank">
                        Image
                    </a>
                </td>
                <td style="width: 85px;">
                    {!! Form::hidden('id', $page->id, ['class' => 'form-control']) !!}
                    <div style="    margin-bottom: 0px;" class="form-group">
                        <a href="/admin/flowers-page/{{$page->id}}">
                            <i style="font-size: 25px;" class="fa fa-fw fa-cog"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
    </table>

@stop()

@section('footer')
    <script>
    </script>
@stop