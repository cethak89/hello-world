
var SubsReceiverModule = angular.module('subscriptionFlower',[
    'menuModule',
    'footerModule',
    'ui.bootstrap',
    'ui.bootstrap.tpls',
    'ui.mask',
    'userPurchaseModule',
    'userAccountModule',
    'angularPayments'
]);

SubsReceiverModule.controller('subscriptionFlowerCtrl',function($scope,deviceDetector , $rootScope, $state, $cookies,momentHelper,textareaHelper,facebookhelper,districtFactory,translateHelper,errorMessages, purchaseModel, purchaseFlowerModel, $document, userPurchase, PageTagsFactory, userAccount, districtObj, analyticsHelper, $stateParams, $http, $timeout, $window, flowerFactory, $modal, deliveryTimesFactory, otherExceptions){
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.contact = {};
    $scope.error = "";
    $scope.working = false;
    PageTagsFactory.changeAndSetVariables();
    analyticsHelper.sendPageView($state.current.name);
    //kissmetricsHelper.sendPageView('Kurumsal Müşteri');

    PageTagsFactory.setTags();
    PageTagsFactory.changeWebSiteVariable();
    $scope.isChecked = false;
    $scope.userContacts = [];
    $scope.receiverDates = [];
    $scope.isErrorHappened = false;
    $scope.receiverTimes = [];
    $scope.chosenContact = undefined;
    $scope.working = false;
    $scope.crossSellActive = false;
    $scope.selectedCrossSell = 0;
    $scope.selectedFreq = {};
    $scope.selectedHour = {};
    $scope.selectedFirstTime = {};
    $scope.subsFreq = [];
    $scope.subsFirst = [];
    $scope.subsFreq = [];
    $scope.subsHour = [];



    if($rootScope.subsExtra != true){
        $rootScope.subsExtra = false;
    }

    $timeout(function () {
        if( !$scope.loggedUser ){
            $state.go('landing');
        }
    }, 3000);

    $rootScope.$on('USER_SIGN_OUT', function () {
        //if ($state.params.purchaseStep === 'kime' || $stateParams.orderId !== undefined) {
        //    $state.go('landing', {'purchaseStep': ''});
        //}

        location.reload();
    });

    //$document.ready(function () {
    //    $('.icheckField input').iCheck({
    //        checkboxClass : 'icheckbox_square-red',
    //        radioClass : 'icheckbox_square-red'
    //    });
    //
    //    $('.icheckbox_square-red').onclick = function(){
    //        $('#labelId').click();
    //    };
    //});

    /****   utility functions for scope     ****/
    $scope.isUserLogin = function () {
        return $rootScope.loggedUser !== undefined;
    };

    purchaseModel.getSubsFreq(function (result) {
        if(!$rootScope.subsFreqSelected && result.data.length > 0){
            $rootScope.subsFreqSelected = result.data[0];
            $scope.selectedFreq = result.data[0];
        }
        else{
            $scope.selectedFreq = $rootScope.subsFreqSelected;
        }
        $scope.subsFreq = result.data;
        //console.log($rootScope.subsFreqSelected);
    });

    purchaseModel.getSubsFirstDays(function (result) {
        if(!$rootScope.subsFirstDaySelected && result.data.length > 0){
            $rootScope.subsFirstDaySelected = result.data[0];
            $scope.selectedFirstTime = result.data[0];
        }
        else{
            $scope.selectedFirstTime = $rootScope.subsFirstDaySelected;
        }
        $scope.subsFirst = result.data;
    });

    purchaseModel.getSubsHours(function (result) {
        if(!$rootScope.subsHourSelected && result.data.length > 0){
            $rootScope.subsHourSelected = result.data[0];
            $scope.selectedHour = result.data[0];
        }
        else{
            $scope.selectedHour = $rootScope.subsHourSelected;
        }
        $scope.subsHour = result.data;
    });

    purchaseModel.getProducts(function (result) {
        if(!$rootScope.selectedFlower && result.data.length > 0){
            $rootScope.selectedFlower = result.data[0];
        }
        $rootScope.products = result.data;
    });

    $scope.selectProduct = function (data) {
        $rootScope.selectedFlower = data;
    };

    $scope.changeSubsExtra = function(){
        $rootScope.subsExtra = !$rootScope.subsExtra;
        console.log($rootScope.subsExtra);
    };

    $scope.setFreq = function(FreqObject){
        $rootScope.subsFreqSelected = FreqObject;
    };

    $scope.setFirst = function(FirstObject){
        $rootScope.subsFirstDaySelected = FirstObject;
    };

    $scope.setHour = function(HourObject){
        $rootScope.subsHourSelected = HourObject;
    };


});