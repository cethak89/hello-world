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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Promo Oluşturma Sayfası</h1><h1 class="col-lg-3 col-md-3">
    </h1>

    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        {!! Form::model(null , ['action' => 'newFunctions@addNewPromo', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <tr>
            <td style="width: 50%;">
                Promo Türü :
            </td>
            <td>
                <div class="form-group">
                    <select style="width: 150px;" name="type" class="btn btn-default dropdown-toggle">
                        <option value="1">Diamond 2-Grid</option>
                        <option value="2">Rectangle 2-Grid</option>
                        <option value="3">Rectangle 1-Grid</option>
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Başlık Üst :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('title_small', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Görünmeyen Başlık :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('temp_name', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Başlık Üst Font-Size :
            </td>
            <td>
                <div class="form-group">
                    <input style="width: 126px;" type="number" name="title_small_size" class="form-control" max="50" min="10" value="24" step=1>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Başlık :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('title', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Başlık Font-Size :
            </td>
            <td>
                <div class="form-group">
                    <input style="width: 126px;" type="number" name="title_font_size" class="form-control" max="50" min="10" value="46" step=1>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Açıklama :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('description', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Açıklama Font-Size :
            </td>
            <td>
                <div class="form-group">
                    <input style="width: 126px;" type="number" name="description_size" class="form-control" max="50" min="10" value="13" step=1>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Button Açıklaması :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('button_text', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Link :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('link', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Arka Plan Fotoğrafı (700px-555px (2 grid) , 350px-555px (1 grid) )) :
            </td>
            <td>
                <div class="form-group">
                    <input name="background_image" type="file" accept="image/*" id="myFileMain" size="1000000000">
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Border Rengi :
            </td>
            <td>
                <div style="width: 125px;" class="input-group my-colorpicker2 colorpicker-element">
                    <input type="text" name="border_color" class="form-control">

                    <div class="input-group-addon">
                        <i style="background-color: rgba(70, 86, 117, 0.57);"></i>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Background Rengi :
            </td>
            <td>
                <div style="width: 125px;" class="input-group my-colorpicker2 colorpicker-element">
                    <input type="text" name="background_color" class="form-control">

                    <div class="input-group-addon">
                        <i style="background-color: rgba(70, 86, 117, 0.57);"></i>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Background Görünürlük Oranı :
            </td>
            <td>
                <div class="input-group">
                    <input style="width: 126px;" type="number" name="opacity" class="form-control" max="1" min="0.1" value="0.8" step=0.1>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Text Rengi :
            </td>
            <td>
                <div style="width: 125px;" class="input-group my-colorpicker2 colorpicker-element">
                    <input type="text" name="text_color" class="form-control">

                    <div class="input-group-addon">
                        <i style="background-color: rgba(70, 86, 117, 0.57);"></i>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Button Text Rengi :
            </td>
            <td>
                <div style="width: 125px;" class="input-group my-colorpicker2 colorpicker-element">
                    <input type="text" name="button_text_color" class="form-control">

                    <div class="input-group-addon">
                        <i style="background-color: rgba(70, 86, 117, 0.57);"></i>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Button Arka Plan Rengi :
            </td>
            <td>
                <div style="width: 125px;" class="input-group my-colorpicker2 colorpicker-element">
                    <input type="text" name="button_background_color" class="form-control">

                    <div class="input-group-addon">
                        <i style="background-color: rgba(70, 86, 117, 0.57);"></i>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="form-group">
                    {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                </div>
            </td>
        </tr>
        {!! Form::close() !!}
    </table>

@stop()

@section('footer')
    <script>

        $('.my-colorpicker2').colorpicker();

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
    </script>
@stop