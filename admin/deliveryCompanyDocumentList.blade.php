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
            <h1>Bloom & Fresh Şirket Sipariş Döküman Listesi</h1>
        </td>
        <td>
            <a href="/companyDocument/yesterday" class="btn btn-primary" style="width:100%; vertical-align: middle;">Dün</a>
        </td>
        <td>
            <a href="/companyDocument" class="btn btn-primary" style="width:100%; vertical-align: middle;">Bugün</a>
        </td>
        <td>
            <a href="/companyDocument/tomorrow" class="btn btn-primary" style="width:100%; vertical-align: middle;">Yarın</a>
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
    {!! Form::model($deliveryList, ['url' => '/companyPrint', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
            <tr>
                <th>Çiçek Adı</th>
                <th>Teslim Tarihi</th>
                <th>Semt</th>
                <th>Adres</th>
                <th>Müşteri Adı Soyadı</th>
                <th>Müşteri Tel</th>
                <th></th>
            </tr>
            @foreach($deliveryList as $delivery)
                <tr>
                    <td style="padding-left:21px;">{{$delivery->product_name}}</td>
                    <td style="padding-left:21px;">{{$delivery->dateInfo}}</td>
                    <td style="padding-left:21px;">{{$delivery->delivery_location}}</td>
                    <td style="padding-left:21px;">{{$delivery->receiver_address}}</td>
                    <td style="padding-left:21px;">{{$delivery->receiver}}</td>
                    <td style="padding-left:21px;">{{$delivery->receiver_mobile}}</td>
                    <td style="padding-left:21px;">
                        {!! Form::checkbox('selected_' . $delivery->id , null, null, [ 'style' => 'width :30px;height:30px']) !!}
                    </td>
                </tr>
            @endforeach
        </table>
        {!! Form::submit('Yazdırma Sayfasına Git', ['class' => 'btn btn-success form-control' , 'id' => 'submitForm' ]) !!}
    {!! Form::close() !!}
@stop()

@section('footer')
    <script>
        function setOrderParameter(paremeter , upOrDown){
            $('input[name=orderParameter]').val(paremeter);
            $('input[name=upOrDown]').val(upOrDown);
            console.log( $('input[name=orderParameter]').val());
        }
        //$('html').click(function() {
        //    window.location='/admin/deliveries';
        //});

        $('#products-table').click(function(event){
            event.stopPropagation();
        });

        // go to anchor
    </script>
@stop