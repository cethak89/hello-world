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
                    Üretim Özet-Ürün Bazında
                </h1>
            </td>
            <td>
            </td>
        </tr>
    </table>
    <a style="margin-left: 15px;width: 212px;margin-bottom: 5px; @if( $date == 'today' ) text-decoration: underline; @endif " onclick="updateDateButton('today')" class="btn btn-info form-control">Bugün</a>
    <a style="width: 212px;margin-bottom: 5px;@if( $date == 'thisweek' ) text-decoration: underline; @endif" onclick="updateDateButton('thisweek')" class="btn btn-warning form-control">Bu Hafta</a>
    <br>
    <a style="margin-left: 15px;width: 212px;margin-bottom: 5px;color: #00c0ef;background-color: white;border-color: #00c0ef;@if( $date == 'yesterday' ) text-decoration: underline; @endif" onclick="updateDateButton('yesterday')" class="btn btn-primary form-control">Geçen Hafta Bugün</a>
    <a style="width: 212px;margin-bottom: 5px;background-color: #ffffff;border-color: #f39c12;color: #f39c12;@if( $date == 'lastweek' ) text-decoration: underline; @endif" onclick="updateDateButton('lastweek')" class="btn btn-success form-control">Geçen Hafta</a>
    <input class="hidden" id="dateId" value="{{$date}}">
    <div style="padding-left: 15px;">
        <table style="width: 426px;font-size: 18px;margin-bottom: 2px;" class="table table-hover">
            <tr>
                <td style="vertical-align: inherit;width: 140px;">
                    Kategori
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="width: 270px;" name="category" class="form-control"  id="category" onchange="updateCategory($(this).val())">
                            <option @if( $category == 0 ) selected @endif value="0">Hepsi</option>
                            <option @if( $category == 1 ) selected @endif value="1">Çiçek</option>
                            <option @if( $category == 2 ) selected @endif value="2">Çikolata</option>
                            <option @if( $category == 3 ) selected @endif value="3">Hediye Kutusu</option>
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: inherit;">
                    Alt Kategori
                </td>
                <td>
                    <div style="margin-bottom: 0px;" class="form-group">
                        <select style="width: 270px;" name="sub_category" class="form-control" id="sub_category" onchange="updateSubCategory($(this).val())">
                            <option @if( $sub_category == 0 ) selected @endif value="0">Hepsi</option>
                            <option @if( $sub_category == 11 ) selected @endif value="11">Buket</option>
                            <option @if( $sub_category == 12 ) selected @endif value="12">Masaüstü</option>
                            <option @if( $sub_category == 13 ) selected @endif value="13">Sukulent</option>
                            <option @if( $sub_category == 14 ) selected @endif value="14">Saksı</option>
                            <option @if( $sub_category == 15 ) selected @endif value="15">Orkide</option>
                            <option @if( $sub_category == 16 ) selected @endif value="16">Solmayan Gül</option>
                            <option @if( $sub_category == 16 ) selected @endif value="17">Kutuda Çiçek</option>
                            <option @if( $sub_category == 21 ) selected @endif value="21">Godiva</option>
                            <option @if( $sub_category == 22 ) selected @endif value="22">Baylan</option>
                            <option @if( $sub_category == 23 ) selected @endif value="23">BNF Macarons</option>
                            <option @if( $sub_category == 24 ) selected @endif value="24">Hazz</option>
                            <option @if( $sub_category == 25 ) selected @endif value="25">TAFE</option>
                            <option @if( $sub_category == 31 ) selected @endif value="31">BNF Kutu</option>
                            <option @if( $sub_category == 32 ) selected @endif value="32">Godiva Kutu</option>
                            <option @if( $sub_category == 33 ) selected @endif value="33">TAFE Kutu</option>
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Tarih Aralığı
                </td>
                <td>
                    <input class="form-control formatDate" style="width: 133px;display: inline;" data-date-format="DD MM YYYY" id="startDate" onchange="updateStartDate($(this).val())" data-date="" autocomplete="off" type="text" value="{{$start_date}}">
                    <input class="form-control formatDate" style="width: 133px;display: inline;" data-date-format="DD MM YYYY" id="endDate" onchange="updateEndDate($(this).val())" data-date="" autocomplete="off" type="text" value="{{$end_date}}">
                </td>
            </tr>
        </table>
    </div>
    <a style="margin-left: 15px;width: 143px;margin-bottom: 15px;background-color: #3c8dbc;border-color: #3c8dbc;color: white;" href="https://everybloom.com/produceByProduct" class="btn form-control">Temizle</a>
    <a style="margin-left: 2px;width: 277px;margin-bottom: 15px;" onclick="filterPage();" class="btn btn-success form-control">Sorgula</a>
    <div style="background: white;" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;max-width: 425px;font-size: 18px;">
            <tbody>
            <tr>
                <th>
                    Ürün Adı
                </th>
                <th style="width: 60px;">
                    Adet
                </th>
                <th style="width: 60px;">
                    Yüzde
                </th>
            </tr>
            @foreach( $products as $product )
                <tr>
                    <td>
                        {{$product->name}}
                    </td>
                    <td>
                        {{$product->saleCount}}
                    </td>
                    <td>
                        {{ number_format($product->saleCount / $total *100 ,1)   }}%
                    </td>
                </tr>
            @endforeach
            <tr>
                <td style="font-weight: bold;">Toplam</td>
                <td style="font-weight: bold;">{{$total}}</td>
                <td style="font-weight: bold;">100.0%</td>
            </tr>
            </tbody>
        </table>
    </div>
@stop()

@section('footer')

    <script>

        var category = '';
        var sub_category = '';
        var date_start = '';
        var date_end = '';
        var dateButton = '';

        $( document ).ready(function() {
            if($('#category').val()){
                updateCategory($('#category').val());
            }

            if($('#sub_category').val()){
                updateSubCategory($('#sub_category').val());
            }

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

        function updateCategory(tempCategory) {
            category = tempCategory;
        }

        function updateSubCategory(tempCategory) {
            sub_category = tempCategory;
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

            tempUrl = 'https://everybloom.com/produceByProduct?';

            if(category){
                tempUrl = tempUrl + 'category='  + category + '&'
            }

            if(sub_category){
                tempUrl = tempUrl + 'sub_category='  + sub_category + '&'
            }

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

@stop