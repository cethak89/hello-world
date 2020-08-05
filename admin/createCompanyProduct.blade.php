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
    <h1 class="col-lg-12 col-md-12">Bloom & Fresh Kurumsal Ürün Oluşturma Sayfası</h1>


    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                {!! Form::model(null , ['action' => 'AdminPanelController@insertCompanyProduct', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <div id="trPart"></div>
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
                        <input name="img" type="file" accept="image/*" id="myFile" size="1000000000">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Detay Sayfası Fotoğrafı :
                    </td>
                    <td>
                        <div class="form-group">
                        <input name="imgDetail" type="file" accept="image/*" id="myFile" size="1000000000">
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
                    <td>
                        Meta Description Tag :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('meta_description', null, ['class' => 'form-control']) !!}
                            @foreach($langList as $lang)
                                {!! Form::text('meta_description' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Image Adı  (Türkçe karakter olmamalı) :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('image_name', null, ['class' => 'form-control' , 'id' => 'imgName']) !!}
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
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_title' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_detail' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_step1' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_step2' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                            @foreach($langList as $lang)
                                {!! Form::text('how_to_step3' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
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
                            <select  id="tagId" name="allTags[]" multiple>
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
                    <td colspan="2">
                    <div class="form-group">
                        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control' , 'id' => 'saveProduct']) !!}
                    </div>
                    </td>
                </tr>
                {!! Form::close() !!}
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


        });
    </script>
    @stop