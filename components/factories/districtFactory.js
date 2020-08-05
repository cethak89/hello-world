/**
 * Created by furkan on 13.03.2015.
 */

angular.module('app')
    .factory('districtFactory', function ($cookies, $http, otherExceptions, $rootScope, $window) {
        //factory("flowerFactory", function ($http, otherExceptions, translateHelper, $cookies) {
        var districts = [];
        var cityFromPlugin = '';

        /*$http.get( 'https://ssl.geoplugin.net/json.gp?k=f871ee13ca05340e').success(function (result) {
         cityFromPlugin = result.geoplugin_city;
         if($cookies){
         var tempSelectedCity = $cookies.getObject('selectCity');
         var tempCities = [
         {
         'value' : 'ist',
         'name' : 'Gönderim Bölgesi: İstanbul'
         },
         {
         'value' : 'ank',
         'name' : 'Gönderim Bölgesi: Ankara'
         }
         ];

         if( tempSelectedCity == null ){
         if( cityFromPlugin == 'Ankara' ){
         $cookies.putObject('selectCity', tempCities[1]);
         tempSelectedCity = $cookies.getObject('selectCity');
         }
         else{
         $cookies.putObject('selectCity', tempCities[0]);
         tempSelectedCity = $cookies.getObject('selectCity');
         }
         }

         }
         else{

         var tempCities = [
         {
         'value' : 'ist',
         'name' : 'Gönderim Bölgesi: İstanbul'
         },
         {
         'value' : 'ank',
         'name' : 'Gönderim Bölgesi: Ankara'
         }
         ];

         if( tempSelectedCity == null ){

         if( cityFromPlugin == 'Ankara' ){
         $cookies.putObject('selectCity', tempCities[1]);
         tempSelectedCity = $cookies.getObject('selectCity');
         }
         else{
         $cookies.putObject('selectCity', tempCities[0]);
         tempSelectedCity = $cookies.getObject('selectCity');
         }
         }

         }

         $http.get(webServer + '/city-list/1' + '/' + tempSelectedCity.value ).success(function (result) {
         districts = result;
         }).error(function () {
         otherExceptions.sendException("districtFactory", "Bölgeleri Çekerken Serverdan Hata Döndü");
         });

         }).error(function () {
         //otherExceptions.sendException("districtFactory", "Bölgeleri Çekerken Serverdan Hata Döndü");
         });*/

        if ($cookies) {
            var tempSelectedCity = $cookies.getObject('selectCity');
            var tempCities = [
                {
                    'value': 'ist',
                    'name': 'Gönderim Bölgesi: İstanbul'
                },
                {
                    'value': 'ank',
                    'name': 'Gönderim Bölgesi: Ankara'
                }
            ];

            if (tempSelectedCity == null) {
                if (cityFromPlugin == 'Ankara') {
                    $cookies.putObject('selectCity', tempCities[1]);
                    tempSelectedCity = $cookies.getObject('selectCity');
                }
                else {
                    $cookies.putObject('selectCity', tempCities[0]);
                    tempSelectedCity = $cookies.getObject('selectCity');
                }
            }

        }
        else {

            var tempCities = [
                {
                    'value': 'ist',
                    'name': 'Gönderim Bölgesi: İstanbul'
                },
                {
                    'value': 'ank',
                    'name': 'Gönderim Bölgesi: Ankara'
                }
            ];

            if (tempSelectedCity == null) {
                $cookies.putObject('selectCity', tempCities[0]);
                tempSelectedCity = $cookies.getObject('selectCity');

            }

        }

        /*$http.get(webServer + '/city-list/1' + '/' + tempSelectedCity.value).success(function (result) {
            districts = result;
        }).error(function () {
            otherExceptions.sendException("districtFactory", "Bölgeleri Çekerken Serverdan Hata Döndü");
        });*/

        //var tempCityName = 'ist';

        /*if ($cookies) {
            var tempSelectedCity = $cookies.getObject('selectCity');
            var tempCities = [
                {
                    'value': 'ist',
                    'name': 'Gönderim Bölgesi: İstanbul'
                },
                {
                    'value': 'ank',
                    'name': 'Gönderim Bölgesi: Ankara'
                }
            ];

            if (tempSelectedCity == null) {
                tempCityName = 'ist';
            }
            else {
                tempCityName = tempSelectedCity.value;
            }
        }*/

        var initDistincts = $http.get(webServer + '/city-list-ups/1' ).success(function (result) {
            districts = result;
        }).error(function () {
            otherExceptions.sendException("districtFactory", "Bölgeleri Çekerken Serverdan Hata Döndü");
        });

        return {
            getDistincts: function () {
                if (districts.length > 0) {
                    return districts;
                }
                else {
                    return initDistincts;
                }
            },
            getDistinctsCallBack: function (callback) {

                $http.get(webServer + '/city-list-ups/1' ).success(function (result) {
                    districts = result;
                    callback(districts);
                }).error(function () {
                    otherExceptions.sendException("districtFactory", "Bölgeleri Çekerken Serverdan Hata Döndü");
                });

                /*if( tempSelectedCity.value != $cookies.getObject('selectCity').value ){
                    tempSelectedCity = $cookies.getObject('selectCity');
                    //$window.location.reload();
                    $http.get(webServer + '/city-list/1' + '/' + $cookies.getObject('selectCity').value).success(function (result) {
                        districts = result;
                        callback(districts);
                    }).error(function () {
                        otherExceptions.sendException("districtFactory", "Bölgeleri Çekerken Serverdan Hata Döndü");
                    });
                }
                else{
                    if (districts.length > 0) {
                        callback(districts);
                    }
                    else
                    {
                        $http.get(webServer + '/city-list/1' + '/' + $cookies.getObject('selectCity').value).success(function (result) {
                            districts = result;
                            callback(districts);
                        }).error(function () {
                            otherExceptions.sendException("districtFactory", "Bölgeleri Çekerken Serverdan Hata Döndü");
                        });
                    }
                }*/
            },
            getDistinctFromId: function (districtsId) {
                if (districts.length > 0) {
                    var flower = {};
                    districts.forEach(function (element) {
                        if (districtsId === element.id) {
                            flower = element;
                        }
                    });
                    return districts;
                }
                else {
                    return initDistincts;
                }
            },
            getDistrictFromName: function (districtName) {
                if (districts.length > 0) {
                    var districtObj = {};

                    districts.forEach(function (element) {
                        if (districtName === element.district) {
                            districtObj = element;
                        }
                    });

                    return districtObj;
                }
            }
        };
    });