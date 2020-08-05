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

    <table class="table table-hover">
    <tr>
        <td>
            <h1>Bloom & Fresh Newsletter Listesi</h1>
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

    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body">
                    <table  style="width: 100%; font-size: 14px;" id="example1" data-width="100%" data-compression="6" data-min="1" data-max="14" cellpadding="0" cellspacing="0" class="table table-bordered table-striped responsive responsiveTable">
                        <thead>
                            <tr>
                                <th>Oluşturulma Tarihi</th>
                                <th>Mail</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($reminderList as $reminder)
                            <tr>
                                <td style="padding-left:21px;">{{$reminder->created_at}}</td>
                                <td style="padding-left:21px;">{{$reminder->email}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Oluşturulma Tarihi</th>
                                <th>Mail</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop()

@section('footer')
<script>
        function setOrderParameter(paremeter , upOrDown){
            $('input[name=orderParameter]').val(paremeter);
            $('input[name=upOrDown]').val(upOrDown);
            console.log( $('input[name=orderParameter]').val());
        }
</script>
@stop