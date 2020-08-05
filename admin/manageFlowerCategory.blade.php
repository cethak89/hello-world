@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
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
                <h1>Bloom & Fresh Ürün-Tag/Çiçekler Sayfası Matrisi</h1>
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
    <button style="width: 176px;margin-bottom: 20px;margin-left: 5px;" class="btn btn-success form-control" onclick="showOnlyPages()">
        Sadece Çiçekler Sayfaları
    </button>
    <button style="width: 176px;margin-bottom: 20px;margin-left: 5px;" class="btn btn-primary form-control" onclick="showOnlyTags()">
        Sadece Tagler
    </button>
    <button style="width: 176px;margin-bottom: 20px;margin-left: 5px;" class="btn btn-info form-control" onclick="changeHorizontal()">
        Yatay Görünüm
    </button>
    <button style="width: 176px;margin-bottom: 20px;margin-left: 5px;" class="btn btn-warning form-control" onclick="changeVertical()">
        Dikey Görünüm
    </button>
    <div id="all-vertical"  class="" style="overflow-x: scroll;transform: rotateX(180deg);">
    <table name="test1" class="table table-hover table-bordered" style="vertical-align: middle;transform: rotateX(180deg);">
        <tr style="background: none;">
            <th>

            </th>
            <th>

            </th>
            <th style="text-align: center;background-color: #f39c120f;" colspan="{{ count($activeFlowers[0]->tagList) }}">
                Çiçek Tagleri
            </th>
            <th style="text-align: center;background-color: #00c0ef05;" colspan="{{ count($activeFlowers[0]->pageList) }}">
                Çiçekler Sayfası
            </th>
        </tr>
        <tr style="background: none;">
            <th>
                Ürün Adı
            </th>
            <th>
                Main Tag
            </th>
            @foreach( $activeFlowers[0]->tagList as $tag )
                <th @if( $tag->id == $category_id && $type ==  1 ) onclick="location.href='/admin/manage-flower-category';" @else onclick="location.href='/admin/manage-flower-category/{{$tag->id}}/1';" @endif style="height: 153px;padding: 15px;position: relative;background-color: #f39c120f;cursor: pointer; @if( $tag->id == $category_id && $type ==  1 ) border: 2px solid #00a65a; @endif ">
                    <p style="width: 150px;transform: rotate(-90deg);position: absolute;top: 56px;left: -54px;text-align: center;height: 40px;margin-bottom: 0px;padding-top: 6px;padding-bottom: 6px;">
                        {{$tag->tags_name}}
                    </p>
                </th>
            @endforeach
            @foreach( $activeFlowers[0]->pageList as $page )
                <th @if( $page->id == $category_id && $type ==  2 ) onclick="location.href='/admin/manage-flower-category';" @else onclick="location.href='/admin/manage-flower-category/{{$page->id}}/2';" @endif style="height: 153px;padding: 15px;position: relative;background-color: #00c0ef05;cursor: pointer; @if( $page->id == $category_id && $type ==  2 ) border: 2px solid #00a65a; @endif">
                    <p style="width: 150px;transform: rotate(-90deg);position: absolute;top: 56px;left: -54px;text-align: center;height: 40px;margin-bottom: 0px;padding-top: 6px;padding-bottom: 6px;">
                        {{$page->head}}
                    </p>
                </th>
            @endforeach
        </tr>
        @foreach($activeFlowers as $product)
            <tr>
                <td>
                    {{$product->name}}
                </td>
                <td>
                    {{$product->tags_name}}
                </td>
                @foreach( $product->tagList as $tag )
                    <td style="background-color: #f39c120f;padding: 2px;text-align: center;" title="{{$tag->tags_name}}">
                        <input onchange="changeStatus('tag', {{$product->id}}, {{$tag->id}}, {{$tag->activePage}} )" @if( $tag->id == $product->tag_id ) disabled @endif style="text-align: center;width:28px;height:28px" name="activationId_1" @if( $tag->activePage == 1 ) checked @endif type="checkbox">
                    </td>
                @endforeach
                @foreach( $product->pageList as $page )
                    <td style="background-color: #00c0ef05;padding: 2px;text-align: center;" title="{{$page->head}}">
                        <input onchange="changeStatus('page', {{$product->id}}, {{$page->id}}, {{$page->activePage}} )" style="text-align: center;width:28px;height:28px" name="activationId_1" @if( $page->activePage == 1 ) checked @endif type="checkbox">
                    </td>
                @endforeach
            </tr>
        @endforeach
        <tr>
            <td style="text-align: right;" colspan="100%">
                <button style="width: 176px;" class="btn btn-success form-control" onclick="$('#formRequestId').submit();">Kaydet</button>
            </td>
        </tr>
    </table>
    </div>
    <div id="all-horizontal" class="" style="overflow-x: scroll;transform: rotateX(180deg);display: none;">
    <table name="test1" class="table table-hover table-bordered" style="vertical-align: middle;transform: rotateX(180deg);">
        <tr>
            <th>

            </th>
            <th>

            </th>
            <th style="text-align: center;background-color: #f39c120f;" colspan="{{ count($activeFlowers[0]->tagList) }}">
                Çiçek Tagleri
            </th>
            <th style="text-align: center;background-color: #00c0ef05;" colspan="{{ count($activeFlowers[0]->pageList) }}">
                Çiçekler Sayfası
            </th>
        </tr>
        <tr>
            <th>
                Ürün Adı
            </th>
            <th>
                Main Tag
            </th>
            @foreach( $activeFlowers[0]->tagList as $tag )
                <th @if( $tag->id == $category_id && $type ==  1 ) onclick="location.href='/admin/manage-flower-category';" @else onclick="location.href='/admin/manage-flower-category/{{$tag->id}}/1';" @endif style="background-color: #f39c120f;cursor: pointer; @if( $tag->id == $category_id && $type ==  1 ) border: 2px solid #00a65a; @endif">
                    <p style="">
                        {{$tag->tags_name}}
                    </p>
                </th>
            @endforeach
            @foreach( $activeFlowers[0]->pageList as $page )
                <th @if( $page->id == $category_id && $type ==  2 ) onclick="location.href='/admin/manage-flower-category';" @else onclick="location.href='/admin/manage-flower-category/{{$page->id}}/2';" @endif style="background-color: #00c0ef05; cursor: pointer; @if( $page->id == $category_id && $type ==  2 ) border: 2px solid #00a65a; @endif">
                    <p style="">
                        {{$page->head}}
                    </p>
                </th>
            @endforeach
        </tr>
        @foreach($activeFlowers as $product)
            <tr>
                <td>
                    {{$product->name}}
                </td>
                <td>
                    {{$product->tags_name}}
                </td>
                @foreach( $product->tagList as $tag )
                    <td style="text-align: center;background-color: #f39c120f;" title="{{$tag->tags_name}}">
                        <input onchange="changeStatus2('tag', {{$product->id}}, {{$tag->id}}, {{$tag->activePage}} )" @if( $tag->id == $product->tag_id ) disabled @endif style="width:30px;height:30px" name="activationId_1" @if( $tag->activePage == 1 ) checked @endif type="checkbox">
                    </td>
                @endforeach
                @foreach( $product->pageList as $page )
                    <td style="text-align: center;background-color: #00c0ef05;" title="{{$page->head}}">
                        <input onchange="changeStatus2('page', {{$product->id}}, {{$page->id}}, {{$page->activePage}} )" style="width:30px;height:30px" name="activationId_1" @if( $page->activePage == 1 ) checked @endif type="checkbox">
                    </td>
                @endforeach
            </tr>
        @endforeach
        <tr>
            <td style="text-align: right;" colspan="100%">
                <button style="width: 176px;" class="btn btn-success form-control" onclick="$('#formRequestId2').submit();">Kaydet</button>
            </td>
        </tr>
    </table>
    </div>
    <table id="only-pages" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;display: none;">
        <tr>
            <th>

            </th>
            <th>

            </th>
            <th style="text-align: center" colspan="{{ count($activeFlowers[0]->pageList) }}">
                Çiçekler Sayfası
            </th>
        </tr>
        <tr>
            <th>
                Ürün Adı
            </th>
            <th>
                Main Tag
            </th>
            @foreach( $activeFlowers[0]->pageList as $page )
                <th style="text-align: center;">
                    <p style="">
                        {{$page->head}}
                    </p>
                </th>
            @endforeach
        </tr>
        @foreach($activeFlowers as $product)
            <tr>
                <td>
                    {{$product->name}}
                </td>
                <td>
                    {{$product->tags_name}}
                </td>
                @foreach( $product->pageList as $page )
                    <td style="text-align: center;" title="{{$page->head}}">
                        <input onchange="changeStatus3('page', {{$product->id}}, {{$page->id}}, {{$page->activePage}} )" style="width:30px;height:30px" name="activationId_1" @if( $page->activePage == 1 ) checked @endif type="checkbox">
                    </td>
                @endforeach
            </tr>
        @endforeach
        <tr>
            <td style="text-align: right;" colspan="100%">
                <button style="width: 176px;" class="btn btn-success form-control" onclick="$('#formRequestId3').submit();">Kaydet</button>
            </td>
        </tr>
    </table>
    <table id="only-tags" name="test1" class="table table-hover table-bordered" style="vertical-align: middle;display: none;">
        <tr>
            <th>

            </th>
            <th>

            </th>
            <th style="text-align: center" colspan="{{ count($activeFlowers[0]->tagList) }}">
                Çiçek Tagleri
            </th>
        </tr>
        <tr>
            <th>
                Ürün Adı
            </th>
            <th>
                Main Tag
            </th>
            @foreach( $activeFlowers[0]->tagList as $tag )
                <th style="text-align: center;">
                    <p style="">
                        {{$tag->tags_name}}
                    </p>
                </th>
            @endforeach
        </tr>
        @foreach($activeFlowers as $product)
            <tr>
                <td>
                    {{$product->name}}
                </td>
                <td>
                    {{$product->tags_name}}
                </td>
                @foreach( $product->tagList as $tag )
                    <td style="text-align: center;" title="{{$tag->tags_name}}">
                        <input onchange="changeStatus4('tag', {{$product->id}}, {{$tag->id}}, {{$tag->activePage}} )" @if( $tag->id == $product->tag_id ) disabled @endif style="width:30px;height:30px" name="activationId_1" @if( $tag->activePage == 1 ) checked @endif type="checkbox">
                    </td>
                @endforeach
            </tr>
        @endforeach
        <tr>
            <td style="text-align: right;" colspan="100%">
                <button style="width: 176px;" class="btn btn-success form-control" onclick="$('#formRequestId4').submit();">Kaydet</button>
            </td>
        </tr>
    </table>
    <div class="hidden">
        <form method="POST" action="https://everybloom.com/admin/manageCategory" accept-charset="UTF-8" enctype="multipart/form-data" id="formRequestId">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>
        <form method="POST" action="https://everybloom.com/admin/manageCategory" accept-charset="UTF-8" enctype="multipart/form-data" id="formRequestId2">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>
        <form method="POST" action="https://everybloom.com/admin/manageCategory" accept-charset="UTF-8" enctype="multipart/form-data" id="formRequestId3">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>
        <form method="POST" action="https://everybloom.com/admin/manageCategory" accept-charset="UTF-8" enctype="multipart/form-data" id="formRequestId4">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>
    </div>
