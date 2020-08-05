@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        div.form-group {
            height: 20px;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .top-container {
            background-color: #f1f1f1;
            padding: 30px;
            text-align: center;
        }

        .header {
            padding: 10px 16px;
            background: #555;
            color: #f1f1f1;
        }

        .content {
            padding: 16px;
        }

        .sticky {
            position: fixed;
            top: 0;
            width: 100%;
        }

        .sticky + .content {
            padding-top: 102px;
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

        .select2-selection--multiple {
            height: 39px;
        }

    </style>
@stop

@section('content')

    <table class="top-container" width="100%">
        <tr style="width: 100%;">
            <td>
                <h1 style="margin-left: 15px;">
                    Üretim Özet-Kategori Bazında
                </h1>
            </td>
            <td>
            </td>
        </tr>
    </table>
    <a style="margin-left: 15px;width: 140px;margin-bottom: 5px; @if( $date == 'today' ) text-decoration: underline; @endif " onclick="updateDateButton('today')" class="btn btn-info form-control">Bugün</a>
    <a style="width: 140px;margin-bottom: 5px;@if( $date == 'thisweek' ) text-decoration: underline; @endif" onclick="updateDateButton('thisweek')" class="btn btn-warning form-control">Bu Hafta</a>
    <a style="width: 140px;margin-bottom: 5px;@if( $date == 'thismonth' ) text-decoration: underline; @endif" onclick="updateDateButton('thismonth')" class="btn btn-danger form-control">Bu Ay</a>
    <br>
    <a style="margin-left: 15px;width: 140px;margin-bottom: 5px;color: #00c0ef;background-color: white;border-color: #00c0ef;@if( $date == 'yesterday' ) text-decoration: underline; @endif" onclick="updateDateButton('yesterday')" class="btn btn-primary form-control">Geçen Hafta Bugün</a>
    <a style="width: 140px;margin-bottom: 5px;background-color: #ffffff;border-color: #f39c12;color: #f39c12;@if( $date == 'lastweek' ) text-decoration: underline; @endif" onclick="updateDateButton('lastweek')" class="btn btn-success form-control">Geçen Hafta</a>
    <a style="width: 140px;margin-bottom: 5px;background-color: #ffffff;border-color: #d73925;color: #d73925;@if( $date == 'lastmonth' ) text-decoration: underline; @endif" onclick="updateDateButton('lastmonth')" class="btn btn-success form-control">Geçen Ay</a>
    <input class="hidden" id="dateId" value="{{$date}}">
    <div style="padding-left: 15px;">
        <table style="width: 426px;font-size: 18px;margin-bottom: 2px;" class="table table-hover">
            <tr>
                <td style="width: 140px;vertical-align: inherit;">
                    Tarih Aralığı
                </td>
                <td>
                    <input class="form-control formatDate" style="width: 133px;display: inline;" data-date-format="DD MM YYYY" id="startDate" onchange="updateStartDate($(this).val())" data-date="" autocomplete="off" type="text" value="{{$start_date}}">
                    <input class="form-control formatDate" style="width: 133px;display: inline;" data-date-format="DD MM YYYY" id="endDate" onchange="updateEndDate($(this).val())" data-date="" autocomplete="off" type="text" value="{{$end_date}}">
                </td>
            </tr>
        </table>
    </div>
    <div>
        <a style="margin-left: 15px;width: 143px;margin-bottom: 15px;background-color: #3c8dbc;border-color: #3c8dbc;color: white;" href="https://everybloom.com/produceByCategory" class="btn form-control">Temizle</a>
        <a style="margin-left: 2px;width: 277px;margin-bottom: 15px;" onclick="filterPage();" class="btn btn-success form-control">Sorgula</a>
    </div>
    <div style="background: white;" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;max-width: 590px;font-size: 18px;display: inline-table;">
            <tbody>
            <tr>
                <th style="width: 120px;">
                    Kategori
                </th>
                <th style="width: 140px;">
                    Alt Kategori
                </th>
                <th style="width: 60px;">
                    Adet
                </th>
                <th style="width: 60px;">
                    Yüzde
                </th>
            </tr>
            @foreach( $sub_categories as $sub_category )
                <tr>
                    <td>
                        @if( number_format($sub_category->category, 0) == 1 )
                            Çiçek
                        @elseif( number_format($sub_category->category, 0) == 2 )
                            Çikolata
                        @else
                            Hediye Kutusu
                        @endif
                    </td>
                    <td>
                        @if( $sub_category->product_type_sub == 11 )
                            Buket
                        @elseif( $sub_category->product_type_sub == 12 )
                            Masaüstü
                        @elseif( $sub_category->product_type_sub == 13 )
                            Sukulent
                        @elseif( $sub_category->product_type_sub == 14 )
                            Saksı
                        @elseif( $sub_category->product_type_sub == 15 )
                            Orkide
                        @elseif( $sub_category->product_type_sub == 16 )
                            Solmayan Gül
                        @elseif( $sub_category->product_type_sub == 17 )
                            Kutuda Çiçek
                        @elseif( $sub_category->product_type_sub == 21 )
                            Godiva
                        @elseif( $sub_category->product_type_sub == 22 )
                            Baylan
                        @elseif( $sub_category->product_type_sub == 23 )
                            BNF Macarons
                        @elseif( $sub_category->product_type_sub == 24 )
                            Hazz
                        @elseif( $sub_category->product_type_sub == 25 )
                            TAFE
                        @elseif( $sub_category->product_type_sub == 31 )
                            BNF Kutu
                        @elseif( $sub_category->product_type_sub == 32 )
                            Godiva Kutu
                        @elseif( $sub_category->product_type_sub == 33 )
                            TAFE Kutu
                        @endif
                    </td>
                    <td>
                        {{ $sub_category->saleCount  }}
                    </td>
                    <td>
                        {{ number_format($sub_category->saleCount / $total *100 ,1)   }}%
                    </td>
                </tr>
            @endforeach
            <tr>
                <td style="font-weight: bold;">Genel Toplam</td>
                <td style="font-weight: bold;"></td>
                <td style="font-weight: bold;">{{$total}}</td>
                <td style="font-weight: bold;">100.0%</td>
            </tr>
            </tbody>
        </table>
        <table id="products-table-2" class="table table-hover table-bordered" style="vertical-align: middle;max-width: 340px;font-size: 18px;display: inline-table;vertical-align: top;margin-left: 40px;">
            <tbody>
            <tr>
                <th style="width: 120px;">
                    Kategori
                </th>
                <th style="width: 60px;">
                    Adet
                </th>
                <th style="width: 60px;">
                    Yüzde
                </th>
            </tr>
            <tr>
                <td style="width: 120px;">
                    Kesme Çiçek
                </td>
                <td style="width: 60px;">
                    {{$flowerKesme}}
                </td>
                <td style="width: 60px;">
                    {{ number_format($flowerKesme / $total * 100 ,1)   }}%
                </td>
            </tr>
            <tr style="border-bottom: 2px solid #000000;">
                <td style="width: 120px;">
                    Topraklı
                </td>
                <td style="width: 60px;">
                    {{$flowerToprak}}
                </td>
                <td style="width: 60px;">
                    {{ number_format($flowerToprak / $total * 100 ,1)   }}%
                </td>
            </tr>
            <tr>
                <td style="width: 120px;">
                    Çiçek
                </td>
                <td style="width: 60px;">
                    {{$flowerTotal}}
                </td>
                <td style="width: 60px;">
                    {{ number_format($flowerTotal / $total * 100 ,1)   }}%
                </td>
            </tr>
            <tr>
                <td style="width: 120px;">
                    Çikolata
                </td>
                <td style="width: 60px;">
                    {{$chocolateTotal}}
                </td>
                <td style="width: 60px;">
                    {{ number_format($chocolateTotal / $total * 100 ,1)   }}%
                </td>
            </tr>
            <tr>
                <td style="width: 120px;">
                    Hediye Kutusu
                </td>
                <td style="width: 60px;">
                    {{$packageTotal}}
                </td>
                <td style="width: 60px;">
                    {{ number_format($packageTotal / $total * 100 ,1)   }}%
                </td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Genel Toplam</td>
                <td style="font-weight: bold;">{{$total}}</td>
                <td style="font-weight: bold;">100.0%</td>
            </tr>
            </tbody>
        </table>
    </div>
    @if( $date == 'today' || $date == 'thisweek' || $date == 'thismonth' )
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div id="chartContainer" style="height: 300px; width: 100%;"></div>
        </div>
    @endif

@stop()
<script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
<script src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script>
@section('footer')

<script>

        var date_start = '';
        var date_end = '';
        var dateButton = '';

        $( document ).ready(function() {
            if($('#dateId').val()){
                dateButton = $('#dateId').val();
                date_start = '';
                date_end = '';
            }

            if($('#startDate').val()){
                updateStartDate($('#startDate').val());
            }

            if($('#endDate').val()){
                updateEndDate($('#endDate').val());
            }

        });


        $('#startDate').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
        $('#endDate').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });


        function updateDateButton(tempDate) {
            dateButton = tempDate;
            date_start = '';
            date_end = '';

            filterPage();
        }

        function updateStartDate(tempStartDate) {
            date_start = tempStartDate;
            dateButton = '';
        }

        function updateEndDate(tempEndDate) {
            date_end = tempEndDate;
            dateButton = '';
        }

        function filterPage() {

            tempUrl = 'https://everybloom.com/produceByCategory?';

            if(dateButton){
                tempUrl = tempUrl + 'date='  + dateButton + '&'
            }

            if(date_start){
                tempUrl = tempUrl + 'start_date='  + date_start + '&'
            }

            if(date_end){
                tempUrl = tempUrl + 'end_date='  + date_end + '&'
            }

            location.replace(tempUrl);

        }

    </script>

