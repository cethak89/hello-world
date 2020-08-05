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
                <h1>Bloom & Fresh Google Maps Siparis Listesi</h1>
            </td>
            <td>
                <button class="btn btn-primary form-control" onclick="$('#filterTable').toggle();">Sorgu Alanlari</button>
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
    <table id="filterTable" class="table table-hover"  style="display: none">
        {!! Form::model($queryParams, ['url' => '/admin/googleMapDeliveries/filter', 'files'=>true, 'accept' => 'image/jpeg,image/gif,image/png,application/pdf,image/x-eps'  , 'method' => 'post']) !!}
            <tr>
                <td style="padding-left:21px;">Gönderim Saati</td><td style="padding-left:21px;">
                <div class="form-group">
                <select name="deliveryHour" class="btn btn-default dropdown-toggle">
                    @foreach($deliveryHourList as $tag)
                        <option value="{{$tag->status}}"
                         @if($tag->status == $queryParams->deliveryHour)
                         selected
                         @else
                         @endif
                         >{{$tag->information}}</option>
                    @endforeach
                </select>
                </div>
                </td>
                <td>
                </td>
            </tr>
            <tr>
                <td style="padding-left:21px;">Gönderim Bölgesi</td><td style="padding-left:21px;">
                <div class="form-group">
                <select name="continent_id" class="btn btn-default dropdown-toggle">
                    @foreach($continentList as $tag)
                        <option value="{{$tag->status}}"
                         @if($tag->status == $queryParams->continent_id)
                         selected
                         @else
                         @endif
                         >{{$tag->information}}</option>
                    @endforeach
                </select>
                </div>
                </td>
                <td>
                </td>
            </tr>
            <tr>
                <td>
                    {!! Form::submit('Sorgula', ['class' => 'btn btn-success form-control' , 'id' => 'submitId']) !!}
                </td>
                <td>
                    {!! Html::link('/googleMapDeliveries' , 'Temizle', ['class' => 'btn btn-primary form-control']) !!}
                </td>
                <td></td>
            </tr>
        {!! Form::close() !!}
    <button id="testXls"class="btn btn-danger"  onClick ="$('#products-table').table2excel({exclude: '.excludeThisClass',name: 'Mesajlar',filename: 'Mesajlar'});">Excel Çıktısı İçin Tıklayınız</button>
    <table id="products-table" class="table table-hover table-bordered" style="vertical-align: middle;">
        <tr>
            <th></th>
            <th>ID</th>
            <th>Bölge</th>
            <th>Girilen Adres</th>
            <!--
            <th>Otomatik Koordinat</th>
            <th>Google Map</th>
            <th>Manuel Koordinat</th>-->
            <th>Dağıtım Saati</th>
            <th style="min-width: 500px"></th>
        </tr>
        @foreach($tempLocations as $location)
                <tr>
                    <td>
                        <input style="width: 34px;height: 34px;" name="check_{{$location->id}}" type="checkbox">
                    </td>
                    <td style="padding-left:21px;">{{$location->id}}</td>
                    <td style="padding-left:21px;">{{$location->district}}</td>
                    <td style="padding-left:21px;">{{$location->receiver_address}}</td>
                    <!--<td style="padding-left:21px;">{{$location->lat}} , {{$location->long}}</td>
                    <td style="padding-left:21px;">{{$location->mapsAddress}}</td>
                    <td style="padding-left:21px;">{{$location->geoLocation}}</td>-->
                    <td>{{explode(':',explode(' ', $location->wanted_delivery_date)[1])[0]}} - {{explode(':',explode(' ', $location->wanted_delivery_limit)[1])[0]}}</td>
                    <td style="padding-left:21px;min-width: 500px">
                        <input value="{{$location->geoSearchName}}" id="pac-input-{{$location->id}}"  style="min-width: 500px" class="controls" type="text" placeholder="Lokasyon Ara">
                        <label>{{$location->geoAddress}}</label>
                    </td>
                </tr>
        @endforeach
    </table>
    <a href="/googleMapDeliveries" class="btn btn-success form-control">Yenile</a>
@stop()

@section('footer')
<script type="text/javascript">



    $(document).ready(function() {
      $(window).keydown(function(event){
        if(event.keyCode == 13) {
          event.preventDefault();
          return false;
        }
      });
    });

        var langs = {!! json_encode($tempLocations) !!};
            console.log(langs);
      function initAutocomplete() {
        //var map = new google.maps.Map(document.getElementById('map'), {
        //  center: {lat: -33.8688, lng: 151.2195},
        //  zoom: 13,
        //  mapTypeId: google.maps.MapTypeId.ROADMAP
        //});

        langs.forEach(function logArrayElements(element, index, array) {
            var input = document.getElementById('pac-input-' + element.id);
                    var searchBox = new google.maps.places.SearchBox(input);
                    //map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
                    // Bias the SearchBox results towards current map's viewport.
                    //map.addListener('bounds_changed', function() {
                    //  searchBox.setBounds(map.getBounds());
                    //});

                    var markers = [];
                    // Listen for the event fired when the user selects a prediction and retrieve
                    // more details for that place.
                    searchBox.addListener('places_changed', function() {
                      var places = searchBox.getPlaces();

                      if (places.length == 0) {
                        return;
                      }

                      // Clear out the old markers.
                      markers.forEach(function(marker) {
                        marker.setMap(null);
                      });
                      markers = [];

                      // For each place, get the icon, name and location.
                      var bounds = new google.maps.LatLngBounds();
                      places.forEach(function(place) {
                        var icon = {
                          url: place.icon,
                          size: new google.maps.Size(71, 71),
                          origin: new google.maps.Point(0, 0),
                          anchor: new google.maps.Point(17, 34),
                          scaledSize: new google.maps.Size(25, 25)
                        };
                        console.log(place);
                        //console.log(place.formatted_address);

                        $.ajax({
                            url: '/updateGoogleLocation',
                            method: "POST",
                            data: {
                                id : element.id ,
                                x : place.geometry.location.lat(),
                                y : place.geometry.location.lng(),
                                geoAddress : place.formatted_address,
                                geoSearchName : place.name

                            },
                            success: function(data) {
                                console.log('we did it!');
                            }
                        });

                        //console.log(google.maps);

                        // Create a marker for each place.
                        //markers.push(new google.maps.Marker({
                        //  map: map,
                        //  icon: icon,
                        //  title: place.name,
                        //  position: place.geometry.location
                        //}));

                        if (place.geometry.viewport) {
                          // Only geocodes have viewport.
                          bounds.union(place.geometry.viewport);
                        } else {
                          bounds.extend(place.geometry.location);
                        }
                      });
                      //map.fitBounds(bounds);
                    });
        });

        // Create the search box and link it to the UI element.

      }

    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCAfPHfNDfmVzqQ7RPkN2jTrWJiVn6hcOI&libraries=places&callback=initAutocomplete&language=tr&region=TR"
                                      async defer></script>
@stop