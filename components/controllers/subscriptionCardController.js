'use strict';

var godivaModule = angular.module('subscriptionCard',[
    'ui.mask',
    'ui.validate',
    'PageTagsFactoryModule'
]);

godivaModule.controller('subscriptionCardController',function($timeout,errorMessages, userAccount, $http,$scope,$rootScope,$location,$document,$modal,translateHelper,$stateParams){
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.contact = {};
    $scope.error = "";
    $scope.working = false;
    //kissmetricsHelper.sendPageView('Kurumsal Müşteri')
    $scope.isChecked = false;
    $scope.userContacts = [];
    $scope.receiverDates = [];
    $scope.isErrorHappened = false;
    $scope.receiverTimes = [];
    $scope.chosenContact = undefined;
    $scope.working = false;
    $scope.crossSellActive = false;
    $scope.selectedCrossSell = 0;
    $scope.taxInclueded = 0;
    $scope.openOrNot = true;

    if ($stateParams.orderId){
            var paymentInfo = {};
            paymentInfo.order_id = $stateParams.orderId;
            var postUrl = "/get-fail-subs-info";

        if( $scope.openOrNot && $rootScope.openOrNot != 1 ){
            console.log('1');
            $rootScope.openOrNot = 1;
            $modal.open({
                templateUrl: '../../views/bf-utility-pages/paymentFailed-v2.0.html',
                size: 'sm',
                controller: function ($scope, $modalInstance) {
                    errorMessages.getErrorMessage(418, function (errorMessage) {
                        $scope.error = errorMessage;
                    });

                    $scope.closeModel = function () {
                        $modalInstance.close();
                    };
                }
            });
        }

            //console.log($stateParams.orderId);
            $http.get(webServer + postUrl + '/' + $stateParams.orderId )
                .success(function (response) {
                    $rootScope.selectedFlower = response.flower;
                    $rootScope.subsFreqSelected = response.freqData;
                    $rootScope.subsFirstDaySelected = response.firstDay;
                    $rootScope.subsHourSelected = response.hour;
                    $rootScope.subsExtra = response.extraProduct;
                    $rootScope.name = response.sales.contact_name;
                    $rootScope.phoneNumber = response.sales.contact_mobile;
                    $rootScope.address = response.sales.contact_address;
                    $rootScope.note = response.sales.note;
                    $rootScope.subsExtra = response.sales.cup_status;
                    $rootScope.sendingDistrict = response.locationData;
                    $rootScope.saleNumber = response.sales.id;


                    if($rootScope.subsExtra != 0){
                        $rootScope.subsExtra = true;
                        $scope.$tempfloatExtra = $rootScope.selectedFlower.side_price.replace(",", ".");
                        $scope.$tempfloat = $rootScope.selectedFlower.price.replace(",", ".");
                        $rootScope.totalPrice = (parseFloat($scope.$tempfloat) + parseFloat($scope.$tempfloatExtra)).toFixed(2);
                        $scope.taxInclueded = parseFloat($rootScope.totalPrice / 100 * 118).toFixed(2);
                        $scope.taxInclueded = String($scope.taxInclueded);
                        $scope.taxInclueded = String($scope.taxInclueded).replace(".", ",");
                        $rootScope.totalPrice = String($rootScope.totalPrice).replace(".", ",");
                    }
                    else{
                        $rootScope.subsExtra = false;
                        $scope.$tempfloat = $rootScope.selectedFlower.price.replace(",", ".");
                        $rootScope.totalPrice = parseFloat($scope.$tempfloat);
                        $scope.taxInclueded = parseFloat($rootScope.totalPrice / 100 * 118).toFixed(2);
                        $scope.taxInclueded = String($scope.taxInclueded);
                        $scope.taxInclueded = String($scope.taxInclueded).replace(".", ",");
                        $rootScope.totalPrice = $rootScope.selectedFlower.price;
                        $rootScope.totalPrice = $rootScope.selectedFlower.price.replace(".", ",");
                    }

                    //$rootScope.saleNumber = response.sale_number;
                    //callback(true);
                }).error(function (response) {
                    errorMessages.getErrorMessage(response.description, function (errorMessage) {
                        $scope.serverError = errorMessage;
                        //callback(false);
                    });
                });
    }
    else{
        if($rootScope.selectedFlower == null || $rootScope.selectedFlower == undefined ) {
            $location.path('/abonelik-1');
        }

        if($rootScope.subsExtra){
            $scope.$tempfloatExtra = $rootScope.selectedFlower.side_price.replace(",", ".");
            $scope.$tempfloat = $rootScope.selectedFlower.price.replace(",", ".");
            $rootScope.totalPrice = (parseFloat($scope.$tempfloat) + parseFloat($scope.$tempfloatExtra)).toFixed(2);
            $scope.taxInclueded = parseFloat($rootScope.totalPrice / 100 * 118).toFixed(2);
            $scope.taxInclueded = String($scope.taxInclueded);
            $scope.taxInclueded = String($scope.taxInclueded).replace(".", ",");
            $rootScope.totalPrice = String($rootScope.totalPrice).replace(".", ",");
        }
        else{
            $scope.taxInclueded = parseFloat($rootScope.totalPrice.replace(",", ".") / 100 * 118).toFixed(2);
            $scope.taxInclueded = String($scope.taxInclueded);
            $scope.taxInclueded = String($scope.taxInclueded).replace(".", ",");
            $rootScope.totalPrice = $rootScope.selectedFlower.price;
            $rootScope.totalPrice = $rootScope.selectedFlower.price.replace(".", ",");
        }
    }
    //}

    $document.ready(function () {
        $('.icheckField input').iCheck({
            checkboxClass : 'icheckbox_square-red',
            radioClass : 'icheckbox_square-red'
        });
    });

    $scope.expirationMonths = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
    $scope.expirationYears = ["15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30"];
    $scope.paymentTypes = ['Kredi Kartı'];



    $scope.payment = {};

    $('input.contactCheckbox')
        .on('ifChecked', function (event) {
            $scope.payment.isContractReaded = true;
        })
        .on('ifUnchecked', function (event) {
            $scope.payment.isContractReaded = false;
        });

    //kissmetricsHelper.clickEvent('.purchaseCompleteButton', 'Ödeme yap butonuna bastı');

    translateHelper.getText('CVC_INFO', function(cvcInfo){
        $scope.cvcInfo = "<p class='tooltipContext'>" + cvcInfo + "</p>";
    });

    $scope.openModel = function (contactType) {
        switch (contactType) {
            case 'longDistancePurchaseContract':
            {
                $modal.open({
                    templateUrl: 'views/contracts/longDistancePurchaseContract.html',
                    size: 'lg',
                    controller: 'ModalCtrl'
                });
                break;
            }
            case 'preinformForm':
            {
                $modal.open({
                    templateUrl: 'views/contracts/preinforming.html',
                    size: 'lg',
                    controller: 'ModalCtrl'
                });
                break;
            }
            case 'addCampaign':
            {
                if($scope.flower.speciality == 1){

                }
                else{
                    $modal.open({
                        templateUrl: '../../views/bf-utility-pages/addCampaignModal-v2.0.html',
                        size: 'sm',
                        controller: function ($scope, $rootScope, $timeout, $modalInstance) {
                            $scope.isChecked = false;
                            $scope.campaign = {};
                            $scope.errorMessage = "";

                            $scope.saveCampaign = function () {
                                if ($(addCampaignForm.campaignId).hasClass('ng-valid')) {
                                    $scope.isChecked = false;
                                    $rootScope.$emit("PROCESS_STATUS_CHANGED", true);
                                    $rootScope.loggedUser.campaigns.addCampaign($scope.campaign.id, $rootScope.loggedUser.access_token,
                                        function (result, errorCode) {
                                            if (result) {
                                                $timeout(function () {
                                                    $rootScope.$emit("USER_CAMPAIGN_ADDED");
                                                    $rootScope.$emit("PROCESS_STATUS_CHANGED", false);
                                                    $modalInstance.dismiss();
                                                }, 500);
                                            }
                                            else {
                                                errorMessages.getErrorMessage(errorCode, function(errorMessage){
                                                    $rootScope.$emit("PROCESS_STATUS_CHANGED", false);
                                                    $scope.errorMessage = errorMessage;
                                                });

                                            }
                                        });
                                } else {
                                    $scope.isChecked = true;
                                }
                            };
                        }
                    });
                }
                break;
            }
        }
    };

    String.prototype.toCardFormat = function () {
        return this.replace(/[^0-9]/g, "").substr(0, 16).split("").reduce(cardFormat, "");
        function cardFormat(str, l, i) {
            return str + ((!i || (i % 4)) ? "" : "-") + l;
        }
    };

    $document.ready(function () {
        $("#paymentCardNumber").keyup(function () {
            $(this).val($(this).val().toCardFormat());
        });
    });

    $scope.$watch('payment.cvcNumber', function (newValue) {
        if ($scope.payment.cardNumber !== undefined && $scope.payment.cvcNumber && $scope.payment.expirationMonth && $scope.payment.expirationYear) {
            //kissmetricsHelper.recordEvent('Kredi kartı bilgisi girdi');
            $scope.serverError = "";
        }
    });

    console.log('qwerq');



});

