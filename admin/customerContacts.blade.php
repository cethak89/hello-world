@extends('newApp')

@section('html-head')
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
                <h1>Müşteri Contact Listesi</h1>
            </td>
            <td style="vertical-align: middle;">
                <button id="testXls" class="btn btn-danger pull-right"  onClick ="$('#example1').table2excel({exclude: '.excludeThisClass',name: 'Alıcı Listesi',filename: 'Alıcı Listesi'});">Excel Çıktısı İçin Tıklayınız</button>
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
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body">
                    <table  style="width: 100%; font-size: 14px;" id="example1" data-width="100%" data-compression="6" data-min="1" data-max="14" cellpadding="0" cellspacing="0" class="table table-bordered table-striped responsive responsiveTable">
                        <thead>
                            <tr>
                                <th>Kayıt Tarihi</th>
                                <th>Id</th>
                                <th>Ad Soyad</th>
                                <th>Customer Id</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contactList as $key => $contact)
                                <tr>
                                    <td>{{$contact->created_at}}</td>
                                    <td>{{$contact->id}}</td>
                                    <td>{{$contact->name}} {{$contact->surname}}</td>
                                    <td>{{$contact->customer_id}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Kayıt Tarihi</th>
                                <th>Id</th>
                                <th>Ad Soyad</th>
                                <th>Customer Id</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop()
@section('footer')
    <script>
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
    </script>
@stop