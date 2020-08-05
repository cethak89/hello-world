'use strict';

/**
 * Created by furkan on 10.03.2015.
 */

var purchaseSuccessModule = angular.module("purchaseSuccess",
    [
        'menuModule',
        'footerModule',
        'PageTagsFactoryModule',
        'userAccountModule'
    ]);

purchaseSuccessModule.controller("purchaseSuccessController", function ($timeout,$scope, $http,$sce, $cookies, $stateParams, $state,momentHelper, facebookhelper, userAccount, adwordsHelper, flowerFactory, analyticsHelper, PageTagsFactory) {
    PageTagsFactory.changeAndSetVariables();
    $scope.canEditBilling = true;

    $scope.isMobileExit = function(){
        var mobile = userAccount.getPhoneNumber();
        return (mobile === undefined || mobile === "" || mobile === null )  ? false : true;
    };
    if ($stateParams.orderId === undefined) {
        //$state.go('landing');
    } else {
        $http.get(webServer + '/sale-success-data/' + $stateParams.orderId)
            .success(function (response) {
                var momentObjStart = momentHelper.getTime('YYYY-MM-DD HH:mm:ss',moment(response.data.wanted_delivery_date));
                var momentObjEnd = momentHelper.getTime('YYYY-MM-DD HH:mm:ss',moment(response.data.wanted_delivery_date_end));
                $scope.receiveDate = momentHelper.getTime('DD MMMM YYYY, dddd',momentObjStart);
                $scope.receiveTime = momentHelper.getTime('HH:ss',momentObjStart) + "-" + momentHelper.getTime('HH:ss',momentObjEnd);

                $scope.purchaseInfo = {
                    price: response.data.sum_total,
                    name: response.data.name,
                    surname: response.data.surname,
                    address: response.data.address,
                    senderNote: response.data.card_message,
                    id : response.data.id,
                    email : response.data.sender_email,
                    godiva_sum_total: replaceString(response.data.godiva_sum_total , ',' , '.') ,
                    godiva_name: response.data.godiva_name,
                    godiva_desc: response.data.godiva_desc,
                    godiva_image: response.data.godiva_image,
                    ups: response.data.ups
                };

                var tempNameList = $scope.purchaseInfo.name.split(" ");
                var tempCompleteName = "";
                for( var x = 0; x < tempNameList.length; x++   ){
                    tempCompleteName += tempNameList[x].charAt(0).toUpperCase() + tempNameList[x].slice(1) + " ";
                }

                $scope.purchaseInfo.name = tempCompleteName;

                var tempNameList2 = $scope.purchaseInfo.surname.split(" ");
                var tempCompleteName2 = "";
                for( var x = 0; x < tempNameList2.length; x++   ){
                    tempCompleteName2 += tempNameList2[x].charAt(0).toUpperCase() + tempNameList2[x].slice(1) + " ";
                }

                $scope.purchaseInfo.surname = tempCompleteName2;


                $scope.flower = flowerFactory.getFlower(response.data.products_id);

                var price = parseFloat($scope.flower.price.toString().trim().replace(",", "."));
                var tax = parseFloat(price) / 100 * 18;
                var checkoutPrice = parseFloat($scope.purchaseInfo.price.toString().trim().replace(",", "."));
                $scope.priceForFrame = $sce.trustAsResourceUrl('https://ad.afftrck.com/SL12W?adv_sub=' + $stateParams.orderId + '&amount=' + (response.data.sum_total.replace(',' , '.') / 118 * 100).toFixed(2));
                $scope.priceForFrameReklm = $sce.trustAsResourceUrl('https://ad.reklm.com/SL76z?adv_sub=' + $stateParams.orderId + '&amount=' + (response.data.sum_total.replace(',' , '.') / 118 * 100).toFixed(2));
                $scope.priceForFrameTicaretMag = $sce.trustAsResourceUrl('https://turkticaretnet.go2cloud.org/aff_l?offer_id=535&adv_sub=' + $stateParams.orderId + '&amount=' + (response.data.sum_total.replace(',' , '.') / 118 * 100).toFixed(2));

                if($scope.flower.tags){
                    $scope.flower.tags.forEach(function(tag){
                        if($scope.flower.tag_id == tag.id){
                            $scope.main_tag_name = tag.tags_name;
                        }
                    });
                }
                else{
                    $scope.main_tag_name = '';
                }

                analyticsHelper.sendPurchaseAction($stateParams.orderId, $scope.flower.id, $scope.flower.name, price, checkoutPrice, tax, $scope.main_tag_name);
                analyticsHelper.sendPageView('/purchaseCompleted');

                facebookhelper.trackEvent(facebookhelper.facebookAdTypes.PURCHASE, {
                    content_name: $scope.flower.name,
                    content_ids: "['" + $stateParams.orderId + "']",
                    value: checkoutPrice,
                    currency: 'TRY',
                    content_type: 'product'
                });

                var usedCouponName = null;
                if ($cookies.getObject('usedCoupon')) {
                    var usedCoupon = $cookies.getObject('usedCoupon');
                    usedCouponName = usedCoupon.name;
                }

                $scope.url = $sce.trustAsResourceUrl("https://digiavantaj.cake.aclz.net/p.ashx?a=21&e=45&p=" + (response.data.sum_total.replace(',' , '.') / 118 * 100).toFixed(2) +"&t=" + $stateParams.orderId);
                $timeout(function () {
                    var tempMail  = "";
                    if (userAccount.checkUserLoggedin()){
                        tempMail = userAccount.getUserMail();
                        tempMail.toLowerCase();
                    }

                    var mainCitySelected = $cookies.getObject('selectCity');

                    var tempDistrictId = "1";
                    if(mainCitySelected){
                        if( mainCitySelected.value == 'ank' ){
                            tempDistrictId = "2";
                        }
                    }

                    analyticsHelper.sendCriteoSuccessSale($stateParams.orderId ,$scope.flower.id , $scope.flower.price.replace(",", ".") , tempMail, tempDistrictId );
                    adwordsHelper.purchaseTrack(checkoutPrice);
                }, 1000);
                //kissmetricsHelper.purchased($stateParams.orderId, $scope.flower.id, $scope.flower.name, checkoutPrice, usedCouponName);
                $cookies.remove('usedCoupon');
            })
            .error(function (response) {
                //$state.go('landing');
            });
    }

    $scope.changeEditBilling = function () {
        $scope.canEditBilling = !$scope.canEditBilling;
    };
});

