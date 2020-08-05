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

        .select2-selection__choice {
            margin-right: 1px !important;
            font-size: 14px;
            padding-left: 5px !important;
            padding-right: 5px !important;
        }

        .select2-selection--multiple {
            height: 39px;
        }


    </style>

@section('content')

    <table class="table table-hover">
    <tr>
        <td>
            <h1>Bloom & Fresh Fatura İçin Excel Sayfası</h1>
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
    <div style="overflow-x: scroll;transform: rotateX(180deg);">
        <table id="filterTable" class="table table-hover" style="vertical-align: middle;transform: rotateX(180deg);">
            {!! Form::model($queryParams, ['url' => '/admin/excelBilling/filter', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            <tr>
                <td style="width: 150px;vertical-align: middle;">Tarih</td>
                <td>
                    {!! Form::input('text', 'created_at', null, [ 'class' => 'form-control formatDate', 'style' => 'width: 90px;display: inline;'  , 'id' => 'wddId' , 'data-date-format' => 'DD MM YYYY' , 'data-date' => '', 'autocomplete' => 'off' ]) !!}
                    {!! Form::input('text', 'created_at_end', null, ['class' => 'form-control formatDate', 'style' => 'width: 90px;display: inline;'  , 'data-date-format' => 'DD MM YYYY' ,  'id' => 'wddeId' , 'data-date' => '', 'autocomplete' => 'off' ]) !!}
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">
                    Fatura Gönderim
                </td>
                <td>
                    {!! Form::checkbox('billing_active', null, $queryParams->billing_active, ['class' => 'changeSales'  , 'style' => 'width : 30px;height: 30px;']) !!}
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">
                    Kurumsal
                </td>
                <td>
                    {!! Form::checkbox('payment_type', null, $queryParams->payment_type, ['class' => 'changeSales'  , 'style' => 'width : 30px;height: 30px']) !!}
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Kurumsal Firma</td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
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
                <td style="vertical-align: middle;">Ürün Türü</td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="max-width: 350px;min-width: 350px;" name="category[]" class="form-control select2" multiple>
                            <option @foreach( $queryParams->category as $category) @if( $category == 1 ) selected @endif @endforeach value="1">Çiçek</option>
                            <option @foreach( $queryParams->category as $category) @if( $category == 2 ) selected @endif @endforeach value="2">Çikolata</option>
                            <option @foreach( $queryParams->category as $category) @if( $category == 3 ) selected @endif @endforeach value="3">Hediye Kutusu</option>
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Ürün Alt Türü</td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="height: 80px;max-width: 1200px;min-width: 1200px;" name="sub_category[]" class="form-control select2" multiple>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 11 ) selected @endif @endforeach value="11">Buket</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 12 ) selected @endif @endforeach value="12">Masaüstü</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 13 ) selected @endif @endforeach value="13">Sukulent</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 14 ) selected @endif @endforeach value="14">Saksı</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 15 ) selected @endif @endforeach value="15">Orkide</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 16 ) selected @endif @endforeach value="16">Solmayan Gül</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 17 ) selected @endif @endforeach value="17">Kutuda Çiçek</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 21 ) selected @endif @endforeach value="21">Godiva</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 22 ) selected @endif @endforeach value="22">Baylan</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 23 ) selected @endif @endforeach value="23">BNF Macarons</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 24 ) selected @endif @endforeach value="24">Hazz</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 25 ) selected @endif @endforeach value="25">TAFE</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 31 ) selected @endif @endforeach value="31">BNF Kutu</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 32 ) selected @endif @endforeach value="32">Godiva Kutu</option>
                            <option @foreach( $queryParams->sub_category as $category) @if( $category == 33 ) selected @endif @endforeach value="33">TAFE Kutu</option>
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
    </div>
    <button id="testXls" class="btn btn-danger" onClick ="$('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Fatura Exceli',filename: 'Fatura Exceli'});">Excel Çıktısı İçin Tıklayınız</button>
    <div style="overflow-x: scroll;transform: rotateX(180deg);">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;transform: rotateX(180deg);">
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>{{$count}}</td>
            <td></td>
            <td>{{$firstPrice}}</td>
            <td>{{$avarageDiscount}}</td>
            <td>{{$totalDiscount}}</td>
            <td>{{$totalPartial}}</td>
            <td>{{$totalKDV}}</td>
            <td>{{$total}}</td>
            <td>{{$cikilotCount}}</td>
            <td>{{$cikilotTotalPrice}}</td>
            <td></td>
            <td></td>
            <td>{{$cikilotBigGeneral}}</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <th>Sipariş No</th>
            <th>İsim</th>
            <th>Semt</th>
            <th>İlçe</th>
            <th>Adres 2</th>
            <th>Vergi Dairesi&No</th>
            <th>Üye No</th>
            <th>Ürün Türü</th>
            <th>Ürün Alt Türü</th>
            <th>Fiili Sevk Tarihi</th>
            <th>Ödeme Tarihi</th>
            <th>Ürün Kodu</th>
            <th>Ürün Adı</th>
            <th>Miktar</th>
            <th>Fiyat</th>
            <th>Tutar</th>
            <th>İndirim Oranı</th>
            <th>İndirim Tutarı</th>
            <th>Ara Toplam</th>
            <th>KDV</th>
            <th>Genel Toplam</th>
            <th>Extra Ürün</th>
            <th>Fiyatı</th>
            <th>Ekstra İndirim</th>
            <th>Troy İndirimi</th>
            <th>Büyük Toplam</th>
            <th>Yazıyla</th>
            <th>Fatura Gönderim</th>
            <th>Cihaz</th>
            <th>Telefon</th>
            <th>Gönderen</th>
            <th>Gönderen Mail</th>
            <td>Ödeme Tipi</td>
            <td>Bölge</td>
        </tr>
        @foreach($list as $delivery)
            <tr>
                <td style="padding-left:21px;">{{$delivery->sales_id}}</td>
                <td style="padding-left:21px;">{{$delivery->name}}</td>
                <td style="padding-left:21px;">{{$delivery->smallCity}}</td>
                <td style="padding-left:21px;">{{$delivery->bigCity}}</td>
                <td style="padding-left:21px;">{{$delivery->address2}}</td>
                <td style="padding-left:21px;">{{$delivery->tax_office}}</td>
                <td style="padding-left:21px;">{{$delivery->user_id}}</td>
                <td>
                    @if( $delivery->product_type == 1 ) <label>Çiçek</label> @endif
                    @if( $delivery->product_type == 2 ) <label>Çikolata</label> @endif
                    @if( $delivery->product_type == 3 ) <label>Hediye Kutusu</label> @endif
                </td>
                <td>
                    @if( $delivery->product_type_sub == 11 ) <label>Buket</label> @endif
                    @if( $delivery->product_type_sub == 12 ) <label>Masaüstü</label> @endif
                    @if( $delivery->product_type_sub == 13 ) <label>Sukulent</label> @endif
                    @if( $delivery->product_type_sub == 14 ) <label>Saksı</label> @endif
                    @if( $delivery->product_type_sub == 15 ) <label>Orkide</label> @endif
                    @if( $delivery->product_type_sub == 16 ) <label>Solmayan Gül</label> @endif
                    @if( $delivery->product_type_sub == 17 ) <label>Kutuda Çiçek</label> @endif
                    @if( $delivery->product_type_sub == 21 ) <label>Godiva</label> @endif
                    @if( $delivery->product_type_sub == 22 ) <label>Baylan</label> @endif
                    @if( $delivery->product_type_sub == 23 ) <label>BNF Macarons</label> @endif
                    @if( $delivery->product_type_sub == 24 ) <label>Hazz</label> @endif
                    @if( $delivery->product_type_sub == 25 ) <label>TAFE</label> @endif
                    @if( $delivery->product_type_sub == 31 ) <label>BNF Kutu</label> @endif
                    @if( $delivery->product_type_sub == 32 ) <label>Godiva Kutu</label> @endif
                    @if( $delivery->product_type_sub == 33 ) <label>TAFE Kutu</label> @endif
                </td>
                <td style="padding-left:21px;">{{$delivery->wantedDate}}</td>
                <td style="padding-left:21px;">{{$delivery->created_at}}</td>
                <td style="padding-left:21px;">{{$delivery->id}}</td>
                <td style="padding-left:21px;">{{$delivery->products}}</td>
                <td style="padding-left:21px;">1</td>
                <td style="padding-left:21px;">{{$delivery->price}}</td>
                <td style="padding-left:21px;">{{$delivery->price}}</td>
                <td style="padding-left:21px;">{{$delivery->discount}}</td>
                <td style="padding-left:21px;">{{$delivery->discountVal}}</td>
                <td style="padding-left:21px;">{{$delivery->sumPartial}}</td>
                <td style="padding-left:21px;">{{$delivery->discountValue}}</td>
                <td style="padding-left:21px;">{{$delivery->sumTotal}}</td>
                <td style="padding-left:21px;">{{$delivery->cikilotName}}</td>
                <td style="padding-left:21px;">{{$delivery->cikilotPrice}}</td>
                <td style="padding-left:21px;">{{$delivery->cikilotDiscount}}</td>
                <td style="padding-left:21px;">
                    @if( $delivery->IsTroyCard )
                        30,00
                    @endif
                </td>
                <td style="padding-left:21px;">{{$delivery->cikilotTotalGeneral}}</td>
                <td style="padding-left:21px;"></td>
                <td style="padding-left:21px;">
                    @if($delivery->payment_type == 'KURUMSAL')
                        9
                    @elseif($delivery->smallCity == '')
                        2
                    @else
                        {{$delivery->billing_send}}
                    @endif
                </td>
                <td style="padding-left:21px;">{{$delivery->device}}</td>
                <td style="padding-left:21px;">{{$delivery->sender_mobile}}</td>
                <td style="padding-left:21px;">{{$delivery->sender_name}} {{$delivery->sender_surname}}</td>
                <td style="padding-left:21px;">{{$delivery->sender_email}}</td>
                <td style="padding-left:21px;"> {{$delivery->payment_type}} @if($delivery->isBank) (ISB) @elseif( $delivery->payment_type == 'POS' && !$delivery->isBank ) (GRT) @endif</td>
                <td style="padding-left:21px;">@if( $delivery->city_id == 1 ) 34 @else 06 @endif </td>
            </tr>
        @endforeach
    </table>
    </div>
@stop()

@section('footer')

    <script>
    $( document ).ready(function() {

        $(".select2").select2();

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