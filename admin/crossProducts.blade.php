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
                <h1>Bloom & Fresh Cross-Sell Ürün Listesi</h1>
            </td>
            <td>
                {!! Html::link('/admin/crossSell/product/create' , 'Ürün Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
            </td>
        </tr>
    </table>
    <button id="testXls" class="btn btn-danger"  onClick ="$('tr').each(function() {$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(0)').remove();$(this).find('th:eq(4)').remove();$(this).find('th:eq(5)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(0)').remove();$(this).find('td:eq(4)').remove();$(this).find('td:eq(4)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Ürün Listesi',filename: 'Ürün Listesi'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>

    <table style="max-width: 440px;margin-bottom: 0px;" id="filterTable" class="table table-hover">
        <tr style="width: 100%">
            <td style="padding-top: 15px;padding-left: 15px;">
                Ürün Durumu :
            </td>
            <td>
                <select style="width: 100px;" class="form-control select2" onchange="updateProducts();" id="tagId">
                    <option value="all">Hepsi</option>
                    <option selected value="1">Aktifler</option>
                    <option value="0">Pasifler</option>
                </select>
            </td>
        </tr>
    </table>

    <table id="products-table" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th style="width: 60px;">Durum</th>
            <th> Ürün Adı </th>
            <th> Açıklama </th>
            <th> Ekran Sirasi </th>
            <th> Fiyat </th>
            <th> Satış Adeti </th>
            <th style="width: 30px;">Fotoğraf</th>
            <th style="width: 90px;"> </th>
        </tr>

        @foreach($products as $product)
            <tr class="trClass all {{$product->status}} @if( $product->status == 0 ) hidden @endif" id="row-id-{{$product->id}}" >
                <td>
                    <div style="margin-bottom: 0px;margin-top: 0px;width: 20px;margin-left: auto;margin-right: auto;" class="checkbox">
                        <label>
                            {!! Form::checkbox('status', null, $product->status, ['style' => 'width:20px;height:20px;', 'disabled' => 'true']) !!}
                        </label>
                    </div>
                </td>
                <td>{{$product->name}}</td>
                <td>{{$product->desc}}</td>
                <td>{{$product->sort_number}}</td>
                <td>{{$product->price}}</td>
                <td>0</td>
                <td>
                    <div style="width: 20px;margin-left: auto;margin-right: auto;">
                        <a href="{{ $product->image }}" target="_blank">
                            <img style="box-shadow: 0px 1px 2px black;width: 20px;" src="{{ $product->image }}">
                        </a>
                    </div>
                </td>
                <td>
                    {!! Form::open(['action' => 'AdminPanelController@deleteCrossSellProduct', 'method' => 'post', 'id' => 'form_' . $product->id ]) !!}
                    {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                    <div style="    margin-bottom: 0px;" class="form-group">
                        <a href="/admin/crossSell/product/{{$product->id}}">
                            <i style="font-size: 25px;" class="fa fa-fw fa-cog"></i>
                        </a>
                        <a href="#" onclick="var x;if (confirm('Silmek istediğinize emin misiniz?') == true) {var tempId = '#form_{{$product->id}}';$(tempId).submit();return true;} else {return false;}" class="test">
                            <i style="font-size: 25px;color:red" class="fa fa-fw fa-remove"></i>
                        </a>
                        {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control test hidden' , 'style' => 'width:100%;', 'id' => 'deleteButton']) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
    </table>

@stop()

@section('footer')
    <script>
        $('.changeClass').click(function() {
            var tempId = $(this).attr('id').split("_")[1];
            var tempPast = '#past_' + tempId;
            var tempActive = '#active_' + tempId;
            console.log($(tempPast).val());
            console.log($(tempActive).is(':checked'));
            if($(tempActive).is(':checked') && $(tempPast).val() == 0 ){
                window.alert("Açtığın Ürünün Dağıtım Saatlerini Güncellemeyi Unutma!");
            }
        });

        $('#products-table').click(function(event){
            event.stopPropagation();
        });

        $( document ).ready(function() {

            /*$('.trClass').addClass('hidden');
            $('.trClass').each(function(i, obj) {

                if( $(obj).hasClass('1') ){
                    $(obj).removeClass('hidden');
                }

            });*/

            setTimeout(function(){
                $('.trClass').addClass('hidden');
                $('.trClass').each(function(i, obj) {

                    if( $(obj).hasClass('1') ){
                        $(obj).removeClass('hidden');
                    }

                });
            }, 1000);

        });

        function updateProducts() {

            console.log('test');

            tempTagId = $('#tagId').val();

            $('.trClass').addClass('hidden');

            $('.trClass').each(function(i, obj) {

                if( $(obj).hasClass(tempTagId) ){
                    $(obj).removeClass('hidden');
                }

            });

        }

        // go to anchor
    </script>
@stop