purchaseSuccessModule.controller('PurchaseBillingCtrl', function ($scope, $http,$modal , $timeout,$stateParams , $document,errorMessages, userAccount,translateHelper, otherExceptions) {
    $scope.working = false;
    $scope.errorMessage = "";
    $scope.invoice = {};
    $scope.processSuccess = false;

    $scope.isOpened = false;

    $scope.isBillingSend = false;

    $(document).mouseleave(function () {

        if( !$scope.processSuccess && !$scope.isOpened ){

            $scope.isOpened = true;

            $modal.open({
                templateUrl: '../../views/bf-utility-pages/leaveWithoutBilling-v2.0.html',
                size: 'sm',
                controller: function ($scope, $modalInstance, $location) {

                    $scope.closeModel = function () {
                        $modalInstance.close();
                    };
                }
            });
        }
    });

    $document.ready(function () {
        $('.icheckField input').iCheck({
            checkboxClass : 'icheckbox_square-red',
            radioClass : 'iradio_flat-red'
        });
    });

    $scope.billingSection = 1;
    $('input.purchasePersonalBilling').iCheck('check');

    userAccount.getUser(function(userData){
        $scope.user = userData;

        if(userData){
            $scope.invoice.name = $scope.user.name;
            var billingInfo = $scope.user.billingInfo.getBillingInfo();

            if (billingInfo !== undefined) {

                if (billingInfo.billing_type === "1") {
                    $('input.purchasePersonalBilling').iCheck('check');
                    $scope.billingSection = 1;
                    $scope.invoice.city = billingInfo.city;
                    $scope.invoice.small_city = billingInfo.small_city;
                    if(billingInfo.tc)
                        $scope.invoice.tc = billingInfo.tc;
                    $scope.invoice.personal_address = billingInfo.personal_address;
                    $scope.invoice.billing_address = billingInfo.billing_address;
                    $scope.invoice.company = billingInfo.company;
                    $scope.invoice.tax_office = billingInfo.tax_office;
                    $scope.invoice.tax_no = billingInfo.tax_no;
                } else {
                    $('input.purchaseCorporateBilling').iCheck('check');
                    $scope.billingSection = 2;
                    $scope.invoice.billing_address = billingInfo.billing_address;
                    $scope.invoice.company = billingInfo.company;
                    $scope.invoice.tax_office = billingInfo.tax_office;
                    $scope.invoice.tax_no = billingInfo.tax_no;
                    $scope.invoice.city = billingInfo.city;
                    $scope.invoice.small_city = billingInfo.small_city;
                    if(billingInfo.tc)
                        $scope.invoice.tc = billingInfo.tc;
                    $scope.invoice.personal_address = billingInfo.personal_address;
                }
            }
        }
    },true);

    $('input.purchasePersonalBilling')
        .on('ifChecked', function () {
            $timeout(function () {
                $scope.billingSection = 1;
                $scope.isChecked = false;
            });
        })
        .on('ifUnchecked', function () {
            $timeout(function () {
                $scope.billingSection = 2;
                $scope.isChecked = false;
            });
        });

    $('input#isSentBill')
        .on('ifChecked', function () {
            $timeout(function () {
                $scope.invoice.billing_send = false;
            });
        })
        .on('ifUnchecked', function () {
            $timeout(function () {
                $scope.invoice.billing_send = true;
            });
        });

    $scope.radioSelected = function (radioName) {
        switch (radioName) {
            case 'personal':
            {
                $timeout(function () {
                    $scope.billingSection = 1;
                    $scope.isChecked = false;
                    $('input.purchasePersonalBilling').iCheck('check');
                });
                break;
            }
            case 'corporate':
            {
                $timeout(function () {
                    $scope.billingSection = 2;
                    $scope.isChecked = false;
                    $('input.purchaseCorporateBilling').iCheck('check');
                });
                break;
            }
        }
    };

    $scope.sendBillingInfo = function () {
        if ($scope.billingSection === 1) {
            if ($(invoiceForm.user_name).hasClass('ng-valid') && $(invoiceForm.city).hasClass('ng-valid') && $(invoiceForm.tc_no).hasClass('ng-valid') &&
                $(invoiceForm.small_city).hasClass('ng-valid') && $(invoiceForm.personal_address).hasClass('ng-valid')) {
                $scope.invoice.billing_address = $scope.invoice.personal_address;
                sendBilling();
            } else {
                errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                    $scope.isChecked = true;
                    otherExceptions.sendException("PersonalInfo-purchaseSuccess", "Eksik Bilgi");
                    $scope.errorMessage = errorMessage;
                });
            }
        }
        else if ($scope.billingSection === 2) {
            if ($(invoiceForm.company_address).hasClass('ng-valid') && $(invoiceForm.company).hasClass('ng-valid') &&
                $(invoiceForm.tax_office).hasClass('ng-valid') && $(invoiceForm.tax_no).hasClass('ng-valid')  && $(invoiceForm.city).hasClass('ng-valid') && $(invoiceForm.small_city).hasClass('ng-valid') ) {
                sendBilling();
            } else {
                errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                    $scope.isChecked = true;
                    otherExceptions.sendException("CompanyBilling-purchaseSuccess", "Eksik Bilgi");
                    $scope.errorMessage = errorMessage;
                });
            }
        }
    };

    function sendBilling() {
        var postUrl = "/add-billing-info";
        $scope.invoice.sales_id = $stateParams.orderId;
        $scope.invoice.billing_type = $scope.billingSection;
        if ($scope.isUserLogin()) {
            postUrl = "/user-add-billing-info";
            $scope.invoice.access_token = $scope.user.access_token;
        }

        $scope.working = true;
        $http.post(webServer + postUrl, $scope.invoice)
            .success(function (response) {
                $scope.working = false;
                $scope.processSuccess = true;
                $scope.isChecked = false;
                $scope.errorMessage = "";
                $('#billingInfoId1').hide();
                $('#billingInfoId2').show();
                $timeout(function () {
                    $scope.canEditBilling = false;
                },3000);
            }).error(function (response) {
                translateHelper.getText(response.description, function(errorText){
                    $scope.errorMessage = errorText;
                    $scope.working = false;
                    $scope.isChecked = true;
                    otherExceptions.sendException("satış özeti","fatura gönderirken hata oldu");
                });
            });
    }
});

