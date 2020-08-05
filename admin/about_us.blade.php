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
            {!! Html::link('/admin/addAboutUsPeople' , 'Kişi Ekle', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
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
            <th>Sıra</th>
            <th>İsim</th>
            <th>Konum</th>
            <th>Image</th>
            <th>---------</th>
        </tr>
        @foreach($aboutUsPeople as $people)
            <tr>
                <td style="padding-left:21px;">{{$people->order}}</td>
                <td style="padding-left:21px;">{{$people->name}}</td>
                <td style="padding-left:21px;">{{$people->tittre}}</td>
                <td style="padding-left:21px;"><a href="{{$people->image_url}}" target="_blank">Görüntüle</a></td>
                <td style="width: 170px;">
                    {!! Html::link('/admin/aboutUs/' . $people->id , 'Değiştir', ['class' => 'btn btn-primary form-control', 'style' => 'width:70px;display: inline;', ]) !!}
                    {!! Form::open(['action' => 'AdminPanelController@deleteAboutUs', 'method' => 'POST', 'style' => 'display: inline;' ]) !!}
                    {!! Form::hidden('id', $people->id, ['class' => 'form-control']) !!}
                    {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control test' , 'style' => 'width:70px;display: inline;']) !!}
                    {!! Form::close() !!}
                </td>
            </tr>
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
    </script>
@stop