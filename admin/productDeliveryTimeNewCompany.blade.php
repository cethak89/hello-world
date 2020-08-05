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
        <th class="header" style="cursor: pointer;">Ürün Adı</th>
        <th style="cursor: pointer;">Mevcut Olacağı Tarih</th>
        <th style="cursor: pointer;">Satılamayacağı Tarih</th>
        <th style="cursor: pointer;"></th>
        </thead>
        <tbody>
        @foreach($productList as $product)
            @if($id == $product->id)
                <tr style="@if($product->activation_status_id == 1)
                        background-color: #E2E1E1;
                @else
                @endif
                        " id="row-id-{{$product->id}}">
                    {!! Form::model($product, ['action' => 'AdminPanelController@updateProductDeliveryTimeCompany', 'files'=>true, 'id' => $product->id ]) !!}
                    <td style="padding-left:21px;">{{$product->name}}</td>
                    <td style="padding-left:21px;">
                        <table>
                            <tr>
                                <td>
                                    <div class="input-group date">
                                        <input type="text" name="avalibility_time" value="{{explode( " " ,$product->avalibility_time)[0]}}" class="form-control pull-right"  id="datepicker">
                                    </div>
                                </td>
                                <td>
                                    <input id="dateHour" required class="form-control" value="{{explode(":" , explode( " " ,$product->avalibility_time)[1] )[0] }}" style="width: 41px;height: 34px;" type="text" maxlength="2"  name="hour">
                                </td>
                                <td>
                                    <input id="dateMin" required class="form-control" value="{{explode(":" , explode( " " ,$product->avalibility_time)[1] )[1]}}" style="width: 41px;height: 34px;" type="text" maxlength="2"   name="minute">
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="padding-left:21px;">
                        <table>
                            <tr>
                                <td>
                                    <div class="input-group date">
                                        <input  name="avalibility_time_end" type="text" value="{{explode( " " ,$product->avalibility_time_end)[0]}}" class="form-control pull-right"  id="datepickerEnd">
                                    </div>
                                </td>
                                <td>
                                    <input id="dateHourEnd" required class="form-control" value="{{explode(":" , explode( " " ,$product->avalibility_time_end)[1] )[0] }}" style="width: 41px;height: 34px;" type="text" maxlength="2"  name="hour_end">
                                </td>
                                <td>
                                    <input id="dateMinEnd" required class="form-control" value="{{explode(":" , explode( " " ,$product->avalibility_time_end)[1] )[1]}}" style="width: 41px;height: 34px;" type="text" maxlength="2"   name="minute_end">
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                    </td>
                    {!! Form::close() !!}
                </tr>
            @else
                <tr style="@if($product->activation_status_id == 1)
                        background-color: #ECF7D6;
                @else
                @endif
                        ">
                    <td style="padding-left:21px;">{{$product->name}}</td>
                    <td style="padding-left:21px;">{{explode( " " ,$product->avalibility_time)[0]}} - {{explode(":" , explode( " " ,$product->avalibility_time)[1] )[0] }}:{{explode(":" , explode( " " ,$product->avalibility_time)[1] )[1]}}</td>
                    <td style="padding-left:21px;">{{explode( " " ,$product->avalibility_time_end)[0]}} - {{explode(":" , explode( " " ,$product->avalibility_time_end)[1] )[0] }}:{{explode(":" , explode( " " ,$product->avalibility_time_end)[1] )[1]}}</td>
                    <td data-order="{{$product->activation_status_id}}">
                        <div class="form-group">
                            {!! Html::link('/admin/product-delivery-time-company/' . $product->id   , 'Değiştir', ['class' => 'btn btn-primary form-control']) !!}
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


        //$('#products-table').DataTable({
        //    "paging": false,
        //    "lengthChange": false,
        //    "searching": false,
        //    "ordering": true,
        //    "info": false,
        //    "autoWidth": false,
        //    "aaSorting": []
        //});

        $('#datepicker').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
        $('#datepickerEnd').datepicker({
            format: 'yyyy-mm-dd',
            language: 'tr',
            autoclose: true
        });
    </script>
@stop