purchaseSuccessModule.controller('PurchaseMobileCtrl',function($scope, $http, $timeout,$stateParams,translateHelper,errorMessages, userAccount,otherExceptions){
    $scope.isMobileEntered = false;
    $scope.working = false;
    $scope.processSuccess = false;
    $scope.isChecked = false;
    $scope.errorMessage = "";
    $scope.mobileObj = {};

    //$scope.isMobileExit = function(){
    //    var mobile = userAccount.getPhoneNumber();
    //    return (mobile === undefined || mobile === "" || mobile === null )  ? false : true;
    //};

    userAccount.getUser(function(userData) {
        $scope.user = userData;
    });

    $scope.sendMobile =function() {
        if ($(mobileForm.mobile).hasClass('ng-valid')) {
            sendMobileInfo();
        } else {
            errorMessages.getErrorMessage("MISSING_INFO", function(errorMessage){
                otherExceptions.sendException("mobileInfo-purchaseSuccess", "Eksik Bilgi");
                $scope.isChecked = true;
                $scope.errorMessage = errorMessage;
            });
        }
    };

    function sendMobileInfo(){
        $scope.working = true;
        var postUrl = "/update-mobile-number";
        $scope.mobileObj.id = $stateParams.orderId;

        if ($scope.isUserLogin()) {
            postUrl = "/user-update-mobile-number";
            $scope.mobileObj.access_token = $scope.user.access_token;
        }

        $http.post(webServer + postUrl, $scope.mobileObj)
            .success(function (response) {
                $scope.working = false;
                $scope.processSuccess = true;
                $scope.isChecked = false;
                $scope.isMobileEntered = true;
                $scope.errorMessage = "";
            }).error(function (response) {
                translateHelper.getText(response.description, function(errorText){
                    $scope.errorMessage = errorText;
                    $scope.working = false;
                    $scope.isChecked = true;
                    otherExceptions.sendException("mobileInfo-purchaseSuccess","fatura gönderirken hata oldu");
                });

            });
    }
});