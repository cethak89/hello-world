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
                <h1>Bloom & Fresh Şirket Siparişleri Teslimata Çıkarma</h1>
            </td>
            <td>
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
    {!! Form::model($deliveryList, ['action' => 'AdminPanelController@updateAllCompanyDeliveriesOneTime', 'files'=>true ,  'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps']) !!}
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th>Seç</th>
            <th>Gönderen</th>
            <th>Ürün</th>
            <th>Saat</th>
            <th>Semt</th>
        </tr>
        @foreach($deliveryList as $delivery)
            <tr>
                <td style="padding-left:21px;">
                {!! Form::checkbox('active_status_' . $delivery->id , null, 0, ['class' => 'checkSale' , 'style' => 'width:30px;height:30px;' , 'id' => 'temp_' . $delivery->id]) !!}
                </td>
                <td style="padding-left:21px;">{{$delivery->company_name}}</td>
                <td style="padding-left:21px;">{{$delivery->product_name}}</td>
                <td style="padding-left:21px;">{{$delivery->wantedDeliveryDate}}</td>
                <td style="padding-left:21px;">{{$delivery->delivery_location}}</td>
            </tr>
        @endforeach
    </table>
    {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control hidden' , 'id' => 'saveForm']) !!}
    {!! Form::close() !!}
    <button onclick="$('#myModal').modal('show');" class="btn btn-danger form-control">Teslimata Çıkış</button>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                        <tr>
                            <th>Gönderen</th>
                            <th>Ürün</th>
                            <th>Saat</th>
                            <th>Adres</th>
                            <th>Semt</th>
                        </tr>
                        @foreach($deliveryList as $delivery)
                            <tr id="modal_tr_{{$delivery->id}}" class="hidden">
                                <td style="padding-left:21px;">{{$delivery->company_name}}</td>
                                <td style="padding-left:21px;">{{$delivery->product_name}}</td>
                                <td style="padding-left:21px;">{{$delivery->wantedDeliveryDate}}</td>
                                <td style="padding-left:21px;">{{$delivery->receiver_address}}</td>
                                <td style="padding-left:21px;">{{$delivery->delivery_location}}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <button class="btn btn-success form-control" onclick="$('#saveForm').click();" data-dismiss="modal" >Teslimata Çıkar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop()

@section('footer')

    <script>
        function selectDiv(event){
            var tempID = $(event).attr('id').split("_")[2];
            var tempSelector = "#status_" + tempID;
            if($(tempSelector).prop("checked")){
                $(tempSelector).prop("checked", false);
            }
            else{
                $(tempSelector).prop("checked", true);
            }
        }

        $(".checkSale").change(function() {
            var tempID = $(this).attr('id').split("_")[1];
            var tempID = '#modal_tr_' +  tempID;
            if(this.checked) {
                $(tempID).removeClass('hidden');
            }
            else{
                $(tempID).addClass('hidden');
            }
        });




    </script>

@stop