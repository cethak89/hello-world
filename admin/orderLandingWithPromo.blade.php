@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        div.form-group {
            height: 20px;
        }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .top-container {
            background-color: #f1f1f1;
            padding: 30px;
            text-align: center;
        }

        .header {
            padding: 10px 16px;
            background: #555;
            color: #f1f1f1;
        }

        .content {
            padding: 16px;
        }

        .sticky {
            position: fixed;
            top: 0;
            width: 100%;
        }

        .sticky + .content {
            padding-top: 102px;
        }
    </style>
@stop

@section('content')

    <style>
        .top-container {
            background-color: #f1f1f1;
            padding: 30px;
            text-align: center;
        }

        .header {
            padding: 10px 16px;
            z-index: 100;
            background-color: white;
        }

        .content {
            padding: 16px;
        }

        .sticky {
            position: fixed;
            top: 0;
        }

        .sticky + .content {
            padding-top: 300px;
        }
    </style>

    <table class="top-container" width="100%">
        <tr style="width: 100%">
            <td>
                <h1>Bloom & Fresh Ana Sayfa Düzenleme</h1>
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
    <div style="margin-top: 8px;" class="header col-lg-12" id="myHeader">
        <div class="col-lg-7">
            <h2 style="text-align: center;">
                Çiçekler
            </h2>
            @foreach( $flowerList as $flower )
                @if( $flower->landingNumber == 0 )
                    <div id="{{$flower->id}}" draggable="true" ondragstart="dragFromFlower(event)" class="col-lg-3" style="border: 1px solid black;">
                        <div style="text-align: center;padding-left: 0px;padding-right: 0px;" class="col-lg-12">
                            <p style="font-size: 18px;text-align: center;margin-bottom: 6px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;margin-bottom: 2px;" title="{{$flower->name}}">
                                {{$flower->name}}
                            </p>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="col-lg-5">
            <h2 style="text-align: center;">
                Promolar
            </h2>
            @foreach( $promoList as $promo )
                @if( $promo->landingNumber == 0 )
                    <div id="{{$promo->id}}" draggable="true" ondragstart="dragFromPromo(event)" class="col-lg-4" style="border: 1px solid black;text-align: center;padding: 0px;height: 35px;">
                        <p style="margin-top: 4px;font-size: 14px;text-align: center;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">
                            {{$promo->title_small}} {{$promo->title}}
                            @if( $promo->title || $promo->title_small )
                                <br/>
                            @endif
                            {{$promo->temp_name}}
                        </p>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    <div style="background: white;" class="content">
        @foreach($landingList as $product)
            <div id="div_{{$product->id}}" style="    margin-left: 0px;
                            margin-right: 0px;
                            padding-left: 0px;
                            padding-right: 0px;background-color: #BFB4B4;" class="col-lg-{{$product->length*3}} col-md-3 col-sm-6 col-xs-12 ">
                <!--<button style="position: absolute;z-index: 2;width: 200px;margin-left: 17px;" class="btn btn-danger form-control">Cikart</button>-->
                <img style="margin-left: 0px;margin-right: 0px;padding-left: 0px;padding-right: 0px;max-height: 400px;"
                     class="col-lg-10 col-md-10 col-sm-10 col-xs-10" id="{{$product->id}}"  ondragover="allowDrop(event)" ondrop="drop(event)" ondragstart="drag(event)" draggable="true" src="{{$product->image}}">
                <p id="name_{{$product->id}}" class="col-lg-10" style="position: absolute;margin: auto;text-align: center;font-size: 36px;">
                    {{$product->name}}
                </p>
                <p id="status_{{$product->id}}" class="col-lg-10" style="position: absolute;margin: auto;text-align: center;font-size: 20px;bottom: 0px;">
                    @if( $product->coming_soon == 1 )
                        Yakında
                    @elseif( $product->limit_statu == 1 )
                        Tükendi
                    @else
                        @if( $product->today == 1 )
                            En Erken: Bugün
                        @elseif( $product->tomorrow == 1 )
                            En Erken: Yarın
                        @else
                            {{$product->avalibility_time}}
                        @endif
                    @endif
                </p>
                @if( count($landingList) > 2  )
                    <a href="/admin/removeLandinWithPromo/{{$product->id}}">
                        <i style="position: absolute;font-size: 25px;color:red;right: 16%;" class="fa fa-fw fa-remove"></i>
                    </a>
                @endif
                <input class="hidden" id="length_{{$product->id}}" value="{{$product->length}}">
                <div ondragover="allowDrop(event)" id="inside_{{$product->id}}" ondrop="dropBetween(event)" class="col-lg-2 col-md-2 col-sm-2 col-xs-2" style="height: 300px;background-color: #BFB4B4;"></div>
            </div>
            {!! Form::model($product, ['url' => '/admin/updateOrderProductBetweenLanding', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            {!! Form::hidden('toPlace', $product->order, ['class' => 'form-control hidden' , 'id' => $product->id . 'toPlace']) !!}
            {!! Form::hidden('fromId', null, ['class' => 'form-control hidden' , 'id' => $product->id . 'fromPlace']) !!}
            {!! Form::submit('Sorgula', ['class' => 'btn btn-success form-control hidden' , 'id' => $product->id . 'submitId']) !!}
            {!! Form::close() !!}
        @endforeach
    </div>
@stop()

@section('footer')
    <script>

        window.onscroll = function() {myFunction()};

        var header = document.getElementById("myHeader");
        var sticky = header.offsetTop;

        function myFunction() {
            if (window.pageYOffset > sticky) {
                header.classList.add("sticky");
            } else {
                header.classList.remove("sticky");
            }
        }

        function allowDrop(ev) {
            ev.preventDefault();
        }

        function drag(ev) {
            ev.dataTransfer.setData("text", ev.target.id);
            ev.dataTransfer.setData("type", '');
        }

        function dragFromFlower(ev) {
            ev.dataTransfer.setData("text", ev.target.id);
            ev.dataTransfer.setData("type", 'flower');
        }

        function dragFromPromo(ev) {
            ev.dataTransfer.setData("text", ev.target.id);
            ev.dataTransfer.setData("type", 'promo');
        }

        function dropBetween(ev) {
            ev.preventDefault();
            var data = ev.dataTransfer.getData("text");
            var type = ev.dataTransfer.getData("type");

            if( type ){
                window.location.replace("/admin/dropBetween-order-landing-with-promo/" + type + "/" + data + "/" + ev.target.id );
            }
            else{
                var tempFromId = '#' + ev.target.id.split('_')[1] + 'fromPlace';
                var tempSubmit = '#' + ev.target.id.split('_')[1] + 'submitId';
                var data = ev.dataTransfer.getData("text");
                console.log($(tempFromId));
                $(tempFromId).val(data);
                $(tempSubmit).click();
            }
        }

        function drop(ev) {
            ev.preventDefault();
            var data = ev.dataTransfer.getData("text");
            var type = ev.dataTransfer.getData("type");

            if( type ){
                window.location.replace("/admin/replace-order-landing-with-promo/" + type + "/" + data + "/" + ev.target.id );
            }
            else{
                console.log(data);
                console.log(ev.target.id);
                $.ajax({
                    url: '/admin/update-order-landing-with-promo',
                    method: "POST",
                    data: {
                        fromId: data,
                        toId: ev.target.id
                    },
                    success: function (returnData) {

                        var tempItem = '#length_' + data;
                        var tempItemTarget = '#length_' + ev.target.id;
                        var tempTargetSrc = $(tempItemTarget).val();
                        console.log($(tempItemTarget).val());
                        $(tempItemTarget).val( $(tempItem).val() );
                        console.log($(tempItemTarget).val());
                        $(tempItem).val( tempTargetSrc);

                        var tempIds = 'length_' + ev.target.id;
                        var tempIds2 = 'length_' + data;
                        $(tempItemTarget).attr('id', 'xyz');
                        $(tempItem).attr('id', tempIds);
                        $('#xyz').attr('id', tempIds2);

                        var tempItemName = '#name_' + data;
                        var tempItemTargetName = '#name_' + ev.target.id;
                        var tempTargetSrcName = $(tempItemTargetName).text();
                        $(tempItemTargetName).text( $(tempItemName).text() );
                        $(tempItemName).text( tempTargetSrcName);

                        var tempItemStatus = '#status_' + data;
                        var tempItemTargetStatus = '#status_' + ev.target.id;
                        var tempTargetSrcStatus = $(tempItemTargetStatus).text();
                        $(tempItemTargetStatus).text( $(tempItemStatus).text() );
                        $(tempItemStatus).text( tempTargetSrcStatus);

                        var tempIds = 'name_' + ev.target.id;
                        var tempIds2 = 'name_' + data;
                        $(tempItemTargetName).attr('id', 'xy');
                        $(tempItemName).attr('id', tempIds);
                        $('#xy').attr('id', tempIds2);

                        var tempIdsStatus = 'status_' + ev.target.id;
                        var tempIds2Status = 'status_' + data;
                        $(tempItemTargetStatus).attr('id', 'xy1');
                        $(tempItemStatus).attr('id', tempIdsStatus);
                        $('#xy1').attr('id', tempIds2Status);

                        var tempItemDiv = '#div_' + data;
                        var tempItemTargetDiv  = '#div_' + ev.target.id;
                        $(tempItemDiv).removeClass('col-lg-3');
                        $(tempItemDiv).removeClass('col-lg-6');
                        $(tempItemTargetDiv).removeClass('col-lg-3');
                        $(tempItemTargetDiv).removeClass('col-lg-6');
                        //var tempColumnNumber = parseInt($(tempItem).val()) * 3;
                        //var tempColumnNumber2 = parseInt($(tempItemTarget).val()) * 3;
                        var tempColumnNumber = parseInt($(tempItemTarget).val()) * 3;
                        var tempColumnNumber2 = parseInt($(tempItem).val()) * 3;
                        $(tempItemDiv).addClass('col-lg-' + tempColumnNumber);
                        $(tempItemTargetDiv).addClass('col-lg-' + tempColumnNumber2);

                        var tempIds = 'div_' + ev.target.id;
                        var tempIds2 = 'div_' + data;
                        $(tempItemTargetDiv).attr('id', 'xyz1');
                        $(tempItemDiv).attr('id', tempIds);
                        $('#xyz1').attr('id', tempIds2);

                        var tempItem = '#' + data;
                        var tempItemTarget = '#' + ev.target.id;
                        var tempTargetSrc = '' + $(tempItemTarget).attr('src');
                        var tempTargetId = ev.target.id;
                        $(tempItemTarget).attr('src', $(tempItem).attr('src'));
                        $(tempItem).attr('src', tempTargetSrc);
                        var tempId = '#x';
                        $(tempItemTarget).attr('id', 'x');
                        $(tempItem).attr('id', tempTargetId);
                        $(tempId).attr('id', data);

                    }
                });
            }

        }
    </script>
@stop