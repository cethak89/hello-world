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
    <style>
        .formatDate {
            position: relative;
            width: 150px; height: 20px;
            color: white;
        }

        .formatDate:before {
            position: absolute;
            top: 3px; left: 3px;
            content: attr(data-date);
            display: inline-block;
            color: black;
        }

        .formatDate::-webkit-datetime-edit, input::-webkit-inner-spin-button, input::-webkit-clear-button {
            display: none;
        }

        .formatDate::-webkit-calendar-picker-indicator {
            position: absolute;
            top: 3px;
            right: 0;
            color: black;
            opacity: 1;
        }
    </style>
@section('content')
    <table class="table table-hover">
        <tr>
            <td>
                <h1>Bloom & Fresh Prime Kullanıcılar</h1>
            </td>
            <td style="vertical-align: middle;">
                <a href="/admin/addPrimeCustomers" class="btn btn-success pull-right" >Prime Kullanıcı Ekle</a>
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
    <div style="overflow-x: scroll;">
        <table id="example1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <thead>
            <th>Üyelik Tarihi</th>
            <th>Son Giriş</th>
            <th>Adı Soyadı</th>
            <th>Mail</th>
            <th>Mobile</th>
            <th>Satış Sayısı</th>
            <th>Üye No</th>
            <th>Tür</th>
            <th>Kayıtlı Kişi</th>
            <th></th>
        </thead>
        @foreach($customers as $customer)
            <tr id="tr_{{$customer->id}}">
                <td>{{$customer->created_at}}</td>
                <td>
                    {{$customer->updated_at}}
                </td>
                <td id="name_{{$customer->id}}">{{$customer->name}} {{$customer->surname}}</td>
                <td>
                    {{$customer->email}}
                </td>
                <td>{{$customer->mobile}}</td>
                <td style="cursor: pointer;" @if($customer->salesNumber > 0)onclick="initiatePopup({{$customer->id}});"@endif>{{$customer->salesNumber}}</td>
                <td>{{$customer->user_id}}</td>
                <td>
                {{$customer->status}}
                </td>
                <td @if($customer->contactNumber > 0)onclick="initiateContactPopup({{$customer->id}});"@endif style="cursor: pointer;">{{$customer->contactNumber}}</td>
                <td>
                    @if(  Auth::user()->user_group_id == 1  )
                    <button class="btn btn-danger form-control checkValid" style="width:100%;" id="row-id-{{$customer->id}}"  onclick="removeCustomers(this)">Sil</button>
                    @endif
                </td>
                <div class="modal fade" id="contactList" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                    <div class="modal-dialog" role="document">
                        <div id="contactDetailModel" class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="myModalLabel"></h4>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-success col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Tamam</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="addDeliveryNote123" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                    <div class="modal-dialog" role="document">
                        <div id="salesDetailModel" class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="myModalLabel">{{$customer->name}} {{$customer->surname}}</h4>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-success col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Tamam</button>
                            </div>
                        </div>
                    </div>
                </div>
            </tr>
        @endforeach
    </table>
    </div>
    <div class="modal fade" id="addUser" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div id="contactDetailModel" class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Kullanıcı Ekle</h4>
                </div>
                <div style="margin-bottom: 20px;" class="modal-body">
                    <p class="col-lg-3 col-md-3 col-sm-3 col-xs-3">Kullanıcı Mail : </p>
                    <input class="col-lg-9 col-md-9 col-sm-9 col-xs-9" type="text" name="userMail">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Tamam</button>
                </div>
            </div>
        </div>
    </div>
@stop()

@section('footer')
<script>
    function initiatePopup($id){
        $('#addDeliveryNote123').modal('show');
        $.ajax({
            url: '/admin/getCustomerSales/' + $id,
            method: "GET",
            success: function(data) {
                $('#salesDetailModel').empty();
                if(data.length > 0){
                    $('#salesDetailModel').append('<div class="modal-header">'
                    + '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                    + '<h4 class="modal-title" id="myModalLabel">' + data[0].sender_name + ' ' + data[0].sender_surname + '</h4>'
                    + '</div>');
                }
                $.each(data, function( index, value ) {
                    $('#salesDetailModel').append(
                    '<a style="color: black;" target="_blank" href="/admin/deliveries/detail/' + value.id + '"><div style="border-bottom-style: dotted;padding-bottom: 35px;border-bottom-width: 1px;" class="modal-body">'
                    + '<p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">' + value.created_at  + '</p>'
                    + '<p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">' + value.products  + '</p>'
                    + '<p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">' + value.district  + '</p>'
                    + '<p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">' + value.name + value.surname + '</p>'
                    + '</div></a>' );
                });
                $('#salesDetailModel').append('<div class="modal-footer">'
                                            + '<button type="button" class="btn btn-success col-lg-12 col-md-12 col-sm-12 col-xs-12" data-dismiss="modal">Tamam</button>'
                                            + '</div>');
            },
            error : function(data){
            }
        });
    }

    function initiateContactPopup($id){
        $('#contactList').modal('show');
        $.ajax({
            url: '/admin/getCustomerContact/' + $id,
            method: "GET",
            success: function(data) {
                $('#contactDetailModel').empty();
                $.each(data, function( index, value ) {
                    $('#contactDetailModel').append(
                    '<div style="border-bottom-style: groove;padding-bottom: 35px;border-bottom-width: 1px;border-bottom-color: blanchedalmond;" class="modal-body">'
                    + '<p style="text-align: center;padding-left: 0px;" class="col-lg-6 col-md-6 col-sm-6 col-xs-6">' + value.created_at  + '</p>'
                    + '<p style="text-align: center;padding-left: 0px;" class="col-lg-6 col-md-6 col-sm-6 col-xs-6">' + value.name + ' ' + value.surname + '</p>'
                    + '</div>' );
                });
                $('#contactDetailModel').append('<div class="modal-footer">'
                                            + '<button type="button" class="btn btn-success col-lg-12 col-md-12 col-sm-12 col-xs-12" data-dismiss="modal">Tamam</button>'
                                            + '</div>');
            },
            error : function(data){
            }
        });
    }

    function removeCustomers(event){
        var tempId = $(event).attr('id').split("-")[2];
        var tempName = '#name_' + tempId;
        if (confirm( $(tempName).text() + " kullanıcısını prime kullanıcılarından çıkarmak istediğinize emin misiniz?"  ) == true) {
            $.ajax({
                url: '/admin/removeFromPrime',
                method : 'POST',
                data : { id  : tempId },
                success: function(data) {
                    console.log(data);
                    var tempId =  '#tr_' + data.data;
                    $(tempId).remove();
                },
                complete: function() {
                  // Schedule the next request when the current one's complete
                  //setTimeout(worker, 30000);
                }
            });
            return true;
        } else {
            return false;
        }
    }

</script>
@stop