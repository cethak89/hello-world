@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        div.form-group {
            height: 20px;
        }

        .form-group {
            margin-bottom: 0px !important;
        }

    </style>
@stop

@section('content')

    <style>
        .form-group {
            margin-bottom: 0px !important;
        }

        .checkbox {
            margin-top: 0px !important;
        }

        td {
            vertical-align: middle !important;
        }

        textarea {
            resize: vertical;
        }

    </style>

    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Ürün Oluşturma Sayfası</h1>
    <h1 class="col-lg-3 col-md-3">
        @foreach($langList as $lang)
            <button class="button hidden" id="{{$lang->lang_id}}"
                    onclick="showSelectedLang('{{$lang->lang_id}}')">{{$lang->lang_name}}</button>
        @endforeach
    </h1>

    {!! Form::model(null , ['action' => 'AdminPanelController@insertProduct', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
    <div class="col-lg-12">
        <h1 style="margin-bottom: 0px;background-color: #3c8dbc4a;border: 1px solid;border-color: #3c8dbc33;height: 63px;padding-top: 17px;padding-right: 20px;border-radius: 10px;font-size: 26px;padding-left: 15px;">
            Ürün Tanımları
            <a style="float: right;" class="glyphicon glyphicon-wrench" data-toggle="collapse" data-target="#productMain" aria-expanded="false" aria-controls="collapseExample"></a>
        </h1>
        <div style="border: 1px solid rgba(60, 141, 188, 0.2);border-radius: 10px;height: 2px;padding: 10px;" class="collapse" id="productMain">
            <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                <div id="trPart"></div>
                <tr>
                    <td style="width: 270px;">
                        Durum :
                    </td>
                    <td>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('activation_status', null, 0, ['style' => 'width:30px;height:30px;']) !!}
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Ürün Adı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('name', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Ürün Türü :
                    </td>
                    <td>
                        <div class="form-group">
                            <select style="width: 150px;" name="product_type" class="btn btn-default dropdown-toggle" onchange="selectProduct($(this).val());" id="productType">
                                <option value="0">Seçiniz</option>
                                <option value="1">Çiçek</option>
                                <option value="2">Çikolata</option>
                                <option value="3">Hediye Kutusu</option>
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
                                <option class="all one" value="11">Buket</option>
                                <option class="all one" value="12">Masaüstü</option>
                                <option class="all one" value="13">Sukulent</option>
                                <option class="all one" value="14">Saksı</option>
                                <option class="all one" value="15">Orkide</option>
                                <option class="all one" value="16">Solmayan Gül</option>
                                <option class="all one" value="17">Kutuda Çiçek</option>
                                <option class="all two" value="21">Godiva</option>
                                <option class="all two" value="22">Baylan</option>
                                <option class="all two" value="23">BNF Macarons</option>
                                <option class="all two" value="24">Hazz</option>
                                <option class="all two" value="25">TAFE</option>
                                <option class="all three" value="31">BNF Kutu</option>
                                <option class="all three" value="32">Godiva Kutu</option>
                                <option class="all three" value="33">TAFE Kutu</option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Ürün Link Adı(url kısmında geçecek isim) :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('url_parametre', null, ['class' => 'form-control' , 'id' => 'urun_link']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fiyat :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('price', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Tanım :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('description', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Ana Sayfa Tanımı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('landing_page_desc', null, ['class' => 'form-control']) !!}
                            @foreach($langList as $lang)
                                {!! Form::text('landing_page_desc' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Detay Sayfası Tanımı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('detail_page_desc', null, ['class' => 'form-control']) !!}
                            @foreach($langList as $lang)
                                {!! Form::text('detail_page_desc' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: inherit;">
                        Nasıl Yapılır Başlığı :
                        <label style="font-size: 11px;vertical-align: super;" id="howHeader"></label>
                    </td>
                    <td>
                        <div class="form-group">
                    <textarea id="metaT2" onkeyup="countChar1(this)" style="width: 100%;" class="span6" rows="3"
                              name="how_to_title"></textarea>
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_title' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                        <div class="form-group">
                    <textarea id="metaT3" onkeyup="countChar2(this)" style="width: 100%;" class="span6" rows="3"
                              name="how_to_detail"></textarea>
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_detail' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                        <div class="form-group">
                    <textarea id="metaT4" onkeyup="countChar3(this)" style="width: 100%;" class="span6" rows="3"
                              name="how_to_step1"></textarea>
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_step1' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                        <div class="form-group">
                    <textarea id="metaT5" onkeyup="countChar4(this)" style="width: 100%;" class="span6" rows="3"
                              name="how_to_step2"></textarea>
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_step2' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                        <div class="form-group">
                    <textarea id="metaT6" onkeyup="countChar5(this)" style="width: 100%;" class="span6" rows="3"
                              name="how_to_step3"></textarea>
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_step3' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Image Adı (Türkçe karakter olmamalı) :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('image_name', null, ['class' => 'form-control' , 'id' => 'imgName']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Extra Bilgi 1 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('extra_info_1', null, ['class' => 'form-control']) !!}
                            @foreach($langList as $lang)
                                {!! Form::text('extra_info_1' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>
                        Extra Bilgi 2 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('extra_info_2', null, ['class' => 'form-control']) !!}
                            @foreach($langList as $lang)
                                {!! Form::text('extra_info_2' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>
                        Extra Bilgi 3 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('extra_info_3', null, ['class' => 'form-control']) !!}
                            @foreach($langList as $lang)
                                {!! Form::text('extra_info_3' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
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
                    <td>
                        Ana Tag:
                    </td>
                    <td>
                        <div class="form-group">
                            <select id="tagId" name="tag_id">
                                @foreach($allTag as $tag)
                                    <option value="{{$tag->id}}">{{$tag->tags_name}}</option>
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
                                    <option value="{{$productByOne->id}}">{{$productByOne->name}}</option>
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
                                    <option value="{{$productByOne->id}}">{{$productByOne->name}}</option>
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
                                    <option value="{{$productByOne->id}}">{{$productByOne->name}}</option>
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
                                    <option value="{{$productByOne->id}}">{{$productByOne->name}}</option>
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
                        <select class="form-control select2" style="width: 215px;" name="supplier">
                            <option value="0">Tedarikçi seç</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{$supplier->id}}">{{$supplier->name}}</option>
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
            <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                <div id="trPart"></div>
                <tr>
                    <td>
                        Url Title :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('url_title', null, ['class' => 'form-control']) !!}
                            @foreach($langList as $lang)
                                {!! Form::text('url_title' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Image Title :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('img_title', null, ['class' => 'form-control']) !!}
                            @foreach($langList as $lang)
                                {!! Form::text('img_title' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                        <div class="form-group">
                    <textarea id="metaT" onkeyup="countChar(this)" style="width: 100%;" class="span6" rows="3"
                              name="meta_description"></textarea>
                            @foreach($langList as $lang)
                                {!! Form::text('meta_description' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
            <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                {!! Form::model(null , ['action' => 'AdminPanelController@insertProduct', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <div id="trPart"></div>
                <tr>
                    <td>
                        Ana Sayfa Fotoğrafı :
                    </td>
                    <td>
                        <div class="form-group">
                            <input name="img" type="file" accept="image/*" id="myFileMain" size="1000000000">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: inherit;">
                        Animasyon(Hower) Fotoğrafı :
                    </td>
                    <td>
                        <div class="form-group">
                            <input name="landingAnimation" type="file" accept="image/*" id="landingAnimation" size="1000000000">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Mobil Fotoğraf :
                    </td>
                    <td>
                        <div class="form-group">
                            <input name="mobileImage" type="file" accept="image/*" id="myFileMain" size="1000000000">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Detay Sayfası Fotoğrafı :
                    </td>
                    <td>
                        <div class="form-group">
                            <input name="imgDetail" type="file" accept="image/*" id="myFileDetail" size="1000000000">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 1 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img1') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 2 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img2') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 3 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img3') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 4 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img4') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 5 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img5') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 6 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img6') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 7 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img7') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 8 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img8') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 9 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img9') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 10 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img10') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 11 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img11') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 12 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img12') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 13 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img13') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 14 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img14') !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf Detay 15 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img15') !!} <a href="" target="_blank"></a>
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
            <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                {!! Form::model(null , ['action' => 'AdminPanelController@insertProduct', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <div id="trPart"></div>
                <tr>
                    <td>
                        300x300 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('300', ['id' => '300' ]) !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        400x400 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('400', ['id' => '400' ]) !!} <a href="" target="_blank"></a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        600x600 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('600', ['id' => '600' ]) !!} <a href="" target="_blank"></a>
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
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="col-lg-12" style="border-radius: 5px;width: 120px;float: right;margin-top: 15px;">
        <input id="backLink" name="backLink" class="hidden" >
        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control saveProdcut' , 'id' => 'saveProduct']) !!}
    </div>

    {!! Form::close() !!}

@stop()

@section('footer')
    <script>

        $( document ).ready(function() {

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

        });

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

            if ($('#productType').val() == 0) {
                if (confirm("Ürün türü seçmek zorundasınız!") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if ($('#productTypeSub').val() == 0) {
                if (confirm("Ürün alt türü seçmek zorundasınız!") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if ($('#urun_link').val() == "") {
                if (confirm("Ürün url adı girmek zorundasınız!") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if ($('#myFileMain').val() == "") {
                if (confirm("Ana sayfa fotosu girmelisiniz!") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if ($('#myFileDetail').val() == "") {
                if (confirm("Detay sayfa fotosu girmelisiniz!") == true) {
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

            if ($('#300').val() == "" || $('#400').val() == "" || $('#600').val() == "") {
                if (confirm("300x300, 400x400 veya 600x600 resimleri yüklemedin! Devam etmek istiyor musun?") == true) {
                    return true;
                } else {
                    return false;
                }
            }

            if ($('#landingAnimation').val() != "") {
                if (confirm("Animasyon Fotoğrafının boyutları 350 X 555 değilse kayıt edilmeyecektir. Devam etmek istiyor musunuz?") == true) {
                    return true;
                } else {
                    return false;
                }
            }

        });
    </script>
@stop