@if( $date == 'thisweek' )
    <script type="text/javascript">

        function toogleDataSeries(e){
            if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
                e.dataSeries.visible = false;
            } else{
                e.dataSeries.visible = true;
            }
            e.chart.render();
        }

        var flowersToprakli = {!! json_encode($groupByDayFlowerToprakli) !!};
        var flowersKesme = {!! json_encode($groupByDayFlowerKesme) !!};
        var chocolates = {!! json_encode($groupByDayChocolate) !!};
        var boxes = {!! json_encode($groupByDayBox) !!};
        var total= {!! json_encode($totalSales) !!};

        console.log(total);

        $(function () {
            var tempFlowersKesme = [];
            var tempFlowersToprakli = [];
            var tempChocolates = [];
            var tempBoxes = [];
            flowersKesme.forEach(function logArrayElements(element, index, array) {
                var d = (  (element.day - 1) * 7);

                if( element.day == 1 && element.year == 2018 ){
                    element.year = 2019;
                }

                if( element.countNumber != 0 ){
                    percentage = ( ( element.countNumber / ( total[index].countNumber ) ) * 100 ).toFixed(1);
                }
                else{
                    percentage = 0;
                }

                tempObject = {x: new Date(element.year, 0, d) , y: parseFloat(percentage)};
                tempFlowersKesme[index] = tempObject;
            });
            flowersToprakli.forEach(function logArrayElements(element, index, array) {
                var d = (  (element.day - 1) * 7);

                if( element.day == 1 && element.year == 2018 ){
                    element.year = 2019;
                }

                if( element.countNumber != 0 ){
                    percentage = ( ( element.countNumber / ( total[index].countNumber ) ) * 100 ).toFixed(1);
                }
                else{
                    percentage = 0;
                }

                tempObject = {x: new Date(element.year, 0, d) , y: parseFloat(percentage)};
                tempFlowersToprakli[index] = tempObject;
            });
            chocolates.forEach(function logArrayElements(element, index, array) {
                var d = ( (element.day - 1) * 7);

                if( element.day == 1 && element.year == 2018 ){
                    element.year = 2019;
                }

                if( element.countNumber != 0 ){
                    percentage = ( ( element.countNumber / ( total[index].countNumber ) ) * 100 ).toFixed(1);
                }
                else{
                    percentage = 0;
                }

                tempObject = {x: new Date(element.year, 0, d) , y: parseFloat(percentage)};
                tempChocolates[index] = tempObject;
            });
            boxes.forEach(function logArrayElements(element, index, array) {
                var d = ( (element.day - 1) * 7);

                if( element.day == 1 && element.year == 2018 ){
                    element.year = 2019;
                }

                if( element.countNumber != 0 ){
                    percentage = ( ( element.countNumber / ( total[index].countNumber ) ) * 100 ).toFixed(1);
                }
                else{
                    percentage = 0;
                }

                tempObject = {x: new Date(element.year, 0, d) , y: parseFloat(percentage)};
                tempBoxes[index] = tempObject;
            });
            var options = {
                animationEnabled: true,
                theme: "light2",
                title:{
                    text: "Haftalık Satış (%)"
                },
                axisX:{
                    valueFormatString: "DD MMM YY",
                    interval:1,
                    intervalType: "month"
                },
                axisY: {
                    title: "Satış Yüzdesi",
                    minimum: -10,
                    maximum: 100,
                    interval:20,
                    suffix: "%"
                },
                toolTip:{
                    shared:true
                },
                legend:{
                    cursor:"pointer",
                    verticalAlign: "bottom",
                    horizontalAlign: "left",
                    dockInsidePlotArea: true,
                    itemclick: toogleDataSeries
                },
                data: [
                    {
                        type: "line",
                        showInLegend: true,
                        name: "Kesme Çiçek",
                        color: "#d73925",
                        yValueFormatString: "#,##0.##\"%\"",
                        dataPoints: tempFlowersKesme
                    },
                    {
                        type: "line",
                        showInLegend: true,
                        name: "Topraklı Çiçek",
                        color: "#3c8dbc",
                        yValueFormatString: "#,##0.##\"%\"",
                        dataPoints: tempFlowersToprakli
                    },
                    {
                        type: "line",
                        showInLegend: true,
                        name: "Çikolata",
                        yValueFormatString: "#,##0.##\"%\"",
                        dataPoints: tempChocolates
                    },
                    {
                        type: "line",
                        showInLegend: true,
                        name: "Hediye Kutusu",
                        color: "#008d4c",
                        yValueFormatString: "#,##0.##\"%\"",
                        dataPoints: tempBoxes
                    }
                ]
            };

            $("#chartContainer").CanvasJSChart(options);
            //$("#chartContainer").css('display' , 'none');

        });
    </script>
