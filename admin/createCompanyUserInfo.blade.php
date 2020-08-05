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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Şirket Kullanıcıs Ekleme Sayfası</h1><h1 class="col-lg-3 col-md-3">
    </h1>

    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                {!! Form::model(null , ['action' => 'AdminPanelController@createCompanyInfoUser', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr>
                    <td>
                        Şirket :
                    </td>
                    <td>
                        <div class="form-group">
                            <select name="company" class="btn btn-default dropdown-toggle">
                                @foreach($companyList as $tag)
                                    <option value="{{$tag->id}}">{{$tag->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Kullanıcı Maili :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('email', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Şirket Admini :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::checkbox('isAdmin', null, 1, ['style' => 'width:30px;height:30px;' , 'id' => 'oldActivationId']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <div class="form-group">
                        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                    </div>
                    </td>
                </tr>
                {!! Form::close() !!}
    </table>

@stop()

@section('footer')
@stop