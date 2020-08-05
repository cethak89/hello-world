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
                <h1>Bloom & Fresh Ürün Sıralama</h1>
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
        @foreach($flowerList as $product)
            <div style="    margin-left: 0px;
                            margin-right: 0px;
                            padding-left: 0px;
                            padding-right: 0px;background-color: #BFB4B4;" class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <!--<button style="position: absolute;z-index: 2;width: 200px;margin-left: 17px;" class="btn btn-danger form-control">Cikart</button>-->
                <img style="margin-left: 0px;margin-right: 0px;padding-left: 0px;padding-right: 0px;"
                    class="col-lg-10 col-md-10 col-sm-10 col-xs-10" id="{{$product->id}}"  ondragover="allowDrop(event)" ondrop="drop(event)" ondragstart="drag(event)" draggable="true" src="{{$product->MainImage}}">
                <div ondragover="allowDrop(event)" id="inside_{{$product->id}}" ondrop="dropBetween(event)" class="col-lg-2 col-md-2 col-sm-2 col-xs-2" style="height: 300px;background-color: #BFB4B4;"></div>
            </div>
            {!! Form::model($product, ['url' => '/admin/update-order-product-between', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
                {!! Form::hidden('toPlace', $product->landing_page_order, ['class' => 'form-control hidden' , 'id' => $product->id . 'toPlace']) !!}
                {!! Form::hidden('fromId', null, ['class' => 'form-control hidden' , 'id' => $product->id . 'fromPlace']) !!}
                {!! Form::submit('Sorgula', ['class' => 'btn btn-success form-control hidden' , 'id' => $product->id . 'submitId']) !!}
            {!! Form::close() !!}
        @endforeach
@stop()

@section('footer')
    <script>
        function allowDrop(ev) {
            ev.preventDefault();
        }

        function drag(ev) {
            ev.dataTransfer.setData("text", ev.target.id);
        }

        function dropBetween(ev) {
            ev.preventDefault();
            var tempFromId = '#' + ev.target.id.split('_')[1] + 'fromPlace';
            var tempSubmit = '#' + ev.target.id.split('_')[1] + 'submitId';
            var data = ev.dataTransfer.getData("text");
            console.log($(tempFromId));
            $(tempFromId).val(data);
            $(tempSubmit).click();
        }

        function drop(ev) {
            ev.preventDefault();
            var data = ev.dataTransfer.getData("text");

            $.ajax({
                url: '/admin/update-order-product',
                method: "POST",
                data: {
                    fromId : data,
                    toId : ev.target.id
                },
                success: function(returnData) {
                    var tempItem = '#' + data;
                    var tempItemTarget = '#' + ev.target.id;
                    var tempTargetSrc = '' + $(tempItemTarget).attr('src');
                    var tempTargetId = ev.target.id;
                    $(tempItemTarget).attr('src' , $(tempItem).attr('src') );
                    $(tempItem).attr('src' , tempTargetSrc );
                    var tempId = '#x';
                    $(tempItemTarget).attr('id' , 'x');
                    $(tempItem).attr('id' , tempTargetId );
                    $(tempId).attr('id' , data );
                }
            });
        }
    </script>
@stop