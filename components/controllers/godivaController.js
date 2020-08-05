
var godivaModule = angular.module('godiva',[
    'menuModule',
    'userAccountModule',
    'footerModule',
    'PageTagsFactoryModule'
]);

godivaModule.controller("godivaCtrl", function ($timeout,$cookies, flowerFactory,districtFactory,purchaseFlowerModel,translateHelper, tagFactory, userAccount, $scope,$rootScope,$filter,$stateParams, $state,analyticsHelper,PageTagsFactory,otherExceptions, $location) {
    /*****  init flowers infos         *********/
    $scope.flowers = flowerFactory.getFlowers();

    $stateParams.tagUrl = 'cikolata';

    $rootScope.canonical = 'https://bloomandfresh.com/godiva-cikolata-gonder';

    $rootScope.city = {};
    $scope.selectedCity = {};
    var cityFromPlugin = '';

    $scope.cities = [
        {
            'value': 'ist',
            'name': 'Gönderim Şehri: İstanbul'
        },
        {
            'value': 'ank',
            'name': 'Gönderim Şehri: Ankara'
        }
    ];

    $scope.banner_image = 'https://d1z5skrvc8vebc.cloudfront.net/188.166.86.116:3000/1324.jpg';

    var tempSelectedCity = $cookies.getObject('selectCity');

    if ( tempSelectedCity == null) {

        $cookies.putObject('selectCity', {
            'value': 'ist',
            'name': 'Gönderim Şehri: İstanbul'
        });
        tempSelectedCity = {
            'value': 'ist',
            'name': 'Gönderim Şehri: İstanbul'
        };
    }

    if( tempSelectedCity ){
        if( tempSelectedCity.value == 'ist' ){
            tempSelectedCity = {
                'value': 'ist',
                'name': 'Gönderim Şehri: İstanbul'
            };
        }
        else{
            tempSelectedCity = {
                'value': 'ank',
                'name': 'Gönderim Şehri: Ankara'
            };
        }
    }


    $scope.selectedCity = tempSelectedCity;
    $rootScope.mainCitySelected = tempSelectedCity;



    /*flowerFactory.getFlowersWithFunction( function(data){
     $scope.flowers = data;
     $scope.flowers.forEach(function(flower){
     flower.isChecked = false;
     });
     console.log($scope.flowers);
     } );*/

    /***** init flower tags  *********/
    $scope.tags = tagFactory.getTags();
    console.log($scope.flowers);

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

        analyticsHelper.sendCriteoFlowerList(tempMail, tempDistrictId);
    }, 2000);
    $scope.districts = districtFactory.getDistincts();
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
            })
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

            if( $scope.chosenTag.url_name ){

                setFilteredFlowerPages();
                //console.log($scope.filteredFlowers);

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
            //console.log('Test');

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

            analyticsHelper.addProduct(flower.id, flower.name, flower.price);
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
                //    $location.url('/');
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
    }

    function setFilteredFlowerPages(){
        $scope.filteredFlowers = $scope.chosenTag !== undefined ? $filter('pageFilter')($scope.flowers,$scope.chosenTag.url_name) : $scope.flowers;
    }

    $scope.setCity = function (city) {
        //console.log(city);
        $cookies.putObject('selectCity', city);
        $rootScope.mainCitySelected = city;
        $scope.selectedCity = $rootScope.mainCitySelected;

        districtFactory.getDistinctsCallBack(function(data){
            $scope.districts = data;
        });

        $scope.districts = districtFactory.getDistincts();

        //location.reload();

        //$cookies.put('selectedCity', $rootScope.chosenCampaign);
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

godivaModule.filter('tagFilter', function () {
    return function (flowers, tagId) {
        var filteredFlowers = [];

        flowers.forEach(function(flower){
            var flowerTags = flower.tags;

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

godivaModule.filter('pageFilter', function () {

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
