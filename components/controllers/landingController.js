
'use strict';

/**
 * Created by furkan on 24.02.2015.
 */

var landingModule = angular.module("landingModule",
    [
        'menuModule',
        'footerModule',
        'newsSubscriptionModule',
        'PageTagsFactoryModule',
        'angular-carousel',
        'userAccountModule',
        'pascalprecht.translate'
    ]
);

landingModule.controller("LandingCtrl", function ( $interval, $document, $http, $state, deviceDetector,$cookies, userAccount, flowerFactory, promoFactory, $stateParams, $scope, facebookhelper, $timeout, PageTagsFactory, translateHelper, analyticsHelper, $rootScope) {
        PageTagsFactory.changeAndSetVariables();
        if ($rootScope.companyOrElse == undefined) {
            $rootScope.companyOrElse = false;
        }

        $interval.cancel($rootScope.animation);

        $rootScope.canonical = 'https://bloomandfresh.com';

        $rootScope.city = {};
        $scope.selectedCity = {};
        var cityFromPlugin = '';

        $scope.cities = [
            {
                "value": "ist",
                "name": "İstanbul"
            },
            {
                "value": "Ankara",
                "name": "Ankara"
            },
            {
                "value": "İzmir",
                "name": "İzmir"
            },
            {
                "value": "Bursa",
                "name": "Bursa"
            },
            {
                "value": "Adana",
                "name": "Adana"
            },
            {
                "value": "Adıyaman",
                "name": "Adıyaman"
            },
            {
                "value": "Afyon",
                "name": "Afyon"
            },
            {
                "value": "Ağrı",
                "name": "Ağrı"
            },
            {
                "value": "Aksaray",
                "name": "Aksaray"
            },
            {
                "value": "Amasya",
                "name": "Amasya"
            },
            {
                "value": "Antalya",
                "name": "Antalya"
            },
            {
                "value": "Ardahan",
                "name": "Ardahan"
            },
            {
                "value": "Artvin",
                "name": "Artvin"
            },
            {
                "value": "Aydın",
                "name": "Aydın"
            },
            {
                "value": "Balıkesir",
                "name": "Balıkesir"
            },
            {
                "value": "Bartın",
                "name": "Bartın"
            },
            {
                "value": "Batman",
                "name": "Batman"
            },
            {
                "value": "Bayburt",
                "name": "Bayburt"
            },
            {
                "value": "Bilecik",
                "name": "Bilecik"
            },
            {
                "value": "Bingöl",
                "name": "Bingöl"
            },
            {
                "value": "Bitlis",
                "name": "Bitlis"
            },
            {
                "value": "Bolu",
                "name": "Bolu"
            },
            {
                "value": "Burdur",
                "name": "Burdur"
            },
            {
                "value": "Çanakkale",
                "name": "Çanakkale"
            },
            {
                "value": "Çankırı",
                "name": "Çankırı"
            },
            {
                "value": "Çorum",
                "name": "Çorum"
            },
            {
                "value": "Denizli",
                "name": "Denizli"
            },
            {
                "value": "Diyarbakır",
                "name": "Diyarbakır"
            },
            {
                "value": "Düzce",
                "name": "Düzce"
            },
            {
                "value": "Edirne",
                "name": "Edirne"
            },
            {
                "value": "Elazığ",
                "name": "Elazığ"
            },
            {
                "value": "Erzincan",
                "name": "Erzincan"
            },
            {
                "value": "Erzurum",
                "name": "Erzurum"
            },
            {
                "value": "Eskişehir",
                "name": "Eskişehir"
            },
            {
                "value": "Gaziantap",
                "name": "Gaziantap"
            },
            {
                "value": "Giresun",
                "name": "Giresun"
            },
            {
                "value": "Gümüşhane",
                "name": "Gümüşhane"
            },
            {
                "value": "Hakkari",
                "name": "Hakkari"
            },
            {
                "value": "Hatay",
                "name": "Hatay"
            },
            {
                "value": "Iğdır",
                "name": "Iğdır"
            },
            {
                "value": "Isparta",
                "name": "Isparta"
            },
            {
                "value": "Kahramanmaraş",
                "name": "Kahramanmaraş"
            },
            {
                "value": "Karabük",
                "name": "Karabük"
            },
            {
                "value": "Karaman",
                "name": "Karaman"
            },
            {
                "value": "Kars",
                "name": "Kars"
            },
            {
                "value": "Kastamonu",
                "name": "Kastamonu"
            },
            {
                "value": "Kayseri",
                "name": "Kayseri"
            },
            {
                "value": "Kıbrıs",
                "name": "Kıbrıs"
            },
            {
                "value": "Kilis",
                "name": "Kilis"
            },
            {
                "value": "Kırıkkale",
                "name": "Kırıkkale"
            },
            {
                "value": "Kırklareli",
                "name": "Kırklareli"
            },
            {
                "value": "Kırşehir",
                "name": "Kırşehir"
            },
            {
                "value": "Kocaeli",
                "name": "Kocaeli"
            },
            {
                "value": "Konya",
                "name": "Konya"
            },
            {
                "value": "Kütahya",
                "name": "Kütahya"
            },
            {
                "value": "Malatya",
                "name": "Malatya"
            },
            {
                "value": "Manisa",
                "name": "Manisa"
            },
            {
                "value": "Mardin",
                "name": "Mardin"
            },
            {
                "value": "Mersin",
                "name": "Mersin"
            },
            {
                "value": "Muğla",
                "name": "Muğla"
            },
            {
                "value": "Muş",
                "name": "Muş"
            },
            {
                "value": "Nevşehir",
                "name": "Nevşehir"
            },
            {
                "value": "Niğde",
                "name": "Niğde"
            },
            {
                "value": "Ordu",
                "name": "Ordu"
            },
            {
                "value": "Osmaniye",
                "name": "Osmaniye"
            },
            {
                "value": "Rize",
                "name": "Rize"
            },
            {
                "value": "Sakarya",
                "name": "Sakarya"
            },
            {
                "value": "Samsun",
                "name": "Samsun"
            },
            {
                "value": "Şanlıurfa",
                "name": "Şanlıurfa"
            },
            {
                "value": "Siirt",
                "name": "Siirt"
            },
            {
                "value": "Sinop",
                "name": "Sinop"
            },
            {
                "value": "Şırnak",
                "name": "Şırnak"
            },
            {
                "value": "Sivas",
                "name": "Sivas"
            },
            {
                "value": "Tekirdağ",
                "name": "Tekirdağ"
            },
            {
                "value": "Tokat",
                "name": "Tokat"
            },
            {
                "value": "Trabzon",
                "name": "Trabzon"
            },
            {
                "value": "Tunceli",
                "name": "Tunceli"
            },
            {
                "value": "Uşak",
                "name": "Uşak"
            },
            {
                "value": "Van",
                "name": "Van"
            },
            {
                "value": "Yalova",
                "name": "Yalova"
            },
            {
                "value": "Yozgat",
                "name": "Yozgat"
            },
            {
                "value": "Zonguldak",
                "name": "Zonguldak"
            }
        ];

        $scope.citiesRibbon = [
            {
                "value": "ist",
                "name": "İstanbul"
            },
            {
                "value": "Ankara",
                "name": "Ankara"
            },
            {
                "value": "İzmir",
                "name": "İzmir"
            },
            {
                "value": "Bursa",
                "name": "Bursa"
            },
            {
                "value": "Adana",
                "name": "Adana"
            },
            {
                "value": "Adıyaman",
                "name": "Adıyaman"
            },
            {
                "value": "Afyon",
                "name": "Afyon"
            },
            {
                "value": "Ağrı",
                "name": "Ağrı"
            },
            {
                "value": "Aksaray",
                "name": "Aksaray"
            },
            {
                "value": "Amsaya",
                "name": "Amsaya"
            },
            {
                "value": "Antalya",
                "name": "Antalya"
            },
            {
                "value": "Ardahan",
                "name": "Ardahan"
            },
            {
                "value": "Artvin",
                "name": "Artvin"
            },
            {
                "value": "Aydın",
                "name": "Aydın"
            },
            {
                "value": "Balıkesir",
                "name": "Balıkesir"
            },
            {
                "value": "Bartın",
                "name": "Bartın"
            },
            {
                "value": "Batman",
                "name": "Batman"
            },
            {
                "value": "Bayburt",
                "name": "Bayburt"
            },
            {
                "value": "Bilecik",
                "name": "Bilecik"
            },
            {
                "value": "Bingöl",
                "name": "Bingöl"
            },
            {
                "value": "Bitlis",
                "name": "Bitlis"
            },
            {
                "value": "Bolu",
                "name": "Bolu"
            },
            {
                "value": "Burdur",
                "name": "Burdur"
            },
            {
                "value": "Çanakkale",
                "name": "Çanakkale"
            },
            {
                "value": "Çankırı",
                "name": "Çankırı"
            },
            {
                "value": "Çorum",
                "name": "Çorum"
            },
            {
                "value": "Denizli",
                "name": "Denizli"
            },
            {
                "value": "Diyarbakır",
                "name": "Diyarbakır"
            },
            {
                "value": "Düzce",
                "name": "Düzce"
            },
            {
                "value": "Edirne",
                "name": "Edirne"
            },
            {
                "value": "Elazığ",
                "name": "Elazığ"
            },
            {
                "value": "Erzincan",
                "name": "Erzincan"
            },
            {
                "value": "Erzurum",
                "name": "Erzurum"
            },
            {
                "value": "Eskişehir",
                "name": "Eskişehir"
            },
            {
                "value": "Gaziantap",
                "name": "Gaziantap"
            },
            {
                "value": "Giresun",
                "name": "Giresun"
            },
            {
                "value": "Gümüşhane",
                "name": "Gümüşhane"
            },
            {
                "value": "Hakkari",
                "name": "Hakkari"
            },
            {
                "value": "Hatay",
                "name": "Hatay"
            },
            {
                "value": "Iğdır",
                "name": "Iğdır"
            },
            {
                "value": "Isparta",
                "name": "Isparta"
            },
            {
                "value": "Kahramanmaraş",
                "name": "Kahramanmaraş"
            },
            {
                "value": "Karabük",
                "name": "Karabük"
            },
            {
                "value": "Karaman",
                "name": "Karaman"
            },
            {
                "value": "Kars",
                "name": "Kars"
            },
            {
                "value": "Kastamonu",
                "name": "Kastamonu"
            },
            {
                "value": "Kayseri",
                "name": "Kayseri"
            },
            {
                "value": "Kıbrıs",
                "name": "Kıbrıs"
            },
            {
                "value": "Kilis",
                "name": "Kilis"
            },
            {
                "value": "Kırıkkale",
                "name": "Kırıkkale"
            },
            {
                "value": "Kırklareli",
                "name": "Kırklareli"
            },
            {
                "value": "Kırşehir",
                "name": "Kırşehir"
            },
            {
                "value": "Kocaeli",
                "name": "Kocaeli"
            },
            {
                "value": "Konya",
                "name": "Konya"
            },
            {
                "value": "Kütahya",
                "name": "Kütahya"
            },
            {
                "value": "Malatya",
                "name": "Malatya"
            },
            {
                "value": "Manisa",
                "name": "Manisa"
            },
            {
                "value": "Mardin",
                "name": "Mardin"
            },
            {
                "value": "Mersin",
                "name": "Mersin"
            },
            {
                "value": "Muğla",
                "name": "Muğla"
            },
            {
                "value": "Muş",
                "name": "Muş"
            },
            {
                "value": "Nevşehir",
                "name": "Nevşehir"
            },
            {
                "value": "Niğde",
                "name": "Niğde"
            },
            {
                "value": "Ordu",
                "name": "Ordu"
            },
            {
                "value": "Osmaniye",
                "name": "Osmaniye"
            },
            {
                "value": "Rize",
                "name": "Rize"
            },
            {
                "value": "Sakarya",
                "name": "Sakarya"
            },
            {
                "value": "Samsun",
                "name": "Samsun"
            },
            {
                "value": "Şanlıurfa",
                "name": "Şanlıurfa"
            },
            {
                "value": "Siirt",
                "name": "Siirt"
            },
            {
                "value": "Sinop",
                "name": "Sinop"
            },
            {
                "value": "Şırnak",
                "name": "Şırnak"
            },
            {
                "value": "Sivas",
                "name": "Sivas"
            },
            {
                "value": "Tekirdağ",
                "name": "Tekirdağ"
            },
            {
                "value": "Tokat",
                "name": "Tokat"
            },
            {
                "value": "Trabzon",
                "name": "Trabzon"
            },
            {
                "value": "Tunceli",
                "name": "Tunceli"
            },
            {
                "value": "Uşak",
                "name": "Uşak"
            },
            {
                "value": "Van",
                "name": "Van"
            },
            {
                "value": "Yalova",
                "name": "Yalova"
            },
            {
                "value": "Yozgat",
                "name": "Yozgat"
            },
            {
                "value": "Zonguldak",
                "name": "Zonguldak"
            }
        ];

        var tempSelectedCity = $cookies.getObject('selectCity');

        var veryTempCity = 'ist';
        /*if( tempSelectedCity ){
            if( tempSelectedCity.value == 'ist' ){
                tempSelectedCity = {
                    'value': 'ist',
                    'name': 'İstanbul'
                };
            }
            else{
                tempSelectedCity = {
                    'value': 'ank',
                    'name': 'Ankara'
                };
            }
            veryTempCity = tempSelectedCity.value;
        }*/

        if ( tempSelectedCity == null) {

            $cookies.putObject('selectCity', {
                'value': 'ist',
                'name': 'İstanbul'
            });
            tempSelectedCity = {
                'value': 'ist',
                'name': 'İstanbul'
            };
        }
        else{
            if(tempSelectedCity.value == 'ist'){
                tempSelectedCity = {
                    'value': 'ist',
                    'name': 'İstanbul'
                };
                $cookies.putObject('selectCity', {
                    'value': 'ist',
                    'name': 'İstanbul'
                });
            }
            else if(tempSelectedCity.value == 'ank'){
                tempSelectedCity = {
                    'value': 'Ankara',
                    'name': 'Ankara'
                };
                $cookies.putObject('selectCity', {
                    'value': 'Ankara',
                    'name': 'Ankara'
                });
            }

        }


        $rootScope.mainCitySelected = tempSelectedCity;
        $scope.selectedCity = $rootScope.mainCitySelected;

        //console.log($scope.selectedCity);

        //if( $scope.cities[0].value == $cookies.get('selectedCity') ){
        //    $rootScope.city = $scope.cities[0];
        //}
        //else{
        //    $rootScope.city = $scope.cities[1];
        //}

        //$timeout(function () {
            //$scope.flowers = flowerFactory.getFlowers();
        //}, 2000);

        $scope.tempFlowersId = [];

        $scope.flowers = flowerFactory.getFlowersWithFunctionAllCities( function(data){
            $scope.flowers = data;
            $scope.landingPromos = promoFactory.getLandingPromos();
            $scope.landingPromos.forEach(function (promo) {
                if( promo.type == 1 ){
                    $scope.flowers.forEach(function (flower) {
                        if( flower.id == promo.product_id && flower.city_id == promo.city_id  ){
                            promo.flower = flower;
                        }
                    });
                }
            });

            $scope.flowers.forEach(function(flowerTemp){

                if( flowerTemp.city_id == 1 ){
                    $scope.tempFlowersId.push(flowerTemp.id);
                }
            });

            facebookhelper.trackEvent(facebookhelper.facebookAdTypes.VIEW_CONTENT, {
                content_ids : $scope.tempFlowersId,
                content_type : 'product'
            });

        } );

        $scope.upMenu = flowerFactory.controllerForUpMenu();
        //$scope.flowers = flowerFactory.getFlowers();
        $scope.activeLanguages = translateHelper.getActiveLanguages();
        $scope.promos = promoFactory.getPromos();
        $scope.companyStatus = true;
        $timeout(function () {
            var tempMail = "";
            if (userAccount.checkUserLoggedin()) {
                tempMail = userAccount.getUserMail();
                tempMail.toLowerCase();
            }

            var tempDistrictId = "1";
            if( $rootScope.mainCitySelected.value == 'ank' ){
                tempDistrictId = "2";
            }

            analyticsHelper.sendCriteoLanding(tempMail, tempDistrictId);
        }, 2000);
        if ($stateParams.promo !== undefined) {
            for (var i in $scope.promos) {
                if ($scope.promos[i].id == $stateParams.promo) {
                    $scope.promos[i].showCurrent = true;
                    break;
                }
            }
            //$scope.promos[parseInt($stateParams.promo)].showCurrent = true;
        }

        $timeout(function () {
            if ($scope.loggedUser) {
                if ($scope.loggedUser.company_info_id)
                    flowerFactory.checkCompanyFlowers($scope.loggedUser.company_info_id, function (data) {
                            $scope.companyStatus = data;
                        }
                    );
            }
        }, 2000);
        if ($stateParams.menu !== undefined) {
            $timeout(function () {
                if ($stateParams.tab !== undefined) {
                    $scope.openMenuFromQueryParams($stateParams.menu, $stateParams.tab);
                } else {
                    $scope.openMenuFromQueryParams($stateParams.menu);
                }

            }, 1000);
        }

        analyticsHelper.sendPageView('/landing');

        //kissmetricsHelper.sendPageView('Anasayfayı');
        //facebookhelper.trackEvent(facebookhelper.facebookAdTypes.VIEW_CONTENT, {
        //    content_ids : $scope.flower.id,
        //    content_type : 'product'
        //});

        $scope.flowerStyle = function ($index) {
            return "flowerStyle" + ($index % 8);
        };

        $scope.langChanged = function () {
            $timeout(function () {
                location.reload();
            }, 100);
        };

        $scope.productAnimation = function(product_id){
            var tempId = '#animation_' + product_id;
            var continueTemp = true;
            $scope.flowers.forEach(function (product) {
                if( product_id == product.id && continueTemp && product.landingAnimation && product.landingAnimation2 ){
                    $(tempId).attr('src', product.landingAnimation );
                    continueTemp = false;
                    $rootScope.animation = $interval(
                        function(){
                            if( $(tempId).attr('src') == product.landingAnimation ){
                                $(tempId).attr('src', product.landingAnimation2 );
                            }
                            else if( $(tempId).attr('src') == product.landingAnimation2 )
                            {
                                $(tempId).attr('src', product.MainImage );
                            }
                            else{
                                $(tempId).attr('src', product.landingAnimation );
                            }
                        }
                    , 3000);
                    //$(tempId).attr('src', product.landingAnimation );
                }
                else if( product_id == product.id && continueTemp && product.landingAnimation ){
                    $(tempId).attr('src', product.landingAnimation );
                    continueTemp = false;
                    $rootScope.animation = $interval(
                        function(){
                            if( $(tempId).attr('src') == product.landingAnimation ){
                                $(tempId).attr('src', product.MainImage );
                            }
                            else{
                                $(tempId).attr('src', product.landingAnimation );
                            }
                        }
                        , 3000);
                    //$(tempId).attr('src', product.landingAnimation );
                }
            });

            //$(tempId).attr('src', tempId );
        };

        $scope.productAnimationStop = function(product_id){
            $interval.cancel($rootScope.animation);

            var tempId = '#animation_' + product_id;
            var continueTemp = true;
            $scope.flowers.forEach(function (product) {

                if( product_id == product.id  && continueTemp )
                {
                    continueTemp = false;
                    $(tempId).attr('src', product.MainImage );
                }

            });
        };

        $scope.showCompanyOrElse = function () {
            if ($rootScope.companyOrElse) {
                $rootScope.companyOrElse = false;
            }
            else {
                $rootScope.companyOrElse = true;
            }
        };

        $scope.isCurrentLang = function (lang) {
            return translateHelper.getCurrentLang() === lang;
        };

        $scope.checkCompanyUser = function () {
            if (userAccount.checkUserLoggedin()) {
                return $scope.loggedUser.company_info_id;
            }
            else
                return false;
        };

        $scope.setCity = function (city) {
            $cookies.putObject('selectCity', city);
            $rootScope.mainCitySelected = city;
            tempSelectedCity = $rootScope.mainCitySelected;
            $scope.selectedCity = $rootScope.mainCitySelected;

            //$cookies.put('selectedCity', $rootScope.chosenCampaign);
        };

        $scope.calculateEstimatedDelivery = function ( extra_days, flowerObject ){

            /*var sendDate = new Date(flowerObject.ups_send_date);

            sendDate.setDate(sendDate.getDate() + parseInt(extra_days ) )

            //sendDate = sendDate.addDa

            var tempString = sendDate.getDate() + '.' + (sendDate.getMonth() + 1) + '.' + sendDate.getFullYear();

            console.log(sendDate);
            console.log(flowerObject);

            return tempString;*/

        }

    }
);