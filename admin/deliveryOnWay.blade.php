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
                <h1>Bloom & Fresh Teslimata Çıkarma</h1>
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
    {!! Form::model($deliveryList, ['action' => 'AdminPanelController@updateAllDeliveriesOneTime', 'files'=>true ,  'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps']) !!}
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th>Seç</th>
            <th>Gönderen</th>
            <th>Ürün</th>
            <th>Saat</th>
            <th>Kıta</th>
            <th>Semt</th>
        </tr>
        @foreach($deliveryList as $delivery)
            <tr style="
            @if($delivery->studio)
                background-color: pink;
            @else
                @if($delivery->continent_id == "Avrupa")
                background-color: #E6EBF3;
                @else
                    background-color: #E9F5E7;
                @endif
            @endif">
                <td style="padding-left:21px;">
                {!! Form::checkbox('active_status_' . $delivery->id , null, 0, ['class' => 'checkSale' , 'style' => 'height: 30px;width: 30px;' , 'id' => 'temp_' . $delivery->id]) !!}
                </td>
                <td style="padding-left:21px;">{{$delivery->customer_name}} {{$delivery->customer_surname}}</td>
                <td style="padding-left:21px;">{{$delivery->products}}</td>
                <td style="padding-left:21px;">{{$delivery->wantedDeliveryDate}}</td>
                <td style="padding-left:21px;">{{$delivery->continent_id}}</td>
                <td style="padding-left:21px;">{{$delivery->district}}</td>
            </tr>
        @endforeach
    </table>
    @foreach($operationList as $tag)
        <input id="status2_{{$tag->id}}" name="status2_{{$tag->id}}" onclick="selectDiv2(this)" class="allCheckBox hidden" type="checkbox" aria-label="...">
    @endforeach
    {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control hidden' , 'id' => 'saveForm']) !!}
    {!! Form::close() !!}
    <button onclick="$('#myModal').modal('show');" class="btn btn-danger form-control">Teslimata Çıkış</button>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div style="min-height: 500px;" class="modal-content">
                <div class="modal-body">
                    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                        <tr>
                            <th>Gönderen</th>
                            <th>Ürün</th>
                            <th>Saat</th>
                            <th>Kıta</th>
                            <th>Adres</th>
                            <th>Semt</th>
                        </tr>
                        @foreach($deliveryList as $delivery)
                            <tr id="modal_tr_{{$delivery->id}}" class="hidden" style="
                            @if($delivery->studio)
                            background-color: pink;
                            @else
                            @if($delivery->continent_id == "Avrupa") background-color: #E6EBF3; @else background-color: #E9F5E7; @endif
                            @endif
                            ">
                                <td style="padding-left:21px;">{{$delivery->customer_name}} {{$delivery->customer_surname}}</td>
                                <td style="padding-left:21px;">{{$delivery->products}}</td>
                                <td style="padding-left:21px;">{{$delivery->wantedDeliveryDate}}</td>
                                <td style="padding-left:21px;">{{$delivery->continent_id}}</td>
                                <td style="padding-left:21px;">{{$delivery->receiver_address}}</td>
                                <td style="padding-left:21px;">{{$delivery->district}}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                <div class="col-lg-12">
                    @foreach($operationList as $tag)
                        <div style="padding-right: 0px;" class="col-lg-2 col-md-2 col-sm-4 col-xs-6">
                            <span style="padding-top: 0px;padding-bottom: 0px;" class="input-group-addon">
                                <input style="width: 25px;height: 25px;" id="status_{{$tag->id}}" name="status_{{$tag->id}}" onclick="selectDiv2(this)" class="allCheckBox" type="checkbox" aria-label="...">
                            </span>
                            <label style="height: 47px;padding-left: 0px;text-align: center;" id="label_status_{{$tag->id}}" onclick="selectDiv(this)" class="form-control" aria-label="...">
                                {{$tag->name}}
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" class="modal-footer">
                    <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <button disabled class="btn btn-success form-control" id="testD" onclick="$('#saveForm').click();" data-dismiss="modal" >Teslimata Çıkar</button>
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
            var tempSelector2 = "#status2_" + tempID;
            if($(tempSelector).prop("checked")){
                $(tempSelector).prop("checked", false);
                $(tempSelector2).prop("checked", false);
                $('#testD').attr('disabled' , true);
            }
            else{
                $('.allCheckBox').prop("checked", false);
                $(tempSelector).prop("checked", true);
                $(tempSelector2).prop("checked", true);
                $('#testD').attr('disabled' , false);
            }
        }

        function selectDiv2(event){
            var tempID = $(event).attr('id').split("_")[1];
            var tempSelector = "#status_" + tempID;
            var tempSelector2 = "#status2_" + tempID;
            if($(tempSelector).prop("checked")){
                $('.allCheckBox').prop("checked", false);
                $(tempSelector).prop("checked", true);
                $(tempSelector2).prop("checked", true);
                $('#testD').attr('disabled' , false);
            }
            else{
                $(tempSelector2).prop("checked", false);
                $('#testD').attr('disabled' , true);
                //$(tempSelector).prop("checked", true);
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