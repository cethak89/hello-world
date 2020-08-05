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
</style>

@section('content')
    <div style="overflow-x: scroll;transform: rotateX(180deg);">
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;transform: rotateX(180deg);">
            <tr>
                <th>id</th>
                <th>name</th>
                <th>product_type</th>
                <th>kdv</th>
                <th>price</th>
                <th>cargo_sendable</th>
                <th>detail_page_desc</th>
                <th>how_to_detail</th>
                <th>how_to_title</th>
                <th>how_to_step1</th>
                <th>how_to_step2</th>
                <th>how_to_step3</th>
                <th>extra_info_1</th>
                <th>extra_info_2</th>
                <th>extra_info_3</th>
                <th>main_image</th>
                <th>extra_image_0</th>
            </tr>
            @foreach($flowerList as $flower)
                <tr>
                    <td>{{$flower->id}}</td>
                    <td>{{$flower->name}}</td>
                    <td>{{$flower->product_type}}</td>
                    <td> @if( $flower->product_type == 2 ) 8% @else 18% @endif </td>
                    <td>{{$flower->price}}</td>
                    <td>0</td>
                    <td>{{$flower->detail_page_desc}}</td>
                    <td>{{$flower->how_to_detail}}</td>
                    <td>{{$flower->how_to_title}}</td>
                    <td>{{$flower->how_to_step1}}</td>
                    <td>{{$flower->how_to_step2}}</td>
                    <td>{{$flower->how_to_step3}}</td>
                    <td>{{$flower->extra_info_1}}</td>
                    <td>{{$flower->extra_info_2}}</td>
                    <td>{{$flower->extra_info_3}}</td>
                    <td>{{$flower->MainImage}}</td>
                    <td> @foreach( $flower->detailListImage as $image  ) {{$image}} @endforeach </td>
                </tr>
            @endforeach
        </table>
    </div>
@stop()

@section('footer')

    <script>
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