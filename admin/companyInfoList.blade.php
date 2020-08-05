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
                <h1>Bloom & Fresh Kurumsal Şirketler Listesi</h1>
            </td>
            <td>
            {!! Html::link('/admin/CompanyInfo/addPage' , 'Şirket Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
            </td>
        </tr>
    </table>
    <table id="example1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <thead>
            <th>İsim</th>
            <th>Açıklama</th>
            <th>Kullanıcı Sayısı</th>
            <th>Sipariş Sayısı</th>
            <th>Kurumsal Çiçek</th>
        </thead>
        @foreach($companyList as $coupon)
                <tr>
                    <td style="padding-left:21px;">{{$coupon->name}}</td>
                    <td style="padding-left:21px;">{{$coupon->description}}</td>
                    <td style="padding-left:21px;">{{$coupon->userCount}}</td>
                    <td style="padding-left:21px;">{{$coupon->saleCount}}</td>
                    <td>
                        {!! Form::checkbox( $coupon->id . '_active' , null, $coupon->flower_status, [ 'class' => 'changeSales', 'style' => 'width:30px;height:30px;margin-left: auto;margin-right: auto;display: -webkit-box;' ]) !!}
                    </td>
                </tr>
        @endforeach
    </table>

@stop()

@section('footer')
    <script>

        $(".changeSales").change(function() {
            if(this.checked) {
                console.log(this.name.split('_')[0]);
                $.ajax({
                    url: '/admin/update-company-list-flower',
                    method: "POST",
                    data: {
                        id : this.name.split('_')[0],
                        value : 1
                    },
                    success: function(data) {
                        console.log('Başarılı');
                    }
                });
                //$('#status_all').attr('checked', false);
            }
            else{
                $.ajax({
                    url: '/admin/update-company-list-flower',
                    method: "POST",
                    data: {
                        id : this.name.split('_')[0],
                        value : 0
                    },
                    success: function(data) {
                        console.log('Başarılı');
                    }
                });
            }
        });

    </script>
@stop