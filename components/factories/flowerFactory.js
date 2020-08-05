/**
 * Created by furkan on 27.02.2015.
 */
'use strict';

angular.module('app')
    .factory("flowerFactory", function ($http, otherExceptions, translateHelper, $cookies) {

        $http.defaults.headers.post["Content-Type"] = "application/json";
        var flowers = [];
        var sendingDistrict = undefined;
        var upMenu = [];
        var cityFromPlugin = '';
        var ankFlowers = [];

        var tempSelectedCity = $cookies.getObject('selectCity');

        /*var tempCities = [
            {
                'value' : 'ist',
                'name' : 'Gönderim Bölgesi: İstanbul'
            },
            {
                'value' : 'ank',
                'name' : 'Gönderim Bölgesi: Ankara'
            }
        ];
        var veryTempCity = 'ist';
        if( tempSelectedCity ){
            veryTempCity = tempSelectedCity.value;
            //$cookies.putObject('selectCity', tempCities[0]);
            //tempSelectedCity = $cookies.getObject('selectCity');
        }*/

        var initFlowers = $http.get(webServer + '/flower-list' )
            .success(function (data) {
                flowers = data;

                $http.get(webServer + '/flower-list' )
                    .success(function (data) {
                        flowers = data;
                    })
                    .error(function () {
                        otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
                    });

            })
            .error(function () {
                otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
            });

        /*$http.get(webServer + '/flower-list/1/' + translateHelper.getCurrentLang() + '/ank' )
            .success(function (data) {
                ankFlowers = data;
            })
            .error(function () {
                otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
            });*/


        if ( tempSelectedCity == null) {

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

            $cookies.putObject('selectCity', tempCities[0]);
            tempSelectedCity = $cookies.getObject('selectCity');

            /*$http.get(webServer + '/flower-list/1/' + translateHelper.getCurrentLang() + '/' + tempSelectedCity.value )
                .success(function (data) {
                    flowers = data;
                })
                .error(function () {
                    otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
                });
            */
            //$http.get('https://ssl.geoplugin.net/json.gp?k=f871ee13ca05340e').success(function (result) {
            //    cityFromPlugin = result.geoplugin_city;

            //    //var tempSelectedCity = $cookies.getObject('selectCity');
            //    var tempCities = [
            //        {
            //            'value': 'ist',
            //            'name': 'Gönderim Bölgesi: İstanbul'
            //        },
            //        {
            //            'value': 'ank',
            //            'name': 'Gönderim Bölgesi: Ankara'
            //        }
            //    ];

            //    //if (tempSelectedCity == null) {
            //    //console.log(result);
            //    if (cityFromPlugin == 'Ankara') {
            //        $cookies.putObject('selectCity', tempCities[1]);
            //        tempSelectedCity = $cookies.getObject('selectCity');
            //    }
            //    else {
            //        $cookies.putObject('selectCity', tempCities[0]);
            //        tempSelectedCity = $cookies.getObject('selectCity');
            //    }
            //    //}

            //    $http.get(webServer + '/flower-list/1/' + translateHelper.getCurrentLang() + '/' + tempSelectedCity.value )
            //        .success(function (data) {
            //            flowers = data;
            //        })
            //        .error(function () {
            //            otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
            //        });

            //}).error(function () {
            //    //otherExceptions.sendException("districtFactory", "Bölgeleri Çekerken Serverdan Hata Döndü");
            //});

            /*if (geoplugin_city() == 'Ankara') {
             $cookies.putObject('selectCity', $scope.cities[1]);
             $rootScope.mainCitySelected = $cookies.getObject('selectCity');
             }
             else {
             $cookies.putObject('selectCity', $scope.cities[0]);
             $rootScope.mainCitySelected = $cookies.getObject('selectCity');
             }*/

        }
        else{
            /*$http.get(webServer + '/flower-list/1/' + translateHelper.getCurrentLang() + '/' + tempSelectedCity.value )
                .success(function (data) {
                    flowers = data;
                })
                .error(function () {
                    otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
                });*/
        }



        var initUpMenu = $http.get(webServer + '/get-up-menu')
            .success(function (data) {
                upMenu = data.data;
            })
            .error(function () {
                otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
            });

        return {
            initFlowers: initFlowers,
            initUpMenu : initUpMenu,
            getFlowers: function () {
                if (flowers.length > 0) {
                    if(tempSelectedCity){
                        if( tempSelectedCity.value == 'ank' && ankFlowers.length > 0 ){
                            flowers = ankFlowers;
                            //console.log(ankFlowers);
                        }
                    }
                    flowers.forEach(function (element) {
                        element.urlName = removeDiacritics(element.name);
                        //console.log(tempSelectedCity.value);
                        //element.sendingDistrict = sendingDistrict;
                    });
                    //console.log(flowers);
                    return flowers;
                }
                else {
                    return initFlowers;
                }
            },
            getFlower: function (flowerId) {
                if (flowers.length > 0) {
                    var flower = {};
                    flowers.forEach(function (element) {
                        if (flowerId === element.id) {
                            element.sendingDistrict = sendingDistrict;
                            flower = element;
                        }
                    });
                    return flower;
                }
                else {
                    return initFlowers;
                }
            },
            getFlowerWithCity: function (flowerId, districtId) {
                if (flowers.length > 0) {
                    var flower = {};

                    if( districtId == 3 ){

                        flowers.forEach(function (element) {
                            if ((flowerId.id == element.id) ) {
                                element.sendingDistrict = sendingDistrict;
                                flower = element;
                            }
                        });

                    }
                    else{

                        flowers.forEach(function (element) {
                            if ((flowerId.id == element.id) && (element.city_id == districtId ) ) {
                                element.sendingDistrict = sendingDistrict;
                                flower = element;
                            }
                        });

                    }

                    return flower;
                }
                else {
                    return initFlowers;
                }
            },
            getInActiveFlower: function (flowerId, callback) {
                $http.get(webServer + '/flower-detail/1/' + flowerId + '/' + translateHelper.getCurrentLang()).success(function (data) {
                    callback(data[0]);
                })
            },
            getCategoryFlowers: function (categoryName, callback) {
                $http.get(webServer + '/flower-list-by-category/' + categoryName ).success(function (data) {
                    callback(data);
                })
            },
            getFlowersWithFunction: function (callback) {

                //var tempSelectedCity = {
                //    'value': 'ist',
                //    'name': 'Gönderim Bölgesi: İstanbul'
                //};

                //var tempSelectedCity = null;

                var tempSelectedCity = $cookies.getObject('selectCity');

                var veryTempCity = 'ist';
                if( tempSelectedCity ){
                    veryTempCity = tempSelectedCity.value;
                }

                if ( tempSelectedCity == null) {

                    $cookies.putObject('selectCity', {
                        'value': 'ist',
                        'name': 'Gönderim Bölgesi: İstanbul'
                    });
                    tempSelectedCity = {
                        'value': 'ist',
                        'name': 'Gönderim Bölgesi: İstanbul'
                    };
                }

                /*if ( tempSelectedCity == null) {

                    $http.get('https://ssl.geoplugin.net/json.gp?k=f871ee13ca05340e').success(function (result) {
                        cityFromPlugin = result.geoplugin_city;

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

                        if (cityFromPlugin == 'Ankara') {
                            $cookies.putObject('selectCity', tempCities[1]);
                            tempSelectedCity = $cookies.getObject('selectCity');
                        }
                        else {
                            $cookies.putObject('selectCity', tempCities[0]);
                            tempSelectedCity = $cookies.getObject('selectCity');
                        }

                        $http.get(webServer + '/flower-list/1/' + translateHelper.getCurrentLang() + '/' + tempSelectedCity.value )
                            .success(function (data) {
                                callback(data);
                            })
                            .error(function () {
                                otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
                            });

                    }).error(function () {
                    });

                }*/
                //else{
                    $http.get(webServer + '/flower-list/1/' + translateHelper.getCurrentLang() + '/' + tempSelectedCity.value )
                        .success(function (data) {
                            callback(data);
                        })
                        .error(function () {
                            otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
                        });
                //}

            },
            getFlowersWithFunctionAllCities: function (callback) {

                $http.get(webServer + '/landingTimesFlowers' )
                    .success(function (data) {
                        callback(data);
                    })
                    .error(function () {
                        otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
                    });

            },
            getFlowersWithFunctionAllCitiesDetail: function (callback) {

                $http.get(webServer + '/flower-list' )
                    .success(function (data) {
                        callback(data);
                    })
                    .error(function () {
                        otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
                    });

            },
            getInRelatedFlower: function (flowerId, cityId, callback) {
                $http.get(webServer + '/get-related-products/' + flowerId + '/' + cityId ).success(function (data) {
                    callback(data.data);
                })
            },
            getProductSoonTime: function (flowerId, callback) {
                $http.get(webServer + '/get-product-soon-times/' + flowerId ).success(function (data) {
                    callback(data.data);
                })
            },
            getUpsTime: function ( callback) {
                $http.get(webServer + '/upsDeliveryTime' ).success(function (data) {
                    callback(data.data);
                })
            },
            getProductSoonTimeWithLocation: function (flowerId, location, callback) {
                $http.get(webServer + '/get-product-soon-times/' + flowerId + '/' + location ).success(function (data) {
                    callback(data.data);
                })
            },
            setSendingDistrict: function (districtObj) {
                sendingDistrict = districtObj;
            },
            controllerForUpMenu : function () {
                return upMenu;
            },
            checkCompanyFlowers : function (companyId, callback){
                $http.get( webServer + '/getCompanyFlowersStatus/' + companyId ).success(function (data) {
                    callback(data.data);
                })
            }
        };
    });