@stop()

@section('footer')

    <script>

        var tempDirection = 'horizontal';

        $(document).ready(function () {
            var tempTr = '.' + 1;
            $('.trClass').addClass('hidden');
            $(tempTr).removeClass('hidden');
            return false;
        });

        function showOnlyPages() {
            $('#all-vertical').hide();
            $('#all-horizontal').hide();
            $('#only-tags').hide();
            $('#only-pages').show();
        }

        function showOnlyTags() {
            $('#all-vertical').hide();
            $('#all-horizontal').hide();
            $('#only-tags').show();
            $('#only-pages').hide();
        }

        function changeHorizontal() {
            $('#all-vertical').hide();
            $('#all-horizontal').show();
            $('#only-tags').hide();
            $('#only-pages').hide();
        }

        function changeVertical() {
            $('#all-vertical').show();
            $('#all-horizontal').hide();
            $('#only-tags').hide();
            $('#only-pages').hide();
        }

        function changeStatus(type, product_id, id, oldValue) {

            newId = 'post_' + type + '_' + product_id + '_' + id;

            tempNewId = '#4_' + newId;
            newValue = !oldValue;

            if( $(tempNewId).length == 0 ){
                $( "#formRequestId" ).append( "<input name='"  + newId + "' id='4_"  + newId + "' value='" + newValue + "'></input>" );
            }
            else{
                $(tempNewId).remove();
            }
        }

        function changeStatus2(type, product_id, id, oldValue) {

            newId = 'post_' + type + '_' + product_id + '_' + id;

            tempNewId = '#3_' + newId;
            newValue = !oldValue;

            if( $(tempNewId).length == 0 ){
                $( "#formRequestId2" ).append( "<input name='"  + newId + "' id='3_"  + newId + "' value='" + newValue + "'></input>" );
            }
            else{
                $(tempNewId).remove();
            }
        }

        function changeStatus3(type, product_id, id, oldValue) {

            newId = 'post_' + type + '_' + product_id + '_' + id;

            tempNewId = '#2_' + newId;
            newValue = !oldValue;

            if( $(tempNewId).length == 0 ){
                $( "#formRequestId3" ).append( "<input name='"  + newId + "' id='2_"  + newId + "' value='" + newValue + "'></input>" );
            }
            else{
                $(tempNewId).remove();
            }
        }

        function changeStatus4(type, product_id, id, oldValue) {

            newId = 'post_' + type + '_' + product_id + '_' + id;

            tempNewId = '#1_' + newId;
            newValue = !oldValue;

            if( $(tempNewId).length == 0 ){
                $( "#formRequestId4" ).append( "<input name='"  + newId + "' id='1_"  + newId + "' value='" + newValue + "'></input>" );
            }
            else{
                $(tempNewId).remove();
            }
        }

        function selectProduct(salesId) {
            var tempTr = '.' + salesId;
            $('.trClass').addClass('hidden');
            $(tempTr).removeClass('hidden');
            return false;
        }

    </script>
@stop