@elseif( $date == 'thismonth' )
    <script type="text/javascript">

        function toogleDataSeries(e){
            if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
                e.dataSeries.visible = false;
            } else{
                e.dataSeries.visible = true;
            }
            e.chart.render();
        }

        var flowersToprakli = {!! json_encode($groupByDayFlowerToprakli) !!};
        var flowersKesme = {!! json_encode($groupByDayFlowerKesme) !!};
        var chocolates = {!! json_encode($groupByDayChocolate) !!};
        var boxes = {!! json_encode($groupByDayBox) !!};
        var total= {!! json_encode($totalSales) !!};

        console.log(total);

        $(function () {
            var tempFlowersKesme = [];
            var tempFlowersToprakli = [];
            var tempChocolates = [];
            var tempBoxes = [];
            flowersKesme.forEach(function logArrayElements(element, index, array) {

                if( element.countNumber != 0 ){
                    percentage = ( ( element.countNumber / ( total[index].countNumber ) ) * 100 ).toFixed(1);
                }
                else{
                    percentage = 0;
                }

                tempObject = {x: new Date(element.year, element.month, 0) , y: parseFloat(percentage)};
                tempFlowersKesme[index] = tempObject;
            });
            flowersToprakli.forEach(function logArrayElements(element, index, array) {

                if( element.countNumber != 0 ){
                    percentage = ( ( element.countNumber / ( total[index].countNumber ) ) * 100 ).toFixed(1);
                }
                else{
                    percentage = 0;
                }

                tempObject = {x: new Date(element.year, element.month, 0) , y: parseFloat(percentage)};
                tempFlowersToprakli[index] = tempObject;
            });
            chocolates.forEach(function logArrayElements(element, index, array) {

                if( element.countNumber != 0 ){
                    percentage = ( ( element.countNumber / ( total[index].countNumber ) ) * 100 ).toFixed(1);
                }
                else{
                    percentage = 0;
                }

                tempObject = {x: new Date(element.year, element.month, 0) , y: parseFloat(percentage)};
                tempChocolates[index] = tempObject;
            });
            boxes.forEach(function logArrayElements(element, index, array) {

                if( element.countNumber != 0 ){
                    percentage = ( ( element.countNumber / ( total[index].countNumber ) ) * 100 ).toFixed(1);
                }
                else{
                    percentage = 0;
                }

                tempObject = {x: new Date(element.year, element.month, 0) , y: parseFloat(percentage)};
                tempBoxes[index] = tempObject;
            });
            var options = {
                animationEnabled: true,
                theme: "light2",
                title:{
                    text: "Aylık Satış (%)"
                },
                axisX:{
                    valueFormatString: "MMM YY",
                    interval:1,
                    intervalType: "month"
                },
                axisY: {
                    title: "Satış Yüzdesi",
                    minimum: -10,
                    maximum: 100,
                    interval:20,
                    suffix: "%"
                },
                toolTip:{
                    shared:true
                },
                legend:{
                    cursor:"pointer",
                    verticalAlign: "bottom",
                    horizontalAlign: "left",
                    dockInsidePlotArea: true,
                    itemclick: toogleDataSeries
                },
                data: [
                    {
                        type: "line",
                        showInLegend: true,
                        name: "Kesme Çiçek",
                        color: "#d73925",
                        yValueFormatString: "#,##0.##\"%\"",
                        xValueFormatString: "MMM, YYYY",
                        dataPoints: tempFlowersKesme
                    },
                    {
                        type: "line",
                        showInLegend: true,
                        name: "Topraklı Çiçek",
                        color: "#3c8dbc",
                        yValueFormatString: "#,##0.##\"%\"",
                        xValueFormatString: "MMM, YYYY",
                        dataPoints: tempFlowersToprakli
                    },
                    {
                        type: "line",
                        showInLegend: true,
                        name: "Çikolata",
                        yValueFormatString: "#,##0.##\"%\"",
                        xValueFormatString: "MMM, YYYY",
                        dataPoints: tempChocolates
                    },
                    {
                        type: "line",
                        showInLegend: true,
                        name: "Hediye Kutusu",
                        color: "#008d4c",
                        yValueFormatString: "#,##0.##\"%\"",
                        xValueFormatString: "MMM, YYYY",
                        dataPoints: tempBoxes
                    }
                ]
            };

            $("#chartContainer").CanvasJSChart(options);
            //$("#chartContainer").css('display' , 'none');

        });
    </script>
@endif

@stop