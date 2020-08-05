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
                <h1>Bloom & Fresh Şirket Kuponları Listesi</h1>
            </td>
            <td>
            {!! Html::link('/create-company-coupon' , 'Kupon Oluştur', ['class' => 'btn btn-success', 'style' => 'width:100%; vertical-align: middle;']) !!}
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
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDescCompany',['created_at'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Eklenme Tarihi <span onclick="window.location='{{ action('AdminPanelController@orderWithCompany' , ['created_at'])  }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDescCompany',['name'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> İsim <span onclick="window.location='{{ action('AdminPanelController@orderWithCompany' , ['name'])  }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDescCompany',['group_name'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Ait Olduğu Grup <span onclick="window.location='{{ action('AdminPanelController@orderWithCompany' , ['group_name'])  }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th>Açıklama</th>
            <th>Tür</th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDescCompany',['value'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Değer <span onclick="window.location='{{ action('AdminPanelController@orderWithCompany' , ['value'])  }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDescCompany',['valid'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Geçerlilik <span onclick="window.location='{{ action('AdminPanelController@orderWithCompany' , ['valid'])  }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDescCompany',['expiredDate'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Süre <span onclick="window.location='{{ action('AdminPanelController@orderWithCompany' , ['expiredDate'])  }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDescCompany',['mail'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Mail <span onclick="window.location='{{ action('AdminPanelController@orderWithCompany' , ['mail'])  }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th><span  onclick="window.location='{{ action('AdminPanelController@orderWithDescCompany',['count'])  }}'" class="glyphicon glyphicon-triangle-bottom" aria-hidden="true"></span> Kullanım Sayısı <span onclick="window.location='{{ action('AdminPanelController@orderWithCompany' , ['count'])  }}'" class="glyphicon glyphicon-triangle-top" aria-hidden="true"></span> </th>
            <th></th>
            {!! Form::close() !!}
        </tr>
        @foreach($coupons as $coupon)
            @if($id == $coupon->id )
                {!! Form::model($coupon, ['action' => 'AdminPanelController@updateCompanyCoupon', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
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
                                {!! Form::text('group_name', null, ['class' => 'form-control']) !!}
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
                                <select name="type">
                                        <option value="1" {{$coupon->type == 1  ? 'selected' : ''}}>TL</option>
                                        <option value="2" {{$coupon->type == 2  ? 'selected' : ''}}>Yüzde</option>
                                </select>
                            </div>
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
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('valid', null, $coupon->valid, ['style' => 'width:30px;height:30px;']) !!}
                            </label>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input type="datetime" value="{{$coupon->expiredDate}}" name="expiredDate">
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            {!! Form::text('mail', null, ['class' => 'form-control']) !!}
                        </div>
                    </td>
                    <td style="padding-left:21px;">{{$coupon->count}}</td>
                    <td>
                        {!! Form::hidden('id', $coupon->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
                        </div>
                    </td>
                </tr>
                {!! Form::close() !!}
            @else
                {!! Form::open(['action' => 'AdminPanelController@deleteCompanyCoupon', 'method' => 'DELETE' ]) !!}
                <tr id="row-id-{{$coupon->id}}" onclick="window.location='{{ action('AdminPanelController@showCompanyCoupon', [ $coupon->id ]) }}'">
                    <td style="padding-left:21px;">{{$coupon->created_at}}</td>
                    <td style="padding-left:21px;">{{$coupon->name}}</td>
                    <td style="padding-left:21px;">{{$coupon->group_name}}</td>
                    <td style="padding-left:21px;">{{$coupon->description}}</td>
                    <td style="padding-left:21px;">{{$coupon->type == 1  ? 'TL' : 'Yuzde'}}</td>
                    <td style="padding-left:21px;">{{$coupon->value}}</td>
                    <td>
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('valid', null, $coupon->valid, ['style' => 'width:30px;height:30px;', 'disabled' => 'true']) !!}
                            </label>
                        </div>
                    </td>
                    <td style="padding-left:21px;">{{$coupon->expiredDate}}</td>
                    <td style="padding-left:21px;">{{$coupon->mail}}</td>
                    <td style="padding-left:21px;">{{$coupon->count}}</td>
                    <td>
                        {!! Form::hidden('id', $coupon->id, ['class' => 'form-control']) !!}
                        <div class="form-group">
                            {!! Form::submit('Sil', ['class' => 'btn btn-danger form-control test', 'style' => 'width:100%;']) !!}
                        </div>
                    </td>
                </tr>
                {!! Form::close() !!}
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