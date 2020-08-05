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
    <h1 class="col-lg-9 col-md-9">Bloom & Fresh Kurumsal Sipariş Ekleme Sayfası</h1><h1 class="col-lg-3 col-md-3">
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
        {!! Form::model(null , ['action' => 'AdminPanelController@insertCompanyDelivery', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <tr>
            <td>
                Şirket :
            </td>
            <td>
                <div class="form-group">
                    <select id="company_id" name="company_id" class="btn btn-default dropdown-toggle">
                        <option value="0">Şirket seçiniz!</option>
                        @foreach($billingList as $tag)
                            <option value="{{$tag->id}}">{{$tag->company}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Ürün :
            </td>
            <td>
                <div class="form-group">
                    <select id="product_id" value="" name="product_id" class="btn btn-default dropdown-toggle">
                        <option value="0">Ürün seçiniz!</option>
                        @foreach($productList as $tag)
                            <option value="{{$tag->id}}">{{$tag->name}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        <input name="company" class="hidden companyInfo" value="">
        <tr class=" trClass hidden">
            <td>
                Fiyat :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('total_price', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td>
                Not İçin Gönderen :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('card_sender', null, ['class' => 'form-control' , 'id' => 'urun_link']) !!}
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td>
                Not İçin Alıcı :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('card_receiver', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td>
                Not :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('card_message', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td>
                Alıcı :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('receiver', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td>
                Alıcı Telefon :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('receiver_mobile', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td>
                Alıcı Adres :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('receiver_address', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td>
                Dağıtım Bölgesi :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('delivery_location', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td>
                Admin Notu :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('admin_not', null, ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td>
                İstenen teslim saati:
            </td>
            <td>
                <div class="form-group">
                    <input type="date"  name="wanted_delivery_date">
                    <div style="display: inline-block;">
                        <select name="wanted_delivery_date_1" class="btn btn-default dropdown-toggle">
                        @foreach($myArray as $tag)
                            <option value="{{$tag->val}}">{{$tag->hour}}</option>
                        @endforeach
                        </select>     -
                        <select name="wanted_delivery_date_2" class="btn btn-default dropdown-toggle">
                        @foreach($myArray as $tag)
                            <option value="{{$tag->val}}">{{$tag->hour}}</option>
                        @endforeach
                        </select>
                    </div>
                </div>
            </td>
        </tr>
        <tr class=" trClass">
            <td colspan="2">
                <div class="form-group">
                    {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control saveProdcut' , 'id' => 'saveProduct']) !!}
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
        $('#saveProduct').click(function() {
            console.log($('#product_id').val());
            if($('#product_id').val() == 0){
                if (confirm("Ürün seçmek zorundasınız!") == true) {
                    return false;
                } else {
                    return false;
                }
            }

            if($('#company_id').val() == 0){
                if (confirm("Şirket seçmek zorundasınız.") == true) {
                    return false;
                } else {
                    return false;
                }
            }

        });
    </script>
    @stop