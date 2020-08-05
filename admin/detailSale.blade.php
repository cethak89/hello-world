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
    <h1 style="margin-top: 0px;">Bloom & Fresh Sipariş Detay Sayfası</h1>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    @foreach($sales as $sale)
        <div class="modal fade" id="changeDeliveries" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            {!! Form::model($sale, ['url' => '/admin/deliveries/updateInformation', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="myModalLabel"> <mark style="font-weight: bold;background-color: #E2E2E2;" class="blue">{{$sale->customer_name}} {{$sale->customer_surname}}</mark>  tarafından <mark style="font-weight: bold;background-color: #E2E2E2;" class="blue">{{$sale->wanted_delivery_date}}</mark> tarihli <mark style="font-weight: bold;background-color: #E2E2E2;" class="blue">{{$sale->product_name}}</mark> siparişinin aşağıdaki ilgili elemanını değiştiriyorsunuz.  </h4>

                    </div>
                    <div id="inputId" style="height: 50px;" class="modal-body hidden">
                        <p id="changeRequestLabel" style="font-weight: bold" class="col-lg-5 col-md-4 col-sm-4 col-xs-4"></p>
                        <input name="changeVariable" id="changeRequestInput" class="col-lg-4 col-md-4 col-sm-4 col-xs-4 hidden">
                        <div class="form-group hidden" id="locationsGroup">
                            <select name="locationName"  class="btn btn-default dropdown-toggle">
                                @foreach($locationList as $location)
                                    <option value="{{$location->id}}"
                                    @if($location->district == $sale->location_name)
                                        selected
                                    @else
                                    @endif
                                    >{{$location->district}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group hidden" id="productsGroup">
                            <select name="productName" class="btn btn-default dropdown-toggle">
                                @foreach($productList as $product)
                                    <option value="{{$product->id}}"
                                    @if($product->name == $sale->product_name)
                                        selected
                                    @else
                                    @endif
                                    >{{$product->name}}</option>
                                @endforeach
                            </select>
                            <p style="padding-top: 20px;">ÖNEMLİ NOT : Üründe fiyat değişikliği oluyorsa teknik ekibe danışınız.</p>
                        </div>
                        <div class="form-group hidden" id="crossSellGroup">
                            <select name="crossSellName" class="btn btn-default dropdown-toggle">
                                @foreach($crossSellProductList as $product)
                                    <option value="{{$product->id}}"
                                            @if($product->name == $sale->cikolatName)
                                            selected
                                    @else
                                            @endif
                                    >{{$product->name}}</option>
                                @endforeach
                            </select>
                            <p style="padding-top: 20px;">ÖNEMLİ NOT : Üründe fiyat değişikliği oluyorsa teknik ekibe danışınız.</p>
                        </div>
                        <div class="form-group hidden col-lg-7" id="deliveryDateGroup">
                            <input style="width: 200px;" name="dateName" class="formatDate form-control col-lg-8" type="date" data-date-format="DD MM YYYY" value="{{ explode(" " , $sale->wanted_delivery_date_temp)[0] }}" data-date="" >
                            <select name="dateNameHour"  class="btn btn-default dropdown-toggle col-lg-4">
                                @foreach($deliveryHourList as $deliveryHour)
                                    <option value="{{$deliveryHour->status}}"
                                    @if($deliveryHour->information == explode("|",$sale->wanted_delivery_date)[1])
                                        selected
                                    @else
                                    @endif
                                    >{{$deliveryHour->information}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <input class="hidden" name="salesId" value="{{$sale->salesId}}">
                    <input class="hidden" name="changingVariable" value="" id="changeId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger col-lg-6 col-md-6 col-sm-6 col-xs-6" data-dismiss="modal">Vazgeçtim</button>
                        <button style="margin-left: 0;" class="btn btn-success col-lg-6 col-md-6 col-sm-6 col-xs-6" type="button btn-success">Değiştir</button>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    @endforeach
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <!--<tr>
            <th>Durum</th>
            <th>Ürün Adı</th>
            <th>Fiyat</th>
            <th>Tanım</th>
            <th>Fotoğraf</th>
            <th style="width:100px;"> </th>
        </tr>-->
        @foreach($sales as $sale)
            <tr>
                <td style="width: 25%">
                    Satış Tarihi :
                </td>
                <td>
                    <div>
                        <label>{{$sale->created_at}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Fiyat :
                </td>
                <td>
                    <div>
                        <label>{{$sale->sum_total}}
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
                        <a style="float: right;width: 1%;" title="Ürün Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="product_senderPhone" name="Ürün Adı_{{$sale->product_name}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Ekstra Ürün :
                </td>
                <td>
                    <div>
                        <label>{{$sale->cikolatName}}</label>
                        @if($sale->cikolatImage)
                            <div style="width: 20px;margin-left: auto;margin-right: auto;display: inline;">
                                <a href="{{$sale->cikolatImage}}" target="_blank">
                                    <img style="box-shadow: 0px 1px 2px black;width: 20px;" src="{{$sale->cikolatImage}}" data-pin-nopin="true">
                                </a>
                            </div>
                        @endif
                        <a style="float: right;width: 1%;" title="Ekstra ürün değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="crossSell_senderPhone" name="Ekstra Ürün Adı_{{$sale->cikolatName}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Müşteri Adı :
                </td>
                <td>
                    <div>
                        <label>{{$sale->customer_name}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Müşteri Soyadı :
                </td>
                <td>
                    <div>
                        <label>{{$sale->customer_surname}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Müşteri Telefon Numarası :
                </td>
                <td>
                    <div>
                        <label>
                            @if( $sale->customer_mobile )
                                ({{substr($sale->customer_mobile, 0, 3)}}) {{substr($sale->customer_mobile, 3, 3)}} {{substr($sale->customer_mobile, 6, 2)}} {{substr($sale->customer_mobile, 8, 2)}}
                            @endif
                        </label>
                        <a style="float: right;width: 1%;" title="Gönderen Telefon Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="input_senderPhone" name="Gönderen Telefon Numarası_{{$sale->customer_mobile}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Müşteri Mail Adresi :
                </td>
                <td>
                    <div>
                        <label>{{$sale->customer_email}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Alıcı Adı :
                </td>
                <td>
                    <div>
                        <label>{{$sale->contact_name}}
                        </label>
                        <a style="float: right;width: 1%;" title="Alıcı Adı Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="input_senderPhone" name="Alıcı Adı_{{$sale->contact_name}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Alıcı Soyadı :
                </td>
                <td>
                    <div>
                        <label>{{$sale->contact_surname}}
                        </label>
                        <a style="float: right;width: 1%;" title="Alıcı Soyadı Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="input_senderPhone" name="Alıcı Soyadı_{{$sale->contact_surname}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Alıcı Telefon Numarası :
                </td>
                <td>
                    <div>
                        <label>
                            @if( $sale->contact_mobile )
                                ({{substr($sale->contact_mobile, 0, 3)}}) {{substr($sale->contact_mobile, 3, 3)}} {{substr($sale->contact_mobile, 6, 2)}} {{substr($sale->contact_mobile, 8, 2)}}
                            @endif
                        </label>
                        <a style="float: right;width: 1%;" title="Alıcı Telefon Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="input_senderPhone" name="Alıcı Telefon Numarası_{{$sale->contact_mobile}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Sipariş Bölgesi :
                </td>
                <td>
                    <div>
                        <label>{{$sale->location_name}}
                        </label>
                        <a style="float: right;width: 1%;" title="Semt Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="location_senderPhone" name="Sipariş Bölgesi_{{$sale->location_name}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Adres :
                </td>
                <td>
                    <div>
                        <label>{{$sale->contact_address}}
                        </label>
                        <a style="float: right;width: 1%;" title="Adres Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="input_senderPhone" name="Adres_{{$sale->contact_address}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    İstenen Teslim Tarihi :
                </td>
                <td>
                    <div>
                        <label>{{$sale->wanted_delivery_date}}
                        </label>
                        <a style="float: right;width: 1%;" title="Teslim Tarihi Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="deliveryDate_senderPhone" name="İstenen Teslim Tarihi_{{$sale->wanted_delivery_date}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Teslim alan kişi :
                </td>
                <td>
                    <div>
                        <label>{{$sale->picker}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <img style="width: 37px;" src="{{ asset('/img/speedy.jpg') }}">Speedy Gonzales :
                </td>
                <td>
                    <div>
                        <label>{{$sale->operation_name}}</label>
                    </div>
                </td>
            </tr>
            <tr style="background-color: rgba(255, 165, 0, 0.47);border-width: 2px;">
                <td>
                    Kart Alıcı Adı :
                </td>
                <td>
                    <div>
                        <label>{{$sale->receiver}}
                        </label>
                        <a style="float: right;width: 1%;" title="Kart Alıcı Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="input_senderPhone" name="Kart Alıcı Adı_{{$sale->receiver}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr style="background-color: rgba(255, 165, 0, 0.47);border-width: 2px;">
                <td>
                    Kart Mesajı :
                </td>
                <td>
                    <div>
                        <label>{{$sale->card_message}}
                        </label>
                        <a style="float: right;width: 1%;" title="Kart Mesajı Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="input_senderPhone" name="Kart Mesajı_{{$sale->card_message}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr style="background-color: rgba(255, 165, 0, 0.47);border-width: 2px;">
                <td>
                    Kart Gönderen Adı :
                </td>
                <td>
                    <div>
                        <label>{{$sale->sender}}
                        </label>
                        <a style="float: right;width: 1%;" title="Kart Gönderen Adı Değiştir" class="glyphicon glyphicon-wrench col-md-1 col-lg-1" id="input_senderPhone" name="Kart Gönderen Adı_{{$sale->sender}}" onclick="openModel(this)"></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Kupon Id :
                </td>
                <td>
                    <div>
                        <label>{{$sale->delivery_notification}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Teslimat Notu :
                </td>
                <td>
                    <div>
                        <label>{{$sale->delivery_not}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Fatura gonderim :
                </td>
                <td>
                    <div>
                        <label>{{$sale->billing_send}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Fatura turu :
                </td>
                <td>
                    <div>
                        <label>{{$sale->billing_type}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Fatura il :
                </td>
                <td>
                    <div>
                        <label>{{$sale->city}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Fatura ilce :
                </td>
                <td>
                    <div>
                        <label>{{$sale->small_city}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Fatura Adress :
                </td>
                <td>
                    <div>
                        <label>{{$sale->billing_address}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Fatura Şirket Adi :
                </td>
                <td>
                    <div>
                        <label>{{$sale->company}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Fatura Vergi Dairesi :
                </td>
                <td>
                    <div>
                        <label>{{$sale->tax_office}}
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    Fatura Vergi Numarasi :
                </td>
                <td>
                    <div>
                        <label>{{$sale->tax_no}}
                        </label>
                    </div>
                </td>
            </tr>
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
                $('#crossSellGroup').addClass('hidden');
                $('#inputId').removeClass('hidden');
                $('#changeRequestInput').val(tempName[1]);
                $('#changeRequestLabel').text(tempName[0]);
            }
            else if(tempType == 'product'){
                $('#changeId').val(tempName[0]);
                $('#changeRequestInput').addClass('hidden');
                $('#locationsGroup').addClass('hidden');
                $('#deliveryDateGroup').addClass('hidden');
                $('#crossSellGroup').addClass('hidden');
                $('#inputId').removeClass('hidden');
                $('#productsGroup').removeClass('hidden');
                $('#changeRequestLabel').text(tempName[0]);
            }
            else if(tempType == 'location'){
                $('#changeId').val(tempName[0]);
                $('#changeRequestInput').addClass('hidden');
                $('#deliveryDateGroup').addClass('hidden');
                $('#productsGroup').addClass('hidden');
                $('#crossSellGroup').addClass('hidden');
                $('#inputId').removeClass('hidden');
                $('#locationsGroup').removeClass('hidden');
                $('#changeRequestLabel').text(tempName[0]);
            }
            else if(tempType == 'deliveryDate'){
                $('#changeId').val(tempName[0]);
                $('#changeRequestInput').addClass('hidden');
                $('#locationsGroup').addClass('hidden');
                $('#productsGroup').addClass('hidden');
                $('#crossSellGroup').addClass('hidden');
                $('#inputId').removeClass('hidden');
                $('#deliveryDateGroup').removeClass('hidden');
                $('#changeRequestLabel').text(tempName[0]);
            }
            else if( tempType == 'crossSell' ){
                $('#changeId').val(tempName[0]);
                $('#changeRequestInput').addClass('hidden');
                $('#locationsGroup').addClass('hidden');
                $('#deliveryDateGroup').addClass('hidden');
                $('#productsGroup').addClass('hidden');
                $('#inputId').removeClass('hidden');
                $('#crossSellGroup').removeClass('hidden');
                $('#changeRequestLabel').text(tempName[0]);
            }
            $('#changeDeliveries').modal('show');
        }

    </script>

@stop