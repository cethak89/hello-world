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
<script type="text/javascript" src="/js/libs/tableSorter/jquery.tablesorter.js"></script>
    <table width="100%">
        <tr style="width: 100%">
            <td>
                <h1>Bloom & Fresh Ürün Bazlı Teslimat Saatleri</h1>
            </td>
            <td>
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
    <button id="testXls"class="btn btn-danger"  onClick ="$('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Mesajlar',filename: 'Mesajlar'});">Excel Çıktısı İçin Tıklayınız</button>
    <table id="products-table" class="tablesorter table table-hover table-bordered" style="vertical-align: middle;">
        <thead>
            <tr>
                <th class="header" style="cursor: pointer;">Ürün Adı</th>
                <th style="cursor: pointer;">Mevcut Olacağı Tarih</th>
                <th style="cursor: pointer;">Satılamayacağı Tarih</th>
                <th style="cursor: pointer;"></th>
            </tr>
        </thead>
        <tbody>
        @foreach($productList as $product)
            @if($id == $product->id)
                {!! Form::model($product, ['action' => 'AdminPanelController@updateProductDeliveryTime', 'files'=>true ]) !!}
                <tr style="@if($product->activation_status_id == 1)
                                background-color: #E2E1E1;
                                @else
                                @endif
                                " id="row-id-{{$product->id}}">
                    <td style="padding-left:21px;">{{$product->name}}</td>
                    <td style="padding-left:21px;">
                        <table>
                            <tr>
                                <td>
                                    <input id="dateId" required type="date" value="{{explode( " " ,$product->avalibility_time)[0]}}"  class ='form-control formatDate'  data-date-format ='DD MM YYYY'  data-date = '' name="avalibility_time">
                                </td>
                                <td>
                                    <input id="dateHour" required class="form-control" value="{{explode(":" , explode( " " ,$product->avalibility_time)[1] )[0] }}" style="width: 41px;height: 40px;" type="text" maxlength="2"  name="hour">
                                </td>
                                <td>
                                    <input id="dateMin" required class="form-control" value="{{explode(":" , explode( " " ,$product->avalibility_time)[1] )[1]}}" style="width: 41px;height: 40px;" type="text" maxlength="2"   name="minute">
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="padding-left:21px;">
                        <table>
                            <tr>
                                <td>
                                    <input id="dateIdEnd" required type="date" value="{{explode( " " ,$product->avalibility_time_end)[0]}}"  class ='form-control formatDate'  data-date-format ='DD MM YYYY'  data-date = '' name="avalibility_time_end">
                                </td>
                                <td>
                                    <input id="dateHourEnd" required class="form-control" value="{{explode(":" , explode( " " ,$product->avalibility_time_end)[1] )[0] }}" style="width: 41px;height: 40px;" type="text" maxlength="2"  name="hour_end">
                                </td>
                                <td>
                                    <input id="dateMinEnd" required class="form-control" value="{{explode(":" , explode( " " ,$product->avalibility_time_end)[1] )[1]}}" style="width: 41px;height: 40px;" type="text" maxlength="2"   name="minute_end">
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                    </td>
                </tr>
                {!! Form::close() !!}
            @else
                <tr style="@if($product->activation_status_id == 1)
                                                    background-color: #ECF7D6;
                                                    @else
                                                    @endif
                                                    ">
                    <td style="padding-left:21px;">{{$product->name}}</td>
                    <td style="padding-left:21px;">{{explode( " " ,$product->avalibility_time)[0]}} - {{explode(":" , explode( " " ,$product->avalibility_time)[1] )[0] }}:{{explode(":" , explode( " " ,$product->avalibility_time)[1] )[1]}}</td>
                    <td style="padding-left:21px;">{{explode( " " ,$product->avalibility_time_end)[0]}} - {{explode(":" , explode( " " ,$product->avalibility_time_end)[1] )[0] }}:{{explode(":" , explode( " " ,$product->avalibility_time_end)[1] )[1]}}</td>
                    <td>
                        <div class="form-group">
                            {!! Html::link('/admin/product-delivery-time/' . $product->id   , 'Değiştir', ['class' => 'btn btn-primary form-control']) !!}
                        </div>
                    </td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>

@stop()

@section('footer')
        <script>
            $(document).ready(function()
                {
                    $("#products-table").tablesorter( );
                }
            );
            $( document ).ready(function() {
                if($('#dateId').attr('data-date') == 'Invalid date')
                    $('#dateId').attr('data-date' , '');
                if($('#dateIdEnd').attr('data-date') == 'Invalid date')
                    $('#dateIdEnd').attr('data-date' , '');
            });
        </script>
@stop