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
    <h1 style="margin-top: 0px;">Bloom & Fresh Ürün Kuponu Oluşturma Sayfası</h1>

    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        {!! Form::model(null , ['action' => 'AdminPanelController@storeProductCoupon', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps' ]) !!}
        <tr>
            <td>
                Kupon Adı :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('name', '', ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Kupon Açıklaması :
            </td>
            <td>
                <div class="form-group">
                    {!! Form::text('description', '', ['class' => 'form-control']) !!}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Çiçekler:
            </td>
            <td>
                <div class="form-group">
                    <select  id="tagId" name="allTags[]" multiple>
                        @foreach($flowers as $flower)
                            <option value="{{$flower->id}}">{{$flower->name}}</option>
                        @endforeach
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Kupon Değeri :
            </td>
            <td>
                <div class="form-group">
                    <select name="value">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option  selected  value="20">20</option>
                        <option value="25">25</option>
                        <option value="30">30</option>
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Geçirli olduğu süre :
            </td>
            <td>
                <div class="form-group">
                      <input type="datetime-local" value={{$now}}  name="expiredDate">
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Teslimat tarihi başlangıç:
            </td>
            <td>
                <div class="form-group">
                      <input type="datetime-local" value={{$now}}  name="startDate">
                </div>
            </td>
        </tr>
        <tr>
            <td>
                Teslimat tarihi bitiş:
            </td>
            <td>
                <div class="form-group">
                      <input type="datetime-local" value={{$now}}  name="endDate">
                </div>
            </td>
        </tr>
        <div class="form-group">
        {!! Form::submit('Kaydet', ['class' => 'btn btn-success form-control']) !!}
        </div>
        {!! Form::close() !!}
    </table>

@stop()