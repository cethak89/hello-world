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
                <h1>Bloom & Fresh Musteri Listesi</h1>
            </td>
            <td style="vertical-align: middle;">
                <button class="btn btn-primary form-control" onclick="$('#filterTable').toggle();">Sorgu Alanlari</button>
            </td>
            <td style="vertical-align: middle;">
                <button id="testXls" class="btn btn-danger pull-right"  onClick ="$('tr').each(function() {$(this).find('td:eq(9)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Müşteri Listesi',filename: 'Müşteriler'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>
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
    <table id="filterTable" class="table table-hover"  style="display: {{$filterShow}}">
                {!! Form::model($queryParams, ['url' => '/admin/customers/filter', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
                <tr>
                    <td style="padding-left:21px;width:30%;">Üyelik Tarihi</td><td style="padding-left:21px;width:30%;">{!! Form::input('date', 'created_at', null , ['class' => 'form-control formatDate'  , 'data-date-format' => 'DD MM YYYY' , 'data-date' => '' , 'id' => 'wddId' ]) !!}</td>
                    <td style="padding-left:21px;width:30%;">{!! Form::input('date', 'created_at_end', null, ['class' => 'form-control formatDate'  , 'data-date-format' => 'DD MM YYYY' , 'data-date' => '' , 'id' => 'wddeId' ]) !!}</td>
                </tr>
                <tr>
                    <td style="padding-left:21px;">Musteri Adi</td><td style="padding-left:21px;">{!! Form::input('text', 'name', $queryParams->name, ['class' => 'form-control']) !!}</td><td></td>
                </tr>
                <tr>
                    <td style="padding-left:21px;">Musteri Soyadi</td><td style="padding-left:21px;">{!! Form::input('text', 'surname', $queryParams->surname, ['class' => 'form-control']) !!}</td><td></td>
                </tr>
                <tr>
                    <td style="padding-left:21px;">User Id</td><td style="padding-left:21px;">{!! Form::input('text', 'user_id', $queryParams->user_id, ['class' => 'form-control']) !!}</td><td></td>
                </tr>
                <tr>
                    <td style="padding-left:21px;">Mail Domain</td><td style="padding-left:21px;">{!! Form::input('text', 'domain2', $queryParams->domain2, ['class' => 'form-control']) !!}</td><td></td>
                </tr>
                <tr>
                     <td style="padding-left:21px;">
                        Mail Domain Dropdown
                     </td>
                     <td>
                        <div class="form-group">
                        <select name="domain" class="btn btn-default dropdown-toggle">
                            @foreach($myArray as $tag)
                                <option value="{{$tag->mail}}"
                                 @if($tag->mail == $queryParams->domain)
                                 selected
                                 @else
                                 @endif
                                 >{{$tag->mail}}</option>
                            @endforeach
                        </select>
                        </div>
                     </td>
                     <td>

                     </td>
                </tr>
                <tr>
                <td>
                    {!! Form::submit('Sorgula', ['class' => 'btn btn-success form-control']) !!}
                </td>
                <td>
                        {!! Html::link('/admin/customers' , 'Temizle', ['class' => 'btn btn-primary form-control']) !!}
                </td>
                <td>

                </td>
                </tr>
                <tr>
                    <td></td><td></td><td></td>
                </tr>
                <tr>
                    <td>
                        <button onclick="makeToday()" class="btn btn-info form-control">Bugün</button>
                    </td>
                    <td>
                        <button onclick="makeWeek()" class="btn btn-warning form-control">Bu Hafta</button>
                    </td>
                    <td>
                        <button onclick="makeMonth()" class="btn btn-danger form-control">Bu Ay</button>
                    </td>
                </tr>
                    {!! Form::hidden('orderParameter', $queryParams->orderParameter, ['class' => 'form-control' , 'name' => 'orderParameter']) !!}
                    {!! Form::hidden('upOrDown', $queryParams->upOrDown, ['class' => 'form-control' , 'name' => 'upOrDown']) !!}
                    {!! Form::hidden('pagination', $queryParams->pagination, ['class' => 'form-control' , 'name' => 'pagination' , 'id' => 'page1']) !!}
                {!! Form::close() !!}
    </table>
    <label>Kullanıcı : {{$userNumber}}</label>
    <label>Misafir : {{$anonimNumber}}</label>
    <label>Toplam Müşteri :{{$totalNumber}}</label>
    <nav>
      <ul style="justify-content: center;" class="pagination pagination-lg">
            @if($selectedPage != 1 && $selectedPage !=0 )
            <li><a onclick="
                makePagination({{($selectedPage -1)}})" href="#"><</a></li>
            @endif
        @for($i = 1; $i <= $pageNumber; $i++)
            <li><a onclick="makePagination({{$i}})" href="#" style="@if( $i == $selectedPage ) background-color: #CACACA;@endif">{{$i}}</a></li>
        @endfor
            @if($selectedPage != (int)$pageNumber  && $selectedPage !=0)
                <li><a onclick="makePagination({{($selectedPage +1)}})" href="#">></a></li>
            @endif
      </ul>
    </nav>
    <div style="overflow-x: scroll;">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            {!! Form::model($queryParams, ['name' => 'test1' , 'url' => '/admin/customers/orderAndFilterDesc/', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            {!! Form::hidden('created_at', $queryParams->created_at, ['class' => 'form-control']) !!}
            {!! Form::hidden('created_at_end', $queryParams->created_at_end, ['class' => 'form-control']) !!}
            {!! Form::hidden('name', $queryParams->name, ['class' => 'form-control']) !!}
            {!! Form::hidden('surname', $queryParams->surname, ['class' => 'form-control']) !!}
            {!! Form::hidden('user_id', $queryParams->user_id, ['class' => 'form-control']) !!}
            {!! Form::hidden('domain', $queryParams->domain, ['class' => 'form-control']) !!}
            {!! Form::hidden('domain2', $queryParams->domain2, ['class' => 'form-control']) !!}
            {!! Form::hidden('orderParameter', $queryParams->orderParameter, ['class' => 'form-control' , 'name' => 'orderParameter']) !!}
            {!! Form::hidden('upOrDown', $queryParams->upOrDown, ['class' => 'form-control' , 'name' => 'upOrDown']) !!}
            {!! Form::hidden('pagination', $queryParams->pagination, ['class' => 'form-control' , 'name' => 'pagination' , 'id' => 'page']) !!}
            <th><span onmouseover="setOrderParameter('created_at' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Üyelik Tarihi <span onmouseover="setOrderParameter('created_at' , 'up')" onclick="document.test1.submit();"   class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span onmouseover="setOrderParameter('updated_at' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Son Giriş <span onmouseover="setOrderParameter('updated_at' , 'up')" onclick="document.test1.submit();"   class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span onmouseover="setOrderParameter('name' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Adi Soyadi <span onmouseover="setOrderParameter('name' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span onmouseover="setOrderParameter('email' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Mail <span onmouseover="setOrderParameter('email' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th>Telefon</th>
            <th><span onmouseover="setOrderParameter('orderCount' , 'down')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Siparis Sayisi <span onmouseover="setOrderParameter('orderCount' , 'up')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span onmouseover="setOrderParameter('user_id' , 'down')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> User Id <span  onmouseover="setOrderParameter('user_id' , 'up')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span onmouseover="setOrderParameter('status' , 'down')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Tür <span  onmouseover="setOrderParameter('status' , 'up')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span onmouseover="setOrderParameter('contactNumber' , 'down')" onclick="document.test1.submit();"  class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Kayıtlı Kişi Sayısı <span  onmouseover="setOrderParameter('contactNumber' , 'up')" onclick="document.test1.submit();" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th></th>
            {!! Form::close() !!}
        </tr>
        @foreach($customers as $customer)
            <tr style="@if($customer->salesNumber == 1) background-color: aliceblue; @elseif($customer->salesNumber > 1) background-color:#5FA9EA;  @endif"  id="tr_{{$customer->id}}">
                <td style="padding-left:21px;">{{$customer->created_at}}</td>
                <td style="padding-left:21px;">
                {{$customer->updated_at}}
                </td>
                <td id="name_{{$customer->id}}" style="padding-left:21px;">{{$customer->name}} {{$customer->surname}}</td>
                <td style="padding-left:21px;">
                {{$customer->email}}
                </td>
                <td style="padding-left:21px;">{{$customer->mobile}}</td>
                <td style="cursor: pointer;" @if($customer->salesNumber > 0)onclick="initiatePopup({{$customer->id}});"@endif  style="padding-left:21px;">{{$customer->salesNumber}}</td>
                <td style="padding-left:21px;">{{$customer->user_id}}</td>
                <td style="padding-left:21px;">
                {{$customer->status}}
                </td>
                <td style="padding-left:21px;">{{$customer->contactNumber}}</td>
                <td style="padding-left:21px;">
                    @if(  Auth::user()->user_group_id == 1  )
                    <button class="btn btn-danger form-control checkValid" style="width:100%;" id="row-id-{{$customer->id}}"  onclick="deleteCustomers(this)">Sil</button>
                    @endif
                </td>
                <div class="modal fade" id="addDeliveryNote123" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                    <div class="modal-dialog" role="document">
                        <div id="salesDetailModel" class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="myModalLabel">{{$customer->name}} {{$customer->surname}}</h4>
                            </div>
                            <!--<div style="border-bottom-style: dotted;padding-bottom: 35px;border-bottom-width: 1px;" class="modal-body">
                                 <p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">2015 13 Nsn Cma</p>
                                 <p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">Valhalla</p>
                                 <p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">Beşiktaş-Balmumcu</p>
                                 <p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">Türkan Avcı</p>
                            </div>
                            <div style="margin-bottom: 20px;border-bottom-style: dotted;padding-bottom: 35px;border-bottom-width: 1px;" class="modal-body">
                                 <p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">2015 13 Nsn Cma</p>
                                 <p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">Valhalla</p>
                                 <p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">Beşiktaş-Balmumcu</p>
                                 <p style="text-align: center;padding-left: 0px;" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">Türkan Avcı</p>
                            </div>-->
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

            $( document ).ready(function() {

                if($('#wddId').attr('data-date') == 'Invalid date')
                    $('#wddId').attr('data-date' , '');

                if($('#wddeId').attr('data-date') == 'Invalid date')
                    $('#wddeId').attr('data-date' , '');
            });

                $(".formatDate").on("change", function() {
                    this.setAttribute(
                        "data-date",
                        moment(this.value, "YYYY-MM-DD")
                        .format( this.getAttribute("data-date-format") )
                    )
                }).trigger("change");

                function makePagination(event){
                    $('#page').val(event);
                    document.test1.submit();
                }

                function deleteCustomers(event){
                        var tempId = $(event).attr('id').split("-")[2];
                        var tempName = '#name_' + tempId;
                        if (confirm( $(tempName).text() + " kullanıcısını silmek istediğinize emin misiniz?"  ) == true) {
                            $.ajax({
                                url: '/admin/deleteCustomer',
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

                function makeToday(){
                    var currentDate = new Date(new Date().getTime());
                    var day = currentDate.getDate();
                    var month = currentDate.getMonth() + 1;
                    var year = currentDate.getFullYear();

                    //var before = new Date();
                    //var after = new Date();
                    //var tomorrowDay = before.getDay();
                    //var month = parseInt(before.getDay()) + 1;
                    //before.setDay();
                    //console.log( before.getFullYear() + '-' + before.getMonth() + '-' + before.getDay());
                    //var month = parseInt(before.getMonth()) + 1;
                    //after.setHours(23,59,59,59);
                    //before.setHours(0,0,0,0);
                    month = (month < 10) ? ("0" + month) : month;
                    day = (day < 10) ? ("0" + day) : day;
                    $('#wddeId').val(year + '-' + month + '-' + day);
                    $('#wddId').val(year + '-' + month + '-' + day);
                    //$('#submitId').click();
                }

                function makeMonth(){
                        var currentDate = new Date(new Date().getTime());
                        var day = currentDate.getDate();
                        var month = currentDate.getMonth() + 1;
                        var year = currentDate.getFullYear();

                        var tempCurrentDate = new Date(new Date().getTime());
                        var tempday = tempCurrentDate.getDate();
                        var tempmonth = tempCurrentDate.getMonth() + 1;
                        var tempyear = tempCurrentDate.getFullYear();
                        tempmonth = (tempmonth < 10) ? ("0" + tempmonth) : tempmonth;
                        tempday = (tempday < 10) ? ("0" + tempday) : tempday;
                        //var before = new Date();
                        //var after = new Date();
                        //var tomorrowDay = before.getDay();
                        //var month = parseInt(before.getDay()) + 1;
                        //before.setDay();
                        //console.log( before.getFullYear() + '-' + before.getMonth() + '-' + before.getDay());
                        //var month = parseInt(before.getMonth()) + 1;
                        //after.setHours(23,59,59,59);
                        //before.setHours(0,0,0,0);
                        month = (month < 10) ? ("0" + month) : month;
                        day = (day < 10) ? ("0" + day) : day;
                        $('#wddeId').val(year + '-' + tempmonth + '-' + tempday);
                        $('#wddId').val(year + '-' + month + '-' + '01');
                        console.log(year + '-' + month + '-' + day);
                        console.log(year + '-' + month + '-' + '01');
                        //$('#submitId').click();
                    }

                function makeWeek(){
                        var nowCurrentDate = new Date(new Date().getTime() );
                        var nowDay = nowCurrentDate.getDate();
                        var nowMonth = nowCurrentDate.getMonth() + 1;
                        var nowYear = nowCurrentDate.getFullYear();

                        var tempCurrentDate = new Date(new Date().getTime());
                        if( tempCurrentDate.getDay() == 0 ){
                            var tempWeekDay = 7;
                        }
                        else{
                            var tempWeekDay = tempCurrentDate.getDay()
                        }

                        var currentDate = new Date(new Date().getTime() - tempWeekDay*24 * 60 * 60 * 1000 + 24 * 60 * 60 * 1000 );
                        var day = currentDate.getDate();
                        var month = currentDate.getMonth() + 1;
                        var year = currentDate.getFullYear();

                        //var before = new Date();
                        //var after = new Date();
                        //var tomorrowDay = before.getDay();
                        //var month = parseInt(before.getDay()) + 1;
                        //before.setDay();
                        //console.log( before.getFullYear() + '-' + before.getMonth() + '-' + before.getDay());
                        //var month = parseInt(before.getMonth()) + 1;
                        //after.setHours(23,59,59,59);
                        //before.setHours(0,0,0,0);
                        nowMonth = (nowMonth < 10) ? ("0" + nowMonth) : nowMonth;
                        nowDay = (nowDay < 10) ? ("0" + nowDay) : nowDay;
                        month = (month < 10) ? ("0" + month) : month;
                        day = (day < 10) ? ("0" + day) : day;
                        $('#wddeId').val(nowYear + '-' + nowMonth + '-' + nowDay);
                        $('#wddId').val(year + '-' + month + '-' + day);
                        console.log(year + '-' + month + '-' + day);
                        console.log(nowYear + '-' + nowMonth + '-' + nowDay);
                        //$('#submitId').click();
                    }

        //$('.checkValid').click(function() {
        //    var x;
        //        if (confirm("Silmek istediğinize emin misiniz?") == true) {
        //            $.ajax({
        //                url: '/admin/deleteCustomer',
        //                method : 'POST',
        //                data : [ id  = '282' ],
        //                success: function(data) {
        //                console.log(data);
        //                },
        //                complete: function() {
        //                  // Schedule the next request when the current one's complete
        //                  //setTimeout(worker, 30000);
        //                }
        //            });
        //            return true;
        //        } else {
        //            return false;
        //        }
        //});

        function setOrderParameter(paremeter , upOrDown){
            $('input[name=orderParameter]').val(paremeter);
            $('input[name=upOrDown]').val(upOrDown);
            console.log( $('input[name=orderParameter]').val());
        }
</script>
@stop