function purchaseCheckSubs() {
    var urlPath = window.location.pathname.split("/");
    var paymentPath = urlPath[2];

    if( $('#3Dcheck').find($('div')).hasClass('checked')){
        $('#payment').attr('action', 'http://188.166.86.116:3000/submit-sale-subs');  //https://everybloom.com/submit-sale
    }
    else{
        $('#payment').attr('action', 'http://188.166.86.116:3000/submit-sale-subs'); //http://188.166.86.116:3000/sales-without-secure
    }

    if ($('section.contracts').find('div.icheckbox_square-red').hasClass('checked')) {

        if ( ($(paymentForm.card_no).hasClass('ng-valid') && $(paymentForm.card_cvv).hasClass('ng-valid')
            && ($('.paymentCardExpirationDay').find('.ui-select-container').hasClass('ng-valid-parse') || $('.paymentCardExpirationDay').find('.mobilDropdownSelect').hasClass('ng-valid-parse'))
            && $('.paymentCardExpirationYear').find('.ui-select-container').hasClass('ng-valid-parse') || $('.paymentCardExpirationYear').find('.mobilDropdownSelect').hasClass('ng-valid-parse') ) || $('#checkIf').hasClass('ng-hide')  ) {
            $('#submitButton').removeAttr("onclick");
            $('#submitButton').removeAttr("type");
            $('#submitButton').addClass("disabled");
            $('#submitButton').attr("disabled" , true);
            $('#submitButton').css("background-color" , '#6F7376');
            $('#payment').submit();

            return false;
        } else {

            $('label.contractsError').text('Kart Bilgileri Hatalı Veya Eksik');


            if (!($(paymentForm.card_no).hasClass('ng-valid')))
                sendErrors('payment-purchase', 'eksik bilgi- kart no');
            else if (!($(paymentForm.card_cvv).hasClass('ng-valid')))
                sendErrors('payment-purchase', 'eksik bilgi- cvc');
            else if ($('.paymentCardExpirationDay').find('.ui-select-container').hasClass('ng-valid-parse') || $('.paymentCardExpirationDay').find('.mobilDropdownSelect').hasClass('ng-valid-parse') == false)
                sendErrors('payment-purchase', 'eksik bilgi- gün');
            else if ($('.paymentCardExpirationYear').find('.ui-select-container').hasClass('ng-valid-parse') || $('.paymentCardExpirationYear').find('.mobilDropdownSelect').hasClass('ng-valid-parse') == false)
                sendErrors('payment-purchase', 'eksik bilgi- ay');
            else
                sendErrors('payment-purchase', 'Kart Bilgileri Hatalı Veya Eksik');
            return false;
        }
    } else {
        $('label.contractsError').text('Kullanıcı Sözleşmesini Onaylaman Gerekiyor');


        sendErrors('payment-purchase', 'Kullanıcı Sözleşmesini Onaylaman Gerekiyor');
        return false;
    }
}