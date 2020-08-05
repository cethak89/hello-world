/**
 * Created by furkan on 04.03.2015.
 */
'use strict';

var flowersModule = angular.module("flowers",
    [
        'menuModule',
        'userAccountModule',
        'footerModule',
        'PageTagsFactoryModule'
    ]);

flowersModule.controller("FlowersController", function ($timeout,$cookies, flowerFactory,districtFactory,purchaseFlowerModel,translateHelper, tagFactory, userAccount, $scope,$rootScope,$filter,$stateParams, $state,analyticsHelper,PageTagsFactory,otherExceptions, $location) {
     /*****  init flowers infos         *********/
    $scope.flowers = flowerFactory.getFlowers();


    $rootScope.city = {};
    $scope.selectedCity = {};
    var cityFromPlugin = '';

    /*$scope.cities = [
        {
            'value': 'ist',
            'name': 'Gönderim Şehri: İstanbul'
        },
        {
            'value': 'ank',
            'name': 'Gönderim Şehri: Ankara'
        }
    ];*/

    $timeout(function () {
        if( $('.flowerClassJS').length == 0 ){
            $('#cargoText').removeClass('hidden');
        }

    }, 1000);

    $scope.cities = [
        {
            "value": "ist",
            "name": "İstanbul-Avrupa"
        },
        {
            "value": "ist-2",
            "name": "İstanbul-Asya"
        },
        {
            "value": "ank",
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

    $scope.banner_image = 'https://d1z5skrvc8vebc.cloudfront.net/188.166.86.116:3000/1324.jpg';

    var tempSelectedCity = $cookies.getObject('selectCity');

    if ( tempSelectedCity == null) {

        $cookies.putObject('selectCity', {
            'value': 'ist',
            'name': 'İstanbul-Avrupa'
        });
        tempSelectedCity = {
            'value': 'ist',
            'name': 'İstanbul-Avrupa'
        };
    }
    else{
        if(tempSelectedCity.value == 'ist'){
            tempSelectedCity = {
                'value': 'ist',
                'name': 'İstanbul-Avrupa'
            };
            $cookies.putObject('selectCity', {
                'value': 'ist',
                'name': 'İstanbul-Avrupa'
            });
        }
        else if(tempSelectedCity.value == 'ank'){
            tempSelectedCity = {
                'value': 'ank',
                'name': 'Ankara'
            };
            $cookies.putObject('selectCity', {
                'value': 'ank',
                'name': 'Ankara'
            });
        }
        else if(tempSelectedCity.value == 'ist-2'){
            tempSelectedCity = {
                'value': 'ist-2',
                'name': 'İstanbul-Asya'
            };
            $cookies.putObject('selectCity', {
                'value': 'ist-2',
                'name': 'İstanbul-Asya'
            });
        }

    }

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
    }*/

    $scope.selectedCity = tempSelectedCity;
    $rootScope.mainCitySelected = tempSelectedCity;

    var tempNameCity;
    if( $rootScope.mainCitySelected.value == 'ank' ){
        tempNameCity = 'ANKARA';
    }
    else if( $rootScope.mainCitySelected.value == 'ist' ){
        tempNameCity = 'İSTANBUL-Avrupa';
    }
    else if( $rootScope.mainCitySelected.value == 'ist-2' ){
        tempNameCity = 'İSTANBUL-Asya';
    }
    else{
        tempNameCity = $rootScope.mainCitySelected.value.toUpperCase();
    }

    /*flowerFactory.getFlowersWithFunction( function(data){
        $scope.flowers = data;
        $scope.flowers.forEach(function(flower){
            flower.isChecked = false;
        });
        console.log($scope.flowers);
    } );*/

    /***** init flower tags  *********/
    $scope.tags = tagFactory.getTags();
    $scope.pages = tagFactory.getPages();

    $timeout(function () {
        var tempMail  = "";
        if (userAccount.checkUserLoggedin()){
            tempMail = userAccount.getUserMail();
            tempMail.toLowerCase();
        }

        var tempDistrictId = "1";
        if( tempSelectedCity.value == 'ank' ){
            tempDistrictId = "2";
        }
        else if( tempSelectedCity.value == 'ist-2' ){
            tempDistrictId = "341";
        }

        analyticsHelper.sendCriteoFlowerList(tempMail, tempDistrictId);
    }, 2000);
    $scope.districts = districtFactory.getDistincts();

    $scope.districts = $scope.districts.filter(function (el) {
        return ( ( el.city.toUpperCase() == tempNameCity && ( el.city_id == 2 || el.city_id == 3 ) ) || ( el.city_id == 1 && $rootScope.mainCitySelected.value == 'ist'  ) || ( el.city_id == 341 && $rootScope.mainCitySelected.value == 'ist-2'  ) );
    });

    $scope.upsDeliveryTime = 3;
    $scope.upsDeliveryTimes = [];

    flowerFactory.getUpsTime( function(data){
        $scope.upsDeliveryTimes = data;

        data.forEach(function(upsCity){
            if( upsCity.value == $rootScope.mainCitySelected.value ){
                $scope.upsDeliveryTime = upsCity.delivery_days;
            }
        });
    });

    $scope.$watch('$root.mainCitySelected.value', function() {

        $timeout(function () {
            if( $('.flowerClassJS').length == 0 ){
                $('#cargoText').removeClass('hidden');
            }

        }, 1000);

        $scope.upsDeliveryTimes.forEach(function(upsCity){
            if( upsCity.value == $rootScope.mainCitySelected.value ){
                $scope.upsDeliveryTime = upsCity.delivery_days;
            }
        });

    });

    var contact = undefined,
        campaign = undefined,
        staticHeader;

    /***** init header dynamic text    *********/
    translateHelper.getText('FLOWERS_SMALL_HEADER',function(flower_small_header) {
        staticHeader = 'Çiçek göndermenin en şık yolu Bloom and Fresh derken ciddiydik! Gül göndermek mi? Ya da eşsiz bir orkide göndermek? Hayır hayır, en iyisi bir kaktüs veya teraryum gönder! Peki eşsiz çiçeklerimizin yanında gönderebileceğin çikolatalar ve hediye kutuları olduğunu da biliyor muydun?' +
'<br><br>Online gönderebileceğin tüm çiçekler ve daha fazlası için bu sayfaya göz atabilirsin! Vereceğin çiçek siparişini kolaylaştırmak için aşağıdaki sihirli etiketleri kullanmayı unutma!';
        $scope.headerText = staticHeader;
        $scope.tagFind = false;
        if($stateParams.tagUrl !== undefined && $stateParams.tagUrl !== ""){
            $scope.tags.forEach(function(tag){
                if(tag.tag_ceo.toUpperCase() === $stateParams.tagUrl.toUpperCase()){
                    $scope.headerText = tag.description;
                    $scope.chosenTag = tag;
                    $scope.tagFind = true;
                    $scope.banner_image = tag.banner_image;

                    setFilteredFlowers();
                }
                else
                    tag.isHovered = false;
            });

            if( !$scope.chosenTag ){
                //$state.go('landing');
            }
            else{
                //console.log($scope.chosenTag.tag_ceo);

                //console.log($scope);

                $rootScope.canonical = 'https://bloomandfresh.com/' + $scope.chosenTag.tag_ceo;
            }

        }


        if( !$scope.tagFind && $stateParams.tagUrl ){

            $scope.pages.forEach(function(page){
                if(page.url_name.toUpperCase() === $stateParams.tagUrl.toUpperCase()){

                    $scope.chosenTag = {};

                    $scope.chosenTag.banner_image = page.image;
                    $scope.chosenTag.tags_name = page.head;
                    $scope.chosenTag.url_name = page.url_name;
                    $scope.headerText = page.desc;
                    $scope.banner_image = page.image;
                    PageTagsFactory.setTags($scope.headerText,page.meta_tittle,page.meta_desc);
                    PageTagsFactory.changeWebSiteVariable();

                    //setFilteredFlowers();
                }
            });

            if( !$scope.chosenTag ){
                $state.go('landing');
            }
            else{
                $rootScope.canonical = 'https://bloomandfresh.com/' + $scope.chosenTag.url_name;
                if( $scope.chosenTag.url_name ){
                    setFilteredFlowerPages();
                }
            }


            /*flowerFactory.getCategoryFlowers( $stateParams.tagUrl, function(data){
                $scope.filteredFlowers = data.data;
                if( data.info.length > 0 ){

                    $scope.chosenTag = {};

                    $scope.chosenTag.banner_image = data.info[0].image;
                    $scope.chosenTag.tags_name = data.info[0].head;
                    $scope.headerText = data.info[0].desc;
                    $scope.banner_image = data.info[0].image;
                    PageTagsFactory.setTags($scope.headerText,data.info[0].meta_tittle,data.info[0].meta_desc);
                    PageTagsFactory.changeWebSiteVariable();
                }
            });*/
        }
        else{

            $scope.chosenTag !== undefined ? PageTagsFactory.setTags($scope.headerText,$scope.chosenTag.tag_header,$scope.chosenTag.meta_description) : PageTagsFactory.setTags($scope.headerText);
            PageTagsFactory.changeWebSiteVariable();

            setFilteredFlowers();

        }

        function compareBestSeller( a, b ) {
            if ( a.bestSellerOrder < b.bestSellerOrder ){
                return -1;
            }
            if ( a.bestSellerOrder > b.bestSellerOrder ){
                return 1;
            }
            return 0;
        }

        if( $scope.chosenTag.url_name == 'cok-satanlar' ){

            $scope.filteredFlowers.sort( compareBestSeller );
        }

    });

    //kissmetricsHelper.sendPageView('çiçekleri');

    $scope.getTagTooltip = function(tagObj){
        return "<h4 class='tooltipHeader'>" + tagObj.tags_name+ "</h4>" +
            "<p class='tooltipContext'>" + tagObj.tag_header + "</p>";
    };

    $scope.flowerStyle = function($index){
        return "flowerStyle" + ($index % 8);
    };

    $scope.sendFlower = function(flower){

        if(flower.sendingDistrict !== undefined){
            flowerFactory.setSendingDistrict(flower.sendingDistrict);

            flower.isChecked = false;

            if(contact !== undefined)
                flower.contact = contact;
            if(campaign !== undefined)
                flower.campaign = campaign;

            //console.log(flower);
            if(flower.tags){
                flower.tags.forEach(function(tag){
                    if(flower.tag_id == tag.id){
                        $scope.main_tag_name = tag.tags_name;
                    }
                });
            }
            else{
                $scope.main_tag_name = '';
            }
            //console.log($scope.main_tag_name)

            analyticsHelper.addProduct(flower.id, flower.name, flower.price, $scope.main_tag_name);
            analyticsHelper.clickEvent('checkoutFromFlowers');

            //kissmetricsHelper.productAdded(flower.id, flower.name, flower.price);

            purchaseFlowerModel.setFlower(flower);

            if(userAccount.checkUserLoggedin()){
                translateHelper.getText('CHECKOUT_URL', function(checkout_url) {
                    $state.go('purchaseProcess',{baseUrl:checkout_url});
                });
            }
            else
                $scope.$emit('USER_LOGIN', 'beforePurchaseSection');
        }else {
            flower.isChecked = true;
            otherExceptions.sendException("flowers", "Eksik Bilgi");
        }
    };

    $scope.tagSelected = function(tag){
        if(tag){
            if($scope.chosenTag !== undefined && ($scope.chosenTag.id === tag.id)){
                //tagChanged(undefined);
                //$timeout(function () {
                //    $location.url('/cicekler/');
                //}, 1000);
            }
            else if($scope.chosenTag === undefined && $stateParams.tagUrl=== tag.tag_ceo){
                tagChanged(tag);
            }
        }
    };

    $rootScope.$on('SEND_FLOWER_TO_CONTACT',function(e, data){
        var districtObj = findDistrict(data.district);
        setDropdownsValue(districtObj);
        contact = data;
    });

    $rootScope.$on('SEND_FLOWER_WITH_CAMPAIGN',function(e, data){
        campaign = data;
    });

    function setDropdownsValue(district){
        for( var i = 0; i < $scope.flowers.length; i++ )
        {
            $scope.flowers[i].sendingDistrict = district;
        }
    }

    function findDistrict (searchingDistrict){
        if($scope.districts === undefined)
            $scope.districts = districtObj;

        var districtOfContact = {};
        $scope.districts.forEach(function(district){
            if(district.district == searchingDistrict){
                districtOfContact = district;
                return;
            }
        });

        return districtOfContact;
    }

    function tagChanged(chosenTag){
        $scope.chosenTag = chosenTag;
        setFilteredFlowers();

        $scope.headerText = $scope.chosenTag ? $scope.chosenTag.description : staticHeader;

        PageTagsFactory.setTags($scope.headerText);
        PageTagsFactory.changeWebSiteVariable();
    }

    function setFilteredFlowers(){
        $scope.filteredFlowers = $scope.chosenTag !== undefined ? $filter('tagFilter')($scope.flowers,$scope.chosenTag.id) : $scope.flowers;
        //console.log($scope.filteredFlowers);
    }

    function setFilteredFlowerPages(){
        $scope.filteredFlowers = $scope.chosenTag !== undefined ? $filter('pageFilter')($scope.flowers,$scope.chosenTag.url_name) : $scope.flowers;
        //console.log($scope.filteredFlowers);
    }

    $scope.setCity = function (city) {
        $cookies.putObject('selectCity', city);
        $rootScope.mainCitySelected = city;
        $scope.selectedCity = $rootScope.mainCitySelected;

        districtFactory.getDistinctsCallBack(function(data){
            $scope.districts = data;
            $scope.districts = $scope.districts.filter(function (el) {
                return ( ( el.city.toUpperCase() == tempNameCity && ( el.city_id == 2 || el.city_id == 3 ) ) || ( el.city_id == 1 && $rootScope.mainCitySelected.value == 'ist'  ) || ( el.city_id == 341 && $rootScope.mainCitySelected.value == 'ist-2'  ) );
            });
        });

        $scope.districts = districtFactory.getDistincts();

        //location.reload();

        //$cookies.put('selectedCity', $rootScope.chosenCampaign);
    };

    $scope.setCityDistrict = function (city) {
        var tempNameCity;
        if( city.city_id == 1 ){
            var tempCity = {
                'value': 'ist',
                'name': 'İstanbul-Avrupa'
            };
            tempNameCity = 'İSTANBUL';
        }
        else if( city.city_id == 2 ) {
            var tempCity = {
                'value': 'ank',
                'name': 'Ankara'
            };
            tempNameCity = 'ANKARA';
        }
        else if( city.city_id == 341 ){
            var tempCity = {
                'value': 'ist-2',
                'name': 'İstanbul-Asya'
            };
            tempNameCity = 'İSTANBUL';
        }
        else{
            var tempCity = {
                'value': city.city,
                'name': '' + city.city
            };
            tempNameCity = city.city.toUpperCase();
        }

        $cookies.putObject('selectCity', tempCity);
        $rootScope.mainCitySelected = tempCity;
        $scope.selectedCity = $rootScope.mainCitySelected;

        //districtFactory.getDistinctsCallBack(function(data){
        //    $scope.districts = data;
        //});

        $rootScope.districtFromDetail = city;

        $scope.districts = districtFactory.getDistincts();
        $scope.districts = $scope.districts.filter(function (el) {
            return ( ( el.city.toUpperCase() == tempNameCity && ( el.city_id == 2 || el.city_id == 3 ) ) || ( el.city_id == 1 && $rootScope.mainCitySelected.value == 'ist'  ) || ( el.city_id == 341 && $rootScope.mainCitySelected.value == 'ist-2'  ) );
        });

    };

    $scope.calculatePriceWithKDV = function($tempFlower){

        if( $tempFlower.product_type == 2 ){
            return (parseFloat(replaceString($tempFlower.price, ',', '.'))*1.08).toFixed(2);
        }
        else{
            return (parseFloat(replaceString($tempFlower.price, ',', '.'))*1.18).toFixed(2);
        }

    };


    $scope.checkCompanyUser = function(){
        if (userAccount.checkUserLoggedin()){
            return $scope.loggedUser.company_info_id;
        }
        else
            return false;
    };

    $timeout(function () {
        analyticsHelper.sendPageView('/flowers/' + $stateParams.tagUrl);
    }, 1000);

});

flowersModule.filter('tagFilter', function () {
    return function (flowers, tagId) {
            var filteredFlowers = [];

            flowers.forEach(function(flower){
                var flowerTags = flower.tags;

                console.log(flower);

                for(var index in flowerTags){
                    if(flowerTags[index].id === tagId){
                        filteredFlowers.push(flower);
                        break;
                    }
                }
            });

        return filteredFlowers;
    };
});

flowersModule.filter('pageFilter', function () {

    return function (flowers, tagId) {

        var filteredFlowers = [];


        flowers.forEach(function(flower){
            //console.log(flower);
            var flowerPages = flower.pageList;

            for(var index in flowerPages ){
                if(flowerPages[index].url_name == tagId){
                    filteredFlowers.push(flower);
                    break;
                }
            }
        });

        //console.log(filteredFlowers);

        return filteredFlowers;
    };
});