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
                <h1>Bloom & Fresh Drop Down Banner Listesi</h1>
            </td>
            <td style="text-align: right;padding-right: 5px;">
                {!! Html::link('/admin/drop-down-banner' , 'Banner Oluştur', ['class' => 'btn btn-success', 'style' => 'width: 150px; vertical-align: middle;']) !!}
                {!! Html::link('/admin/drop-down-banner-product' , 'Banner Oluştur - Hazır Ürün', ['class' => 'btn btn-info', 'style' => 'width: 200px; vertical-align: middle;']) !!}
            </td>
        </tr>
    </table>
    <button id="testXls" class="btn btn-danger"  onClick ="$('tr').each(function() {$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(4)').remove();$(this).find('th:eq(5)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(4)').remove();$(this).find('td:eq(4)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Ürün Listesi',filename: 'Ürün Listesi'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>
    <table id="products-table" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th style="width: 60px;">Durum</th>
            <th> İlk Başlık </th>
            <th> İkinci Başlık </th>
            <th> Link </th>
            <th> Button Text </th>
            <th> Banner Fotoğrafı </th>
            <th style="width: 85px;">  </th>
        </tr>

        @foreach($banners as $banner)
            <tr>
                <td>
                    <div style="margin-bottom: 0px;margin-top: 0px;width: 20px;margin-left: auto;margin-right: auto;" class="checkbox">
                        <label>
                            {!! Form::checkbox('status', null, $banner->active, ['style' => 'width:20px;height:20px;', 'disabled' => 'true']) !!}
                        </label>
                    </div>
                </td>
                <td>{{$banner->first_header}}</td>
                <td>{{$banner->second_header}}</td>
                <td>
                    <a href="{{ $banner->link_url }}" target="_blank">
                        {{ $banner->link_url }}
                    </a>
                </td>
                <td>{{$banner->button_name}}</td>
                <td>
                    <a href="{{ $banner->image }}" target="_blank">
                        Image
                    </a>
                </td>
                <td style="width: 85px;">
                    {!! Form::open(['action' => 'AdminPanelController@deleteDropDownBanner', 'method' => 'post', 'id' => 'form_' . $banner->id ]) !!}
                    {!! Form::hidden('id', $banner->id, ['class' => 'form-control']) !!}
                    <div style="    margin-bottom: 0px;" class="form-group">
                        <a href="/admin/drop-down/{{$banner->id}}">
                            <i style="font-size: 25px;" class="fa fa-fw fa-cog"></i>
                        </a>
                        <a href="#" onclick="var x;if (confirm('Silmek istediğinize emin misiniz?') == true) {var tempId = '#form_{{$banner->id}}';$(tempId).submit();return true;} else {return false;}" class="test">
                            <i style="font-size: 25px;color:red" class="fa fa-fw fa-remove"></i>
                        </a>
                        {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control test hidden' , 'style' => 'width:100%;', 'id' => 'deleteButton']) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
    </table>

@stop()

@section('footer')
    <script>
    </script>
@stop