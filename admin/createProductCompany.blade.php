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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Kurum-Ürün Ekleme Sayfası</h1><h1 class="col-lg-3 col-md-3">
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
        <tr>
            <td>
                Şirket :
            </td>
            <td>
                <div class="form-group">
                    <select name="company" onchange="$('.companyInfo').val($(this).val())" class="btn btn-default dropdown-toggle">
                        <option value="0">Ürün seçiniz!</option>
                        @foreach($companyList as $tag)
                            <option value="{{$tag->id}}">{{$tag->name}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Kurumsal Ürünler :
            </td>
            <td>
                <div class="form-group">
                    <select value="" onchange="selectProduct($(this).val());" name="company" class="btn btn-default dropdown-toggle">
                        <option value="0">Ürün seçiniz!</option>
                        @foreach($products as $tag)
                            <option value="{{$tag->id}}">{{$tag->name}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        @foreach($products as $product)
                {!! Form::model($product , ['action' => 'AdminPanelController@insertProductCompany', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <input name="company" class="hidden companyInfo" value="">
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Ürün Adı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('name', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Ürün Link Adı(url kısmında geçecek isim) :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('url_parametre', null, ['class' => 'form-control' , 'id' => 'urun_link']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Fiyat :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('price', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Ana Sayfa Fotoğrafı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('img') !!}<a href="{{ $product->MainImage }}" target="_blank">{{ $product->MainImage }}</a>
                        <input name="img" class="hidden" value="{{ $product->MainImage }}">
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Detay Sayfası Fotoğrafı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::file('imgDetail') !!}<a href="{{ $product->DetailImage }}" target="_blank">{{ $product->DetailImage }}</a>
                            <input name="imgDetail" class="hidden" value="{{ $product->DetailImage }}">
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Ana Sayfa Tanımı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('landing_page_desc', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Detay Sayfası Tanımı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('detail_page_desc', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Image Adı : (Türkçe karakter olmamalı)
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('image_name', null, ['class' => 'form-control' , 'id' => 'imgName' ]) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Url Title :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('url_title', null, ['class' => 'form-control' ]) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Image Title :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('img_title', null, ['class' => 'form-control' ]) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Meta Description Tag :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('meta_description', null, ['class' => 'form-control' ]) !!}

                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Nasıl Yapılır Başlığı :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_title', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Nasıl Yapılır İçerik :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_detail', null, ['class' => 'form-control']) !!}

                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Nasıl Yapılır Adım 1 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_step1', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Nasıl Yapılır Adım 2 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_step2', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Nasıl Yapılır Adım 3 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('how_to_step3', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Extra Bilgi 1 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('extra_info_1', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Extra Bilgi 2 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('extra_info_2', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
                    <td>
                        Extra Bilgi 3 :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('extra_info_3', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
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
                <tr class="{{$product->id}} trClass hidden">
                    <td colspan="2">
                        <div class="form-group">
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control saveProdcut' , 'id' => 'saveProduct']) !!}
                        </div>
                    </td>
                </tr>
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

        function selectProduct(salesId){
            var tempTr = '.' + salesId;
            $('.trClass').addClass('hidden');
            $(tempTr).removeClass('hidden');
        }

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