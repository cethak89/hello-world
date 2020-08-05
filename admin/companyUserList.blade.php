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
                <h1>Bloom & Fresh Kurumsal Şirket Kullanıcıları</h1>
            </td>
            <td>
            {!! Html::link('/admin/CompanyInfo/addUserPage' , 'Şirket Kullanıcısı Ekle', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
            </td>
        </tr>
    </table>
    <table id="example1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <thead>
            <th>ID</th>
            <th>Mail</th>
            <th>İsim</th>
            <th>Admin</th>
            <th>Şirket</th>
            <th>Sipariş Sayısı</th>
        </thead>
        @foreach($tempCompanyUsers as $coupon)
            <tr>
                <td style="padding-left:21px;">{{$coupon->id}}</td>
                <td style="padding-left:21px;">{{$coupon->email}}</td>
                <td style="padding-left:21px;">{{$coupon->name}} {{$coupon->surname}}</td>
                <td style="padding-left:21px;">{{$coupon->companyAdmin}}</td>
                <td style="padding-left:21px;">{{$coupon->company_name}}</td>
                <td style="padding-left:21px;">{{$coupon->saleCount}}</td>
            </tr>
        @endforeach
    </table>

@stop()

@section('footer')
@stop