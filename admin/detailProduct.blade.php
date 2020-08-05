@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        div.form-group {
            height: 20px;
        }

    </style>
@stop

@section('content')
    <style>
        .form-group {
            margin-bottom: 0px !important;
        }
    </style>
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Ürün Detay Sayfası</h1>
    <h1 class="col-lg-3 col-md-3">
        @foreach($descriptionList as $lang)
            <button style="float: right;" class="button" id="{{$lang->lang_id}}"
                    onclick="showSelectedLang('{{$lang->lang_id}}')">{{$lang->lang_id}}</button>
        @endforeach
    </h1>

    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <!--<table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">-->
        <!--<tr>
            <th>Durum</th>
            <th>Ürün Adı</th>
            <th>Fiyat</th>
            <th>Tanım</th>
            <th>Fotoğraf</th>
            <th style="width:100px;"> </th>
        </tr>-->
        @foreach($products as $product)
            {!! Form::model($product , ['action' => 'AdminPanelController@updateProduct', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}

            <div class="col-lg-12">
                <h1 style="margin-bottom: 0px;background-color: #3c8dbc4a;border: 1px solid;border-color: #3c8dbc33;height: 63px;padding-top: 17px;padding-right: 20px;border-radius: 10px;font-size: 26px;padding-left: 15px;">
                    Ürün Tanımları
                    <a style="float: right;" class="glyphicon glyphicon-wrench" data-toggle="collapse" data-target="#productMain" aria-expanded="false" aria-controls="collapseExample"></a>
                </h1>
                <div style="border: 1px solid rgba(60, 141, 188, 0.2);border-radius: 10px;height: 2px;padding: 10px;" class="collapse" id="productMain">
                    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;margin-bottom: 0px;">
                        <tr>
                            <td style="vertical-align: inherit;">
                                Durum :
                            </td>
                            <td>
                                <table>
                                    <tr>
                                        @foreach( $statusList as $key => $statu )
                                            <td style=" @if( $key == 0 ) width: 90px; @else  width: 135px; @endif;text-align: center;">
                                                <div class="checkbox">
                                                    @if( count($statusList) > 1 ) <span>{{$statu->name}}</span> @endif
                                                    <label>
                                                        <input style="width:30px;height:30px"
                                                               @if( $statu->activation_status_id ) checked
                                                               @endif name="activationId_{{$statu->city_id}}" type="checkbox">
                                                        <input class="hidden" name="activationTemp_{{$statu->city_id}}"
                                                               value="1">
                                                    </label>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Limit :
                            </td>
                            <td>
                                <table>
                                    <tr>
                                        @foreach( $statusList as $key => $limit )
                                            <td style=" @if( $key == 0 ) width: 90px; @else  width: 135px; @endif text-align: center;">
                                                <div class="checkbox">
                                                    @if( count($statusList) > 1 ) <span>{{$limit->name}}</span> @endif
                                                    <label>
                                                        <input style="width:30px;height:30px"
                                                               @if( $limit->limit_statu ) checked
                                                               @endif name="limitId_{{$limit->city_id}}" type="checkbox">
                                                        <input class="hidden" name="limitTemp_{{$limit->city_id}}"
                                                               value="1">
                                                    </label>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Yakında :
                            </td>
                            <td>
                                <table>
                                    <tr>
                                        @foreach( $statusList as $key =>  $soon )
                                            <td style=" @if( $key == 0 ) width: 90px; @else  width: 135px; @endif;text-align: center;">
                                                <div class="checkbox">
                                                    @if( count($statusList) > 1 ) <span>{{$soon->name}}</span> @endif
                                                    <label>
                                                        <input style="width:30px;height:30px"
                                                               @if( $soon->coming_soon ) checked
                                                               @endif name="soonId_{{$soon->city_id}}" type="checkbox">
                                                        <input class="hidden" name="soonTemp_{{$soon->city_id}}"
                                                               value="1">
                                                    </label>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Ürün Türü :
                            </td>
                            <td>
                                <div class="form-group">
                                    <select style="width: 150px;" name="product_type" id="productType" class="select2 btn btn-default dropdown-toggle" onchange="selectProduct($(this).val());">
                                        <option @if( $product->product_type == 1 ) selected @endif value="1">Çiçek</option>
                                        <option @if( $product->product_type == 2 ) selected @endif value="2">Çikolata</option>
                                        <option @if( $product->product_type == 3 ) selected @endif value="3">Hediye Kutusu</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Ürün Alt Türü :
                            </td>
                            <td>
                                <div class="form-group">
                                    <select style="width: 150px;" name="product_type_sub" class="select2 btn btn-default dropdown-toggle" id="productTypeSub">
                                        <option class="all one two three" selected value="0">Seçiniz</option>
                                        <option class="all one" @if( $product->product_type_sub == 11 ) selected @endif value="11">Buket</option>
                                        <option class="all one" @if( $product->product_type_sub == 12 ) selected @endif value="12">Masaüstü</option>
                                        <option class="all one" @if( $product->product_type_sub == 13 ) selected @endif value="13">Sukulent</option>
                                        <option class="all one" @if( $product->product_type_sub == 14 ) selected @endif value="14">Saksı</option>
                                        <option class="all one" @if( $product->product_type_sub == 15 ) selected @endif value="15">Orkide</option>
                                        <option class="all one" @if( $product->product_type_sub == 16 ) selected @endif value="16">Solmayan Gül</option>
                                        <option class="all one" @if( $product->product_type_sub == 17 ) selected @endif value="17">Kutuda Çiçek</option>
                                        <option class="all two" @if( $product->product_type_sub == 21 ) selected @endif value="21">Godiva</option>
                                        <option class="all two" @if( $product->product_type_sub == 22 ) selected @endif value="22">Baylan</option>
                                        <option class="all two" @if( $product->product_type_sub == 23 ) selected @endif value="23">BNF Macarons</option>
                                        <option class="all two" @if( $product->product_type_sub == 24 ) selected @endif value="24">Hazz</option>
                                        <option class="all two" @if( $product->product_type_sub == 25 ) selected @endif value="25">TAFE</option>
                                        <option class="all three" @if( $product->product_type_sub == 31 ) selected @endif value="31">BNF Kutu</option>
                                        <option class="all three" @if( $product->product_type_sub == 32 ) selected @endif value="32">Godiva Kutu</option>
                                        <option class="all three" @if( $product->product_type_sub == 33 ) selected @endif value="33">TAFE Kutu</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Ürün Adı :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('name', null, ['class' => 'form-control']) !!}
                                </div>
                            </td>
                        </tr>
                        @if( count($cityList) == 1 )
                            <tr>
                                <td style="vertical-align: inherit;">
                                    İleriye Sipariş:
                                </td>
                                <td>
                                    <div class="form-group">
                                        <select style="width: 150px;" name="future_delivery_day" id="future_delivery_day" class="select2 btn btn-default dropdown-toggle">
                                            <option @if( $product->future_delivery_day == 0 ) selected @endif value="0">Aynı Gün</option>
                                            <option @if( $product->future_delivery_day == 1 ) selected @endif value="1">1 Gün Sonra</option>
                                            <option @if( $product->future_delivery_day == 2 ) selected @endif value="2">2 Gün Sonra</option>
                                            <option @if( $product->future_delivery_day == 3 ) selected @endif value="3">3 Gün Sonra</option>
                                            <option @if( $product->future_delivery_day == 4 ) selected @endif value="4">4 Gün Sonra</option>
                                            <option @if( $product->future_delivery_day == 5 ) selected @endif value="5">5 Gün Sonra</option>
                                            <option @if( $product->future_delivery_day == 6 ) selected @endif value="6">6 Gün Sonra</option>
                                        </select>
                                        <input class="hidden" id="future_delivery_day_old" value="{{$product->future_delivery_day}}">
                                    </div>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td>
                                Kargolanabilir :
                            </td>
                            <td>
                                <div style="margin-top: 0px;" class="checkbox">
                                    <label>
                                        <input style="width:30px;height:30px" @if( $product->cargo_sendable ) checked @endif name="cargo_sendable" type="checkbox">
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Çok Satan :
                            </td>
                            <td>
                                <div style="margin-top: 0px;" class="checkbox">
                                    <label>
                                        <input style="width:30px;height:30px" @if( $product->best_seller) checked @endif name="best_seller" type="checkbox">
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Atölyenin Tercihi :
                            </td>
                            <td>
                                <div style="margin-top: 0px;" class="checkbox">
                                    <label>
                                        <input style="width:30px;height:30px" @if( $product->choosen ) checked @endif name="choosen" type="checkbox">
                                    </label>
                                </div>
                            </td>
                        </tr>
                        @if( count($userCities) > 1 )
                            <tr>
                                <td style="vertical-align: inherit;">
                                    Aktif Şehirler :
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input class="hidden" id="totalCity" name="totalCity"
                                               value="{{count($userCities)}}">
                                        <select style="height: 50px;" id="cityId" name="allCities[]" multiple>
                                            @foreach($userCities as $city)
                                                <option value="{{$city->city_id}}" {{$city->activeCity ? 'selected' : ''}}>{{$city->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td style="vertical-align: inherit;">
                                Ürün Link Adı(url kısmında geçecek isim) :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('url_parametre', null, ['class' => 'form-control' , 'id' => 'urun_link']) !!}
                                </div>
                            </td>
                        </tr>
                        @if( $product->old_price )
                            <tr>
                                <td style="vertical-align: inherit;">
                                    İndirimli Fiyat :
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input class="form-control" name="old_price" type="text" id="old_price" value="{{$product->price}}">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: inherit;">
                                    Fiyat :
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input class="form-control" name="price" type="text" id="price" value="{{$product->old_price}}">
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td style="vertical-align: inherit;">
                                    İndirimli Fiyat :
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input class="form-control" name="old_price" type="text" id="old_price" value="{{$product->old_price}}">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: inherit;">
                                    Fiyat :
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input class="form-control" name="price" type="text" id="price" value="{{$product->price}}">
                                    </div>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td style="vertical-align: inherit;">
                                Ana Sayfa Tanımı :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('landing_page_desc', null, ['class' => 'form-control']) !!}
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('landing_page_desc' . $lang->lang_id , $lang->landing_page_desc, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Detay Sayfası Tanımı :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('detail_page_desc', null, ['class' => 'form-control']) !!}
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('detail_page_desc' . $lang->lang_id , $lang->detail_page_desc, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Image Adı : (Türkçe karakter olmamalı)
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('image_name', null, ['class' => 'form-control' , 'id' => 'imgName' ]) !!}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Nasıl Yapılır Başlığı :
                                <label style="font-size: 11px;vertical-align: super;" id="howHeader"></label>
                            </td>
                            <td>
                                <div style="padding-top: 7px;" class="form-group">
                                    <textarea id="metaT2" onkeyup="countChar1(this)" style="width: 100%;" class="span6" rows="3" name="how_to_title" placeholder="What's up?" required>{{$product->how_to_title}}</textarea>
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('how_to_title' . $lang->lang_id , $lang->how_to_title, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Nasıl Yapılır İçerik :
                                <label style="font-size: 11px;vertical-align: super;" id="howContent"></label>
                            </td>
                            <td>
                                <div style="padding-top: 7px;" class="form-group">
                                    <textarea id="metaT3" onkeyup="countChar2(this)" style="width: 100%;" class="span6" rows="3" name="how_to_detail" placeholder="What's up?" required>{{$product->how_to_detail}}</textarea>
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('how_to_detail' . $lang->lang_id , $lang->how_to_detail, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Nasıl Yapılır Adım 1 :
                                <label style="font-size: 11px;vertical-align: super;" id="how1"></label>
                            </td>
                            <td>
                                <div style="padding-top: 7px;" class="form-group">
                                    <textarea id="metaT4" onkeyup="countChar3(this)" style="width: 100%;" class="span6" rows="3" name="how_to_step1" placeholder="What's up?" required>{{$product->how_to_step1}}</textarea>
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('how_to_step1' . $lang->lang_id , $lang->how_to_step1, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Nasıl Yapılır Adım 2 :
                                <label style="font-size: 11px;vertical-align: super;" id="how2"></label>
                            </td>
                            <td>
                                <div style="padding-top: 7px;" class="form-group">
                                    <textarea id="metaT5" onkeyup="countChar4(this)" style="width: 100%;" class="span6" rows="3" name="how_to_step2" placeholder="What's up?" required>{{$product->how_to_step2}}</textarea>
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('how_to_step2' . $lang->lang_id , $lang->how_to_step2, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Nasıl Yapılır Adım 3 :
                                <label style="font-size: 11px;vertical-align: super;" id="how3"></label>
                            </td>
                            <td>
                                <div style="padding-top: 7px;" class="form-group">
                                    <textarea id="metaT6" onkeyup="countChar5(this)" style="width: 100%;" class="span6" rows="3" name="how_to_step3" placeholder="What's up?" required>{{$product->how_to_step3}}</textarea>
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('how_to_step3' . $lang->lang_id , $lang->how_to_step3, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Youtube Embed Source :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('youtube_url', null, ['class' => 'form-control']) !!}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Extra Bilgi 1 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('extra_info_1', null, ['class' => 'form-control']) !!}
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('extra_info_1' . $lang->lang_id , $lang->extra_info_1, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Extra Bilgi 2 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('extra_info_2', null, ['class' => 'form-control']) !!}
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('extra_info_2' . $lang->lang_id , $lang->extra_info_2, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Extra Bilgi 3 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('extra_info_3', null, ['class' => 'form-control']) !!}
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('extra_info_3' . $lang->lang_id , $lang->extra_info_3, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Tagler:
                            </td>
                            <td>
                                <div class="form-group">
                                    <select id="tagId" name="allTags[]" multiple>
                                        @foreach($allTag as $tag)
                                            <option value="{{$tag->id}}" {{$tag->selected ? 'selected' : ''}}>{{$tag->tags_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Ana Tag:
                            </td>
                            <td>
                                <div class="form-group">
                                    <select id="tagId" name="tag_id">
                                        @foreach($allTag as $tag)
                                            <option value="{{$tag->id}}" {{$product->tag_id == $tag->id ? 'selected' : ''}}>{{$tag->tags_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                        </tr>
                        @if( count($cityList) == 1 )
                            <tr>
                                <td style="vertical-align: inherit;">
                                    1. Related Ürün :
                                </td>
                                <td>
                                    <select class="form-control select2" data-placeholder="Select a State" style="width: 100%;"
                                            id="customerId" name="related_1">
                                        @foreach($allProduct as $productByOne)
                                            @if(count($relatedList) > 0)
                                                <option value="{{$productByOne->id}}" {{$productByOne->id == $relatedList[0]->id ? 'selected'  : ''}}>{{$productByOne->name}}</option>
                                            @else
                                                <option value="{{$productByOne->id}}">{{$productByOne->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: inherit;">
                                    2. Related Ürün :
                                </td>
                                <td>
                                    <select class="form-control select2" data-placeholder="Select a State" style="width: 100%;"
                                            id="customerId" name="related_2">
                                        @foreach($allProduct as $productByOne)
                                            @if(count($relatedList) > 1)
                                                <option value="{{$productByOne->id}}" {{$productByOne->id == $relatedList[1]->id ? 'selected'  : ''}}>{{$productByOne->name}}</option>
                                            @else
                                                <option value="{{$productByOne->id}}">{{$productByOne->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: inherit;">
                                    3. Related Ürün :
                                </td>
                                <td>
                                    <select class="form-control select2" data-placeholder="Select a State" style="width: 100%;"
                                            id="customerId" name="related_3">
                                        @foreach($allProduct as $productByOne)
                                            @if(count($relatedList) > 2)
                                                <option value="{{$productByOne->id}}" {{$productByOne->id == $relatedList[2]->id ? 'selected'  : ''}}>{{$productByOne->name}}</option>
                                            @else
                                                <option value="{{$productByOne->id}}">{{$productByOne->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: inherit;">
                                    4. Related Ürün :
                                </td>
                                <td>
                                    <select class="form-control select2" data-placeholder="Select a State" style="width: 100%;"
                                            id="customerId" name="related_4">
                                        @foreach($allProduct as $productByOne)
                                            @if(count($relatedList) > 3)
                                                <option value="{{$productByOne->id}}" {{$productByOne->id == $relatedList[3]->id ? 'selected'  : ''}}>{{$productByOne->name}}</option>
                                            @else
                                                <option value="{{$productByOne->id}}">{{$productByOne->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td style="vertical-align: inherit;">
                                Tedarikçi :
                            </td>
                            <td>
                                <select class="form-control select2" style="width: 100%;" id="customerId" name="supplier">
                                    @if( $product->supplier_id == 0 )
                                        <option selected="selected" value="0">Tedarikçi Seçiniz.</option>
                                    @else
                                        <option value="0">Tedarikçi Seçiniz.</option>
                                    @endif
                                    @foreach($suppliers as $supplier)
                                        <option @if($supplier->id == $product->supplier_id ) selected="selected"  @endif  value="{{$supplier->id}}">{{$supplier->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="col-lg-12">
                <h1 style="margin-bottom: 0px;background-color: #3c8dbc4a;border: 1px solid;border-color: #3c8dbc33;height: 63px;padding-top: 17px;padding-right: 20px;border-radius: 10px;font-size: 26px;padding-left: 15px;">
                    SEO
                    <a style="float: right;" class="glyphicon glyphicon-wrench" data-toggle="collapse" data-target="#seo" aria-expanded="false" aria-controls="collapseExample"></a>
                </h1>
                <div style="border: 1px solid rgba(60, 141, 188, 0.2);border-radius: 10px;height: 2px;padding: 10px;" class="collapse" id="seo">
                    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;margin-bottom: 0px;">
                        <tr>
                            <td style="vertical-align: inherit;">
                                Url Title :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('url_title', null, ['class' => 'form-control' ]) !!}
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('url_title' . $lang->lang_id , $lang->url_title, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Image Title :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::text('img_title', null, ['class' => 'form-control' ]) !!}
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('img_title' . $lang->lang_id , $lang->img_title, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Meta Description Tag :
                                <label style="font-size: 11px;vertical-align: super;" id="meta"></label>
                            </td>
                            <td>
                                <div style="padding-top: 7px;" class="form-group">
                                    <textarea id="metaT" onkeyup="countChar(this)" style="width: 100%;" class="span6" rows="3" name="meta_description" required>{{$product->meta_description}}</textarea>
                                    @foreach($descriptionList as $lang)
                                        {!! Form::text('meta_description' . $lang->lang_id , $lang->meta_description, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="col-lg-12">
                <h1 style="margin-bottom: 0px;background-color: #3c8dbc4a;border: 1px solid;border-color: #3c8dbc33;height: 63px;padding-top: 17px;padding-right: 20px;border-radius: 10px;font-size: 26px;padding-left: 15px;">
                    Web Medya
                    <a style="float: right;" class="glyphicon glyphicon-wrench" data-toggle="collapse" data-target="#webMedia" aria-expanded="false" aria-controls="collapseExample"></a>
                </h1>
                <div style="border: 1px solid rgba(60, 141, 188, 0.2);border-radius: 10px;height: 2px;padding: 10px;" class="collapse" id="webMedia">
                    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;margin-bottom: 0px;">
                        <tr>
                            <td style="vertical-align: inherit;">
                                Ana Sayfa Fotoğrafı :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img') !!}<a href="{{ $product->MainImage }}"
                                                                target="_blank">{{ $product->MainImage }}</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Animasyon(Hower) Fotoğrafı :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('landingAnimation', ['id' => 'landingAnimationId']) !!}
                                    @if( isset($product->landingAnimation) )
                                        <a href="{{ $product->landingAnimation }}" target="_blank">{{ $product->landingAnimation }}</a>
                                        {!! Html::link('/admin/delete/detailImage/' . $product->landingAnimationId  . '/' .  $product->id  , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Animasyon(Hower) Fotoğrafı - 2 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('landingAnimation2', ['id' => 'landingAnimation2Id']) !!}
                                    @if( isset($product->landingAnimation2) )
                                        <a href="{{ $product->landingAnimation2 }}" target="_blank">{{ $product->landingAnimation2 }}</a>
                                        {!! Html::link('/admin/delete/detailImage/' . $product->landingAnimation2Id  . '/' .  $product->id  , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Mobil Fotoğrafı :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('mobileImg') !!}
                                    <a href="{{ $product->mobileImage }}" target="_blank">{{ $product->mobileImage }}</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Detay Sayfası Fotoğrafı :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('imgDetail') !!}<a href="{{ $product->DetailImage }}"
                                                                      target="_blank">{{ $product->DetailImage }}</a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 1:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img1') !!} @if(count($product->detailListImage) > 0) <a href="{{ $product->image1 }}" target="_blank">{{ $product->image1 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image1Id  . '/' .  $product->id  , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 2:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img2') !!}@if(count($product->detailListImage) > 1) <a
                                            href="{{ $product->image2 }}" target="_blank">{{ $product->image2 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image2Id  . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 3:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img3') !!} @if(count($product->detailListImage) > 2) <a
                                            href="{{ $product->image3 }}" target="_blank">{{ $product->image3 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image3Id  . '/' .  $product->id  , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 4:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img4') !!} @if(count($product->detailListImage) > 3) <a
                                            href="{{ $product->image4 }}" target="_blank">{{ $product->image4 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image4Id  . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 5:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img5') !!} @if(count($product->detailListImage) > 4) <a
                                            href="{{ $product->image5 }}" target="_blank">{{ $product->image5 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image5Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 6:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img6') !!} @if(count($product->detailListImage) > 5) <a
                                            href="{{ $product->image6 }}" target="_blank">{{ $product->image6 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image6Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 7:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img7') !!} @if(count($product->detailListImage) > 6) <a
                                            href="{{ $product->image7 }}" target="_blank">{{ $product->image7 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image7Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 8:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img8') !!} @if(count($product->detailListImage) > 7) <a
                                            href="{{ $product->image8 }}" target="_blank">{{ $product->image8 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image8Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 9:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img9') !!} @if(count($product->detailListImage) > 8) <a
                                            href="{{ $product->image9 }}" target="_blank">{{ $product->image9 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image9Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 10:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img10') !!} @if(count($product->detailListImage) > 9) <a
                                            href="{{ $product->image10 }}" target="_blank">{{ $product->image10 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image10Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 11:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img11') !!} @if(count($product->detailListImage) > 10) <a
                                            href="{{ $product->image11 }}" target="_blank">{{ $product->image11 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image11Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 12:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img12') !!} @if(count($product->detailListImage) > 11) <a
                                            href="{{ $product->image12 }}" target="_blank">{{ $product->image12 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image12Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 13:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img13') !!} @if(count($product->detailListImage) > 12) <a
                                            href="{{ $product->image13 }}" target="_blank">{{ $product->image13 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image13Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 14:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img14') !!} @if(count($product->detailListImage) > 13) <a
                                            href="{{ $product->image14 }}" target="_blank">{{ $product->image14 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image14Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="vertical-align: inherit;">
                                Fotoğraf 15:
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('img15') !!} @if(count($product->detailListImage) > 14) <a
                                            href="{{ $product->image15 }}" target="_blank">{{ $product->image15 }}</a>
                                    {!! Html::link('/admin/delete/detailImage/' . $product->image15Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @else @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="col-lg-12">
                <h1 style="margin-bottom: 0px;background-color: #3c8dbc4a;border: 1px solid;border-color: #3c8dbc33;height: 63px;padding-top: 17px;padding-right: 20px;border-radius: 10px;font-size: 26px;padding-left: 15px;">
                    Diğer Medya
                    <a style="float: right;" class="glyphicon glyphicon-wrench" data-toggle="collapse" data-target="#otherMedia" aria-expanded="false" aria-controls="collapseExample"></a>
                </h1>
                <div style="border: 1px solid rgba(60, 141, 188, 0.2);border-radius: 10px;height: 2px;padding: 10px;" class="collapse" id="otherMedia">
                    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;margin-bottom: 0px;">
                        <tr>
                            <td>
                                300x300 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('300', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';" style="width: 40px;" src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/300-300/{{$product->id}}.jpg">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                400x400 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('400', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/400-400/{{$product->id}}.jpg">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                600x600 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('600', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="https://s3.eu-central-1.amazonaws.com/bloomandfresh/600-600/{{$product->id}}.jpg">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1080x1080 - Main :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('1080', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    @if( count($fbImage) > 0 )
                                        <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="{{$fbImage[0]->image_url}}">
                                        {!! Html::link('/admin/delete/fbImage/' . $fbImage[0]->id . '/' . $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1080x1080 - 2 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('1080_2', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    @if( count($fbImage2) > 0 )
                                        <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="{{$fbImage2[0]->image_url}}">
                                        {!! Html::link('/admin/delete/fbImage/' . $fbImage2[0]->id . '/' . $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1080x1080 - 3 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('1080_3', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    @if( count($fbImage3) > 0 )
                                        <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="{{$fbImage3[0]->image_url}}">
                                        {!! Html::link('/admin/delete/fbImage/' . $fbImage3[0]->id . '/' . $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1080x1080 - 4 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('1080_4', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    @if( count($fbImage4) > 0 )
                                        <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="{{$fbImage4[0]->image_url}}">
                                        {!! Html::link('/admin/delete/fbImage/' . $fbImage4[0]->id . '/' . $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1080x1080 - 5 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('1080_5', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    @if( count($fbImage5) > 0 )
                                        <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="{{$fbImage5[0]->image_url}}">
                                        {!! Html::link('/admin/delete/fbImage/' . $fbImage5[0]->id . '/' . $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1080x1080 - 6 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('1080_6', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    @if( count($fbImage6) > 0 )
                                        <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="{{$fbImage6[0]->image_url}}">
                                        {!! Html::link('/admin/delete/fbImage/' . $fbImage6[0]->id . '/' . $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1080x1080 - 7 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('1080_7', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    @if( count($fbImage7) > 0 )
                                        <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="{{$fbImage7[0]->image_url}}">
                                        {!! Html::link('/admin/delete/fbImage/' . $fbImage7[0]->id . '/' . $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1080x1080 - 8 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('1080_8', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    @if( count($fbImage8) > 0 )
                                        <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="{{$fbImage8[0]->image_url}}">
                                        {!! Html::link('/admin/delete/fbImage/' . $fbImage8[0]->id . '/' . $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1080x1080 - 9 :
                            </td>
                            <td>
                                <div class="form-group">
                                    {!! Form::file('1080_9', [ 'style' => 'display: inline;width: 185px;' ]) !!} <a href="" target="_blank"></a>
                                    @if( count($fbImage9) > 0 )
                                        <img onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/a/ac/No_image_available.svg';"  style="width: 40px;" src="{{$fbImage9[0]->image_url}}">
                                        {!! Html::link('/admin/delete/fbImage/' . $fbImage9[0]->id . '/' . $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width: 60px;float: right;']) !!}
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}

                    <div class="col-lg-12" style="border-radius: 5px;width: 120px;float: right;margin-top: 15px;">
                        <input id="backLink" name="backLink" class="hidden" >
                        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control saveProdcut' , 'id' => 'saveProduct']) !!}
                    </div>

        <!--<tr>
                    <td>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('activation_status', null, $product->activation_status_id, ['class' => 'form-control']) !!}
                </label>
            </div>
        </td>
        <td>
            <div class="form-group">
{!! Form::text('name', null, ['class' => 'form-control']) !!}
                </div>
            </td>
            <td>
                <div class="form-group">
{!! Form::text('price', null, ['class' => 'form-control']) !!}
                </div>
            </td>
            <td>
                <div class="form-group">
{!! Form::text('description', null, ['class' => 'form-control']) !!}
                </div>
            </td>
            <td style="padding-left:21px;">{!! Form::file('img') !!} <a href="/productImageUploads/{{ $product->id }}.pdf" target="_blank">{{ $product->id }}.png</a></td>
                    <td>
                        {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                <div class="form-group">
{!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                </div>
            </td>
        </tr>-->
            {!! Form::close() !!}
        @endforeach
    <!--</table>-->
@stop()
@section('footer')
    <script>
        $( document ).ready(function() {
            $('#meta').text($('#metaT').text().length);
            $('#howHeader').text($('#metaT2').text().length);
            $('#howContent').text($('#metaT3').text().length);
            $('#how1').text($('#metaT4').text().length);
            $('#how2').text($('#metaT5').text().length);
            $('#how3').text($('#metaT6').text().length);

            var tempValue = $('#productType').val();

            $('.all').addClass('hidden');

            if( tempValue == '1' ){
                $('.one').removeClass('hidden');
            }
            else if( tempValue == '2' ){
                $('.two').removeClass('hidden');
            }
            else if( tempValue == '3' ){
                $('.three').removeClass('hidden');
            }

            $('#backLink').val(getUrlVars()["bl"]);

        });

        function getUrlVars()
        {
            var vars = [], hash;
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
            for(var i = 0; i < hashes.length; i++)
            {
                hash = hashes[i].split('=');
                vars.push(hash[0]);
                vars[hash[0]] = hash[1];
            }
            return vars;
        }

        function selectProduct(val){
            $('.all').addClass('hidden');
            $('#productTypeSub').val(0);
            if( val == '1' ){
                $('.one').removeClass('hidden');
            }
            else if( val == '2' ){
                $('.two').removeClass('hidden');
            }
            else if( val == '3' ){
                $('#productTypeSub').val(31);
                $('.three').removeClass('hidden');
            }
        };

        function countChar(val) {
            $('#meta').text(val.value.length);
        };

        function countChar1(val) {
            $('#howHeader').text(val.value.length);
        };

        function countChar2(val) {
            $('#howContent').text(val.value.length);
        };

        function countChar3(val) {
            $('#how1').text(val.value.length);
        };

        function countChar4(val) {
            $('#how2').text(val.value.length);
        };

        function countChar5(val) {
            $('#how3').text(val.value.length);
        };



        $('#urun_link').bind('keypress', function (event) {
            var regex = new RegExp("^[a-zA-Z0-9]+$");
            var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
            if (!regex.test(key) && key != '-') {
                event.preventDefault();
                return false;
            }
        });

        var selectedLang = "";

        function showSelectedLang(tempId) {
            var tempClassId = '.' + tempId;
            var buttonId = "#" + tempId;
            if (selectedLang == "") {
                $(buttonId).css('border-color', '#23BF20');
                selectedLang = tempId;
                $(tempClassId).removeClass('hidden');
            }
            else if (selectedLang == tempId) {
                selectedLang = "";
                $(buttonId).css('border-color', '');
                $(tempClassId).addClass('hidden');
            }
            else if (selectedLang != tempId) {
                var tempBackClassId = '.' + selectedLang;
                var oldButtonId = "#" + selectedLang;
                $(oldButtonId).css('border-color', '');
                selectedLang = tempId;
                $(buttonId).css('border-color', '#23BF20');
                $(tempBackClassId).removeClass('hidden');
                $(tempClassId).addClass('hidden');
            }
            //$(tempClassId).hide();
            return false;
        }

        $('#saveProduct').click(function () {

            if ($('#urun_link').val() == "") {
                if (confirm("Ürün url adı girmek zorundasınız!") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if ($('#old_price').val() && $('#price').val() == '' ) {
                if (confirm("Sadece indirimli fiyat giremezseniz! Eğer indirim yoksa 'Fiyat' alanını güncelleyip 'İndirimli Fiyat' alanını boş bırakınız.") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if ($('#old_price').val() == '0' ) {
                if (confirm("İndirimli fiyat alanına sıfır girdiniz!!!!!!") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if ($('#tagId').val() == null) {
                if (confirm("Tag seçmeden değişiklik yapamazsınız.") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if ($('#imgName').val() == "") {
                if (confirm("Resim adı seçmedin! :'''(") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if ($('#landingAnimationId').val() != "") {
                if (confirm("Animasyon Fotoğrafının boyutları 350 X 555 değilse kayıt edilmeyecektir. Devam etmek istiyor musunuz?") == true) {
                    return true;
                } else {
                    return false;
                }
            }

            if ($('#future_delivery_day_old').val() > 0 && $('#future_delivery_day').val()  == 0 ) {
                window.alert("İleriye sipariş alanını pasif yaptın! İlgili ürünün, Ürün Dağıtım Saatleri'ni de güncellemen gerekmektedir!");
            }

            if ($('#oldActivationId').is(':checked') && $('#activationId').val() == 0) {
                window.alert("Açtığın Ürünün Dağıtım Saatlerini Güncellemeyi Unutma!");
            }

        });
    </script>
@stop