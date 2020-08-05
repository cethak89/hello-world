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

    <table class="table table-hover">
    <tr>
        <td>
            <h1>Bloom & Fresh Teslimat Listesi</h1>
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

   <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel">
       <div class="modal-dialog" role="document">
           <div class="modal-content">
               <div class="modal-header">
                   <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                   <h4 class="modal-title" id="exampleModalLabel">New message</h4>
               </div>
               @if($deliveryList[0]->studio)
                {!! Form::model(null, ['url' => '/admin/studioDeliveryComplete', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
               @else
                {!! Form::model(null, ['url' => '/admin/deliveryComplete', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
               @endif
               <div class="modal-body">
                    <div class="form-group">
                        <label for="recipient-name" class="control-label">Teslim alan</label>
                        <input type="text" name="picker" class="form-control" id="recipient-name">
                        <input id="tempId" name="tempId" type="text" class="form-control hidden">
                    </div>
               </div>
               <div class="modal-footer">
                   <button type="button" onclick="setPickerReceiver()" class="btn btn-info danger">KENDİNE</button>
                   <button type="button" class="btn btn-default danger" data-dismiss="modal">VAZGEÇ</button>
                   {!! Form::submit('TAMAMLA!', ['class' => 'btn btn-success success']) !!}
               </div>
               {!! Form::close() !!}
           </div>
       </div>
   </div>
   <button id="test1" type="button" class="btn btn-primary hidden" data-toggle="modal" data-target="#exampleModal" data-whatever="@getbootstrap">Open modal for</button>
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th style="width: 20%;">Sipariş No</th>
            <th style="width: 20%;">Bölge</th>
            <th style="width: 20%;">Alıcı</th>
            <th style="width: 20%;">Ürün</th>
            <th style="width: 20%;">-------</th>
        </tr>
        @foreach($deliveryList as $delivery)
            <tr>
                <input id="receiver" type="text" class="form-control hidden" value="{{$delivery->name}} {{$delivery->surname}}">
                <td style="width: 20%;">{{$delivery->id}}</td>
                <td style="width: 20%;">{{$delivery->district}}</td>
                <td style="width: 20%;">{{$delivery->name}} {{$delivery->surname}}</td>
                <td style="width: 20%;">{{$delivery->products}}</td>
                <td style="width: 20%;">
                    <button type="button" onclick="$('#tempId').val('{{$delivery->id}}');$('#test1').click();" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal" data-whatever="@getbootstrap">Teslim Et!</button>
                </td>
            </tr>
        @endforeach
    </table>
@stop()

@section('footer')
    <script>
        function setOrderParameter(paremeter , upOrDown){
            $('input[name=orderParameter]').val(paremeter);
            $('input[name=upOrDown]').val(upOrDown);
            console.log( $('input[name=orderParameter]').val());
        }

        function setPickerReceiver(){
            $('#recipient-name').val($('#receiver').val());
        }
        //$('html').click(function() {
        //    window.location='/admin/deliveries';
        //});

        $('#products-table').click(function(event){
            event.stopPropagation();
        });

        // go to anchor
    </script>
@stop