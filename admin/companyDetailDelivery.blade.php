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
    <h1>Bloom & Fresh Şirket Sipariş Detay Sayfası</h1>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <!--<tr>
            <th>Durum</th>
            <th>Ürün Adı</th>
            <th>Fiyat</th>
            <th>Tanım</th>
            <th>Fotoğraf</th>
            <th style="width:100px;"> </th>
        </tr>-->
        {!! Form::model(null , ['action' => 'AdminPanelController@updateCompanyDeliveryDetail', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        @foreach($sales as $sale)
            <tr>
                <td>
                    Fiyat :
                </td>
                <td>
                    <div>
                        <label>{{$sale->total}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Ürün Adı :
                </td>
                <td>
                    <div>
                        <label>{{$sale->product_name}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Şirket :
                </td>
                <td>
                    <div>
                        <label>{{$sale->company_name}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Şirket Numarası :
                </td>
                <td>
                    <div>
                        <label>{{$sale->company_mobile}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Alıcı :
                </td>
                <td>
                    <div>
                        <input class="form-control" name="receiver" value="{{$sale->receiver}}">
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Alıcı Telefon Numarası :
                </td>
                <td>
                    <div>
                        <input class="form-control" name="receiver_mobile" value="{{$sale->receiver_mobile}}">
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Sipariş Bölgesi :
                </td>
                <td>
                    <div>
                        <input class="form-control" name="delivery_location" value="{{$sale->delivery_location}}">
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Adres :
                </td>
                <td>
                    <div>
                        <input class="form-control" name="receiver_address" value="{{$sale->receiver_address}}">
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    İstenen Teslim Tarihi :
                </td>
                <td>
                    <div>
                        <label>{{$sale->wanted_delivery_date}}</label>
                    </div>
                </td>
            </tr>
            <tr style="background-color: rgba(255, 165, 0, 0.47);;border-style: dashed;
                                                    border-width: 2px;">
                <td>
                    Kart Alıcı Adı :
                </td>
                <td>
                    <div>
                        <input class="form-control" name="card_receiver" value="{{$sale->card_receiver}}">
                    </div>
                </td>
            </tr>
            <tr style="background-color: rgba(255, 165, 0, 0.47);;border-style: dashed;
                                                    border-width: 2px;">
                <td>
                    Kart Mesajı :
                </td>
                <td>
                    <div>
                        <input class="form-control" name="card_message" value="{{$sale->card_message}}">
                    </div>
                </td>
            </tr>
            <tr style="background-color: rgba(255, 165, 0, 0.47);;border-style: dashed;
                                                    border-width: 2px;">
                <td>
                    Kart Gönderen Adı :
                </td>
                <td>
                    <div>
                        <input class="form-control" name="card_sender" value="{{$sale->card_sender}}">
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Teslimat Notu :
                </td>
                <td>
                    <div>
                        <input class="form-control" name="delivery_not" value="{{$sale->delivery_not}}">
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <input class="form-control hidden" name="id" value="{{$sale->id}}">
                    <button type="submit" class="btn btn-success form-control">
                        Kaydet
                    </button>
                </td>
            </tr>
            {!! Form::close() !!}
        @endforeach
    </table>

@stop()


@section('footer')

    <script>

    $(".formatDate").on("change", function() {
        this.setAttribute(
            "data-date",
            moment(this.value, "YYYY-MM-DD")
            .format( this.getAttribute("data-date-format") )
        )
    }).trigger("change");

        function openModel(event){
            var tempType = $(event).attr('id').split("_")[0];
            var tempName = $(event).attr('name').split("_");
            if( tempType == 'input' ){
                $('#changeId').val(tempName[0]);
                $('#changeRequestInput').removeClass('hidden');
                $('#productsGroup').addClass('hidden');
                $('#deliveryDateGroup').addClass('hidden');
                $('#locationsGroup').addClass('hidden');
                $('#inputId').removeClass('hidden');
                $('#changeRequestInput').val(tempName[1]);
                $('#changeRequestLabel').text(tempName[0]);
            }
            else if(tempType == 'product'){
                $('#changeId').val(tempName[0]);
                $('#changeRequestInput').addClass('hidden');
                $('#locationsGroup').addClass('hidden');
                $('#deliveryDateGroup').addClass('hidden');
                $('#inputId').removeClass('hidden');
                $('#productsGroup').removeClass('hidden');
                $('#changeRequestLabel').text(tempName[0]);
            }
            else if(tempType == 'location'){
                $('#changeId').val(tempName[0]);
                $('#changeRequestInput').addClass('hidden');
                $('#deliveryDateGroup').addClass('hidden');
                $('#productsGroup').addClass('hidden');
                $('#inputId').removeClass('hidden');
                $('#locationsGroup').removeClass('hidden');
                $('#changeRequestLabel').text(tempName[0]);
            }
            else if(tempType == 'deliveryDate'){
                $('#changeId').val(tempName[0]);
                $('#changeRequestInput').addClass('hidden');
                $('#locationsGroup').addClass('hidden');
                $('#productsGroup').addClass('hidden');
                $('#inputId').removeClass('hidden');
                $('#deliveryDateGroup').removeClass('hidden');
                $('#changeRequestLabel').text(tempName[0]);
            }
            $('#changeDeliveries').modal('show');
        }

    </script>

@stop