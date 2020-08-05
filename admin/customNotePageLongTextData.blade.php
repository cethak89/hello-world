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

    @foreach($allReceiver as $key=>$product )
        <div class="newFont col-lg-12" style="position: relative;display: list-item;height: 210mm;padding: 0px;">
            <img src="/img/bnfNew.jpg" style="width: 148mm;visibility: hidden;">
            <div style="position: absolute;left: 61mm;top: 44mm;transform: rotate(45deg);width: 73mm;height: 5mm;text-align: center;">
                <p style="display: table-cell;vertical-align: middle;">{{$product->A}}</p>
            </div>
            <div style="position: absolute;left: 46mm;top: 50mm;transform: rotate(45deg);width: 81mm;height: 26mm;text-align: left;">
                <p style="line-height: 4.3mm;font-size: 13px;white-space: pre-line;">Kadının gücüne ve toplumdaki katma değerinin büyüklüğüne inanan Eurofins, Tüketici Ürünleri Türkiye şirketinde yarattığı istihdamın %60'ından fazlasının kadın olmasıyla gurur duymaktadır. Kaliteli ve güvenli ürünlerin tüm dünyaya ulaşmasında el ele çalıştığımız siz değerli paydaşlarımızın 8 Mart Dünya Kadınlar Günü'nü kutluyoruz.</p>
            </div>
            <div style="position: absolute;left: 24mm;top: 80mm;transform: rotate(45deg);width: 88mm;height: 5mm;text-align: right;font-size: 13px;">
                <p style="vertical-align: middle;margin-left: auto;display: block;font-size: 13px;letter-spacing: -0.2px;">Eurofins Tüketici Ürünleri Test Hizmetleri Türkiye Ekibi</p>
            </div>
            <div style="position: absolute;left: 101mm;top: 124mm;transform: rotate(45deg);width: 41mm;height: 20mm;text-align: center;">
                <p style="display: table-cell;vertical-align: middle;height: 78px;width: 163px;">Sn. {{$product->A}}</p>
            </div>
        </div>
    @endforeach

    <script>
        $(document).ready(function () {
            window.print();
        });
    </script>

@stop()

@section('footer')
@stop