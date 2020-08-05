@extends('newApp')

@section('html-head')

@stop
@section('content')
<style>
td.salesNumber {
    cursor: pointer;
}
.dataTables_filter input {
    width: 300px !important
}
td.contactNumber {
    cursor: pointer;
}
tr.one {
    background-color: #CAE6FF !important;
}
tr.two {
    background-color: #ABD0F1 !important;
}
tr.three {
    background-color: #5FA9EA !important
}
td.addPrimeClass{
    text-align: center;
        padding-top: 0px !important;
        padding-bottom: 0px !important;
        padding-left: 0px !important;
        padding-right: 0px !important;
}
td.removeClass{
    cursor : pointer;
    //width: 10px;
}
</style>
<section class="content-header">
    <h1>Müşteri Listesi</h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Müşteri</a></li>
        <li class="active">Müşteriler</li>
    </ol>
</section>
<section class="content">
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
        <div style="padding-bottom: 11px;padding-left: 0px;" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div style="width: 430px;display: inline-flex;" class="input-group col-lg-5 col-md-5 col-sm-12 col-xs-12">
                <label style="width: 100px;padding-top: 6px;">Üyelik Tarihi:</label>
                <button style="width: 300px;" type="button" class="btn btn-default pull-right" id="daterange-btn">
                    <span>
                        <i class="fa fa-calendar"></i> Tarih Aralığı Seç
                    </span>
                    <i class="fa fa-caret-down"></i>
                </button>
            </div>
            <div style="width: 410px;display: inline-flex;" class="input-group col-lg-5 col-md-5 col-sm-12 col-xs-12">
                <label style="width: 100px;padding-top: 6px;">Son Giriş:</label>
                <button style="width: 300px;" type="button" class="btn btn-default pull-right" id="daterange-btn-update">
                    <span>
                        <i class="fa fa-calendar"></i> Tarih Aralığı Seç
                    </span>
                    <i class="fa fa-caret-down"></i>
                </button>
            </div>
            <div style="display: inline-flex;" class="input-group">
                <button style="width: 80px;" type="button" id="dateSearch" class="btn btn-sm btn-primary">Arama</button>
            </div>
            <a style="float: right;width: 135px;" class="btn btn-success form-control" href="/admin/primeCustomers">Prime Kullanıcıları</a>
        </div>
        <div>
            <div style="padding-left: 0px;padding-right: 0px;padding-top: 12px;display: inline-flex;float: right" class="col-lg-12 col-md-3 col-sm-12 col-xs-12">
                <label>Üye : </label><label id="userCount"></label><label style="padding-left: 8px;"> Misafir : </label><label id="anonimCount"></label><label style="padding-left: 8px;"> Toplam : </label><label id="totalCount"></label>
            </div>
        </div>
        <div class="row hidden">
            <div class="col-xs-4 form-inline" style="position: absolute; z-index: 2;">
                <div class="input-daterange input-group">
                    <input type="text" class="input-sm form-control" id="start_date" name="start_date" value="" />
                    <span class="input-group-addon">to</span>
                    <input type="text" class="input-sm form-control" id="end_date" name="end_date" value=""/>
                </div>
            </div>
        </div>
        <div class="row hidden">
            <div class="col-xs-4 form-inline" style="position: absolute; z-index: 2;">
                <div class="input-daterange input-group">
                    <input type="text" class="input-sm form-control" id="start_date_update" name="start_date_update" value="" />
                    <span class="input-group-addon">to</span>
                    <input type="text" class="input-sm form-control" id="end_date_update" name="end_date_update" value=""/>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-body">
                        <table  style="width: 100%; font-size: 14px;" id="users-table" data-width="100%" data-compression="6" data-min="1" data-max="14" cellpadding="0" cellspacing="0" class="table display nowrap table-bordered table-striped responsive responsiveTable">
                            <thead>
                                <tr>
                                    <th>Üyelik Tarihi</th>
                                    <th>Son Giriş</th>
                                    <th>Adı Soyadı</th>
                                    <th>Mail</th>
                                    <th>Mobile</th>
                                    <th>Satış Sayısı</th>
                                    <th>Üye No</th>
                                    <th>Tür</th>
                                    <th>Kayıtlı Kişi</th>
                                    <th width="5%">Sil</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td width="5%"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="addDeliveryNote123" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div id="salesDetailModel" class="modal-content">
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
        <div class="modal fade" id="changeMail" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div id="changeMailModel" class="modal-content">
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
@stop()
</section>
@endsection
@push('scripts')
<script>
$(function() {

    var table = $('#users-table').DataTable({
        search: {
            "caseInsensitive": true
        },
        processing: true,
        serverSide: true,
        "order": [[ 0, "desc" ]],
        ajax: {
            url:"addPrimeCustomersData",
            data: function(d) {
                d.start_date = $('input[name=start_date]').val();
                d.end_date = $('input[name=end_date]').val();
                d.start_date_update = $('input[name=start_date_update]').val();
                d.end_date_update = $('input[name=end_date_update]').val();
            }
        },
        lengthMenu: [[100, 500, -1], [ 100, 500, "Hepsi"]],
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.12/i18n/Turkish.json"
        },
        "columnDefs": [ {
            "width": "20px",
            "targets": 9,
            "data": null,
            "defaultContent": "<button class='btn btn-success form-control'>Prime Yap</button>",
            "class" : "addPrimeClass"
        } ],
        columns: [
            { data: 'created_at', name: 'created_at', className : '' },
            { data: 'updated_at', name: 'updated_at', className : '',"orderSequence": [ "desc", "asc" ] },
            { data: 'name', name: 'name', className : '' ,"orderSequence": [ "desc", "asc" ]},
            { data: 'email', name: 'email', className : '' ,"orderSequence": [ "desc", "asc" ]},
            { data: 'mobile', name: 'mobile', className : '' ,"orderSequence": [ "desc", "asc" ]},
            { data: 'salesNumber', name: 'salesNumber', className : 'salesNumber' ,"orderSequence": [ "desc", "asc" ]},
            { data: 'user_id', name: 'user_id', className : 'userClass',"orderSequence": [ "desc", "asc" ] },
            { data: 'status', name: 'status', className : '' ,"orderSequence": [ "desc", "asc" ]},
            { data: 'contactNumber', name: 'contactNumber', className : 'contactNumber' ,"orderSequence": [ "desc", "asc" ]}
        ],
        dom: "<'row'<'col-lg-6 col-md-6 col-sd-6'l><'col-lg-6 col-md-6 col-sd-6'p>><'row'<'col-lg-6 col-md-6 col-sd-6'B><'col-lg-6 col-md-6 col-sd-6'f>><'row'>",
        buttons: [
            {
                extend: 'collection',
                text: 'Export',
                buttons: [
                    'copy',
                    'excel',
                    'csv',
                    'pdf',
                    'print'
                ]
            }
        ],
        "fnRowCallback": function(nRow, aaData, iDisplayIndex) {
            console.log(aaData);
            $('#userCount').text(aaData.userNumber);
            $('#anonimCount').text(aaData.anonimNumber);
            $('#totalCount').text(aaData.totalNumber);
            var tempColorClass = '0';
            if(aaData.salesNumber > 3){
                tempColorClass = 'three';
            }
            else if(aaData.salesNumber > 1){
                tempColorClass = 'two';
            }
            else if(aaData.salesNumber == 1){
                tempColorClass = 'one';
            }
            if(aaData.prime != '0'){
                $(nRow).children('.addPrimeClass').children().remove();
                $(nRow).children('.addPrimeClass').append('<i style="padding-top: 7px;color: #53FF1F;font-size: 18px;" class="fa fa-fw fa-check"></i>');
            }
            $(nRow).addClass(tempColorClass);
            return nRow;
        }
    });

    $('#users-table').on( 'click', '.addPrimeClass', function () {
        var data = table.row( $(this).parents('tr') ).data();
        console.log(data);
        //var tempId = $(event).attr('id').split("-")[2];
        //var tempName = '#name_' + tempId;
        if (confirm( data.name + " kullanıcısını prime yapmak istediğinize emin misiniz?"  ) == true){
            $.ajax({
                url: '/admin/setPrimeCustomer',
                method : 'POST',
                data : { id  : data.DT_RowId },
                success: function(data){
                    table.draw();
                //console.log(data);
                //var tempId =  '#tr_' + data.data;
                //$(tempId).remove();
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
    } );

    $('.input-daterange').datepicker({
                     autoclose: true,
                     todayHighlight: true
                });

    $('#dateSearch').on('click', function() {
                    table.draw();
                });

    $('#users-table').on( 'click', '.salesNumber', function () {
        var data = table.row( $(this).parents('tr') ).data();
        console.log(data.DT_RowId);
        initiatePopup(data.DT_RowId);
        //alert( data[0] +"'s salary is: "+ data['DT_RowId'] );
    } );

    $('#users-table').on( 'click', '.contactNumber', function () {
        var data = table.row( $(this).parents('tr') ).data();
        console.log(data.DT_RowId);
        initiateContactPopup(data.DT_RowId);
        //alert( data[0] +"'s salary is: "+ data['DT_RowId'] );
    } );

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
        moment.locale('tr');
        $('#daterange-btn').daterangepicker(
            {
                ranges: {
                    'Bugün': [moment(), moment()],
                    'Dün': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Bu Hafta': [moment().startOf('week'), moment()],
                    'Bu Ay': [moment().startOf('month'), moment()],
                    'Geçen Ay': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Hepsi': [moment().year(2014), moment().year(2020)]
                },
                startDate: moment().year(2014),
                endDate:  moment().year(2020)
            },
            function (start, end) {
              if(end.year() == 2020){
                $('#daterange-btn span').html('Tarih Aralığı Seç')
              }
              else{
                $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
              }
              $('#start_date').val(start.format('YYYY-MM-DD HH:mm:ss'));
              $('#end_date').val(end.format('YYYY-MM-DD HH:mm:ss'));
            }
        );

        $('#daterange-btn-update').daterangepicker(
            {
                ranges: {
                    'Bugün': [moment(), moment()],
                    'Dün': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Bu Hafta': [moment().startOf('week'), moment()],
                    'Bu Ay': [moment().startOf('month'), moment()],
                    'Geçen Ay': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Hepsi': [moment().year(2014), moment().year(2020)]
                },
                startDate: moment().year(2014),
                endDate:  moment().year(2020)
            },
            function (start, end) {
              if(end.year() == 2020){
                $('#daterange-btn-update span').html('Tarih Aralığı Seç')
              }
              else{
                $('#daterange-btn-update span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
              }
              $('#start_date_update').val(start.format('YYYY-MM-DD HH:mm:ss'));
              $('#end_date_update').val(end.format('YYYY-MM-DD HH:mm:ss'));
            }
        );

});
</script>
@endpush
@section('footer')
@stop