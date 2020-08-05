/**
 * Created by furkan on 27.02.2015.
 */

'use strict';

var flowerModule = angular.module("flowerDescription",
    [
        'angular-carousel',
        'menuModule',
        'footerModule',
        'userAccountModule',
        'ui.select',
        'ngSanitize',
        'PageTagsFactoryModule',
        '720kb.socialshare',
        'pascalprecht.translate',
        'userAccountModule'
    ]);
flowerModule.controller("FlowerDetailController", function ($interval, $http ,flowerFactory, districtFactory,errorMessages,facebookhelper,reminderHelper,purchaseFlowerModel,productSubscriptionHelper,purchaseModel,userAccount,$location,$cookies, $scope,$rootScope,$translate, $state, $stateParams, analyticsHelper,$timeout , $modal, PageTagsFactory,otherExceptions, $sce) {

    $scope.trustSrc = function(src) {
        return $sce.trustAsResourceUrl(src);
    };

    //console.log($rootScope.districtFromDetail);

    $interval.cancel($rootScope.animation);

    $scope.cityName = '';

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

    $rootScope.mainCitySelected = $cookies.getObject('selectCity');

    /*if( $rootScope.mainCitySelected ){
        if( $rootScope.mainCitySelected.value == 'ist' ){
            $rootScope.mainCitySelected = {
                'value': 'ist',
                'name': 'İstanbul'
            };
        }
        else{
            $rootScope.mainCitySelected = {
                'value': 'ank',
                'name': 'Ankara'
            };
        }
    }*/

    $scope.selectedCity = $rootScope.mainCitySelected;

    if( $rootScope.mainCitySelected == null ){

        $cookies.putObject('selectCity', $scope.cities[0]);
        $rootScope.mainCitySelected = $cookies.getObject('selectCity');
        $scope.cityName = $rootScope.mainCitySelected.name;
    }
    else{
        if($rootScope.mainCitySelected.value == 'ist'){
            $rootScope.mainCitySelected = {
                'value': 'ist',
                'name': 'İstanbul'
            };
            $cookies.putObject('selectCity', {
                'value': 'ist',
                'name': 'İstanbul'
            });
            $scope.cityName = $rootScope.mainCitySelected.name;
        }
        else if($rootScope.mainCitySelected.value == 'ank'){
            $rootScope.mainCitySelected = {
                'value': 'Ankara',
                'name': 'Ankara'
            };
            $cookies.putObject('selectCity', {
                'value': 'Ankara',
                'name': 'Ankara'
            });
            $scope.cityName = $rootScope.mainCitySelected.name;
        }

    }

    $scope.cityName = $rootScope.mainCitySelected.name;

    $scope.upsDeliveryTime = 3;

    $scope.isChecked = false;

    var tempDistrictId = "1";
    var tempNameCity = "1";
    /*if( $rootScope.mainCitySelected.value == 'ank' ){
        tempDistrictId = "2";
        tempNameCity = 'ANKARA';
    }*/

    if( $rootScope.mainCitySelected.value == 'ist' ){
        tempNameCity = 'İSTANBUL';
    }
    else{
        tempNameCity = $rootScope.mainCitySelected.value.toUpperCase();
    }

    $scope.upsDeliveryTimes = [];

    flowerFactory.getUpsTime( function(data){
        $scope.upsDeliveryTimes = data;

        data.forEach(function(upsCity){
            if( upsCity.value == $rootScope.mainCitySelected.value ){
                $scope.upsDeliveryTime = upsCity.delivery_days;
            }
        });
    });

    $scope.flower = flowerFactory.getFlowerWithCity($stateParams, tempDistrictId );
    if($scope.flower){
        if( $scope.flower.sendingDistrict){
            if( $scope.flower.city_id != $scope.flower.sendingDistrict.city_id ){
                $scope.flower.sendingDistrict = undefined;
            }
        }
    }

    $scope.href = $location.absUrl();
    $scope.subscribe = {};

    districtFactory.getDistinctsCallBack(function(data){
        $scope.districts = data;
        console.log($scope.districts);
        $scope.districts = $scope.districts.filter(function (el) {

            return ( ( el.city.toUpperCase() == tempNameCity && ( el.city_id == 2 || el.city_id == 3 ) ) || ( el.city_id == 1 && $rootScope.mainCitySelected.value == 'ist'  ) );
        });
    });
    if($scope.flower.id === undefined) {  // if someone come to the flower Page directly, fetch flower info from server.
        flowerFactory.getInActiveFlower($stateParams.id, function(data){

            $scope.flower = data;
            if($scope.flower){
                if( $scope.flower.sendingDistrict){
                    if( $scope.flower.city_id != $scope.flower.sendingDistrict.city_id ){
                        $scope.flower.sendingDistrict = undefined;
                    }
                }

                if( $scope.flower ){

                    if( $scope.flower.product_type == 2 ){
                        $scope.flower.priceWithKDV =  (parseFloat(replaceString($scope.flower.price, ',', '.'))*1.01).toFixed(2);
                    }
                    else if( $scope.flower.product_type == 3 ){
                        $scope.flower.priceWithKDV =  (parseFloat(replaceString($scope.flower.price, ',', '.'))*1.18).toFixed(2);
                    }
                    else{
                        $scope.flower.priceWithKDV = (parseFloat(replaceString($scope.flower.price, ',', '.'))*1.08).toFixed(2);
                    }
                }


                //if($scope.flower){
                    $rootScope.canonical = 'https://bloomandfresh.com/' + $scope.flower.tag_main + '/' +  $scope.flower.url_parametre + '-' + $scope.flower.id;
                //}
            }
            setPageTags();
            setAnalytics();
            $scope.tags = $scope.flower.tags;
            $scope.isActive = false;
            if($scope.tags){
                $scope.tags.forEach(function(tag){
                    if($scope.flower.tag_id == tag.id){
                        $scope.main_tag_name = tag.tags_name;
                        $scope.main_tag_url = $stateParams.flowerCategory;
                    }
                });
            }
        })
    }
    else{
        setPageTags();
        setAnalytics();
        $scope.tags = $scope.flower.tags;
        $scope.isActive = true;
    }
    flowerFactory.getInRelatedFlower($stateParams.id, tempDistrictId, function(data){
        $scope.relatedFlowers = data;
    });
    flowerFactory.getProductSoonTimeWithLocation($stateParams.id, tempDistrictId, function(data){
        $scope.productSoonTime = data;

        if( $rootScope.mainCitySelected.value == 'ist' ){

            if($scope.flower.today){
                $scope.soonTime = 'En erken: Bugün'
            }
            else if($scope.flower.tomorrow){
                $scope.soonTime = 'En erken: Yarın'
            }
            else{
                $scope.soonTime = 'En erken: ' + $scope.flower.theDayAfter;
            }
        }
        else{

            $scope.productSoonTime.forEach(function(soonTime){
                if( soonTime.continent_id == 'Ups' ){

                    if(soonTime.now && $scope.flower.today){
                        $scope.soonTime = 'En erken: Bugün'
                    }
                    else if(soonTime.tomorrow && ( $scope.flower.tomorrow || $scope.flower.today ) ){
                        $scope.soonTime = 'En erken: Yarın'
                    }
                    else{
                        $scope.soonTime = 'En erken: ' + $scope.flower.theDayAfter;
                    }

                    //$scope.upsDendDate = soonTime.ups_send_date;

                }
            });

        }


    });
    $rootScope.$on('$stateChangeSuccess', function (event) {
        if($state.current.name !== 'flowerDescription'){
            analyticsHelper.setpageViewSended(false);
        }
    });

    $scope.$watch('$root.mainCitySelected.value', function() {

        if( $rootScope.mainCitySelected.value == 'ist' ){

            if($scope.flower.today){
                $scope.soonTime = 'En erken: Bugün'
            }
            else if($scope.flower.tomorrow){
                $scope.soonTime = 'En erken: Yarın'
            }
            else{
                $scope.soonTime = 'En erken: ' + $scope.flower.theDayAfter;
            }
        }
        else{

            if($scope.productSoonTime){
                $scope.productSoonTime.forEach(function(soonTime){
                    if( soonTime.continent_id == 'Ups' ){

                        if(soonTime.now && $scope.flower.today){
                            $scope.soonTime = 'En erken: Bugün'
                        }
                        else if(soonTime.tomorrow && ( $scope.flower.tomorrow || $scope.flower.today ) ){
                            $scope.soonTime = 'En erken: Yarın'
                        }
                        else{
                            $scope.soonTime = 'En erken: ' + $scope.flower.theDayAfter;
                        }

                        //$scope.upsDendDate = soonTime.ups_send_date;

                    }
                });
            }

        }

        $scope.upsDeliveryTimes.forEach(function(upsCity){
            if( upsCity.value == $rootScope.mainCitySelected.value ){
                $scope.upsDeliveryTime = upsCity.delivery_days;
            }
        });

    });



    if($scope.tags){
        $scope.tags.forEach(function(tag){
            if($scope.flower.tag_id == tag.id){
                $scope.main_tag_name = tag.tags_name;
                $scope.main_tag_url = $stateParams.flowerCategory;
            }
        });
    }

    if($scope.flower){
        $rootScope.canonical = 'https://bloomandfresh.com/' + $scope.flower.tag_main + '/' +  $scope.flower.url_parametre + '-' + $scope.flower.id;
    }

    if( $scope.flower ){

        if( $scope.flower.product_type == 2 ){
            $scope.flower.priceWithKDV =  (parseFloat(replaceString($scope.flower.price, ',', '.'))*1.01).toFixed(2);
        }
        else if( $scope.flower.product_type == 3 ){
            $scope.flower.priceWithKDV =  (parseFloat(replaceString($scope.flower.price, ',', '.'))*1.18).toFixed(2);
        }
        else{
            $scope.flower.priceWithKDV = (parseFloat(replaceString($scope.flower.price, ',', '.'))*1.08).toFixed(2);
        }
    }


    $scope.locationChanged = function () {

        var tempDistrict = $scope.flower.sendingDistrict;

        $scope.productSoonTime.forEach(function(soonTime){
            if($scope.flower.sendingDistrict.continent_id == soonTime.continent_id){
                if(soonTime.now && $scope.flower.today){
                    $scope.soonTime = 'En erken: Bugün'
                }
                else if(soonTime.tomorrow && ( $scope.flower.tomorrow || $scope.flower.today ) ){
                    $scope.soonTime = 'En erken: Yarın'
                }
                else{
                    $scope.soonTime = 'En erken: ' + $scope.flower.theDayAfter;
                }
            }

            //if( soonTime.continent_id == 'Ups' ){
            //    $scope.upsDendDate = soonTime.ups_send_date;
            //}

        });

        //setCity

        if( $scope.flower.sendingDistrict.city_id == 1 ){
            var tempCity = {
                'value': 'ist',
                'name': 'İstanbul'
            };
        }
        /*else if( $scope.flower.sendingDistrict.city_id == 2 ) {
            var tempCity = {
                'value': 'Ankara',
                'name': 'Ankara'
            };
        }*/
        else{
            var tempCity = {
                'value': $scope.flower.sendingDistrict.city,
                'name': $scope.flower.sendingDistrict.city
            };
        }



        $cookies.putObject('selectCity', tempCity);
        $rootScope.mainCitySelected = tempCity;
        $scope.selectedCity = $rootScope.mainCitySelected;

        /*districtFactory.getDistinctsCallBack(function(data){
            $scope.districts = data;
        });*/

        $scope.cityName = $rootScope.mainCitySelected.name;

        var tempDistrictId = $scope.flower.sendingDistrict.city_id;
        /*if( $scope.selectedCity.value == 'ank' ){
            tempDistrictId = "2";
        }
        else if( $scope.selectedCity.value != 'ist' ){
            tempDistrictId = "3";
        }*/

        //$scope.flower = flowerFactory.getFlowerWithCity($stateParams, tempDistrictId );


        var tempFlowerWS = flowerFactory.getFlowerWithCity($stateParams, tempDistrictId );

        if(tempFlowerWS.id === undefined) {  // if someone come to the flower Page directly, fetch flower info from server.
            flowerFactory.getInActiveFlower($stateParams.id, function(data){

                $scope.flower = data;
                if($scope.flower){
                    if( $scope.flower.sendingDistrict){
                        if( $scope.flower.city_id != $scope.flower.sendingDistrict.city_id ){
                            $scope.flower.sendingDistrict = undefined;
                        }
                    }
                }
                setPageTags();
                setAnalytics();
                $scope.tags = $scope.flower.tags;
                $scope.isActive = false;
                if($scope.tags){
                    $scope.tags.forEach(function(tag){
                        if($scope.flower.tag_id == tag.id){
                            $scope.main_tag_name = tag.tags_name;
                            $scope.main_tag_url = $stateParams.flowerCategory;
                        }
                    });
                }
            })
        }
        else{
            $scope.flower = tempFlowerWS;
            setPageTags();
            setAnalytics();
            $scope.tags = $scope.flower.tags;
            $scope.isActive = true;
            if($scope.tags){
                $scope.tags.forEach(function(tag){
                    if($scope.flower.tag_id == tag.id){
                        $scope.main_tag_name = tag.tags_name;
                        $scope.main_tag_url = $stateParams.flowerCategory;
                    }
                });
            }
        }



        if($scope.flower){

            if( tempDistrictId == 3 || tempDistrictId == 2  ){
                if( $scope.flower.cargo_sendable == 0  ){
                    $scope.flower.sendingDistrict = undefined;
                    $scope.isActive = false;
                }
            }

            if( $scope.flower.sendingDistrict){

                if( tempDistrictId == 3  || tempDistrictId == 2 ){
                    if( $scope.flower.cargo_sendable == 0  ){
                        $scope.flower.sendingDistrict = undefined;
                        $scope.isActive = false;
                    }
                }
                else if( $scope.flower.city_id != $scope.flower.sendingDistrict.city_id ){
                    $scope.flower.sendingDistrict = undefined;
                }
            }
        }

        $scope.flower.sendingDistrict = tempDistrict;

        /*if($scope.flower.today){
            $scope.soonTime = 'En erken: Bugün'
        }
        else if($scope.flower.tomorrow){
            $scope.soonTime = 'En erken: Yarın'
        }
        else{
            $scope.soonTime = 'En erken: ' + $scope.flower.theDayAfter;
        }*/

        flowerFactory.getInRelatedFlower($stateParams.id, tempDistrictId, function(data){
            $scope.relatedFlowers = data;
        });

    };

    if( $rootScope.districtFromDetail ){

        setTimeout(function () {
            $scope.flower.sendingDistrict = $rootScope.districtFromDetail;
            $scope.locationChanged();
        }, 3000);

    }

    function setAnalytics(){
        if(!analyticsHelper.isPageViewSended()){
            if($scope.flower.tags){
                $scope.flower.tags.forEach(function(tag){
                    if($scope.flower.tag_id == tag.id){
                        $scope.main_tag_name = tag.tags_name;
                    }
                });
            }
            analyticsHelper.addImpression($scope.flower.id,$scope.flower.name, $scope.main_tag_name);
            analyticsHelper.sendPageView('/flower-detail/' + $scope.flower.name); // send analytics page viewed event
            analyticsHelper.setpageViewSended(true);    // for prevending unnecessary events

            //sessioncamHelper.pageChanged($scope.flower.name + "-" + $scope.flower.id, 'cicek-detay'); // set page name for session cam

            //kissmetricsHelper.productViewed($scope.flower.id,$scope.flower.name,$scope.flower.price); // send product viewed event to kissmetrics

            facebookhelper.trackEvent(facebookhelper.facebookAdTypes.VIEW_CONTENT, {
                content_name : $scope.flower.name,
                content_ids : $scope.flower.id,
                content_type : 'product'
            });
        }
    }

    function setPageTags(){

        if( !$scope.flower ){
            $state.go('landing');
        }
        else{
            PageTagsFactory.setTags($scope.flower);
            PageTagsFactory.changeWebSiteVariable();
        }

    }

    $scope.getTagTooltip = function(tagObj){
        return "<h4 class='tooltipHeader'>" + tagObj.tags_name+ "</h4>" +
            "<p class='tooltipContext'>" + tagObj.tag_header + "</p>";
    };

    $scope.getCategory = function(){
        $scope.tags.forEach(function(tag){
            if($scope.flower.tag_id == tag.id){
                $scope.main_tag_name = tag.tags_name;
            }
        });
    };

    $scope.shareEvent = function(sharePlace){
        var string = $scope.flower.name + ", " + sharePlace + "'da paylaşıldı";

        //kissmetricsHelper.recordEvent(string);
        analyticsHelper.sendEvent("share", string);
    };

    function fixPrice(price) {
        var ret = replaceString(price, ",", ".");
        return parseFloat(ret);
    }

    $scope.setCity = function (city) {

        $cookies.putObject('selectCity', city);
        $rootScope.mainCitySelected = city;
        $scope.selectedCity = $rootScope.mainCitySelected;

        districtFactory.getDistinctsCallBack(function(data){
            $scope.districts = data;
            $scope.districts = $scope.districts.filter(function (el) {

                return ( ( el.city.toUpperCase() == tempNameCity && ( el.city_id == 2 || el.city_id == 3 ) ) || ( el.city_id == 1 && $rootScope.mainCitySelected.value == 'ist'  ) );
            });
        });

        $scope.cityName = $rootScope.mainCitySelected.name;

        var tempDistrictId = "1";
        //if( $scope.selectedCity.value == 'ank' ){
        //    tempDistrictId = "2";
        //}

        //$scope.flower = flowerFactory.getFlowerWithCity($stateParams, tempDistrictId );

        var tempFlowerWS = flowerFactory.getFlowerWithCity($stateParams, tempDistrictId );

        if(tempFlowerWS.id === undefined) {  // if someone come to the flower Page directly, fetch flower info from server.
            flowerFactory.getInActiveFlower($stateParams.id, function(data){

                $scope.flower = data;
                if($scope.flower){
                    if( $scope.flower.sendingDistrict){
                        if( $scope.flower.city_id != $scope.flower.sendingDistrict.city_id ){
                            $scope.flower.sendingDistrict = undefined;
                        }
                    }
                }
                setPageTags();
                setAnalytics();
                $scope.tags = $scope.flower.tags;
                $scope.isActive = false;
                if($scope.tags){
                    $scope.tags.forEach(function(tag){
                        if($scope.flower.tag_id == tag.id){
                            $scope.main_tag_name = tag.tags_name;
                            $scope.main_tag_url = $stateParams.flowerCategory;
                        }
                    });
                }
            })
        }
        else{
            $scope.flower = tempFlowerWS;
            setPageTags();
            setAnalytics();
            $scope.tags = $scope.flower.tags;
            $scope.isActive = true;

            if($scope.tags){
                $scope.tags.forEach(function(tag){
                    if($scope.flower.tag_id == tag.id){
                        $scope.main_tag_name = tag.tags_name;
                        $scope.main_tag_url = $stateParams.flowerCategory;
                    }
                });
            }
        }

        if($scope.flower){
            if( $scope.flower.sendingDistrict){
                if( $scope.flower.city_id != $scope.flower.sendingDistrict.city_id ){
                    $scope.flower.sendingDistrict = undefined;
                }
            }
        }

        if($scope.flower.today){
            $scope.soonTime = 'En erken: Bugün'
        }
        else if($scope.flower.tomorrow){
            $scope.soonTime = 'En erken: Yarın'
        }
        else{
            $scope.soonTime = 'En erken: ' + $scope.flower.theDayAfter;
        }

        flowerFactory.getInRelatedFlower($stateParams.id, tempDistrictId, function(data){
            $scope.relatedFlowers = data;
        });


        //location.reload();

        //$cookies.put('selectedCity', $rootScope.chosenCampaign);
    };

    /* This function check whether district is selected or not. */
    /* After that the flower informations will be saved to "purchaseFlowerModel" and user will be passed to checkout pages  */
    $scope.send = function () {
        if($scope.flower.company_product > 0){
            if(userAccount.checkUserLoggedin()){
                if($scope.loggedUser.company_info_id == $scope.flower.company_product){

                }
                else{
                    $state.go('landing');
                }
            }
            else{
                $state.go('landing');
            }
        }
        $rootScope.speciality = $scope.flower.speciality;
        if ($scope.flower.sendingDistrict !== undefined && $scope.flower.sendingDistrict !== null) {
            if($scope.flower.speciality == 1){
                if (userAccount.checkUserLoggedin()){
                    var data = {
                        access_token : $scope.loggedUser.access_token,
                        flower_id : $scope.flower.id
                    };

                    var data2Temp = {
                        access_token : $scope.loggedUser.access_token,
                        flower_id : $scope.flower.id,
                        coupon_id : '0'
                    };
                    var tempDistrict = $scope.flower.sendingDistrict;
                    var tempScopeFlower = $scope.flower;
                    $http.post(webServer + "/user-check-burberry-coupon", data)
                    .success(function (response) {
                        flowerFactory.setSendingDistrict($scope.flower.sendingDistrict);
                        $scope.isChecked = false;
                        purchaseFlowerModel.setFlower($scope.flower);

                        //kissmetricsHelper.productAdded($scope.flower.id, $scope.flower.name, $scope.flower.price);

                        if (userAccount.checkUserLoggedin()) { // if user is login, go directly to checkout pages
                            $translate('CHECKOUT_URL').then(function (checkout_url) {
                                analyticsHelper.addProduct($scope.flower.id, $scope.flower.name, fixPrice($scope.flower.price), $scope.main_tag_name);
                                $state.go('purchaseProcess', {baseUrl: checkout_url});
                            });
                        }
                    })
                    .error(function (response) {
                        $modal.open({
                            templateUrl: '../../views/bf-utility-pages/burberyInfoPopup.html',
                            size: 'sm',
                            controller: function ($scope, $modalInstance , flowerFactory ,purchaseFlowerModel, userAccount,$translate) {
                                $scope.closeModel = function () {
                                    $modalInstance.close();
                                };
                                $scope.sendModel = function () {
                                    if($('#campaignId').val() == '' || $('#campaignId').val() == null ){
                                        $('#CouponError').removeClass('hidden');
                                        $('#CouponError').text('Kod Girmedin');
                                        $('#CouponText').addClass('hidden');
                                        return false;
                                    }
                                    else{
                                        data2Temp.coupon_id = $('#campaignId').val();
                                        $http.post(webServer + "/user-add-burberry-coupon", data2Temp)
                                            .success(function (response) {
                                                flowerFactory.setSendingDistrict(tempDistrict);
                                                $scope.isChecked = false;
                                                purchaseFlowerModel.setFlower(tempScopeFlower);

                                                //kissmetricsHelper.productAdded(tempScopeFlower.id, tempScopeFlower.name, tempScopeFlower.price);
                                                $modalInstance.close();
                                                if (userAccount.checkUserLoggedin()) { // if user is login, go directly to checkout pages
                                                    $translate('CHECKOUT_URL').then(function (checkout_url) {
                                                        analyticsHelper.addProduct($scope.flower.id, $scope.flower.name, fixPrice($scope.flower.price), $scope.main_tag_name);
                                                        $state.go('purchaseProcess', {baseUrl: checkout_url});
                                                    });
                                                }
                                            })
                                            .error(function (response) {
                                                $('#CouponError').removeClass('hidden');
                                                $('#CouponError').text('Hatalı Kod!');
                                                $('#CouponText').addClass('hidden');
                                            });
                                    }
                                };
                            }
                        });

                        return false;
                    });
                }
                else{
                    $modal.open({
                        templateUrl: '../../views/bf-utility-pages/bvlgariMustLoginPopup.html',
                        size: 'sm',
                        controller: function ($scope, $modalInstance , flowerFactory ,purchaseFlowerModel, userAccount,$translate) {
                            $scope.closeModel = function () {
                                $modalInstance.close();
                                return false;
                            };
                            $scope.sendModel = function () {
                                $modalInstance.close();
                            };
                        }
                    });
                }
                return false;
            }

            flowerFactory.setSendingDistrict($scope.flower.sendingDistrict);
            $scope.isChecked = false;
            purchaseFlowerModel.setFlower($scope.flower);

            //kissmetricsHelper.productAdded($scope.flower.id, $scope.flower.name, $scope.flower.price);

            if (userAccount.checkUserLoggedin()) { // if user is login, go directly to checkout pages
                $translate('CHECKOUT_URL').then(function (checkout_url) {
                    analyticsHelper.addProduct($scope.flower.id, $scope.flower.name, fixPrice($scope.flower.price), $scope.main_tag_name);
                    $state.go('purchaseProcess', {baseUrl: checkout_url});
                });
            }
            else // if user not login, show the menu which ask the user to do you want to login or register?
                $scope.$emit('USER_LOGIN', 'beforePurchaseSection');

        } else { // if the district where the flower will go is not entered, show the error message
            $scope.isChecked = true;
            otherExceptions.sendException("flowerDescription", "Eksik Bilgi");
        }
    };

    $scope.productSubscriptionMail = function(){
        if($(productSubscription.productSubscriptionMail).hasClass('ng-valid')){
            productSubscriptionHelper.subscribeMail($scope.flower.id, $scope.subscribe.mail, tempDistrictId ,function(result, errorCode){
                if(result){
                    $scope.IsSuccess = true;
                    $scope.isChecked = false;
                    //kissmetricsHelper.recordEvent($scope.flower.name + ' için E-posta adresi bıraktı');
                }else{
                    errorMessages.getErrorMessage(errorCode, function (errorMessage) {
                        $scope.isChecked = true;
                        $scope.IsSuccess = false;
                        $scope.errorMessage = errorMessage;

                        setTimeout(function () {
                            $scope.errorMessage = "";
                            $scope.isChecked = false;
                            $scope.$apply();
                        }, 2000);
                    });
                }
            });
        }
        else{
            $scope.isChecked = true;
            otherExceptions.sendException("subscribe-product",  "Eksik Bilgi");
        }

    };

    $scope.topDistance = function(){
        var distance = 30;

        if($scope.flower.extra_info_1){
            distance -= 10;
            if($scope.flower.extra_info_2){
                distance -= 10;
                if($scope.flower.extra_info_3)
                    distance -= 10;
            }
        }

        return { 'margin-top' : distance };
    };

    $scope.remindMeLater = function(){
        reminderHelper.addReminder($scope.flower.name,$scope.flower.id, function(result){
            $scope.isSuccess = result;
        });
    };
    $timeout(function () {
        var tempMail  = "";
        if (userAccount.checkUserLoggedin()){
            tempMail = userAccount.getUserMail();
            tempMail.toLowerCase();
        }
        analyticsHelper.sendCriteoFlowerDetail($scope.flower.id, tempMail, tempDistrictId);
    }, 2000);

    $scope.getDeliveryUPSDate = function ( extra_days, flowerObject ){

        //console.log(flowerObject);

        //var sendDate = new Date(flowerObject);

        //console.log(sendDate);

        //sendDate.setDate(sendDate.getDate() + parseInt(extra_days ) );

        //var tempString = sendDate.getDate() + '.' + (sendDate.getMonth() + 1) + '.' + sendDate.getFullYear();

        //return tempString;

    }

    $scope.isCityUps = function (currentCity){

        if( currentCity != 'ist'){

            return 'upsHeight';

        }
        else{
            return '';
        }



    }

});