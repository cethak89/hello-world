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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Ürün Detay Sayfası</h1><h1 class="col-lg-3 col-md-3">
        @foreach($descriptionList as $lang)
            <button class="button" id="{{$lang->lang_id}}" onclick="showSelectedLang('{{$lang->lang_id}}')">{{$lang->lang_id}}</button>
        @endforeach
    </h1>

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
        @foreach($products as $product)
                {!! Form::model($product , ['action' => 'AdminPanelController@updateProductCompany', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr>
                    <td>
                        Durum :
                    </td>
                    <td>
                        <div class="checkbox">
                            <label>
                            {!! Form::checkbox('activation_status', null, $product->activation_status_id, ['style' => 'width: 30px;height: 30px;' , 'id' => 'oldActivationId']) !!}
                            <input class="hidden" id="activationId" value="{{$product->activation_status_id}}">
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Tükendi :
                    </td>
                    <td>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('limit_statu', null, $product->limit_statu, ['style' => 'width: 30px;height: 30px;' , 'id' => '']) !!}
                                <input class="hidden" id="" value="{{$product->limit_statu}}">
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Yakında :
                    </td>
                    <td>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('coming_soon', null, $product->coming_soon, ['style' => 'width: 30px;height: 30px;' , 'id' => '']) !!}
                                <input class="hidden" id="" value="{{$product->coming_soon}}">
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Şirket Adı :
                    </td>
                    <td>
                        <div class="form-group">
                            {{$product->companyName}}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Kurumsal Ürün Adı :
                    </td>
                    <td>
                        <div class="form-group">
                            {{$product->companyProductName}}
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
                        Ürün Sırası :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('landing_page_order', $product->landing_page_order, ['class' => 'form-control']) !!}
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
                        Ana Sayfa Fotoğrafı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img') !!}<a href="{{ $product->MainImage }}" target="_blank">{{ $product->MainImage }}</a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Arka plan rengi :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('background_color', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Arka plan rengi Alt :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('second_background_color', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Detay Sayfası Fotoğrafı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('imgDetail') !!}<a href="{{ $product->DetailImage }}" target="_blank">{{ $product->DetailImage }}</a>
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
                            @foreach($descriptionList as $lang)
                                {!! Form::text('landing_page_desc' . $lang->lang_id , $lang->landing_page_desc, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                            @foreach($descriptionList as $lang)
                                {!! Form::text('detail_page_desc' . $lang->lang_id , $lang->detail_page_desc, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Image Adı : (Türkçe karakter olmamalı)
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('image_name', null, ['class' => 'form-control' , 'id' => 'imgName' ]) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
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
                    <td>
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
                    <td>
                        Meta Description Tag :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('meta_description', null, ['class' => 'form-control' ]) !!}
                            @foreach($descriptionList as $lang)
                                {!! Form::text('meta_description' . $lang->lang_id , $lang->meta_description, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Nasıl Yapılır Başlığı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_title', null, ['class' => 'form-control']) !!}
                            @foreach($descriptionList as $lang)
                                {!! Form::text('how_to_title' . $lang->lang_id , $lang->how_to_title, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Nasıl Yapılır İçerik :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_detail', null, ['class' => 'form-control']) !!}
                            @foreach($descriptionList as $lang)
                                {!! Form::text('how_to_detail' . $lang->lang_id , $lang->how_to_detail, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Nasıl Yapılır Adım 1 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_step1', null, ['class' => 'form-control']) !!}
                            @foreach($descriptionList as $lang)
                                {!! Form::text('how_to_step1' . $lang->lang_id , $lang->how_to_step1, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Nasıl Yapılır Adım 2 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_step2', null, ['class' => 'form-control']) !!}
                            @foreach($descriptionList as $lang)
                                {!! Form::text('how_to_step2' . $lang->lang_id , $lang->how_to_step2, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Nasıl Yapılır Adım 3 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_step3', null, ['class' => 'form-control']) !!}
                            @foreach($descriptionList as $lang)
                                {!! Form::text('how_to_step3' . $lang->lang_id , $lang->how_to_step3, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
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
                            @foreach($descriptionList as $lang)
                                {!! Form::text('extra_info_1' . $lang->lang_id , $lang->extra_info_1, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                            @foreach($descriptionList as $lang)
                                {!! Form::text('extra_info_2' . $lang->lang_id , $lang->extra_info_2, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                            @foreach($descriptionList as $lang)
                                {!! Form::text('extra_info_3' . $lang->lang_id , $lang->extra_info_3, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                                    <option value="{{$tag->id}}" {{$product->tag_id == $tag->id ? 'selected' : ''}}>{{$tag->tags_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 1:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img1') !!} @if(count($product->detailListImage) > 0) <a href="{{ $product->image1 }}" target="_blank">{{ $product->image1 }}</a>
                             {!! Html::link('/admin/delete/detailImage/' . $product->image1Id  . '/' .  $product->id  , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                             @else @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 2:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img2') !!}@if(count($product->detailListImage) > 1) <a href="{{ $product->image2 }}" target="_blank">{{ $product->image2 }}</a>
                            {!! Html::link('/admin/delete/detailImage/' . $product->image2Id  . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                            @else @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 3:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img3') !!} @if(count($product->detailListImage) > 2) <a href="{{ $product->image3 }}" target="_blank">{{ $product->image3 }}</a>
                            {!! Html::link('/admin/delete/detailImage/' . $product->image3Id  . '/' .  $product->id  , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                            @else @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 4:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img4') !!} @if(count($product->detailListImage) > 3) <a href="{{ $product->image4 }}" target="_blank">{{ $product->image4 }}</a>
                            {!! Html::link('/admin/delete/detailImage/' . $product->image4Id  . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                            @else @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 5:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img5') !!} @if(count($product->detailListImage) > 4) <a href="{{ $product->image5 }}" target="_blank">{{ $product->image5 }}</a>
                            {!! Html::link('/admin/delete/detailImage/' . $product->image5Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                            @else @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 6:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img6') !!} @if(count($product->detailListImage) > 5) <a href="{{ $product->image6 }}" target="_blank">{{ $product->image6 }}</a>
                            {!! Html::link('/admin/delete/detailImage/' . $product->image6Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                            @else @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 7:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img7') !!} @if(count($product->detailListImage) > 6) <a href="{{ $product->image7 }}" target="_blank">{{ $product->image7 }}</a>
                            {!! Html::link('/admin/delete/detailImage/' . $product->image7Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                            @else @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 8:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img8') !!} @if(count($product->detailListImage) > 7) <a href="{{ $product->image8 }}" target="_blank">{{ $product->image8 }}</a>
                            {!! Html::link('/admin/delete/detailImage/' . $product->image8Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                            @else @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 9:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img9') !!} @if(count($product->detailListImage) > 8) <a href="{{ $product->image9 }}" target="_blank">{{ $product->image9 }}</a>
                            {!! Html::link('/admin/delete/detailImage/' . $product->image9Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                            @else @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf 10:
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img10') !!} @if(count($product->detailListImage) > 9) <a href="{{ $product->image10 }}" target="_blank">{{ $product->image10 }}</a>
                            {!! Html::link('/admin/delete/detailImage/' . $product->image10Id . '/' .  $product->id , 'Sil', ['class' => 'btn btn-danger', 'style' => 'width:100%; vertical-align: right;']) !!}
                            @else @endif
                        </div>
                    </td>
                </tr>
                {!! Form::hidden('id', $product->id, ['class' => 'form-control']) !!}
                <tr>
                    <td colspan="2">
                    <div class="form-group">
                        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control saveProdcut' , 'id' => 'saveProduct']) !!}
                    </div>
                    </td>
                </tr>

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
    </table>
@stop()
@section('footer')
    <script>

        $('#urun_link').bind('keypress', function (event) {
            var regex = new RegExp("^[a-zA-Z0-9]+$");
            var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
            if (!regex.test(key) && key != '-') {
               event.preventDefault();
               return false;
            }
        });

        var selectedLang = "";
        function showSelectedLang(tempId){
            var tempClassId = '.' + tempId;
            var buttonId = "#" + tempId;
            if(selectedLang == ""){
                $(buttonId).css('border-color' , '#23BF20');
                selectedLang = tempId;
                $(tempClassId).removeClass('hidden');
            }
            else if(selectedLang == tempId ){
                selectedLang = "";
                $(buttonId).css('border-color' , '');
                $(tempClassId).addClass('hidden');
            }
            else if(selectedLang != tempId ){
                var tempBackClassId = '.' + selectedLang;
                var oldButtonId = "#" + selectedLang;
                $(oldButtonId).css('border-color' , '');
                selectedLang = tempId;
                $(buttonId).css('border-color' , '#23BF20');
                $(tempBackClassId).removeClass('hidden');
                $(tempClassId).addClass('hidden');
            }
            //$(tempClassId).hide();
            return false;
        }
        $('#saveProduct').click(function() {

            if($('#urun_link').val() == ""){
                if (confirm("Ürün url adı girmek zorundasınız!") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if($('#tagId').val() == null){
                if (confirm("Tag seçmeden değişiklik yapamazsınız.") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if($('#imgName').val() == ""){
                if (confirm("Resim adı seçmedin! :'''(") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if($('#oldActivationId').is(':checked') && $('#activationId').val() == 0 ){
                window.alert("Açtığın Ürünün Dağıtım Saatlerini Güncellemeyi Unutma!");
            }

        });
    </script>
    @stop