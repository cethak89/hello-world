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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Banner Oluşturma Sayfası</h1>
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        {!! Form::model($aboutUsPeople , ['action' => 'AdminPanelController@updateAboutUs', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr>
                    <td>
                        Sıra :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('order', $aboutUsPeople->order, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        İsim :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('name', $aboutUsPeople->name, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Konum :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('tittre', $aboutUsPeople->tittre, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        LinkedIn :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('linked_url', $aboutUsPeople->linked_url, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        Resim url :
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('image_url', $aboutUsPeople->image_url, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <div class="form-group">
                        {!! Form::text('id', $aboutUsPeople->id, ['class' => 'form-control hidden']) !!}
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