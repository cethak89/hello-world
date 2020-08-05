@extends('newApp')

@section('html-head')
    <style>
        .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th{
            vertical-align: middle;
        }
        div.form-group {
            height: 20px;
        }
    </style>
@stop

@section('content')
    <table width="100%">
        <tr style="width: 100%">
            <td>
                <h1 style="margin-left: 15px;">Bloom & Fresh Kupon Oluşturma Sayfası</h1>
            </td>
        </tr>
    </table>
    {!! Form::model(null , ['action' => 'newFunctions@insertDailyCoupon', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <td>
                Kupon Adı :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('name', null , ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Kupon Açıklaması :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('description', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Kod :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('code', null , ['class' => 'form-control', 'style' => 'width: 150px;' ]) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Kullanım Sayısı:
            </td>
            <td>
                <div style="margin-bottom: 0px;" class="form-group">
                    <select style="width: 100px;" class="form-control select" name="using_count">
                        <option selected value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Kupon Tipi:
            </td>
            <td>
                <div style="margin-bottom: 0px;" class="form-group">
                    <select style="width: 100px;" class="form-control select" onchange="changeValue($(this).val());" name="type">
                        <option value="1">TL</option>
                        <option selected value="2">Yüzde</option>
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Kupon Değeri :
            </td>
            <td>
                <div style="margin-bottom: 0px;" class="form-group">
                    <select style="width: 100px;" class="form-control select" name="value" id="values">
                        <option class="allValues 2" value="5">5</option>
                        <option class="allValues 2" value="10">10</option>
                        <option class="allValues 2" value="15">15</option>
                        <option selected class="allValues 2" value="20">20</option>
                        <option class="allValues 2" value="25">25</option>
                        <option class="allValues 2" value="30">30</option>
                        <option class="allValues 2" value="40">40</option>
                        <option class="allValues 1 hidden" value="25">25</option>
                        <option class="allValues 1 hidden" value="50">50</option>
                        <option class="allValues 1 hidden" value="100">100</option>
                        <option class="allValues 1 hidden" value="150">150</option>
                        <option class="allValues 1 hidden" value="250">250</option>
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Zaman Aralığı:
            </td>
            <td>
                <div style="margin-bottom: 0px;" class="form-group">
                    <div class="input-group">
                        <input autocomplete="off" style="width: 300px;" type="text" class="form-control pull-right" name="timeRange" id="reservationtime">
                    </div>
                </div>
            </td>
        </tr>
        <div class="form-group">
        </div>
    </table>
    {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control', 'style' => 'width: 150px;margin-left: 10px;' ]) !!}
    {!! Form::close() !!}
    <script>
        $(".select").select();

        function changeValue(salesId){
            var tempTr = '.' + salesId;
            $('.allValues').addClass('hidden');
            $('.allValues').removeAttr('selected');;
            $(tempTr).removeClass('hidden');
            $(tempTr).attr('selected', '1');
        }


    </script>
@stop()