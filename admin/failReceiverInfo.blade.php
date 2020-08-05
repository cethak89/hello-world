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
            <h1>Bloom & Fresh Sepet Terk Listesi</h1>
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
            <th>Oluşturulma Tarih</th>
            <th>Ip</th>
            <th>Müşteri Numarası</th>
            <th>Müşteri Ad Soyad</th>
            <th>Alıcı Ad Soyad</th>
            <th>Ürün Adı</th>
            <th>Adres</th>
            <th>Şehir</th>
            <th>İstenen teslim tarihi</th>
            <th>Telefon No:</th>
            <th></th>
            {!! Form::close() !!}
        </tr>
        @foreach($customers as $customer)
            <tr  id="tr_{{$customer->id}}">
                <td style="padding-left:21px;">{{$customer->created_at}}</td>
                <td style="padding-left:21px;">{{$customer->register_ip}}</td>
                <td style="padding-left:21px;">{{$customer->customer_id}}</td>
                <td style="padding-left:21px;">{{$customer->customer_name}} {{$customer->customer_surname}}</td>
                <td style="padding-left:21px;">{{$customer->name}} {{$customer->surname}}</td>
                <td style="padding-left:21px;">{{$customer->product_name}}</td>
                <td style="padding-left:21px;">{{$customer->address}}</td>
                <td style="padding-left:21px;">{{$customer->city}}</td>
                <td style="padding-left:21px;">{{$customer->delivery_date}}</td>
                <td style="padding-left:21px;">{{$customer->phone}}</td>
                <td style="padding-left:21px;">
                @if(  Auth::user()->user_group_id == 1  )
                    {!! Form::open(['action' => 'AdminPanelController@deleteLogReceiver', 'method' => 'POST' ]) !!}
                    {!! Form::hidden('id', $customer->id, ['class' => 'form-control']) !!}
                    {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control checkValid', 'style' => 'width:100%;']) !!}
                    {!! Form::close() !!}
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

@stop()

@section('footer')
<script>

        $('.checkValid').click(function() {
            var x;
                if (confirm("Silmek istediğinize emin misiniz?") == true) {
                    return true;
                } else {
                    return false;
                }
        });

        function setOrderParameter(paremeter , upOrDown){
            $('input[name=orderParameter]').val(paremeter);
            $('input[name=upOrDown]').val(upOrDown);
            console.log( $('input[name=orderParameter]').val());
        }
</script>
@stop