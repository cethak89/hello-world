@extends('newApp')

@section('html-head')

    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        @font-face {
            font-family: "mvboli";
            src: url("/fonts/mvboli.ttf") format("ttf");
        }

        div.form-group {
            height: 20px;
        }

        .content-wrapper {
        }
    </style>
@stop

@section('content')
    <style>
        .content-wrapper {
        }

        @font-face {
            font-family: 'mv_boliregular';
            src: url('/fonts/BradleyHandITCTT-Bold.woff') format('woff');
            font-weight: normal;
            font-style: normal;
        }

        .newFont {
            font-family: mv_boliregular;
        }

        @media print {
            .no-print, .no-print * {
                display: none !important;
            }
        }

    </style>

    @foreach($tempQueryList as $key=>$product )
        <div class="newFont col-lg-12" style="position: relative;display: list-item;height: 210mm;padding: 0px;">
            <img src="/img/bnfNew.jpg" style="width: 148mm;visibility: hidden;">
            <div style="position: absolute;left: 61mm;top: 49mm;transform: rotate(45deg);width: 73mm;height: 5mm;text-align: center;">
                <p style="display: table-cell;vertical-align: middle;">{{$product->receiver}}</p>
            </div>
            <div style="position: absolute;left: 44mm;top: 57mm;transform: rotate(45deg);width: 81mm;height: 26mm;text-align: left;">
                <p style="line-height: 4.3mm;">{{$product->card_message}}</p>
            </div>
            <div style="position: absolute;left: 28mm;top: 77mm;transform: rotate(45deg);width: 81mm;height: 5mm;text-align: right;">
                <p style="vertical-align: middle;margin-left: auto;display: block;">{{$product->sender}}</p>
            </div>
            <div style="position: absolute;left: 101mm;top: 124mm;transform: rotate(45deg);width: 41mm;height: 20mm;text-align: center;">
                <p style="display: table-cell;vertical-align: middle;height: 78px;width: 163px;">Sn. {{$product->name}} {{$product->surname}}</p>
            </div>
        </div>
    @endforeach

    <script>
        $(document).ready(function () {
            window.print();
        });
    </script>
@stop()