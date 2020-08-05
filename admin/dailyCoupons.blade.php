@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
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
                <h1 style="margin-left: 15px;">Bloom & Fresh Günlük Kupon Listesi</h1>
            </td>
            <td style="padding-right: 15px;">
                {!! Html::link('/admin/create/dailyCoupon' , 'Kupon Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
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
            <th>Durum</th>
            <th>Geçerlilik Aralığı</th>
            <th>Kod</th>
            <th>Başlık</th>
            <th>Açıklama</th>
            <th>İndirim Türü</th>
            <th>Tutar</th>
            <th>Kullanılan/Toplam</th>
            <th></th>
        </tr>

        @foreach($coupons as $coupon)
            <tr>
                <td style="width: 60px;text-align: center;">
                    @if( $coupon->active )
                        <i style="color: green;font-size: 19px;" class="fa fa-fw fa-check"></i>
                    @endif
                </td>

                <td>{{$coupon->start_date}} - {{$coupon->end_date}}</td>
                <td>{{$coupon->code}}</td>
                <td>{{$coupon->name}}</td>
                <td>{{$coupon->description}}</td>
                <td>{{$coupon->type == 1  ? 'TL' : 'Yüzde'}}</td>
                <td>{{$coupon->value}}</td>
                <td>{{$coupon->used}} / {{$coupon->total}}</td>
                <td style="width: 40px;">
                    <div class="">
                        <a style="font-size: 25px;color:red" href="/admin/dailyCoupon/delete/{{$coupon->id}}" class="fa fa-fw fa-remove"></a>
                    </div>
                </td>
            </tr>
        @endforeach
    </table>

@stop()

@section('footer')
    <script>

        function setOrderParameter(paremeter, upOrDown) {
            $('input[name=orderParameter]').val(paremeter);
            $('input[name=upOrDown]').val(upOrDown);
            console.log($('input[name=orderParameter]').val());
        }

        $('#products-table').click(function (event) {
            event.stopPropagation();
        });
    </script>
@stop