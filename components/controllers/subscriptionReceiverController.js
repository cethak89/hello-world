'use strict';

var SubsReceiverModule = angular.module('subscriptionReceiver', [
    'menuModule',
    'footerModule',
    'ui.bootstrap',
    'ui.bootstrap.tpls',
    'ui.mask',
    'userPurchaseModule',
    'userAccountModule',
    'angularPayments',
    'ui.router'
]);

SubsReceiverModule.controller('subscriptionReceiverController', function ($scope, $location, deviceDetector, $rootScope, $state, $cookies, momentHelper, textareaHelper, facebookhelper, districtFactory, translateHelper, errorMessages, purchaseModel, purchaseFlowerModel, $document, userPurchase, PageTagsFactory, userAccount, districtObj, analyticsHelper, $stateParams, $http, $timeout, $window, flowerFactory, $modal, deliveryTimesFactory, otherExceptions) {
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
    $rootScope.totalPrice = 0.0;
    $scope.$tempfloatExtra = 0.0;
    $scope.$tempfloat = 0.0;

    $scope.districts = districtFactory.getDistincts();

    //console.log($state);
    //$state.href('/abonelik-3');
    //$state.go('how', {});
    //console.log($rootScope.selectedFlower);

    if ($rootScope.selectedFlower == null || $rootScope.selectedFlower == undefined) {
        $location.path('/abonelik-1');
    }
    //}

    if ($rootScope.subsExtra) {
        $scope.$tempfloatExtra = $rootScope.selectedFlower.side_price.replace(",", ".");
        $scope.$tempfloat = $rootScope.selectedFlower.price.replace(",", ".");
        $rootScope.totalPrice = (parseFloat($scope.$tempfloat) + parseFloat($scope.$tempfloatExtra)).toFixed(2);
        $rootScope.totalPrice = String($rootScope.totalPrice).replace(".", ",");
    }
    else {
        $rootScope.totalPrice = $rootScope.selectedFlower.price;
        $rootScope.totalPrice = $rootScope.selectedFlower.price.replace(".", ",");
    }


    $rootScope.$on('USER_SIGN_OUT', function () {
        if ($state.params.purchaseStep === 'kime' || $stateParams.orderId !== undefined) {
            $state.go('landing', {'purchaseStep': ''});
        }

        location.reload();
    });
    $rootScope.$on('USER_SIGN_IN', function () {
        if ($state.params.purchaseStep === 'kime' || $stateParams.orderId !== undefined) {
            $state.go('landing', {'purchaseStep': ''});
        }

        location.reload();
    });

    $document.ready(function () {
        $('.icheckField input').iCheck({
            checkboxClass: 'icheckbox_square-red',
            radioClass: 'icheckbox_square-red'
        });
    });

    /****   utility functions for scope     ****/
    $scope.isUserLogin = function () {
        return $rootScope.loggedUser !== undefined;
    };

    $scope.checkReceiverSubs = function () {
        if ($(receiverForm.contact_name).hasClass('ng-valid') && $(receiverForm.contact_mobile).hasClass('ng-valid') && $(receiverForm.contact_address).hasClass('ng-valid')) {


            if (userAccount.checkUserLoggedin()){
                console.log($scope.loggedUser);
                var paymentInfo = {};
                //paymentInfo.access_token = $rootScope.sender.access_token;
                paymentInfo.flower_id = $rootScope.selectedFlower.id;
                paymentInfo.freq_id = $rootScope.subsFreqSelected.id;
                paymentInfo.first_day = $rootScope.subsFirstDaySelected.id;
                paymentInfo.hour_id = $rootScope.subsHourSelected.id;
                paymentInfo.extra = $rootScope.subsExtra;
                paymentInfo.name = $rootScope.name;
                paymentInfo.number = $rootScope.phoneNumber;
                paymentInfo.district_id = $rootScope.sendingDistrict.id;
                paymentInfo.address = $rootScope.address;
                paymentInfo.note = $rootScope.note;
                paymentInfo.access_token = $scope.loggedUser.access_token;

                var postUrl = "/save-data-before-subs";

                $http.post(webServer + postUrl, paymentInfo)
                    .success(function (response) {
                        $rootScope.saleNumber = response.sale_number;
                        $location.path('/abonelik-3');
                        //callback(true);
                    }).error(function (response) {
                        errorMessages.getErrorMessage(response.description, function (errorMessage) {
                            $scope.serverError = errorMessage;
                            //callback(false);
                        });
                    });

            }

            //$state.go('subs3');
            //return true;

        } else {
            errorMessages.getErrorMessageFromName("MISSING_INFO", function (errorMessage) {
                $scope.isChecked = true;

                if (!($(receiverForm.contact_name).hasClass('ng-valid')))
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- alıcı ismi");
                else if (!($(receiverForm.contact_mobile).hasClass('ng-valid')))
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- alıcı numarası");
                else if (!($(receiverForm.contact_address).hasClass('ng-valid')))
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- alıcı adresi");
                else if ($rootScope.sender.name === undefined)
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- gönderen adı");
                else if ($rootScope.sender.name !== undefined)
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- gönderen mail");
                else
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi");

                $scope.serverError = errorMessage;

                return false;
            });
        }
    };

});