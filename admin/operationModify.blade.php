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
    <h1 style="width: 100%;" class="col-lg-9 col-md-9">Bloom & Fresh Üst Menü Elemanları</h1>
    {!! Form::model(null , ['action' => 'newFunctions@updateOperationPerson', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;max-width: 500px;">
        <tr>
            <th>Sıra</th>
            <th>İsim</th>
            <th>Aktif</th>
        </tr>
        @foreach($operationPerson as $person)
            <tr>
                <td style="width: 60px;">
                    <input style="text-align: center;" class="form-control" id="{{$person->id}}" type="text" value="{{$person->position}}" onchange="updateData(this, 'position')">
                </td>
                <td>
                    <input class="form-control" id="{{$person->id}}" type="text" value="{{$person->name}}" onchange="updateData(this, 'name')">
                </td>
                <td style="width: 60px;">
                    <input style="width:30px;height:30px;margin-left: auto;margin-right: auto;display: -webkit-box;" id="{{$person->id}}" @if( $person->active ) checked="checked" @endif   onchange="updateData(this, 'active')" type="checkbox">
                </td>
            </tr>
        @endforeach
    </table>
    {!! Form::close() !!}

@stop()

@section('footer')
    <script>

        function updateData(e, type) {

            var operationId = $(e).attr('id');

            if( type ==  'active'){
                var tempValue = $(e).prop('checked');
            }
            else{
                var tempValue = $(e).val();
            }

            $.ajax({
                url: '/admin/update_operation_person',
                method: "POST",
                data: {
                    operationId : operationId,
                    value : tempValue,
                    data : type
                },
                success: function(returnData) {
                    console.log(returnData);
                }
            });
        }


    </script>
@stop