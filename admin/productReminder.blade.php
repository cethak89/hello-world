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
                <h1>Bloom & Fresh Ürün Hatırlatma Listesi</h1>
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
    {!! Form::model($mailList, ['url' => '/admin/product-reminder/filter', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
    <!--<table class="table table-hover table-bordered">
        <tr>
            <td>
                <label> Ürünler</label>
            </td>-->
            @foreach($myArray as $tag)
                <div class="hidden col-lg-1 col-md-2 col-sm-3 col-xs-6">
                    <span style="padding-top: 0px;padding-bottom: 0px;" class="input-group-addon">
                        <input style="width: 25px;height: 25px;" id="status_{{$tag->product_id}}" name="status_{{$tag->product_id}}" type="checkbox" aria-label="..."
                         {{$tag->checked}}
                        >
                    </span>
                    <label id="label_status_{{$tag->product_id}}" onclick="selectDiv(this)" class="form-control" aria-label="...">
                        {{$tag->name}}
                    </label>
                </div>
                <!--
                <td>-->
                <!--</td>-->
            @endforeach<!--
        </tr>
    </table>-->
    <div>
        <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;margin-bottom: 10px;">
            <tr>
                <td style="width: 80px;vertical-align: middle;">
                    <label style="width: 60px;vertical-align: sub;"> Ürünler :</label>
                </td>
                <td>
                    <select  class="form-control select2"  data-placeholder="Ürün Seç" style="width: 400px;" id="customerId" name="products[]" multiple>
                        @foreach($myArray as $tag)
                            <option value="{{$tag->product_id}}" {{$tag->checked ? 'selected' : ''}}>{{$tag->name}}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
        </table>
    </div>
    <div style="margin-bottom: 0px;">
        @foreach( $topWaitingFlowers as $flowerWanted )
            <div style="display: inline-block;padding: 6px;font-size: 16px;border: 1px solid #f4f4f4;margin: 2px;">
                {{$flowerWanted->name}}: {{$flowerWanted->totalWaiting}}
            </div>
        @endforeach
    </div>
    <p style="font-size: 12px;padding-left: 5px;">
        *Ürün hatırlatma listesi top ürünler
    </p>
    <table style="width: 510px" class="table table-hover table-bordered">
        <tr>
            <td @if( $mailStatus == 'waiting' )style="width: 170px;background-color: #D6D4D0"@endif>
                <button style="width: 150px;" onclick="setButtonWaiting()" class="btn btn-info form-control">Mail Bekleyenler</button>
            </td>
            <td @if( $mailStatus == 'sent' )style="width: 170px;background-color: #D6D4D0"@endif>
                <button style="width: 150px;"  onclick="setButtonSended()" class="btn btn-warning form-control">Mail Yollananlar</button>
            </td>
            <td @if( $mailStatus == 'all' )style="width: 170px;background-color: #D6D4D0"@endif>
                <button style="width: 150px;"  onclick="setButtonAll()" class="btn btn-success form-control">Hepsi</button>
            </td>
        </tr>
    </table>

    <div class="modal fade" id="checkProduct" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel"> Aşağıda bulunan çiçekler için hatırlatma mailleri yollamak istediğinize emin misiniz?</h4>
                </div>
                <div style="margin-bottom: 20px;" class="modal-body">
                    @foreach($myArray as $tag)
                    @if($tag->checked == 'checked')
                    <p style="font-weight: bold" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">{{$tag->name}}</p><br>
                    @endif
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                    <button style="margin-left: 0;" class="btn btn-success col-lg-6 col-md-6 col-sm-6 col-xs-6" type="button btn-success" data-dismiss="modal" onclick="$('#submitForm').click();">Mail Yolla</button>
                </div>
            </div>
        </div>
    </div>

    <input name="button_id" class="hidden" id="button_id">
    {!! Form::submit('Sorgula', ['class' => 'btn hidden btn-success form-control' , 'id' => 'submitId']) !!}
    {!! Form::close() !!}
    {!! Form::model($mailList, ['url' => '/admin/product-reminder/send-email', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th>Mail</th>
            <th>Ürün Adı</th>
            <th>Tarih</th>
            <th>Mail Durum</th>
        </tr>
        @foreach($mailList as $mailer)
            <tr>
                <td style="padding-left:21px;">{{$mailer->mail}}</td>
                <td style="padding-left:21px;">{{$mailer->name}}</td>
                <td style="padding-left:21px;">{{$mailer->created_at}}</td>
                <td style="padding-left:21px;">
                @if($mailer->mail_send == 1)
                    Gönderildi
                @else
                    Gönderilecek
                @endif
                </td>
            </tr>
            <input name="mailName_{{$mailer->id}}" class="hidden" value="{{$mailer->mail}}ω{{$mailer->name}}ω{{$mailer->product_id}}">
        @endforeach
    </table>
    <input name="flowers" value="
    @foreach($myArray as $tag)
    @if($tag->checked == 'checked')
    {{$tag->product_id}}_
    @endif
    @endforeach
    " class="hidden">
    @if( $mailStatus == 'waiting' )
    @endif
    {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control hidden' , 'id' => 'submitForm' ]) !!}
    {!! Form::close() !!}
    <button onclick="openModel()" class="btn btn-success form-control">Ürün Hatırlatma Maili yolla</button>
@stop()

@section('footer')
    <script>
        $(".select2").select2();
        function selectDiv(event){
            var tempID = $(event).attr('id').split("_")[2];
            var tempSelector = "#status_" + tempID;
            if($(tempSelector).prop("checked")){
                $(tempSelector).prop("checked", false);
            }
            else{
                $(tempSelector).prop("checked", true);
            }
        }

        function openModel(){
            $('#checkProduct').modal('show');
        }

        function setButtonWaiting(){
            $('#button_id').val('waiting');
            $('#submitId').click();
        }

        function setButtonSended(){
            $('#button_id').val('sent');
            $('#submitId').click();
        }

        function setButtonAll(){
            $('#button_id').val('all');
            $('#submitId').click();
        }

    </script>

@stop






















