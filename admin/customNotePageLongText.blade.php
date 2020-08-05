@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        div.form-group {
            height: 20px;
        }
    </style>
@stop

@section('content')

    <style>
        .form-group {
            margin-bottom: 0px;
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

    <div class="newFont mainContent hidden col-lg-12" style="position: relative;display: list-item;height: 210mm;padding: 0px;">
        <img src="/img/bnfNew.jpg" style="width: 148mm;visibility: hidden;">
        <div style="position: absolute;
    left: 61mm;
    top: 44mm;
    transform: rotate(45deg);
    width: 73mm;
    height: 5mm;
    text-align: center;">
            <p id="receiver_name_p" style="display: table-cell;vertical-align: middle;">Receiver</p>
        </div>
        <div style="    position: absolute;
    left: 46mm;
    top: 50mm;
    transform: rotate(45deg);
    width: 81mm;
    height: 26mm;
    text-align: left;">
            <p id="note_p" style="line-height: 4.3mm;white-space: pre-line;font-size: 13px">Note</p>
        </div>
        <div style="position: absolute;
    left: 24mm;
    top: 80mm;
    transform: rotate(45deg);
    width: 88mm;
    height: 5mm;
    text-align: right;
    font-size: 13px;">
            <p id="sender_name_p" style="    vertical-align: middle;
    margin-left: auto;
    display: block;
    font-size: 13px;
    letter-spacing: -0.2px;">Sender</p>
        </div>
        <div style="position: absolute;left: 101mm;top: 124mm;transform: rotate(45deg);width: 41mm;height: 20mm;text-align: center;">
            <p id="picker_name_p" style="display: table-cell;vertical-align: middle;height: 78px;width: 163px;">Name</p>
        </div>
    </div>

    <h1 class="col-lg-9 col-md-9 hideForPrint">Bloom & Fresh Custom Not Oluşturma Sayfası</h1>

    <table id="products-table" class="hideForPrint table table-hover table-bordered" style="vertical-align: middle;">
        <div id="trPart"></div>
        <tr>
            <td style="vertical-align: inherit;font-size: 16px;padding-left: 20px;width: 220px;min-width: 220px">
                Alıcı adı(Kart içi):
            </td>
            <td>
                <div class="form-group">
                    <input id="receiver_name" style="width: 500px;" class="form-control" name="receiver_name">
                </div>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: inherit;font-size: 16px;padding-left: 20px;width: 220px;">
                Not:
            </td>
            <td>
                <div class="form-group">
                    <textarea id="note" style="width: 500px;margin-top: 0px;margin-bottom: 0px;height: 100px;min-height: 100px;max-width: 1000px;min-width: 500px;" class="form-control" name="note"></textarea>
                </div>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: inherit;font-size: 16px;padding-left: 20px;width: 220px;">
                Gönderen adı(Kart için):
            </td>
            <td>
                <div class="form-group">
                    <input id="sender_name" style="width: 500px;" class="form-control" name="sender_name">
                </div>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: inherit;font-size: 16px;padding-left: 20px;width: 220px;">
                Alıcı adı(Ön yüz):
            </td>
            <td>
                <div class="form-group">
                    <input id="picker_name" style="width: 500px;" class="form-control" name="picker_name">
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <input style="width: 205px;display: block;margin-top: 10px;" onclick="produceForm();"
                       class="btn btn-success form-control saveProdcut" id="saveProduct" value="Yazdır">
            </td>
        </tr>
    </table>

@stop()

@section('footer')
    <script>

        function produceForm() {

            $('.hideForPrint').addClass('hidden');

            $('#receiver_name_p').text($('#receiver_name').val());
            $('#note_p').html($('#note').val());
            $('#sender_name_p').text($('#sender_name').val());
            $('#picker_name_p').text($('#picker_name').val());

            $('.mainContent').removeClass('hidden');

            setTimeout(function(){ window.print(); }, 1000);


        }

    </script>
@stop