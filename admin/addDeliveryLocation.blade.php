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
    <h1 class="col-lg-9 col-md-9">Dağıtım Bölgeleri</h1>
    <h1 class="col-lg-3 col-md-3"><a style="width: 103px;float: right;" class="btn btn-block btn-success" href="/admin/add-delivery-location">Bölge Ekle</a></h1>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
            {!! Form::model(null , ['action' => 'AdminPanelController@insertDeliveryLocation', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
            <tr class="trClass">
                <td>
                    Bölge
                </td>
                <td>
                    <input name="district" value="">
                </td>
            </tr>
            <tr class="trClass">
                <td>
                    İlçe
                </td>
                <td>
                    <input name="small_city" value="">
                </td>
            </tr>
            <tr class="trClass">
                <td>
                    Kıta
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="width: 100px;" name="continent_id" class="btn btn-default dropdown-toggle">
                            @foreach( $tempAreaList as $area )
                                <option value="{{$area->continent_id}}">
                                    @if( $area->continent_id == 'Avrupa-2' ) Oyaka @else {{$area->continent_id}} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </td>
            </tr>
            <tr class="trClass">
                <td>
                    Aktiflik
                </td>
                <td>
                    {!! Form::checkbox( 'active', null, 0, [ 'style' => 'margin-top: 0px;height:30px;width:30px;']) !!}
                </td>
            </tr>
            <tr class=" trClass">
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
        $(".select2").select2();
        function selectProduct(salesId){
            var tempTr = '.' + salesId;
            $('.trClass').addClass('hidden');
            $(tempTr).removeClass('hidden');
        }

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