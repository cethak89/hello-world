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
    <h1 class="col-lg-9 col-md-9">Admin Ekleme</h1>
    <h1 class="col-lg-3 col-md-3"></h1>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        {!! Form::model(null , ['action' => 'AdminPanelController@addNewAdminUser', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
            <tr>
                <td>
                    Email
                </td>
                <td>
                    <input class="form-control" name="email">
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="form-group">
                        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control saveProdcut' , 'id' => 'saveProduct']) !!}
                    </div>
                </td>
            </tr>
        {!! Form::close() !!}
    </table>
@stop()
@section('footer')
    <script>
        function checkIsOk(e){
            if (confirm( "Silmek istediğinize emin misiniz?"  ) == true) {
                window.location.href = "/admin/delete-admin-user/" + e;
                //return true;
            }
            else {
                return false;
            }
        }
    </script>
    @stop