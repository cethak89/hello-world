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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Banner Oluşturma Sayfası</h1><h1 class="col-lg-3 col-md-3">
        @foreach($langList as $lang)
            <button class="button" id="{{$lang->lang_id}}" onclick="showSelectedLang('{{$lang->lang_id}}')">{{$lang->lang_name}}</button>
        @endforeach
    </h1>

    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
                @if($mobile)
                    {!! Form::model(null , ['action' => 'AdminPanelController@createMobileBanner', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                @else
                    {!! Form::model(null , ['action' => 'AdminPanelController@createBanner', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                @endif
                <tr>
                    <td>
                        Durum :
                    </td>
                    <td>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('active', null, 0, ['class' => 'form-control']) !!}
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        URL :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('url', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Başlık :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('header', null, ['class' => 'form-control']) !!}
                            @foreach($langList as $lang)
                                {!! Form::text('header' . $lang->lang_id , null, ['class' => 'hidden form-control ' . $lang->lang_id  ]) !!}
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Fotoğraf :
                    </td>
                    <td>
                        <div class="form-group">
                        <input name="img_url" type="file" accept="image/*" id="myFile" size="1000000000">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Metin Rengi :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('font_color', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Arka Plan Rengi :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('background_color', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                @if($mobile)
                @else
                    <tr>
                        <td>
                            Görüntülenme Sırası :
                        </td>
                        <td>
                            <div class="form-group">
                                {!! Form::text('order_number', null, ['class' => 'form-control']) !!}
                            </div>
                        </td>
                    </tr>
                @endif
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