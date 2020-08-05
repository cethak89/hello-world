@extends('newApp')

@section('html-head')
    <style>
        .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
            vertical-align: middle;
        }

        div.form-group {
            height: 20px;
        }

        p {
            margin-bottom: 0px !important;
        }
    </style>
@stop

@section('content')
    <table class="table table-hover">
        <tr>
            <td>
                <h1 style="text-align: center;">Atölye Çiçek Hazır Yap</h1>
            </td>
        </tr>
    </table>
    <div class="col-lg-12">
        @foreach( $tempFLowerList as $flower )
            <div onclick="makeReady( '{{$flower->id}}', '{{$flower->name}}')" class="col-lg-1" style="cursor: pointer;border-right-style: solid;border-right-width: 1px;border-right-color: #222d32;">
                <img width="100%" src="{{$flower->image_url}}">
                <p style="text-align: center;width: 100%;text-align: center;font-weight: 700;margin-bottom: 0px;">{{$flower->name}}</p>
                <p style="text-align: center;width: 100%;font-weight: 800;">Total : {{$flower->totalFlower}}</p>
            </div>
        @endforeach
    </div>

@stop()

@section('footer')
    <script>
        $(".select2").select2();
        function makeReady(id, name) {
            if (confirm( name + " çiçeğini değiştirmek istediğinize emin misiniz?"  ) == true) {
                $.ajax({
                    url: '/admin/makeFlowerReady',
                    method: "POST",
                    data: {
                        id : id
                    },
                    success: function(data) {
                        location.reload();
                    }
                });
            } else {
                return false;
            }
        }
    </script>
@stop