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

    <table class="table table-hover">
    <tr>
        <td>
            <h1>Bloom & Fresh Banner Listesi</h1>
        </td>
        <td>
            {!! Html::link('/admin/createMobileBanner' , 'Mobil Baneri Oluştur', ['class' => 'btn btn-info', 'style' => 'width:100%; vertical-align: middle;']) !!}
            {!! Html::link('/admin/createBanner' , 'Baner Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
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
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th>Aktif</th>
            <th>Sıra</th>
            <th>ID</th>
            <th>Başlık</th>
            <th>Metin Rengi</th>
            <th>Arka Plan Rengi</th>
            <th>Link URL</th>
            <th>Resim</th>
            <th>---------</th>
        </tr>
        @foreach($bannerList as $banner)
            @if($id == $banner->id )
                {!! Form::model($banner, ['action' => 'AdminPanelController@updateBanners', 'files'=>true ,  'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps']) !!}
                <tr id="row-id-{{$banner->id}}">
                    <td style="padding-left:21px;">
                        <div class="form-group">
                            {!! Form::checkbox('active', null, $banner->active, ['style' => 'width:30px;height:30px']) !!}
                        </div>
                    </td>
                    <td style="padding-left:21px;">
                        <div class="form-group">
                            {!! Form::text('order_number', $banner->order_number, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td style="padding-left:21px;">
                        {{$banner->id}}
                    </td>
                    <td style="padding-left:21px;">
                        <div class="form-group">
                            {!! Form::text('header', $banner->header, ['class' => 'form-control']) !!}
                            @foreach($descriptionList as $lang)
                                {!! Form::text('header' . $lang->lang_id , $lang->content, ['class' => 'form-control ' . $lang->lang_id  , 'placeholder' => $lang->lang_id ]) !!}
                            @endforeach
                        </div>
                    </td>
                    <td style="padding-left:21px;">
                        <div class="form-group">
                            {!! Form::text('font_color', $banner->font_color, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td style="padding-left:21px;">
                        <div class="form-group">
                            {!! Form::text('background_color', $banner->background_color, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td style="padding-left:21px;">
                        <div class="form-group">
                            {!! Form::text('url', $banner->url, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td style="padding-left:21px;">
                        <div class="form-group">
                            {!! Form::file('img_url') !!}
                        </div>
                    </td>
                    <td>
                        {!! Form::hidden('id', $banner->id, ['class' => 'form-control']) !!}
                        {!! Form::hidden('mobile', $banner->mobile, ['class' => 'form-control']) !!}
                        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                    </td>
                </tr>
                {!! Form::close() !!}
            @else
                <tr>
                    <td style="padding-left:21px;">{!! Form::checkbox('active', null, $banner->active, ['style' => 'width:30px;height:30px', 'disabled' => 'true']) !!}</td>
                    <td style="padding-left:21px;">
                        @if($banner->mobile == 1)
                            Mobil
                        @else
                            {{$banner->order_number}}
                        @endif
                    </td>
                    <td style="padding-left:21px;">{{$banner->id}}</td>
                    <td style="padding-left:21px;">{{$banner->header}}</td>
                    <td style="padding-left:21px;">{{$banner->font_color}}</td>
                    <td style="padding-left:21px;">{{$banner->background_color}}</td>
                    <td style="padding-left:21px;">{{$banner->url}}</td>
                    <td style="padding-left:21px;"><a href="{{$banner->img_url}}" target="_blank">Görüntüle</a></td>
                    <td>
                        <div class="form-group">
                            {!! Html::link('/admin/banner/' . $banner->id , 'Değiştir', ['class' => 'btn btn-primary form-control']) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::open(['action' => 'AdminPanelController@deleteBanner', 'method' => 'POST' ]) !!}
                            {!! Form::hidden('id', $banner->id, ['class' => 'form-control']) !!}
                            {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control test' , 'style' => 'width:100%;']) !!}
                            {!! Form::close() !!}
                        </div>
                    </td>
                </tr>
            @endif
        @endforeach
    </table>
@stop()

@section('footer')
    <script>
        $('.test').click(function() {
            var x;
                if (confirm("Silmek istediğinize emin misiniz?") == true) {
                    return true;
                } else {
                    return false;
                }
        });
        if( {{ $id > 0 ? 'true' : 'false' }} )
        {
            window.scrollTo(0, document.getElementById('row-id-{{ $id }}').offsetTop);
        }
    </script>
@stop