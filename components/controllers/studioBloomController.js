'use strict';

var studioBloomModule = angular.module("studioBloom",
    [
        'menuModule',
        'footerModule',
        'ui.bootstrap',
        'ui.bootstrap.tpls',
        'ui.mask',
        'userPurchaseModule',
        'userAccountModule',
        'angularPayments'
    ]
);
studioBloomModule.controller('studioBloomController', function ($scope, $rootScope, $state, $cookies,momentHelper,textareaHelper,facebookhelper,districtFactory,translateHelper,errorMessages, purchaseModel, purchaseFlowerModel, $document, userPurchase, PageTagsFactory, userAccount, analyticsHelper, $stateParams, $http, $timeout, $window, flowerFactory, $modal, deliveryTimesFactory, otherExceptions) {

    $scope.isChecked = false;
    $scope.isErrorHappened = false;
    $scope.working = false;

    $document.ready(function () {
        $('.icheckField input').iCheck({
            checkboxClass : 'icheckbox_square-red',
            radioClass : 'icheckbox_square-red'
        });
    });

    $scope.incrementSectionProgress = function () {
        $rootScope.sectionProgress++;
    };
    $scope.isSection = function (section) {
        return $scope.section === section;
    };
    $scope.isActive = function (activeSection) {
        return $scope.section >= activeSection;
    };

    /***   purchase complete functions ***/

    function fixPrice(price) {
        var ret = replaceString(price, ",", ".");
        return parseFloat(ret);
    }

    $scope.expirationMonths = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
    $scope.expirationYears = ["15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30"];
    $scope.paymentTypes = ['Kredi Kartı'];
    if($stateParams.error){
        $scope.serverError = 'Satış gerçekleşmedi. Banka veya kart ile ilgili teknik bir sorun var. Lütfen tekrar dene. Yada 0212 212 0 282’den bizimle iletişime geç.';
    }

    scrollToTop();
    var isMember = 'non-member';
    $http.get(webServer + '/getStudioBloomPaymentPage/' + $stateParams.orderId).success(function (response) {
        initVariables(response.data);
        //$rootScope.userCampaigns = purchaseModel.setCouponsWithDate( response.data.products_id , response.data.wanted_delivery_date );
    }).error(function (data) {
        $state.go('purchaseProcess', {'purchaseStep': ''});
    });

    function initVariables(data) {

        var flower = jQuery.extend(true, {}, data);
        flower.price = parseFloat(replaceString(flower.price, ",", ".")).toFixed(2);
        flower.newPrice = (flower.price / 100 * 118).toFixed(2);
        $scope.flower = flower;

        flower.price = replaceString(flower.price, ".", ",");
        flower.newPrice = replaceString(flower.newPrice, ".", ",");

        var tempFlower = {
            'id' :  flower.id,
            'price' : flower.price,
            'newPrice' : flower.newPrice,
            'MainImage' : 'https://d1z5skrvc8vebc.cloudfront.net/bloomandfresh.com/images/logos/bloomandfresh-logo3-v2.png',
            'name' : flower.name,
            'landing_page_desc' : flower.landing_page_desc
        };
        $scope.flower = tempFlower;
        //console.log($scope.flower);
        //var flowerObj = flowerFactory.getFlower(data.products_id);
        //purchaseFlowerModel.setFlower(flowerObj);
        //$scope.flower = purchaseFlowerModel.getFlower();
        //console.log($scope.flower);

        //$timeout(function () {
        //    if(data.products_id != $scope.flower.id){
        //        var flowerObj = flowerFactory.getFlower(data.products_id);
        //        purchaseFlowerModel.setFlower(flowerObj);
        //        $scope.flower = purchaseFlowerModel.getFlower();
        //    }
        //}, 3000);

    }

    function initUser(isMember) {
        $scope.initUserWorking = true;

        purchaseModel.initUser(function (sender, user, receiver) {
            if (sender.id !== undefined) {
                $rootScope.sender = sender;
                $rootScope.loggedUser = jQuery.extend(true, {}, user);
                $scope.userContacts = $rootScope.sender.userContacts;
                $rootScope.userCampaigns = $rootScope.sender.userCampaigns;
                $rootScope.chosenCampaign = $rootScope.sender.chosenCampaign;
                $scope.chosenContact = $rootScope.sender.chosenContact;
                $rootScope.receiver = receiver;
                $scope.selectedContact = $scope.chosenContact;

                isMember = 'member';
                $scope.initUserWorking = false;
            } else {
                $scope.initUserWorking = false;
            }
        });

    }

    textareaHelper.checkMaxLength();
});


/*purchaseModule.controller('PaymentCtrl', function ($scope, $rootScope, $document, $modal,translateHelper,errorMessages) {
    $scope.payment = {};

    $scope.payment.selectedCoupon = $rootScope.chosenCampaign;

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

    $scope.openModel2 = function (contactType) {

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
                    templateUrl: 'views/bf-utility-pages/addCampaignModal-v2.0.html',
                    size: 'sm',
                    controller: function ($scope, $rootScope, $timeout, $modalInstance) {
                        $scope.isChecked = false;
                        $scope.campaign = {};
                        $scope.errorMessage = "";

                        $scope.saveCampaign2 = function () {
                            if ($(addCampaignForm.campaignId).hasClass('ng-valid')) {
                                $scope.isChecked = false;
                                $rootScope.$emit("PROCESS_STATUS_CHANGED", true);
                                $scope.errorMessage = "";
                                $('#warningId').removeClass('hidden');
                                $rootScope.loggedUser.campaigns.addCampaign($scope.campaign.id, $rootScope.loggedUser.access_token,
                                    function (result, errorCode) {
                                        if (result) {

                                            $timeout(function () {
                                                $rootScope.$emit("USER_CAMPAIGN_ADDED");
                                                $rootScope.$emit("PROCESS_STATUS_CHANGED", false);
                                                $modalInstance.dismiss();
                                                $('#warningId').addClass('hidden');
                                            }, 500);
                                        }
                                        else {
                                            errorMessages.getErrorMessage(errorCode, function(errorMessage){
                                                $rootScope.$emit("PROCESS_STATUS_CHANGED", false);
                                                $('#warningId').addClass('hidden');
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

    $scope.isCampaignChosen = function () {
        return $rootScope.chosenCampaign !== undefined;
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
});
*/
purchaseModule.controller('ModalCtrl', function ($scope, $rootScope,purchaseFlowerModel,momentHelper) {
    $scope.flower = purchaseFlowerModel.getFlower();
    $scope.sender = $rootScope.sender;
    $scope.flowerPrice = $scope.flower.newPrice;
});

function purchaseCheckStudioBloom() {
    var urlPath = window.location.pathname.split("/");
    var paymentPath = urlPath[2];

    if( $('#3Dcheck').find($('div')).hasClass('checked')){
        //$('#payment').attr('action', 'https://everybloom.com/studio-bloom-complete');
        $('#payment').attr('action', 'https://everybloom.com/studio-bloom-complete');
    }
    else{
        //$('#payment').attr('action', 'https://everybloom.com/studioBloomList/completePayment');
        $('#payment').attr('action', 'https://everybloom.com/studioBloomList/completePayment');
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

            return true;
        } else {
            $('label.contractsError').text('Kart Bilgileri Hatalı Veya Eksik');
            return false;
        }
    } else {
        $('label.contractsError').text('Kullanıcı Sözleşmesini Onaylaman Gerekiyor');
        return false;
    }
}