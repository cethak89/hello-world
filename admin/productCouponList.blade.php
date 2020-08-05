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

    <table width="100%">
        <tr style="width: 100%">
            <td>
                <h1>Bloom & Fresh Ürün Kuponları Listesi</h1>
            </td>
            <td>
            {!! Html::link('/create-product-coupon' , 'Kupon Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
            </td>
        </tr>
    </table>
    @if( $errors->any() )
        <ul class="alert alert-danger">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <button id="testXls"class="btn btn-danger" onClick ="$('tr').each(function() {$(this).find('th:eq(6)').remove();$(this).find('td:eq(6)').remove();$(this).find('th:eq(9)').remove();$(this).find('td:eq(9)').remove();}); $('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Şirket Kuponları Listesi',filename: 'Şirket Kuponları'});location.reload();">Excel Çıktısı İçin Tıklayınız</button>
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th>Oluşturulma Tarihi</th>
            <th>Kupon Adı</th>
            <th>Kupon Açıklaması</th>
            <th>Kupon Değeri</th>
            <th>Çiçekler</th>
            <th>Son kullanım tarihi</th>
            {!! Form::close() !!}
        </tr>
        @foreach($coupons as $coupon)
            @if($id == $coupon->id )
                {!! Form::model($coupon, ['action' => 'AdminPanelController@updateProductCoupon', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
                <tr id="row-id-{{$coupon->id}}">
                    <td style="padding-left:21px;">{{$coupon->created_at}}</td>
                    <td>
                        <div class="form-group">
                            <label>
                                {!! Form::text('name', null, ['class' => 'form-control']) !!}
                            </label>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <label>
                                {!! Form::text('description', null, ['class' => 'form-control']) !!}
                            </label>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <div class="form-group">
                                <select name="value">
                                    <option value="5"  {{$coupon->value == 5  ?  'selected' : ''}}>5</option>
                                    <option value="10" {{$coupon->value == 10  ? 'selected' : ''}}>10</option>
                                    <option value="15" {{$coupon->value == 15  ? 'selected' : ''}}>15</option>
                                    <option value="20" {{$coupon->value == 20  ? 'selected' : ''}}>20</option>
                                    <option value="25" {{$coupon->value == 25  ? 'selected' : ''}}>25</option>
                                    <option value="30" {{$coupon->value == 30  ? 'selected' : ''}}>30</option>
                                </select>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <select  id="tagId" name="allTags[]" multiple>
                                @foreach($flowers as $flower)
                                    <option value="{{$flower->id}}" {{$flower->selected ? 'selected' : ''}}>{{$flower->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input type="datetime" value="{{$coupon->expired_date}}" name="expiredDate">
                        </div>
                    </td>
                    <td>
                        {!! Form::hidden('id', $coupon->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                        </div>
                    </td>
                </tr>
                {!! Form::close() !!}
            @else
                <tr id="row-id-{{$coupon->id}}" onclick="window.location='{{ action('AdminPanelController@showProductCoupon', [ $coupon->id ]) }}'">
                    <td style="padding-left:21px;">{{$coupon->created_at}}</td>
                    <td style="padding-left:21px;">{{$coupon->name}}</td>
                    <td style="padding-left:21px;">{{$coupon->description}}</td>
                    <td style="padding-left:21px;">{{$coupon->value}}</td>
                    <td style="padding-left:21px;">
                        @foreach($coupon->flowers as $flower)
                            {{$flower->name}}
                        @endforeach
                    </td>
                    <td style="padding-left:21px;">{{$coupon->expired_date}}</td>
                </tr>
            @endif
        @endforeach
    </table>
@stop()

@section('footer')
    <script>
        $('.test').click(function() {
            var x;
                if (confirm("Silmek istediğinize emin misiniz?") == true) {
                    return true;
                } else {
                    return false;
                }
        });
        //$('html').click(function() {
        //    window.location='/admin/coupons';
        //});
//
        //function setOrderParameter(paremeter , upOrDown){
        //    $('input[name=orderParameter]').val(paremeter);
        //    $('input[name=upOrDown]').val(upOrDown);
        //    console.log( $('input[name=orderParameter]').val());
        //}
//
        //$('#products-table').click(function(event){
        //    event.stopPropagation();
        //});
//
        //// go to anchor
        //if( {{ $id > 0 ? 'true' : 'false' }} )
        //{
        //    window.scrollTo(0, document.getElementById('row-id-{{ $id }}').offsetTop);
        //}
    </script>
@stop