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

        @media print
        {
            .no-print, .no-print *
            {
                display: none !important;
            }
        }

    </style>

    @foreach($tempQueryList as $key=>$product )
        @if( $key%6 == 0 )
            <div class="newFont col-lg-12" style="position: relative;display: list-item;height: 297mm;padding: 0px;transform: rotate(180deg);">
                <img src="/img/bgPrint.JPG" class="no-print"
                     style="width: 210mm;height: 297mm;transform: rotate(180deg);margin-left: -10px;margin-top: -2px;visibility: hidden;">
                <div style="position: absolute;bottom: 24mm;left: 26mm;transform: rotate(-90deg);width: 33mm;text-align: center;">
                    <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">{{$product->receiver}}</p>
                </div>
                <div style="position: absolute;bottom: 73mm;left: 18mm;transform: rotate(-90deg);width: 43mm;text-align: center;height: 33mm;text-align: center;">
                    <p style="width: 43mm;margin-left: auto;margin-right: auto;text-align: center;line-height: 4.3mm;height: 33mm;">{{$product->card_message}}</p>
                </div>
                <div style="position: absolute;bottom: 86mm;left: 42mm;transform: rotate(-90deg);width: 40mm;height: 11mm;text-align: center;">
                    <p style="width: 40mm;display: table-cell;line-height: 5mm;height: 10mm;text-align: -webkit-center;vertical-align: middle;">{{$product->sender}}</p>
                </div>
                @elseif( $key%6 == 1 )
                    <div style="position: absolute;bottom: 25mm;left: 87mm;transform: rotate(-90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">{{$product->receiver}}</p>
                    </div>
                    <div style="position: absolute;bottom: 73mm;left: 78mm;transform: rotate(-90deg);width: 43mm;text-align: center;height: 33mm;text-align: center;">
                        <p style="width: 43mm;margin-left: auto;margin-right: auto;display: -webkit-box;line-height: 4.3mm;height: 33mm;">{{$product->card_message}}</p>
                    </div>
                    <div style="position: absolute;bottom: 85mm;left: 102mm;transform: rotate(-90deg);width: 40mm;height: 11mm;text-align: center;">
                        <p style="width: 40mm;display: table-cell;line-height: 5mm;height: 10mm;text-align: -webkit-center;vertical-align: middle;">{{$product->sender}}</p>
                    </div>
                @elseif( $key%6 == 2 )
                    <div style="position: absolute;bottom: 24mm;left: 148mm;transform: rotate(-90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">{{$product->receiver}}</p>
                    </div>
                    <div style="position: absolute;bottom: 73mm;left: 137mm;transform: rotate(-90deg);width: 43mm;text-align: center;height: 33mm;text-align: center;">
                        <p style="width: 43mm;margin-left: auto;margin-right: auto;display: -webkit-box;line-height: 4.3mm;height: 33mm;">{{$product->card_message}}</p>
                    </div>
                    <div style="position: absolute;bottom: 86mm;left: 162mm;transform: rotate(-90deg);width: 40mm;height: 11mm;text-align: center;">
                        <p style="width: 40mm;display: table-cell;line-height: 5mm;height: 10mm;text-align: -webkit-center;vertical-align: middle;">{{$product->sender}}</p>
                    </div>
                @elseif( $key%6 == 3 )
                    <div style="position: absolute;bottom: 178mm;left: 28mm;transform: rotate(-90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">{{$product->receiver}}</p>
                    </div>
                    <div style="position: absolute;bottom: 229mm;left: 19mm;transform: rotate(-90deg);width: 43mm;text-align: center;height: 33mm;text-align: center;">
                        <p style="width: 43mm;margin-left: auto;margin-right: auto;text-align: center;line-height: 4.3mm;height: 33mm;">{{$product->card_message}}</p>
                    </div>
                    <div style="position: absolute;bottom: 240mm;left: 42mm;transform: rotate(-90deg);width: 40mm;height: 11mm;text-align: center;">
                        <p style="width: 40mm;display: table-cell;line-height: 5mm;height: 10mm;text-align: -webkit-center;vertical-align: middle;">{{$product->sender}}</p>
                    </div>
                @elseif( $key%6 == 4 )
                    <div style="position: absolute;bottom: 180mm;left: 87mm;transform: rotate(-90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">{{$product->receiver}}</p>
                    </div>
                    <div style="position: absolute;bottom: 229mm;left: 79mm;transform: rotate(-90deg);width: 43mm;text-align: center;height: 33mm;text-align: center;">
                        <p style="width: 43mm;margin-left: auto;margin-right: auto;display: -webkit-box;line-height: 4.3mm;height: 33mm;">{{$product->card_message}}</p>
                    </div>
                    <div style="position: absolute;bottom: 240mm;left: 102mm;transform: rotate(-90deg);width: 40mm;height: 11mm;text-align: center;">
                        <p style="width: 40mm;display: table-cell;line-height: 5mm;height: 10mm;text-align: -webkit-center;vertical-align: middle;">{{$product->sender}}</p>
                    </div>
                @elseif( $key%6 == 5 )
                    <div style="position: absolute;bottom: 180mm;left: 147mm;transform: rotate(-90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">{{$product->receiver}}</p>
                    </div>
                    <div style="position: absolute;bottom: 228mm;left: 140mm;transform: rotate(-90deg);width: 43mm;text-align: center;height: 33mm;text-align: center;">
                        <p style="width: 43mm;margin-left: auto;margin-right: auto;display: -webkit-box;line-height: 4.3mm;height: 33mm;">{{$product->card_message}}</p>
                    </div>
                    <div style="position: absolute;bottom: 240mm;left: 163mm;transform: rotate(-90deg);width: 40mm;height: 11mm;text-align: center;">
                        <p style="width: 40mm;display: table-cell;line-height: 5mm;height: 10mm;text-align: -webkit-center;vertical-align: middle;">{{$product->sender}}</p>
                    </div>
        @endif
    @endforeach
    @foreach($tempQueryList as $key=>$product )
        @if( $key%6 == 0 )
            </div>
            <div class="newFont col-lg-12" style="position: relative;display: list-item;height: 297mm;padding: 0px;transform: rotate(180deg);">
                <img src="/img/cardFront.JPG" class="no-print"
                     style="width: 210mm;height: 297mm;transform: rotate(180deg);margin-left: -10px;margin-top: -2px;visibility: hidden;">
                <div style="position: absolute;bottom: 27mm;left: 127mm;transform: rotate(90deg);width: 33mm;text-align: center;">
                    <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">Sn. {{$product->name}} {{$product->surname}}</p>
                </div>
                @elseif( $key%6 == 1 )
                    <div style="position: absolute;bottom: 27mm;left: 66mm;transform: rotate(90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">Sn. {{$product->name}} {{$product->surname}}</p>
                    </div>
                @elseif( $key%6 == 2 )
                    <div style="position: absolute;bottom: 26mm;left: 7mm;transform: rotate(90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">Sn. {{$product->name}} {{$product->surname}}</p>
                    </div>
                @elseif( $key%6 == 3 )
                    <div style="position: absolute;bottom: 181mm;left: 126mm;transform: rotate(90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">Sn. {{$product->name}} {{$product->surname}}</p>
                    </div>
                @elseif( $key%6 == 4 )
                    <div style="position: absolute;bottom: 180mm;left: 66mm;transform: rotate(90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">Sn. {{$product->name}} {{$product->surname}}</p>
                    </div>
                @elseif( $key%6 == 5 )
                    <div style="position: absolute;bottom: 180mm;left: 7mm;transform: rotate(90deg);width: 33mm;text-align: center;">
                        <p style="width: 130px;height: 40px;margin-left: auto;margin-right: auto;display: table-cell;vertical-align: middle;">Sn. {{$product->name}} {{$product->surname}}</p>
                    </div>
        @endif
    @endforeach
            </div>

    <script>
        $(document).ready(function () {
            window.print();
        });
    </script>
@stop()