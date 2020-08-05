@extends('newApp')

@section('html-head')
    <style>
        .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th{
            vertical-align: middle;
        }
        div.form-group {
            height: 20px;
        }
        input {
            position: relative;
            width: 150px; height: 20px;
            color: white;
        }

        input:before {
            position: absolute;
            top: 3px; left: 3px;
            content: attr(data-date);
            display: inline-block;
            color: black;
        }

        input::-webkit-datetime-edit, input::-webkit-inner-spin-button, input::-webkit-clear-button {
            display: none;
        }

        input::-webkit-calendar-picker-indicator {
            position: absolute;
            top: 3px;
            right: 0;
            color: black;
            opacity: 1;
        }
    </style>
@stop
<style>
    .textALigCenter > td, th{
        text-align: center;
    }
    .textALigCenter > th{
        text-align: center;
    }
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
                <h1>Bloom & Fresh Kupon Raporu</h1>
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
    <table id="filterTable" class="table table-hover">
        {!! Form::model($queryParams, ['url' => '/admin/showCoupon/filter', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
        <tr>
            <td style="width: 150px;">Tarih</td>
            <td>
                {!! Form::input('text', 'created_at', null, [ 'class' => 'form-control formatDate', 'style' => 'width: 90px;display: inline;'  , 'id' => 'wddId' , 'data-date-format' => 'DD MM YYYY' , 'data-date' => ''  ]) !!}
                {!! Form::input('text', 'created_at_end', null, ['class' => 'form-control formatDate', 'style' => 'width: 90px;display: inline;'  , 'data-date-format' => 'DD MM YYYY' ,  'id' => 'wddeId' , 'data-date' => '' ]) !!}
            </td>
        </tr>
        <tr>
            <td>İndirim</td>
            <td>
                <div style="margin-bottom: 0px;" class="form-group">
                    <select style="width: 330px;" name="couponName" class="form-control select2">
                        <option value="0" @if($queryParams->couponName == 0) selected @endif >Hepsi</option>
                        <option value="1" @if($queryParams->couponName == 1) selected @endif >İndirimli</option>
                        <option value="2" @if($queryParams->couponName == 2) selected @endif >İndirimsiz</option>
                        @foreach($allCoupons as $coupon)
                            <option value="{{$coupon->name}}" @if($coupon->name == $queryParams->couponName) selected="true" @endif>{{$coupon->name}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        <tr class="hidden">
            <td>
                Fatura Gönderim
            </td>
            <td>
                {!! Form::checkbox('billing_active', null, $queryParams->billing_active, ['class' => 'changeSales'  , 'style' => 'width : 30px;height: 30px;']) !!}
            </td>
        </tr>
        <tr class="hidden">
            <td>
                Kurumsal
            </td>
            <td>
                {!! Form::checkbox('payment_type', null, $queryParams->payment_type, ['class' => 'changeSales'  , 'style' => 'width : 30px;height: 30px']) !!}
            </td>
        </tr>
        <tr class="hidden">
            <td>Kurumsal Firma</td>
            <td>
                <div class="form-group">
                    <select name="CompanyId" class="btn btn-default dropdown-toggle">
                        @foreach($companyList as $tag)
                            <option value="{{$tag->status}}"
                                    @if($tag->status == $queryParams->status)
                                    selected
                            @else
                                    @endif
                            >{{$tag->information}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                {!! Form::submit('Sorgula', [ 'style' => 'width: 100px;', 'class' => 'btn btn-success form-control' , 'id' => 'submitId']) !!}
                {!! Html::link('/admin/excelBilling' , 'Temizle', [ 'style' => 'width: 100px;', 'class' => 'btn btn-primary form-control']) !!}
                <button style="width: 100px;" onclick="makeToday()" class="btn btn-info form-control">Bugün</button>
                <button style="width: 100px;" onclick="makeWeek()" class="btn btn-warning form-control">Bu Hafta</button>
                <button style="width: 100px;" onclick="makeMonth()" class="btn btn-danger form-control">Bu Ay</button>
            </td>
        </tr>
        {!! Form::close() !!}
    </table>
    <button id="testXls" class="btn btn-danger" onClick ="$('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Fatura Exceli',filename: 'Fatura Exceli'});">Excel Çıktısı İçin Tıklayınız</button>
    <div style="overflow-x: scroll;transform: rotateX(180deg);">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;transform: rotateX(180deg);">
            <tr class="textALigCenter">
                <td>{{$count}}</td>
                <td></td>
                <td></td>
                <td>{{$cikilotCount}}</td>
                <td></td>
                <td>{{$firstPrice}}</td>
                <td></td>
                <td>{{$avarageDiscount}}</td>
                <td>{{$totalDiscount}}</td>
                <td>{{$totalPartial}}</td>
                <td>{{$totalKDV}}</td>
                <td>{{$total}}</td>
                <td>{{$cikilotTotalPrice}}</td>
                <td>{{$cikilotBigGeneral}}</td>
            </tr>
            <tr class="textALigCenter">
                <th>Sipariş No</th>
                <th>İsim</th>
                <th>Ürün Adı</th>
                <th>Extra Ürün</th>
                <th>Ödeme Tarihi</th>
                <th>Tutar</th>
                <th>İndirim Adı</th>
                <th>İndirim Oranı</th>
                <th>İndirim Tutarı</th>
                <th>Ara Toplam</th>
                <th>KDV</th>
                <th>Genel Toplam</th>
                <th>Çikolata Fiyatı</th>
                <th>Büyük Toplam</th>
            </tr>
            @foreach($list as $delivery)
                <tr  class="textALigCenter">
                    <td>{{$delivery->sales_id}}</td>
                    <td>{{$delivery->name}}</td>
                    <td>{{$delivery->products}}</td>
                    <td>{{$delivery->cikilotName}}</td>
                    <td>{{$delivery->created_at}}</td>
                    <td>{{$delivery->price}}</td>
                    <td>{{$delivery->discountName}}</td>
                    <td>{{$delivery->discount}}</td>
                    <td>{{$delivery->discountVal}}</td>
                    <td>{{$delivery->sumPartial}}</td>
                    <td>{{$delivery->discountValue}}</td>
                    <td>{{$delivery->sumTotal}}</td>
                    <td>{{$delivery->cikilotPrice}}</td>
                    <td>{{$delivery->cikilotTotalGeneral}}</td>
                </tr>
            @endforeach
        </table>
    </div>
@stop()

@section('footer')

    <script>
        $(".select2").select2();
        $( document ).ready(function() {
            //var currentDate = new Date(new Date().getTime());
            //var day = currentDate.getDate();
            //var month = currentDate.getMonth() + 1;
            //var year = currentDate.getFullYear();
            //month = (month < 10) ? ("0" + month) : month;
            //day = (day < 10) ? ("0" + day) : day;
            ////$('#wddId').attr('data-date-format' , 'DD MM YYYY');
            //$('#wddeId').attr('data-date' ,year + '-' + month + '-' + day);
            //$('#wddId').attr('data-date' ,year + '-' + month + '-' + day);
            $('#changeFluid').removeClass('container');
            $('#changeFluid').addClass('container-fluid');

            //if($('#wddId').attr('data-date') == 'Invalid date')
            //    $('#wddId').attr('data-date' , '');
//
            //if($('#wddeId').attr('data-date') == 'Invalid date')
            //    $('#wddeId').attr('data-date' , '');

            $('#wddId').datepicker({
                format: 'yyyy-mm-dd',
                language: 'tr',
                autoclose: true
            });
            $('#wddeId').datepicker({
                format: 'yyyy-mm-dd',
                language: 'tr',
                autoclose: true
            });

            //$("#wddId").on("change", function() {
            //    this.setAttribute(
            //        "data-date",
            //        moment(this.value, "YYYY-MM-DD")
            //        .format( this.getAttribute("data-date-format") )
            //    )
            //}).trigger("change");
        });

        //$(".formatDate").on("change", function() {
        //    this.setAttribute(
        //        "data-date",
        //        moment(this.value, "YYYY-MM-DD")
        //        .format( this.getAttribute("data-date-format") )
        //    )
        //}).trigger("change");

        function makeToday(){
            var nowCurrentDate = new Date(new Date().getTime());
            var nowDay = nowCurrentDate.getDate();
            var nowMonth = nowCurrentDate.getMonth() + 1;
            var nowYear = nowCurrentDate.getFullYear();

            var tempCurrentDate = new Date(new Date().getTime());
            var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 );
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
            $('#wddId').val(nowYear + '-' + nowMonth + '-' + nowDay);
            $('#wddeId').val(year + '-' + month + '-' + day);
            console.log(year + '-' + month + '-' + day);
            console.log(nowYear + '-' + nowMonth + '-' + nowDay);
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

        //tesstt

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

    </script>
@stop